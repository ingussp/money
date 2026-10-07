<?php
declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(): void
    {
        require_login();

        $pdo = db();

        $docCount = (int)$pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
        $verified = (int)$pdo->query("SELECT COUNT(*) FROM documents WHERE is_verified = 1")->fetchColumn();
        $accuracy = $docCount > 0 ? round(($verified / $docCount) * 100) : 97;

        $stats = [
            'documents' => $docCount,
            'accuracy' => $accuracy,
            'companies' => (int)$pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
            'integrations' => (int)$pdo->query('SELECT COUNT(*) FROM integrations WHERE connected = 1')->fetchColumn(),
            'cards' => (int)$pdo->query("SELECT COUNT(*) FROM cards WHERE status = 'active'")->fetchColumn(),
            'transactions' => (int)$pdo->query('SELECT COUNT(*) FROM card_transactions')->fetchColumn(),
        ];

        $recentDocs = $pdo->query(
            'SELECT d.*, c.name AS category_name FROM documents d
             LEFT JOIN categories c ON c.id = d.category_id
             ORDER BY d.created_at DESC LIMIT 8'
        )->fetchAll();

        $recentTxns = $pdo->query(
            'SELECT t.*, cd.holder FROM card_transactions t
             JOIN cards cd ON cd.id = t.card_id
             ORDER BY t.created_at DESC LIMIT 8'
        )->fetchAll();

        $this->view('dashboard', [
            'title' => t('dashboard'),
            'activeRoute' => 'dashboard',
            'stats' => $stats,
            'recentDocs' => $recentDocs,
            'recentTxns' => $recentTxns,
        ]);
    }
}
