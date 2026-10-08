<?php
declare(strict_types=1);

final class LedgerController
{
    public static function dashboard(): void
    {
        [$start, $end, $month] = month_range();
        [$scope, $params] = Ledger::visibility();
        $summary = Ledger::summary($start, $end);
        $pending = Database::one("SELECT COUNT(*) n,COALESCE(SUM(amount),0) amount FROM entries e WHERE workspace_id=? AND type='expense' AND status='pending' $scope", [workspace_id(), ...$params]);
        $recent = Database::all("SELECT e.*,c.name category FROM entries e LEFT JOIN categories c ON c.id=e.category_id WHERE e.workspace_id=? $scope ORDER BY e.created_at DESC,e.id DESC LIMIT 7", [workspace_id(), ...$params]);
        $categories = Database::all("SELECT COALESCE(c.name,'Uncategorized') name,SUM(e.amount) amount FROM entries e LEFT JOIN categories c ON c.id=e.category_id WHERE e.workspace_id=? AND e.type='expense' AND e.status='paid' AND e.paid_on>=? AND e.paid_on<? $scope GROUP BY c.id,c.name ORDER BY amount DESC LIMIT 5", [workspace_id(), $start, $end, ...$params]);
        render('dashboard', compact('summary', 'pending', 'recent', 'categories', 'month') + ['title' => 'Overview', 'chart' => Ledger::months()]);
    }

    public static function index(string $type): void
    {
        if ($type === 'income') require_manager();
        [$start, $end, $month] = month_range();
        $search = is_string($_GET['q'] ?? '') ? mb_substr(trim($_GET['q'] ?? ''), 0, 100) : '';
        $status = is_string($_GET['status'] ?? '') ? ($_GET['status'] ?? '') : '';
        [$scope, $params] = Ledger::visibility();
        $where = "e.workspace_id=? AND e.type=? AND e.entry_date>=? AND e.entry_date<? $scope";
        $args = [workspace_id(), $type, $start, $end, ...$params];
        if ($search !== '') { $where .= ' AND (e.description LIKE ? OR e.reference LIKE ?)'; $args[] = '%' . $search . '%'; $args[] = '%' . $search . '%'; }
        if ($status !== '') { $where .= ' AND e.status=?'; $args[] = $status; }
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $count = (int) Database::one("SELECT COUNT(*) n FROM entries e WHERE $where", $args)['n'];
        $page = min($page, max(1, (int) ceil($count / 25)));
        $offset = ($page - 1) * 25;
        $entries = Database::all("SELECT e.*,c.name category,co.name contact,(SELECT COUNT(*) FROM documents d WHERE d.entry_id=e.id) documents FROM entries e LEFT JOIN categories c ON c.id=e.category_id LEFT JOIN contacts co ON co.id=e.contact_id WHERE $where ORDER BY e.entry_date DESC,e.id DESC LIMIT 25 OFFSET $offset", $args);
        $summary = Database::one("SELECT COALESCE(SUM(amount),0) total,COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) paid,COALESCE(SUM(CASE WHEN status NOT IN ('paid','rejected','draft') THEN amount ELSE 0 END),0) pending FROM entries e WHERE $where", $args);
        render('ledger', compact('entries', 'type', 'month', 'status', 'search', 'count', 'page', 'summary') + ['title' => $type === 'income' ? 'Income' : 'Expenses']);
    }

    public static function form(): void
    {
        $id = id_param();
        $entry = $id ? Ledger::entry($id) : null;
        $type = $entry['type'] ?? ($_GET['type'] ?? 'expense');
        if (!in_array($type, ['income', 'expense'], true)) abort_request(404, 'Unknown entry type');
        if ($type === 'income') require_manager();
        $editable = !$entry || Ledger::editable($entry);
        $error = null;
        try {
            if (is_post()) {
                if (!$editable) throw new ValidationException('Only draft or rejected entries can be edited.');
                $description = required('description');
                $amount = cents(required('amount'));
                $tax = cents(input('tax_amount', '0'), true);
                if ($tax > $amount) throw new ValidationException('Tax cannot exceed the total amount.');
                $categoryId = (int) input('category_id') ?: null;
                $contactId = (int) input('contact_id') ?: null;
                if ($categoryId && scoped('categories', $categoryId)['type'] !== $type) throw new ValidationException('Choose a category for this entry type.');
                if ($contactId) scoped('contacts', $contactId);
                $date = date_value(required('entry_date'));
                $status = input('status', 'draft');
                $allowed = can_manage() ? ($type === 'expense' ? ['draft', 'pending', 'approved', 'paid'] : ['draft', 'pending', 'paid']) : ['draft', 'pending'];
                if (!in_array($status, $allowed, true)) throw new ValidationException('Choose a valid status.');
                $paidOn = $status === 'paid' ? date_value(required('paid_on')) : null;
                $reference = input('reference');
                $notes = input('notes');
                if (mb_strlen($reference) > 100 || mb_strlen($notes) > 5000) throw new ValidationException('Reference or notes are too long.');
                $data = ['description' => $description, 'amount' => $amount, 'tax_amount' => $tax, 'category_id' => $categoryId, 'contact_id' => $contactId, 'entry_date' => $date, 'paid_on' => $paidOn, 'status' => $status, 'reference' => $reference, 'notes' => $notes];
                $id = Database::transaction(function () use ($entry, $data, $type) {
                    if ($entry) {
                        $fresh = Database::one('SELECT * FROM entries WHERE id=? FOR UPDATE', [$entry['id']]);
                        if (!Ledger::editable($fresh)) throw new ValidationException('This entry has changed. Reload the page.');
                        $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                        Database::query("UPDATE entries SET $set,review_note='',reviewed_by=NULL WHERE id=? AND workspace_id=?", [...array_values($data), $entry['id'], workspace_id()]);
                        $id = (int) $entry['id'];
                    } else {
                        $id = Database::insert('entries', $data + ['workspace_id' => workspace_id(), 'created_by' => current_user()['id'], 'type' => $type]);
                    }
                    audit('entry.saved', ucfirst($type) . ' #' . $id . ': ' . $data['description']);
                    return $id;
                });
                flash(ucfirst($type) . ' saved.');
                redirect('entry', ['id' => $id]);
            }
        } catch (ValidationException $e) { $error = $e->getMessage(); http_response_code(422); }
        $categories = Database::all('SELECT * FROM categories WHERE workspace_id=? AND type=? ORDER BY name', [workspace_id(), $type]);
        $contacts = Database::all('SELECT * FROM contacts WHERE workspace_id=? ORDER BY name', [workspace_id()]);
        $documentScope = can_manage() ? '' : ' AND uploaded_by=' . (int) current_user()['id'];
        $documents = $entry ? Database::all("SELECT * FROM documents WHERE workspace_id=? AND entry_id=? $documentScope ORDER BY id DESC", [workspace_id(), $id]) : [];
        $invoice = $entry ? Database::one('SELECT id,number FROM invoices WHERE entry_id=?', [$id]) : null;
        render('entry', compact('entry', 'type', 'editable', 'categories', 'contacts', 'documents', 'error', 'invoice') + ['title' => $entry ? ucfirst($type) . ' #' . $id : 'New ' . $type]);
    }

    public static function action(): never
    {
        post_only();
        $entry = Ledger::entry(id_param());
        $action = input('action');
        Database::transaction(function () use ($entry, $action) {
            $entry = Database::one('SELECT * FROM entries WHERE id=? FOR UPDATE', [$entry['id']]);
            if (Database::one('SELECT id FROM invoices WHERE entry_id=?', [$entry['id']])) throw new ValidationException('Manage this payment from its invoice.');
            if ($action === 'delete') {
                if (!Ledger::editable($entry)) throw new ValidationException('Only draft or rejected entries can be deleted.');
                Database::query('DELETE FROM entries WHERE id=? AND workspace_id=?', [$entry['id'], workspace_id()]);
            } elseif ($action === 'submit' && in_array($entry['status'], ['draft', 'rejected'], true)) {
                Database::query("UPDATE entries SET status='pending',review_note='' WHERE id=?", [$entry['id']]);
            } elseif (in_array($action, ['approve', 'reject'], true) && $entry['type'] === 'expense' && $entry['status'] === 'pending') {
                require_manager();
                $note = $action === 'reject' ? required('review_note', 500) : '';
                Database::query('UPDATE entries SET status=?,reviewed_by=?,review_note=? WHERE id=?', [$action === 'approve' ? 'approved' : 'rejected', current_user()['id'], $note, $entry['id']]);
            } elseif ($action === 'paid' && (($entry['type'] === 'expense' && $entry['status'] === 'approved') || ($entry['type'] === 'income' && $entry['status'] === 'pending'))) {
                require_manager();
                Database::query("UPDATE entries SET status='paid',paid_on=? WHERE id=?", [date_value(required('paid_on')), $entry['id']]);
            } else {
                throw new ValidationException('This status change is not allowed.');
            }
            audit('entry.' . $action, ucfirst($entry['type']) . ' #' . $entry['id'] . ': ' . $entry['description']);
        });
        flash('Entry updated.');
        redirect($action === 'delete' ? ($entry['type'] === 'income' ? 'income' : 'expenses') : 'entry', $action === 'delete' ? [] : ['id' => $entry['id']]);
    }

    public static function approvals(): void
    {
        require_manager();
        $entries = Database::all("SELECT e.*,u.name author,c.name category FROM entries e JOIN users u ON u.id=e.created_by LEFT JOIN categories c ON c.id=e.category_id WHERE e.workspace_id=? AND e.type='expense' AND e.status='pending' ORDER BY e.created_at LIMIT 100", [workspace_id()]);
        render('approvals', ['title' => 'Approvals', 'entries' => $entries]);
    }

    public static function reports(bool $export): void
    {
        require_manager();
        [$start, $end, $month] = month_range();
        $rows = Database::all("SELECT e.*,c.name category,co.name contact FROM entries e LEFT JOIN categories c ON c.id=e.category_id LEFT JOIN contacts co ON co.id=e.contact_id WHERE e.workspace_id=? AND e.status='paid' AND e.paid_on>=? AND e.paid_on<? ORDER BY e.paid_on,e.id", [workspace_id(), $start, $end]);
        if ($export) {
            csv_download('money-cash-flow-' . $month . '.csv', ['Date', 'Type', 'Description', 'Contact', 'Category', 'Reference', 'Currency', 'Amount', 'Tax', 'Balance impact'], array_map(fn($r) => [$r['paid_on'], $r['type'], $r['description'], $r['contact'], $r['category'], $r['reference'], workspace()['currency'], decimal((int) $r['amount']), decimal((int) $r['tax_amount']), decimal((int) $r['amount'] * ($r['type'] === 'income' ? 1 : -1))], $rows));
        }
        $categories = Database::all("SELECT e.type,COALESCE(c.name,'Uncategorized') name,COUNT(*) n,SUM(e.amount) amount,SUM(e.tax_amount) tax FROM entries e LEFT JOIN categories c ON c.id=e.category_id WHERE e.workspace_id=? AND e.status='paid' AND e.paid_on>=? AND e.paid_on<? GROUP BY e.type,c.id,c.name ORDER BY e.type,amount DESC", [workspace_id(), $start, $end]);
        render('reports', ['title' => 'Reports', 'summary' => Ledger::summary($start, $end), 'categories' => $categories, 'rows' => $rows, 'month' => $month, 'chart' => Ledger::months(12)]);
    }
}
