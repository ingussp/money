<?php
declare(strict_types=1);

class ApiController extends Controller
{
    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function users(): void
    {
        require_login();
        $q = trim($_GET['q'] ?? '');
        $sql = 'SELECT id, name, email FROM users WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ?)';
            $params = ["%$q%", "%$q%"];
        }
        $sql .= ' ORDER BY name LIMIT 30';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $this->json($stmt->fetchAll());
    }

    public function documents(): void
    {
        require_login();
        $q = trim($_GET['q'] ?? '');
        $sql = 'SELECT id, vendor, amount, currency, status FROM documents WHERE 1';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (vendor LIKE ? OR id = ?)';
            $params = ["%$q%", (int)$q];
        }
        $sql .= ' ORDER BY id DESC LIMIT 50';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $this->json($stmt->fetchAll());
    }

    public function categories(): void
    {
        require_login();
        $this->json(db()->query('SELECT * FROM categories ORDER BY name')->fetchAll());
    }

    public function exchange(): void
    {
        require_login();
        $from = strtoupper(trim($_GET['from'] ?? 'EUR'));
        $to = strtoupper(trim($_GET['to'] ?? 'EUR'));
        $amount = (float)($_GET['amount'] ?? 1);
        $this->json([
            'rate' => Services::exchangeRate($from, $to),
            'converted' => Services::toCurrency($amount, $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
