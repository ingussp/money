<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

try {
    $route = trim((string) ($_GET['r'] ?? 'home'), '/');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
    }
    $public = ['home', 'login', 'register', 'forgot-password', 'reset-password', 'invite', 'privacy'];
    if (!in_array($route, $public, true)) {
        require_login();
    }
    match ($route) {
        'home' => render('home', ['title' => 'Business finances, in focus'], 'public'),
        'privacy' => render('privacy', ['title' => 'Privacy'], 'public'),
        'login', 'register', 'logout', 'forgot-password', 'reset-password', 'invite' => AuthController::handle($route),
        'dashboard' => LedgerController::dashboard(),
        'income', 'expenses' => LedgerController::index($route === 'income' ? 'income' : 'expense'),
        'entry' => LedgerController::form(),
        'entry-action' => LedgerController::action(),
        'approvals' => LedgerController::approvals(),
        'reports', 'export' => LedgerController::reports($route === 'export'),
        'documents', 'document-download', 'document-delete' => DocumentController::handle($route),
        'invoices', 'invoice', 'invoice-action', 'invoice-print' => InvoiceController::handle($route),
        'contacts', 'settings', 'team', 'activity', 'integrations', 'switch-workspace' => WorkspaceController::handle($route),
        'reconciliation', 'bank-import', 'bank-match' => ReconciliationController::handle($route),
        default => abort_request(404, 'Page not found'),
    };
} catch (ValidationException $e) {
    http_response_code(422);
    render('error', ['title' => 'Check your information', 'message' => $e->getMessage()], current_user() ? 'app' : 'public');
} catch (PDOException $e) {
    error_log((string) $e);
    http_response_code(503);
    require __DIR__ . '/app/views/unavailable.php';
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    require __DIR__ . '/app/views/unavailable.php';
}
