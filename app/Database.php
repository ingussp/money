<?php
declare(strict_types=1);

/**
 * SQLite database bootstrap: creates the schema and seeds demo data on first run.
 */
final class Database
{
    private PDO $pdo;

    public function __construct(string $file)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->pdo = new PDO('sqlite:' . $file);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function migrate(): void
    {
        $this->pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'employee',
    company_id INTEGER,
    locale TEXT DEFAULT 'en',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS companies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    reg_number TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    color TEXT DEFAULT '#6c757d',
    icon TEXT DEFAULT 'fa-tag'
);

CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    currency TEXT NOT NULL UNIQUE,
    rate REAL NOT NULL DEFAULT 1.0,
    updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    user_id INTEGER,
    type TEXT DEFAULT 'receipt',
    vendor TEXT,
    doc_number TEXT,
    amount REAL DEFAULT 0,
    currency TEXT DEFAULT 'EUR',
    amount_home REAL DEFAULT 0,
    tax REAL DEFAULT 0,
    category_id INTEGER,
    date TEXT,
    status TEXT DEFAULT 'pending',
    digitized TEXT DEFAULT 'none',
    is_verified INTEGER DEFAULT 0,
    duplicate_of INTEGER,
    file_name TEXT,
    hash TEXT,
    e_invoice INTEGER DEFAULT 0,
    approved_by INTEGER,
    approved_at TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS line_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_id INTEGER,
    description TEXT,
    quantity REAL DEFAULT 1,
    unit_price REAL DEFAULT 0,
    amount REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    user_id INTEGER,
    type TEXT DEFAULT 'expense',
    title TEXT,
    status TEXT DEFAULT 'draft',
    submitted_at TEXT,
    approved_at TEXT,
    approved_by INTEGER,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS report_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_id INTEGER,
    document_id INTEGER,
    purpose TEXT,
    distance_km REAL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    holder TEXT,
    last4 TEXT,
    card_type TEXT DEFAULT 'virtual',
    status TEXT DEFAULT 'active',
    "limit" REAL DEFAULT 0,
    currency TEXT DEFAULT 'EUR',
    provider TEXT DEFAULT 'CardNet',
    issued_at TEXT,
    expires_at TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS card_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_id INTEGER,
    amount REAL DEFAULT 0,
    currency TEXT DEFAULT 'EUR',
    amount_home REAL DEFAULT 0,
    merchant TEXT,
    description TEXT,
    booked_at TEXT,
    matched_document_id INTEGER,
    category_id INTEGER,
    status TEXT DEFAULT 'pending',
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS integrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    logo TEXT,
    description TEXT,
    type TEXT DEFAULT 'accounting',
    connected INTEGER DEFAULT 0,
    connected_at TEXT,
    last_sync_at TEXT,
    is_visible INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS e_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_id INTEGER,
    invoice_number TEXT,
    vendor TEXT,
    buyer TEXT,
    amount REAL DEFAULT 0,
    currency TEXT DEFAULT 'EUR',
    amount_home REAL DEFAULT 0,
    issue_date TEXT,
    due_date TEXT,
    status TEXT DEFAULT 'draft',
    xml TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);
SQL);
    }

    public function seed(): void
    {
        if ((int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            return;
        }

        // Default admin / accountant / employee.
        $users = [
            ['Admin User', 'admin@money.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 1],
            ['Anna Accountant', 'accountant@money.local', password_hash('demo123', PASSWORD_DEFAULT), 'accountant', 1],
            ['Erik Employee', 'employee@money.local', password_hash('demo123', PASSWORD_DEFAULT), 'employee', 1],
        ];
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?)');
        foreach ($users as $u) {
            $stmt->execute($u);
        }

        $companies = ['Nordic Design OAo', 'Baltic Logistics SIA', 'Green Energy AS'];
        $cStmt = $this->pdo->prepare('INSERT INTO companies (name, reg_number) VALUES (?, ?)');
        $cStmt->execute(['Nordic Design OAo', '12345678']);
        $cStmt->execute(['Baltic Logistics SIA', '40001234567']);
        $cStmt->execute(['Green Energy AS', '98765432']);

        $categories = [
            ['Meals', '#e74c3c', 'fa-utensils'],
            ['Travel', '#3498db', 'fa-plane'],
            ['Accommodation', '#9b59b6', 'fa-bed'],
            ['Office Supplies', '#f39c12', 'fa-box'],
            ['Transport', '#2ecc71', 'fa-car'],
            ['Entertainment', '#e67e22', 'fa-music'],
            ['Software', '#1abc9c', 'fa-laptop-code'],
            ['Utilities', '#7f8c8d', 'fa-bolt'],
            ['Other', '#6c757d', 'fa-tag'],
        ];
        $catStmt = $this->pdo->prepare('INSERT INTO categories (name, color, icon) VALUES (?, ?, ?)');
        foreach ($categories as $c) {
            $catStmt->execute($c);
        }

        // rate = home-currency (EUR) value of 1 unit of the foreign currency.
        $rates = [
            ['EUR', 1.0],
            ['USD', 0.92],
            ['GBP', 1.18],
            ['SEK', 0.088],
            ['NOK', 0.086],
            ['DKK', 0.134],
            ['PLN', 0.23],
            ['CHF', 1.06],
        ];
        $rStmt = $this->pdo->prepare('INSERT INTO exchange_rates (currency, rate) VALUES (?, ?)');
        foreach ($rates as $r) {
            $rStmt->execute($r);
        }

        $integrations = [
            ['Xero', 'fab fa-xero', 'Cloud accounting for small businesses.'],
            ['Merit Aktiva', 'fa-chart-line', 'The most popular accounting program in Estonia.'],
            ['Directo', 'fa-cubes', 'Web-based business software.'],
            ['SimplBooks', 'fa-book', 'Simple accounting for small business and accountants.'],
            ['Briox', 'fa-cogs', 'Smoother workflows for agencies and entrepreneurs.'],
            ['Standard Books', 'fa-folder-open', 'Business software with nearly 30 modules.'],
            ['Moneo', 'fa-coins', 'Accounting for advanced bureaus.'],
            ['Telema', 'fa-exchange-alt', 'Leading EDI and e-invoicing operator in the Baltics.'],
            ['ERPLY Books', 'fa-store', 'Professional web-based business software.'],
            ['Paytraq', 'fa-ship', 'Cloud-based ERP for invoicing, accounting, inventory.'],
            ['SmartAccounts', 'fa-calculator', 'Invoices, purchases, salaries, fixed assets.'],
        ];
        $iStmt = $this->pdo->prepare('INSERT INTO integrations (name, logo, description, type, connected, is_visible) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($integrations as $idx => $i) {
            $iStmt->execute([$i[0], $i[1], $i[2], 'accounting', $idx < 3 ? 1 : 0, 1]);
        }

        // Demo documents.
        $docs = [
            [1, 3, 'receipt', 'Rimi', 'R-1042', 24.90, 'EUR', 24.90, 0, 1, '2026-10-05', 'approved', 'robo', 1],
            [1, 2, 'invoice', 'Telia', 'INV-5581', 89.00, 'EUR', 89.00, 18.69, 8, '2026-10-04', 'pending', 'robo', 1],
            [2, 3, 'receipt', 'Hertz', 'HZ-991', 340.50, 'USD', 313.26, 0, 2, '2026-09-28', 'pending', 'human', 1],
            [1, 2, 'invoice', 'Microsoft', 'MS-2209', 120.00, 'EUR', 120.00, 25.20, 7, '2026-09-20', 'paid', 'robo', 1],
            [3, 3, 'receipt', 'Circle K', 'CK-771', 65.40, 'EUR', 65.40, 0, 5, '2026-09-15', 'approved', 'robo', 1],
        ];
        $dStmt = $this->pdo->prepare('INSERT INTO documents (company_id, user_id, type, vendor, doc_number, amount, currency, amount_home, tax, category_id, date, status, digitized, is_verified) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($docs as $d) {
            $dStmt->execute($d);
        }

        $lines = [
            [1, 'Office lunch', 1, 24.90, 24.90],
            [2, 'Monthly plan', 1, 89.00, 89.00],
            [3, 'Car rental 3 days', 3, 113.50, 340.50],
            [4, 'Microsoft 365', 12, 10.00, 120.00],
            [5, 'Fuel', 40, 1.635, 65.40],
        ];
        $lStmt = $this->pdo->prepare('INSERT INTO line_items (document_id, description, quantity, unit_price, amount) VALUES (?,?,?,?,?)');
        foreach ($lines as $l) {
            $lStmt->execute($l);
        }

        // Demo cards and transactions.
        $cards = [
            [1, 'Admin User', '4455', 'virtual', 'active', 2000, 'EUR', 'CardNet', '2026-01-01', '2029-01-01'],
            [1, 'Erik Employee', '7788', 'physical', 'active', 800, 'EUR', 'CardNet', '2026-01-01', '2029-01-01'],
            [2, 'Anna Accountant', '9911', 'virtual', 'frozen', 1500, 'EUR', 'CardNet', '2026-01-01', '2029-01-01'],
        ];
        $cardStmt = $this->pdo->prepare('INSERT INTO cards (company_id, holder, last4, card_type, status, "limit", currency, provider, issued_at, expires_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ($cards as $c) {
            $cardStmt->execute($c);
        }

        $txns = [
            [1, 24.90, 'EUR', 24.90, 'Rimi', 'Groceries', '2026-10-05', 1, 1, 'matched'],
            [1, 89.00, 'EUR', 89.00, 'Telia', 'Telecom', '2026-10-04', 2, 8, 'matched'],
            [2, 340.50, 'USD', 313.26, 'Hertz', 'Car rental', '2026-09-28', 3, 2, 'matched'],
            [2, 120.00, 'EUR', 120.00, 'Microsoft', 'SaaS', '2026-09-20', 4, 7, 'matched'],
            [3, 65.40, 'EUR', 65.40, 'Circle K', 'Fuel', '2026-09-15', 5, 5, 'matched'],
            [2, 15.00, 'EUR', 15.00, 'Bolt', 'Taxi', '2026-10-06', null, null, 'pending'],
        ];
        $tStmt = $this->pdo->prepare('INSERT INTO card_transactions (card_id, amount, currency, amount_home, merchant, description, booked_at, matched_document_id, category_id, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ($txns as $x) {
            $tStmt->execute($x);
        }

        // Demo e-invoices.
        $xml = static function (string $num, string $vendor, string $buyer, float $amount, string $currency, string $issue, string $due): string {
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                . "<Invoice>\n"
                . "  <InvoiceNumber>{$num}</InvoiceNumber>\n"
                . "  <Supplier>{$vendor}</Supplier>\n"
                . "  <Buyer>{$buyer}</Buyer>\n"
                . "  <Amount currency=\"{$currency}\">" . number_format($amount, 2, '.', '') . "</Amount>\n"
                . "  <IssueDate>{$issue}</IssueDate>\n"
                . "  <DueDate>{$due}</DueDate>\n"
                . "</Invoice>";
        };

        $eInv = [
            [1, 'EINV-1001', 'Telia Eesti AS', 'Nordic Design OAo', 89.00, 'EUR', 89.00, '2026-10-01', '2026-10-31', 'sent'],
            [2, 'EINV-2200', 'LMT SIA', 'Baltic Logistics SIA', 45.00, 'EUR', 45.00, '2026-10-02', '2026-11-01', 'draft'],
            [1, 'EINV-3303', 'Elering AS', 'Nordic Design OAo', 210.00, 'EUR', 210.00, '2026-10-03', '2026-11-03', 'paid'],
        ];
        $eStmt = $this->pdo->prepare('INSERT INTO e_invoices (company_id, invoice_number, vendor, buyer, amount, currency, amount_home, issue_date, due_date, status, xml) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($eInv as $e) {
            $eStmt->execute([$e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8], $e[9], $xml($e[1], $e[2], $e[3], $e[4], $e[5], $e[7], $e[8])]);
        }

        $this->pdo->exec("INSERT INTO settings (key, value) VALUES ('home_currency', 'EUR')");
    }
}
