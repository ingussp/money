<?php
declare(strict_types=1);

final class ReconciliationController
{
    public static function handle(string $route): void
    {
        require_manager();
        if ($route === 'bank-import') {
            post_only();
            $file = $_FILES['statement'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) throw new ValidationException('Choose a CSV statement up to 2 MB.');
            $handle = fopen($file['tmp_name'], 'rb');
            $headers = fgetcsv($handle);
            if ($headers) $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            if ($headers !== ['date', 'description', 'amount', 'reference']) throw new ValidationException('CSV columns must be: date,description,amount,reference.');
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null]) continue;
                if (count($rows) >= 2000 || count($row) !== 4) throw new ValidationException('Use up to 2,000 rows with four columns each.');
                [$date, $description, $amount, $reference] = array_map('trim', $row);
                date_value($date);
                if ($description === '' || mb_strlen($description) > 190 || $reference === '' || strlen($reference) > 190) throw new ValidationException('Each row needs a description and unique bank reference up to 190 characters.');
                $negative = str_starts_with($amount, '-');
                $value = cents($negative ? substr($amount, 1) : $amount) * ($negative ? -1 : 1);
                $rows[] = ['workspace_id' => workspace_id(), 'transaction_date' => $date, 'description' => $description, 'amount' => $value, 'fingerprint' => hash('sha256', $reference)];
            }
            fclose($handle);
            $count = Database::transaction(function () use ($rows) {
                $count = 0;
                foreach ($rows as $row) {
                    if (Database::one('SELECT id FROM bank_transactions WHERE workspace_id=? AND fingerprint=?', [workspace_id(), $row['fingerprint']])) continue;
                    Database::insert('bank_transactions', $row);
                    ++$count;
                }
                audit('bank.imported', "$count bank transactions imported");
                return $count;
            });
            flash("$count transactions imported. Duplicate references were skipped.");
            redirect('reconciliation');
        }
        if ($route === 'bank-match') {
            post_only();
            $transaction = scoped('bank_transactions', id_param());
            Database::transaction(function () use ($transaction) {
                $transaction = Database::one('SELECT * FROM bank_transactions WHERE id=? FOR UPDATE', [$transaction['id']]);
                if (input('action') === 'unmatch') {
                    Database::query('UPDATE bank_transactions SET entry_id=NULL WHERE id=?', [$transaction['id']]);
                } else {
                    if ($transaction['entry_id']) throw new ValidationException('This transaction is already matched.');
                    $entry = Ledger::entry((int) required('entry_id'));
                    $entry = Database::one('SELECT * FROM entries WHERE id=? FOR UPDATE', [$entry['id']]);
                    $signedAmount = (int) $entry['amount'] * ($entry['type'] === 'income' ? 1 : -1);
                    if ($entry['status'] !== 'paid' || $signedAmount !== (int) $transaction['amount']) throw new ValidationException('Match a paid entry with the same amount and direction.');
                    if (Database::one('SELECT id FROM bank_transactions WHERE entry_id=?', [$entry['id']])) throw new ValidationException('This entry is already matched.');
                    Database::query('UPDATE bank_transactions SET entry_id=? WHERE id=?', [$entry['id'], $transaction['id']]);
                }
                audit('bank.matched', 'Bank transaction #' . $transaction['id']);
            });
            flash('Reconciliation updated.');
            redirect('reconciliation');
        }
        $transactions = Database::all('SELECT b.*,e.description entry_description FROM bank_transactions b LEFT JOIN entries e ON e.id=b.entry_id WHERE b.workspace_id=? ORDER BY b.transaction_date DESC,b.id DESC LIMIT 200', [workspace_id()]);
        $entries = Database::all("SELECT e.* FROM entries e WHERE e.workspace_id=? AND e.status='paid' AND NOT EXISTS(SELECT 1 FROM bank_transactions b WHERE b.entry_id=e.id) ORDER BY e.paid_on DESC LIMIT 1000", [workspace_id()]);
        render('reconciliation', ['title' => 'Reconciliation', 'transactions' => $transactions, 'entries' => $entries]);
    }
}
