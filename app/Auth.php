<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['user_id']) ? Database::one('SELECT * FROM users WHERE id=?', [$_SESSION['user_id']]) : null;
        if ($user && (int) ($_SESSION['auth_version'] ?? 0) !== (int) $user['auth_version']) {
            unset($_SESSION['user_id'], $_SESSION['workspace_id']);
            $user = null;
        }
    }
    return $user;
}
function require_login(): void
{
    if (!current_user()) redirect('login');
    if (!workspace()) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Your workspace access has ended. Contact your workspace owner for a new invitation.', 'warning');
        redirect('login');
    }
}
function workspace(): ?array
{
    static $workspace = false;
    if ($workspace === false) {
        $user = current_user();
        $workspace = $user ? Database::one('SELECT w.*,m.role FROM workspaces w JOIN memberships m ON m.workspace_id=w.id WHERE m.user_id=? AND w.id=?', [$user['id'], $_SESSION['workspace_id'] ?? 0]) : null;
        if ($user && !$workspace) {
            $workspace = Database::one('SELECT w.*,m.role FROM workspaces w JOIN memberships m ON m.workspace_id=w.id WHERE m.user_id=? ORDER BY w.id LIMIT 1', [$user['id']]);
            if ($workspace) $_SESSION['workspace_id'] = $workspace['id'];
        }
    }
    return $workspace;
}
function workspace_id(): int { return (int) workspace()['id']; }
function can_manage(): bool { return in_array(workspace()['role'], ['owner', 'manager'], true); }
function require_manager(): void { if (!can_manage()) abort_request(403, 'This action requires a manager or owner.'); }
function require_owner(): void { if (workspace()['role'] !== 'owner') abort_request(403, 'Only the workspace owner can do this.'); }
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION = ['user_id' => $user['id'], 'auth_version' => $user['auth_version'], 'csrf' => bin2hex(random_bytes(32))];
}
function seed_categories(int $workspaceId): void
{
    foreach (['expense' => ['Software', 'Travel', 'Office', 'Professional services', 'Marketing', 'Other expenses'], 'income' => ['Sales', 'Services', 'Other income']] as $type => $names) {
        foreach ($names as $name) Database::insert('categories', ['workspace_id' => $workspaceId, 'type' => $type, 'name' => $name]);
    }
}
function send_account_link(string $email, string $subject, string $link): void
{
    $body = $subject . "\n\n" . $link . "\n\nMoney\n";
    if (config('environment') === 'local') {
        $file = config('storage') . '/outbox/' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.json';
        file_put_contents($file, json_encode(['to' => $email, 'subject' => $subject, 'body' => $body], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    } elseif (!mail($email, $subject, $body, "From: noreply@" . parse_url(config('url'), PHP_URL_HOST))) {
        throw new RuntimeException('Mail delivery is not configured.');
    }
}
