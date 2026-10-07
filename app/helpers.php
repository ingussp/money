<?php
declare(strict_types=1);

/**
 * Helper functions shared across the application.
 */

function base_path(string $path = ''): string
{
    return rtrim(BASE_PATH, '/\\') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

/** Build an internal URL using query-string routing (works without mod_rewrite). */
function url(string $route = '', array $params = []): string
{
    $qs = ['route' => $route];
    if ($params) {
        $qs = array_merge($qs, $params);
    }
    return 'index.php?' . http_build_query($qs);
}

function redirect(string $route = '', array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Flash messages (one-shot session values). */
function flash(string $key, ?string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (isset($_SESSION['_flash'][$key])) {
        $m = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $m;
    }
    return null;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), (string)$token)) {
        http_response_code(419);
        exit('CSRF token mismatch. Please go back and try again.');
    }
}

/** Translate a key using the active locale. */
function t(string $key, array $replace = []): string
{
    global $LANG;
    $locale = current_locale();
    $value = $LANG[$locale][$key] ?? ($LANG['en'][$key] ?? $key);
    foreach ($replace as $k => $v) {
        $value = str_replace(':' . $k, (string)$v, $value);
    }
    return $value;
}

function current_locale(): string
{
    if (isset($_SESSION['locale'])) {
        return $_SESSION['locale'];
    }
    return 'en';
}

function set_locale(string $locale): void
{
    $_SESSION['locale'] = $locale;
}

/** Format money with a currency suffix. */
function money($amount, string $currency = 'EUR'): string
{
    return number_format((float)$amount, 2, '.', ' ') . ' ' . strtoupper($currency);
}

/** Current authenticated user or null. */
function current_user(): ?array
{
    if (isset($_SESSION['user_id'])) {
        static $user = null;
        if ($user === null) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        return $user;
    }
    return null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('auth/login');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

/** Append an entry to the audit log. */
function log_action(string $action): void
{
    $user = current_user();
    $stmt = db()->prepare('INSERT INTO audit_log (user_id, action) VALUES (?, ?)');
    $stmt->execute([$user['id'] ?? null, $action]);
}

/** Return the PDO instance (created lazily by bootstrap). */
function db(): PDO
{
    global $pdo;
    return $pdo;
}

/** Read a setting value. */
function setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT key, value FROM settings') as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([$key, $value]);
}

/** Read a setting value (alias of setting()). */
function get_setting(string $key, $default = null)
{
    return setting($key, $default);
}

/** Return the uploads directory path. */
function uploads_dir(): string
{
    return UPLOAD_PATH;
}

/** Render a Bootstrap badge for a status value. */
function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'paid' => 'info',
        'draft' => 'secondary',
        'submitted' => 'primary',
        'sent' => 'primary',
        'active' => 'success',
        'frozen' => 'warning',
        'blocked' => 'danger',
        'terminated' => 'dark',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge badge-' . $color . '">' . e(t($status)) . '</span>';
}

/** Handle an uploaded document file; returns stored filename or null. */
function upload_file(string $field): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmp = $_FILES[$field]['tmp_name'];
    $size = (int)$_FILES[$field]['size'];
    $max = 10 * 1024 * 1024; // 10 MB
    if ($size > $max) {
        flash('error', 'File is too large (max 10 MB).');
        return null;
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($ext, $allowed, true)) {
        flash('error', 'Unsupported file type.');
        return null;
    }
    $name = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0775, true);
    }
    if (!move_uploaded_file($tmp, UPLOAD_PATH . '/' . $name)) {
        flash('error', 'Failed to store the uploaded file.');
        return null;
    }
    return $name;
}

/** Render a badge describing how a document was digitised. */
function digitized_badge(string $digitized, int $verified): string
{
    $label = $digitized === 'robo' ? t('robo') : ($digitized === 'human' ? t('human') : t('none'));
    $color = $verified ? 'success' : 'secondary';
    return '<span class="badge badge-' . $color . '"><i class="fas fa-robot mr-1"></i>' . e($label) . '</span>';
}
