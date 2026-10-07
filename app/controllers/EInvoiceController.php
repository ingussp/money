<?php
declare(strict_types=1);

class EInvoiceController extends Controller
{
    public function index(): void
    {
        require_login();
        $invoices = db()->query(
            'SELECT e.*, co.name AS company_name FROM e_invoices e
             LEFT JOIN companies co ON co.id = e.company_id ORDER BY e.id DESC'
        )->fetchAll();
        $this->view('einvoices/index', [
            'title' => t('einvoices'),
            'activeRoute' => 'einvoices',
            'invoices' => $invoices,
        ]);
    }

    public function create(): void
    {
        require_login();
        $pdo = db();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $companyId = (int)($_POST['company_id'] ?? 0) ?: null;
            $vendor = trim($_POST['vendor'] ?? '');
            $buyer = trim($_POST['buyer'] ?? '');
            $invoiceNumber = trim($_POST['invoice_number'] ?? '');
            $amount = (float)($_POST['amount'] ?? 0);
            $currency = strtoupper(trim($_POST['currency'] ?? 'EUR'));
            $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
            $dueDate = trim($_POST['due_date'] ?? '');

            $amountHome = Services::toHome($amount, $currency);
            $xml = self::buildXml(compact('invoiceNumber', 'vendor', 'buyer', 'amount', 'currency', 'issueDate', 'dueDate'));

            $stmt = $pdo->prepare(
                'INSERT INTO e_invoices (company_id, invoice_number, vendor, buyer, amount, currency, amount_home, issue_date, due_date, status, xml)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$companyId, $invoiceNumber, $vendor, $buyer, $amount, $currency, $amountHome, $issueDate, $dueDate, 'draft', $xml]);
            log_action('create e-invoice ' . $invoiceNumber);
            flash('success', 'E-invoice created.');
            redirect('einvoices');
        }

        $this->view('einvoices/form', [
            'title' => t('new_einvoice'),
            'activeRoute' => 'einvoices',
            'invoice' => null,
            'companies' => $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll(),
        ]);
    }

    public function show(): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = db()->prepare('SELECT e.*, co.name AS company_name FROM e_invoices e LEFT JOIN companies co ON co.id = e.company_id WHERE e.id = ?');
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            http_response_code(404);
            exit('E-invoice not found.');
        }
        $this->view('einvoices/show', [
            'title' => $invoice['invoice_number'],
            'activeRoute' => 'einvoices',
            'invoice' => $invoice,
        ]);
    }

    public function send(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE e_invoices SET status = 'sent' WHERE id = ?")->execute([$id]);
        log_action('send e-invoice #' . $id);
        flash('success', 'E-invoice sent.');
        redirect('einvoices');
    }

    public function markPaid(): void
    {
        require_role('admin', 'accountant');
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare("UPDATE e_invoices SET status = 'paid' WHERE id = ?")->execute([$id]);
        log_action('mark e-invoice #' . $id . ' paid');
        flash('success', 'E-invoice marked paid.');
        redirect('einvoices');
    }

    public function download(): void
    {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $stmt = db()->prepare('SELECT * FROM e_invoices WHERE id = ?');
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            http_response_code(404);
            exit('E-invoice not found.');
        }
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $invoice['invoice_number'] . '.xml"');
        echo $invoice['xml'];
        exit;
    }

    private static function buildXml(array $d): string
    {
        $esc = static function (string $s): string {
            return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<Invoice>\n"
            . "  <InvoiceNumber>" . $esc($d['invoiceNumber']) . "</InvoiceNumber>\n"
            . "  <Supplier>" . $esc($d['vendor']) . "</Supplier>\n"
            . "  <Buyer>" . $esc($d['buyer']) . "</Buyer>\n"
            . "  <Amount currency=\"" . $esc($d['currency']) . "\">" . number_format($d['amount'], 2, '.', '') . "</Amount>\n"
            . "  <IssueDate>" . $esc($d['issueDate']) . "</IssueDate>\n"
            . "  <DueDate>" . $esc($d['dueDate']) . "</DueDate>\n"
            . "</Invoice>";
    }
}
