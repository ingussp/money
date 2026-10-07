<?php
declare(strict_types=1);

class DocumentController extends Controller
{
    public function index(): void
    {
        require_login();
        $pdo = db();

        $where = [];
        $params = [];

        $search = trim($_GET['q'] ?? '');
        if ($search !== '') {
            $where[] = '(d.vendor LIKE ? OR d.doc_number LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $category = (int)($_GET['category'] ?? 0);
        if ($category > 0) {
            $where[] = 'd.category_id = ?';
            $params[] = $category;
        }
        $status = trim($_GET['status'] ?? '');
        if ($status !== '') {
            $where[] = 'd.status = ?';
            $params[] = $status;
        }

        $sql = 'SELECT d.*, c.name AS category_name, co.name AS company_name
                FROM documents d
                LEFT JOIN categories c ON c.id = d.category_id
                LEFT JOIN companies co ON co.id = d.company_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY d.date DESC, d.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $documents = $stmt->fetchAll();

        $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

        $this->view('documents/index', [
            'title' => t('documents'),
            'activeRoute' => 'documents',
            'documents' => $documents,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        require_login();
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $vendor = trim($_POST['vendor'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $currency = strtoupper(trim($_POST['currency'] ?? 'EUR'));
            $date = trim($_POST['date'] ?? date('Y-m-d'));
            $type = in_array($_POST['type'] ?? '', ['receipt', 'invoice'], true) ? $_POST['type'] : 'receipt';
            $companyId = (int)($_POST['company_id'] ?? 0);
            $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
            $tax = (float)($_POST['tax'] ?? 0);
            $digitized = in_array($_POST['digitized'] ?? '', ['robo', 'human', 'none'], true) ? $_POST['digitized'] : 'none';
            $status = in_array($_POST['status'] ?? '', ['draft', 'submitted', 'pending', 'approved', 'paid'], true) ? $_POST['status'] : 'submitted';

            if ($vendor === '' || $amount <= 0) {
                flash('error', 'Vendor and a positive amount are required.');
                redirect('documents/create');
            }

            // AI categorisation: fall back to historical vendor data.
            if ($categoryId === null) {
                $categoryId = Services::suggestCategory($vendor);
            }

            // Simulated OCR / Robo digitisation.
            $docNumber = trim($_POST['doc_number'] ?? '');
            $isVerified = $digitized !== 'none' ? 1 : 0;
            if ($digitized === 'robo') {
                $ocr = Services::robotDigitize($vendor);
                $docNumber = $docNumber !== '' ? $docNumber : $ocr['doc_number'];
            }

            $amountHome = Services::toHome($amount, $currency);
            $hash = Services::docHash($vendor, $amount, $date);
            $duplicateId = Services::detectDuplicate($hash);

            $fileName = upload_file('file');
            $userId = (int)$_SESSION['user_id'];

            $stmt = $pdo->prepare(
                'INSERT INTO documents
                 (company_id, user_id, type, vendor, doc_number, amount, currency, amount_home, tax, category_id, date, status, digitized, is_verified, hash, duplicate_of, file_name)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $companyId ?: null, $userId, $type, $vendor, $docNumber ?: null, $amount, $currency, $amountHome,
                $tax, $categoryId, $date, $status, $digitized, $isVerified, $hash, $duplicateId, $fileName,
            ]);
            $newId = (int)$pdo->lastInsertId();

            self::saveLines($newId, $_POST['lines'] ?? []);

            log_action('create document: ' . $vendor);
            $msg = 'Document saved.';
            if ($duplicateId) {
                $msg .= ' Possible duplicate of #' . $duplicateId . ' detected.';
                flash('warning', $msg);
            } else {
                flash('success', $msg);
            }
            redirect('documents');
        }

        $this->view('documents/form', [
            'title' => t('documents_new'),
            'activeRoute' => 'documents',
            'document' => null,
            'lines' => [],
            'categories' => $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(),
            'companies' => $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll(),
        ]);
    }

    public function show(): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $document = $this->findDocument($id);

        $stmt = db()->prepare('SELECT * FROM line_items WHERE document_id = ? ORDER BY id');
        $stmt->execute([$id]);
        $lines = $stmt->fetchAll();

        $this->view('documents/show', [
            'title' => e($document['vendor']),
            'activeRoute' => 'documents',
            'document' => $document,
            'lines' => $lines,
        ]);
    }

    public function edit(): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $document = $this->findDocument($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $pdo = db();

            $vendor = trim($_POST['vendor'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $currency = strtoupper(trim($_POST['currency'] ?? 'EUR'));
            $date = trim($_POST['date'] ?? date('Y-m-d'));
            $type = in_array($_POST['type'] ?? '', ['receipt', 'invoice'], true) ? $_POST['type'] : 'receipt';
            $companyId = (int)($_POST['company_id'] ?? 0);
            $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
            $tax = (float)($_POST['tax'] ?? 0);
            $docNumber = trim($_POST['doc_number'] ?? '');
            $status = in_array($_POST['status'] ?? '', ['draft', 'submitted', 'pending', 'approved', 'paid'], true) ? $_POST['status'] : $document['status'];

            $amountHome = Services::toHome($amount, $currency);
            $hash = Services::docHash($vendor, $amount, $date);
            $duplicateId = Services::detectDuplicate($hash, $id);

            $fileName = upload_file('file') ?: $document['file_name'];

            $stmt = $pdo->prepare(
                'UPDATE documents SET company_id=?, type=?, vendor=?, doc_number=?, amount=?, currency=?, amount_home=?, tax=?, category_id=?, date=?, status=?, hash=?, duplicate_of=?, file_name=? WHERE id=?'
            );
            $stmt->execute([
                $companyId ?: null, $type, $vendor, $docNumber ?: null, $amount, $currency, $amountHome,
                $tax, $categoryId, $date, $status, $hash, $duplicateId, $fileName, $id,
            ]);

            self::replaceLines($id, $_POST['lines'] ?? []);

            log_action('update document #' . $id);
            flash('success', 'Document updated.');
            redirect('documents/show', ['id' => $id]);
        }

        $stmt = db()->prepare('SELECT * FROM line_items WHERE document_id = ? ORDER BY id');
        $stmt->execute([$id]);
        $lines = $stmt->fetchAll();

        $this->view('documents/form', [
            'title' => t('edit'),
            'activeRoute' => 'documents',
            'document' => $document,
            'lines' => $lines,
            'categories' => db()->query('SELECT * FROM categories ORDER BY name')->fetchAll(),
            'companies' => db()->query('SELECT * FROM companies ORDER BY name')->fetchAll(),
        ]);
    }

    public function approve(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $approvedBy = (int)$_SESSION['user_id'];
        db()->prepare("UPDATE documents SET status = 'approved', approved_by = ?, approved_at = ? WHERE id = ?")
            ->execute([$approvedBy, date('c'), $id]);
        log_action('approve document #' . $id);
        flash('success', 'Document approved.');
        redirect('documents/show', ['id' => $id]);
    }

    public function reject(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        db()->prepare("UPDATE documents SET status = 'rejected' WHERE id = ?")->execute([$id]);
        log_action('reject document #' . $id . ($reason ? ' (' . $reason . ')' : ''));
        flash('success', 'Document rejected.');
        redirect('documents/show', ['id' => $id]);
    }

    public function digitize(): void
    {
        require_login();
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $document = $this->findDocument($id);
        $ocr = Services::robotDigitize($document['vendor']);
        db()->prepare('UPDATE documents SET doc_number = COALESCE(doc_number, ?), digitized = ?, is_verified = 1 WHERE id = ?')
            ->execute([$ocr['doc_number'], 'robo', $id]);
        log_action('robo-digitize document #' . $id);
        flash('success', 'Document digitised by Robo.');
        redirect('documents/show', ['id' => $id]);
    }

    public function delete(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM line_items WHERE document_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
        log_action('delete document #' . $id);
        flash('success', 'Document deleted.');
        redirect('documents');
    }

    private function findDocument(int $id): array
    {
        $stmt = db()->prepare(
            'SELECT d.*, c.name AS category_name, co.name AS company_name, du.vendor AS duplicate_vendor
             FROM documents d
             LEFT JOIN categories c ON c.id = d.category_id
             LEFT JOIN companies co ON co.id = d.company_id
             LEFT JOIN documents du ON du.id = d.duplicate_of
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        $document = $stmt->fetch();
        if (!$document) {
            http_response_code(404);
            exit('Document not found.');
        }
        return $document;
    }

    private static function saveLines(int $documentId, array $lines): void
    {
        foreach (self::normaliseLines($lines) as $line) {
            $stmt = db()->prepare('INSERT INTO line_items (document_id, description, quantity, unit_price, amount) VALUES (?,?,?,?,?)');
            $stmt->execute([$documentId, $line['description'], $line['quantity'], $line['unit_price'], $line['amount']]);
        }
    }

    private static function replaceLines(int $documentId, array $lines): void
    {
        db()->prepare('DELETE FROM line_items WHERE document_id = ?')->execute([$documentId]);
        self::saveLines($documentId, $lines);
    }

    /** Normalise the HTML line-item arrays into clean rows. */
    private static function normaliseLines(array $lines): array
    {
        $rows = [];
        $descriptions = $lines['description'] ?? [];
        foreach ($descriptions as $i => $desc) {
            $desc = trim((string)$desc);
            if ($desc === '') {
                continue;
            }
            $quantity = (float)($lines['quantity'][$i] ?? 1);
            $unitPrice = (float)($lines['unit_price'][$i] ?? 0);
            $amount = (float)($lines['amount'][$i] ?? 0);
            if ($amount == 0 && $quantity > 0 && $unitPrice > 0) {
                $amount = $quantity * $unitPrice;
            }
            $rows[] = ['description' => $desc, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'amount' => $amount];
        }
        return $rows;
    }
}
