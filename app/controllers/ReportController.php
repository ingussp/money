<?php
declare(strict_types=1);

class ReportController extends Controller
{
    public function index(): void
    {
        require_login();
        $pdo = db();

        $month = trim($_GET['month'] ?? date('Y-m'));
        $companyId = (int)($_GET['company_id'] ?? 0);

        $where = ["substr(date, 1, 7) = ?"];
        $params = [$month];
        if ($companyId > 0) {
            $where[] = 'company_id = ?';
            $params[] = $companyId;
        }
        $whereSql = implode(' AND ', $where);

        $byCategory = $pdo->prepare(
            "SELECT COALESCE(c.name, '?') AS category, COUNT(*) AS n, SUM(d.amount_home) AS total
             FROM documents d LEFT JOIN categories c ON c.id = d.category_id
             WHERE d.status != 'rejected' AND " . $whereSql . "
             GROUP BY d.category_id ORDER BY total DESC"
        );
        $byCategory->execute($params);
        $byCategory = $byCategory->fetchAll();

        $byCompany = $pdo->prepare(
            "SELECT COALESCE(co.name, '?') AS company, SUM(d.amount_home) AS total
             FROM documents d LEFT JOIN companies co ON co.id = d.company_id
             WHERE d.status != 'rejected' AND " . $whereSql . "
             GROUP BY d.company_id ORDER BY total DESC"
        );
        $byCompany->execute($params);
        $byCompany = $byCompany->fetchAll();

        // Monthly trend for the last 6 months.
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime($month . '-01 -' . $i . ' months'));
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_home), 0) FROM documents WHERE status != 'rejected' AND substr(date,1,7) = ?");
            $stmt->execute([$m]);
            $trend[] = ['month' => $m, 'total' => (float)$stmt->fetchColumn()];
        }

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_home), 0) AS t FROM documents WHERE status != 'rejected' AND " . $whereSql);
        $stmt->execute($params);
        $total = (float)$stmt->fetchColumn();

        $companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();

        $this->view('reports/index', [
            'title' => t('reports'),
            'activeRoute' => 'reports',
            'month' => $month,
            'companyId' => $companyId,
            'companies' => $companies,
            'byCategory' => $byCategory,
            'byCompany' => $byCompany,
            'trend' => $trend,
            'total' => $total,
        ]);
    }

    public function export(): void
    {
        require_login();
        $pdo = db();
        $stmt = $pdo->query(
            'SELECT date, vendor, doc_number, type, currency, amount, amount_home, tax, status, digitized
             FROM documents ORDER BY date DESC'
        );
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="expenses-' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['date', 'vendor', 'doc_number', 'type', 'currency', 'amount', 'amount_home_eur', 'tax', 'status', 'digitized']);
        foreach ($rows as $r) {
            fputcsv($out, $r);
        }
        fclose($out);
        exit;
    }
}
