# Money

A lightweight expense-management and receipt-digitization web application, built as a
pure-PHP clone of [CostPocket](https://costpocket.com). It needs no framework — just PHP
with PDO and SQLite, styled with the free [AdminLTE](https://adminlte.io) dashboard theme.

## Features

- **Authentication & roles** — register, log in, log out; three roles (`admin`,
  `accountant`, `employee`) with CSRF protection and session management.
- **Companies** — manage multiple legal entities with registration numbers.
- **Documents (receipts / invoices)** — upload photos or PDFs, automated digitization,
  automatic category suggestion, duplicate detection, currency conversion to the home
  currency, line items, and an approval / rejection workflow.
- **Categories** — expense categories with colors and icons.
- **Corporate cards** — virtual and physical cards with limits, currency and provider.
- **Reconciliation** — match and unmatch card transactions against documents.
- **E-invoices** — create, view, send and mark as paid, with PEPPOL XML
  generation / download.
- **Integrations** — connect and disconnect banking, accounting, EPOS and other services.
- **Reports** — filtered expense reports with CSV export.
- **Settings** — home currency, language, audit log and user management.
- **Multi-language** — English and Latvian (full), with Estonian / Finnish / Lithuanian /
  Polish falling back to English.
- **REST API** — JSON endpoints for users, documents, categories and exchange rates.

## Tech stack

- Pure PHP (no framework)
- PDO + SQLite (single-file database)
- AdminLTE 3 / Bootstrap 4 / Font Awesome via CDN
- Apache with `mod_rewrite` (XAMPP) or the PHP built-in server

## Requirements

- PHP 8.1+ with the `pdo_sqlite` extension
- Apache with `mod_rewrite` enabled (for pretty URLs), or the PHP built-in server

## Installation

1. Copy the project into your web root, for example `C:\xampp\htdocs\money`.
2. Make sure the web server can write to the `data/` and `uploads/` directories.
3. Open the application in a browser. The SQLite schema and demo data are created
   automatically on the first request (see `app/Database.php`).

To run quickly with the built-in server:

    php -S localhost:8000 -t .

Then open `http://localhost:8000`.

## Demo login data

| Role       | Email                     | Password   |
| ---------- | ------------------------- | ---------- |
| Admin      | `admin@money.local`       | `admin123` |
| Accountant | `accountant@money.local`  | `demo123`  |
| Employee   | `employee@money.local`    | `demo123`  |

## Project structure

    money/
    ├── app/
    │   ├── controllers/      # request handlers
    │   ├── views/            # AdminLTE templates
    │   ├── bootstrap.php     # session, language, database, helpers
    │   ├── Database.php      # schema + demo seeder
    │   ├── Router.php        # simple router with controller mapping
    │   ├── Services.php      # digitization, currency, duplicates
    │   ├── lang.php          # localisation strings
    │   └── ...
    ├── data/money.sqlite     # SQLite database (committed with demo data only)
    ├── uploads/              # uploaded receipt/invoice files (created at runtime)
    ├── config.php            # application configuration
    ├── index.php             # front controller
    └── .htaccess             # pretty-URL rules

## Database

`data/money.sqlite` is committed with seed/demo data only, so the application works
immediately after cloning. Runtime sidecar files (`*.sqlite-wal`, `*.sqlite-shm`,
journals and backups) are ignored by git.
