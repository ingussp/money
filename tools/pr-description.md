## Changes

Replace the previous SQLite/AdminLTE application with a framework-free PHP 8.2 and MySQL/MariaDB Money workspace, using H01 / Green Clarity for the public homepage and 06 / Daily Focus for the internal interface. Remove obsolete controllers, templates and the tracked SQLite database.

- Add registration/login, password reset, isolated company workspaces, team invitations and owner/manager/member permissions.
- Keep income and expenses in separate sections; derive dashboard cash flow and net balance from paid entries.
- Add protected document uploads, expense approvals, contacts, invoices, reports, accounting CSV export and bank CSV reconciliation.
- Record invoice payments as income once, with integer-cent calculations and transaction/row-lock protection.
- Include responsive English interfaces, local icons, original product artwork, schema installation and XAMPP deployment utilities.
- Document installation, project structure, implemented workflows and external service status in README.
- Set the local demo credentials to `demo@money.local` / `123`; provide an idempotent demo seed and an explicit existing-password reset option.
- Include self-contained Playwright development tools and a repeatable Windows PR publishing helper.

## Validation

- PHP syntax validation: 45 files passed.
- Browser/workflow suite: 225 checks passed, covering authentication, demo login, permissions, workspace isolation, cash-flow calculations, invoice payment idempotency, uploads, CSV exchange and responsive layouts.
- Local dry run of the PR helper, including repository, branch, clean-worktree and JSON request validation. Windows PowerShell sends the PR description as a plain JSON string.

## Integration Status

Manual finance/document workflows and CSV exchange are implemented. OCR, direct banking/card issuing, accounting-provider synchronization and PEPPOL are not connected. Development account emails are stored in the private local outbox.
