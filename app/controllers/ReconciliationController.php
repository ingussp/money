<?php
declare(strict_types=1);

class ReconciliationController extends Controller
{
    public function index(): void
    {
        require_login();
        $pdo = db();

        $filter = trim($_GET['filter'] ?? '');
        $sql = 'SELECT t.*, cd.holder, cd.last4, d.vendor AS matched_vendor
                FROM card_transactions t
                JOIN cards cd ON cd.id = t.card_id
                LEFT JOIN documents d ON d.id = t.matched_document_id';
        $params = [];
        if ($filter === 'unmatched') {
            $sql .= ' WHERE t.matched_document_id IS NULL';
        } elseif ($filter === 'matched') {
            $sql .= ' WHERE t.matched_document_id IS NOT NULL';
        }
        $sql .= ' ORDER BY t.booked_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        $this->view('reconciliation/index', [
            'title' => t('reconciliation'),
            'activeRoute' => 'reconciliation',
            'transactions' => $transactions,
            'filter' => $filter,
        ]);
    }

    public function match(): void
    {
        require_login();
        verify_csrf();
        $txnId = (int)($_POST['transaction_id'] ?? 0);
        $docId = (int)($_POST['document_id'] ?? 0);

        $stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$docId]);
        $doc = $stmt->fetch();
        if (!$doc) {
            flash('error', 'Document not found.');
            redirect('reconciliation');
        }

        db()->prepare('UPDATE card_transactions SET matched_document_id = ?, category_id = ?, status = ? WHERE id = ?')
            ->execute([$docId, $doc['category_id'], 'matched', $txnId]);
        log_action('match transaction #' . $txnId . ' -> document #' . $docId);
        flash('success', 'Transaction matched to document.');
        redirect('reconciliation');
    }

    public function unmatch(): void
    {
        require_login();
        verify_csrf();
        $txnId = (int)($_POST['transaction_id'] ?? 0);
        db()->prepare('UPDATE card_transactions SET matched_document_id = NULL, category_id = NULL, status = ? WHERE id = ?')
            ->execute(['pending', $txnId]);
        log_action('unmatch transaction #' . $txnId);
        flash('success', 'Transaction unmatched.');
        redirect('reconciliation');
    }
}
