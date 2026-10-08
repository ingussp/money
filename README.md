# Money

Money is a business-finance workspace built in **plain PHP 8.2 and MySQL / MariaDB**. The application UI and source code are English. It replaces the previous SQLite/AdminLTE application with the selected **H01 / Green Clarity** public homepage and **06 / Daily Focus** internal interface.

## Demo Access

| Field | Value |
| --- | --- |
| Email | `demo@money.local` |
| Password | `123` |
| Workspace | Forma Studio |
| Role | Owner |

The demo contains six months of sample income and expenses, contacts, an expense budget and pending approvals. New registrations create empty workspaces. The demo password is a local example; normal registration and password changes require at least 12 characters.

Create the sample workspace after installing the database:

```powershell
C:\xampp\php\php.exe tools\seed-demo.php
```

To change an existing demo account to the example password without replacing its financial records:

```powershell
C:\xampp\php\php.exe tools\seed-demo.php --reset-password
```

The seed is idempotent and does not duplicate records. `MONEY_DEMO_PASSWORD` can override the example password.

## Features

- Registration, login, logout, profile settings, password changes and expiring single-use password reset links.
- Separate **Income** and **Expenses** sections, categories, searches, monthly filters, pagination and draft editing/deletion.
- Dashboard with monthly income, expenses, net balance, a six-month cash-flow chart, category spending and a monthly expense budget.
- Expense submission, approval, rejection with a reason and payment recording.
- Private receipt/invoice uploads (PDF, JPG, PNG, WebP), linked entries and authenticated downloads.
- Customer/supplier contacts and customer invoices with line items, quantities, tax, printing/PDF and draft/sent/paid/cancelled statuses.
- Invoice payment automatically creates one locked income entry. Repeated payment requests do not create duplicate income.
- Bank statement CSV import, duplicate-reference detection and manual reconciliation with paid entries.
- Monthly reports, a 12-month chart, accounting CSV exports and an activity log.
- Separate company workspaces and owner/manager/member access. Owners manage team invitations, roles and access revocation. Members only see their own expenses and uploaded documents.
- Responsive desktop/mobile layouts with local icons and product artwork; the application has no external CDN dependency.

## Financial Rules

Amounts are stored as integer cents. Each workspace has one fixed currency (EUR, USD or GBP). The dashboard and reports use **paid entries and their payment dates**. Net balance is period income minus period expenses; it is not a bank-account balance. Income and expense lists use the entry date.

Expense/income totals are gross amounts with the included tax entered separately. Invoice tax is calculated and rounded per line on the server. Submitted and finalized entries are locked; rejected expenses can be corrected and resubmitted. Invoice company/customer details are snapshots and remain unchanged when contacts or company settings are edited.

## Installation With XAMPP

1. Place the project in `C:\xampp\htdocs\money` and start Apache and MySQL in XAMPP.
2. Copy `config.example.php` to `config.local.php` and set your MySQL credentials. Defaults: host `127.0.0.1`, port `3306`, database `money_app`, user `root`, empty password.
3. Install the schema and optional demo data:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\money\tools\install.php
C:\xampp\php\php.exe C:\xampp\htdocs\money\tools\seed-demo.php
```

4. Open **http://localhost/money/** and sign in with the demo credentials or register a new account.

The installer creates missing tables without deleting existing databases. The old SQLite database is not imported. `tools/deploy-xampp.cmd` deploys this checkout into the exact XAMPP Money directory, archiving the previous project outside the web root first. This deployment requires write access to `C:\xampp\htdocs`; the agent environment did not have that access.

Set PHP `upload_max_filesize=10M` and `post_max_size=12M` for 10 MB document uploads. Apache must allow the project's `.htaccess`, which blocks configuration, application internals, tools, schema files and private storage. The built-in PHP router applies equivalent restrictions during preview.

Configuration is environment driven: `MONEY_ENV`, `MONEY_URL`, `MONEY_DB_HOST`, `MONEY_DB_PORT`, `MONEY_DB_NAME`, `MONEY_DB_USER`, `MONEY_DB_PASSWORD`, `MONEY_STORAGE`. Ignored `config.local.php` overrides these values. Sessions, uploads, logs and development mail are kept in ignored private storage.

## Project Structure

```text
index.php                 Route dispatch
config.php                Configuration
app/Database.php          PDO queries and transactions
app/Auth.php              Sessions and workspace authorization
app/Ledger.php            Entry permissions and cash-flow queries
app/controllers/          Authentication and business workflows
app/views/                Escaped PHP templates and layouts
assets/                   CSS, JavaScript, icons and original artwork
database/schema.sql       MySQL / MariaDB schema
storage/                  Private runtime files (not committed)
tools/                    Install, demo seed, preview and deployment utilities
tests/                    Browser/workflow verification
create-pr.cmd             Publish the prepared branch and create its GitHub PR
```

The application requires no PHP framework, Composer package or Node server. Node and Playwright are only development tools. URLs use `index.php?r=...`.

## Local Preview And Verification

The working checkout is `C:\dev\money`. The current ignored local config uses MariaDB on `127.0.0.1:3307`, with its data in `C:\dev\money-runtime\mysql`. It is separate from XAMPP's standard database directory. The preview is **http://127.0.0.1:8085**.

```powershell
node tools\start-preview.mjs
```

For development checks, install the declared dependencies in this repository:

```powershell
npm install
npx playwright install chromium
npm run lint:php
npm test
```

The test suite uses a separate `money_test_*` database and a temporary PHP server on port 8086. It checks demo login, authentication, authorization, workspace isolation, financial calculations, approvals, invoice payment idempotency, protected uploads, CSV exchange, reset links and responsive layouts at 320/390/768/1440/1920 px. Test servers stop on completion. Reports and screenshots are written to ignored `tests/results/`.

The browser helper uses `MONEY_BROWSER_PATH`, a locally installed Chromium cache or Chrome/Edge. Tests are self-contained in this repository and do not depend on the earlier design gallery. XAMPP PHP is expected at `C:\xampp\php\php.exe` for the Windows development utilities.

## Create The Pull Request

Run **`create-pr.cmd`** from this checkout. It pushes `feature/php-money-workspace` to **https://github.com/ingussp/money** and creates a PR into `main` with the prepared title and description. If an open PR already exists for the branch, it prints that PR's URL.

The script uses authenticated GitHub CLI when available. Otherwise it uses Git Credential Manager's GitHub credentials or `GH_TOKEN`/`GITHUB_TOKEN` with the GitHub API. Credentials are never written into this project. A GitHub sign-in may be needed on your machine. The script requires the prepared branch and a clean worktree, and stops if Git push fails.

Check the publication setup without pushing or creating a PR:

```powershell
create-pr.cmd -CheckOnly
```

## External Services

Manual entry, document storage, CSV bank imports and accounting exports work. OCR/AI extraction, incoming receipt email processing, direct bank feeds, card issuing, accounting-provider synchronization, PEPPOL, currency conversion, native mobile apps, travel/mileage bundles and paid subscriptions are not connected or implemented. The integration page shows their actual status. Feature reference: https://costpocket.com/en/features.

In local mode, password-reset and invitation emails are saved to `storage/outbox/*.json`; no email is sent externally. Non-local mode uses PHP `mail()` and requires a configured mail transport. Public hosting needs its own HTTPS, database credentials, email delivery, backups and legal policies.

## Licenses

Local Lucide/Feather icons retain their license in `assets/LICENSE-lucide.txt`. Product artwork was rendered from the actual English UI with sample data. The pictured receipt is illustrative artwork.
