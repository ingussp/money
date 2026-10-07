<?php
declare(strict_types=1);

class CardController extends Controller
{
    public function index(): void
    {
        require_login();
        $pdo = db();
        $cards = $pdo->query(
            'SELECT c.*, co.name AS company_name,
                    (SELECT COUNT(*) FROM card_transactions t WHERE t.card_id = c.id) AS txn_count,
                    (SELECT COALESCE(SUM(amount_home),0) FROM card_transactions t WHERE t.card_id = c.id) AS spend
             FROM cards c LEFT JOIN companies co ON co.id = c.company_id ORDER BY c.id'
        )->fetchAll();
        $this->view('cards/index', [
            'title' => t('cards'),
            'activeRoute' => 'cards',
            'cards' => $cards,
        ]);
    }

    public function create(): void
    {
        require_login();
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $companyId = (int)($_POST['company_id'] ?? 0) ?: null;
            $holder = trim($_POST['holder'] ?? '');
            $cardType = in_array($_POST['card_type'] ?? '', ['virtual', 'physical'], true) ? $_POST['card_type'] : 'virtual';
            $limit = (float)($_POST['limit'] ?? 0);
            $currency = strtoupper(trim($_POST['currency'] ?? 'EUR'));
            $provider = trim($_POST['provider'] ?? 'CardNet');
            $last4 = substr(md5(microtime()), 0, 4);

            $stmt = $pdo->prepare(
                'INSERT INTO cards (company_id, holder, last4, card_type, status, `limit`, currency, provider, issued_at, expires_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $companyId, $holder, $last4, $cardType, 'active', $limit, $currency,
                $provider, date('Y-m-d'), date('Y-m-d', strtotime('+3 years')),
            ]);
            log_action('create card ' . $holder);
            flash('success', 'Card issued.');
            redirect('cards');
        }

        $this->view('cards/form', [
            'title' => t('new_card'),
            'activeRoute' => 'cards',
            'card' => null,
            'companies' => $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll(),
        ]);
    }

    public function edit(): void
    {
        require_login();
        $pdo = db();
        $id = (int)($_GET['id'] ?? 0);
        $card = $this->findCard($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $holder = trim($_POST['holder'] ?? '');
            $limit = (float)($_POST['limit'] ?? 0);
            $status = in_array($_POST['status'] ?? '', ['active', 'frozen', 'blocked', 'terminated'], true) ? $_POST['status'] : 'active';
            $pdo->prepare('UPDATE cards SET holder = ?, `limit` = ?, status = ? WHERE id = ?')
                ->execute([$holder, $limit, $status, $id]);
            log_action('update card #' . $id);
            flash('success', 'Card updated.');
            redirect('cards');
        }

        $this->view('cards/form', [
            'title' => t('edit'),
            'activeRoute' => 'cards',
            'card' => $card,
            'companies' => $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll(),
        ]);
    }

    public function delete(): void
    {
        require_role('admin');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM cards WHERE id = ?')->execute([$id]);
        log_action('delete card #' . $id);
        flash('success', 'Card deleted.');
        redirect('cards');
    }

    private function findCard(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM cards WHERE id = ?');
        $stmt->execute([$id]);
        $card = $stmt->fetch();
        if (!$card) {
            http_response_code(404);
            exit('Card not found.');
        }
        return $card;
    }
}
