<?php
declare(strict_types=1);

final class ValidationException extends RuntimeException {}

function config(string $key): mixed { return $GLOBALS['config'][$key] ?? null; }
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function base_path(): string { return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/.'); }
function url(string $route = 'home', array $params = []): string { return base_path() . '/index.php?' . http_build_query(['r' => $route] + $params); }
function asset(string $path): string { return base_path() . '/assets/' . $path; }
function redirect(string $route, array $params = []): never { header('Location: ' . url($route, $params), true, 303); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }
function post_only(): void { if (!is_post()) abort_request(405, 'Method not allowed'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(csrf()) . '">'; }
function verify_csrf(): void {
    if (!is_string($_POST['_token'] ?? null) || !hash_equals(csrf(), $_POST['_token'])) abort_request(419, 'Your session expired. Refresh the page and try again.');
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = ['message' => $message, 'type' => $type]; }
function render(string $view, array $data = [], string $layout = 'app'): void {
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT . '/app/views/' . $view . '.php';
    $content = ob_get_clean();
    require ROOT . '/app/views/layouts/' . $layout . '.php';
}
function abort_request(int $status, string $message): never {
    http_response_code($status);
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $status . ' | Money</title><link rel="stylesheet" href="' . e(asset('app.css')) . '"><main class="standalone"><a class="brand" href="' . e(url()) . '">money.</a><h1>' . $status . '</h1><p>' . e($message) . '</p><a class="button" href="' . e(url('dashboard')) . '">Back to Money</a></main></html>';
    exit;
}
function input(string $key, string $default = ''): string {
    $value = $_POST[$key] ?? $default;
    if (!is_string($value)) throw new ValidationException('Invalid form value.');
    return trim($value);
}
function required(string $key, int $max = 190): string {
    $value = input($key);
    if ($value === '' || mb_strlen($value) > $max) throw new ValidationException(ucfirst(str_replace('_', ' ', $key)) . " is required and must be at most $max characters.");
    return $value;
}
function date_value(string $value): string {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value || $value < '2000-01-01' || $value > '2100-12-31') throw new ValidationException('Enter a valid date between 2000 and 2100.');
    return $value;
}
function cents(string $value, bool $allowZero = false): int {
    if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $value)) throw new ValidationException('Enter a valid amount with up to two decimal places.');
    $parts = explode('.', $value);
    $amount = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    if ((!$allowZero && $amount <= 0) || $amount > 99999999999) throw new ValidationException('The amount must be greater than zero.');
    return $amount;
}
function decimal(int $amount): string { return number_format($amount / 100, 2, '.', ''); }
function money(int|string|null $amount, ?string $currency = null): string {
    return ($currency ?? (workspace()['currency'] ?? 'EUR')) . ' ' . number_format((int) $amount / 100, 2, '.', ',');
}
function short_date(?string $date): string { return $date ? date('d M Y', strtotime($date)) : '-'; }
function selected(mixed $a, mixed $b): string { return (string) $a === (string) $b ? ' selected' : ''; }
function icon(string $name): string { return '<span class="icon" data-icon="' . e($name) . '" aria-hidden="true"></span>'; }
function badge(string $status): string { return '<span class="badge ' . e($status) . '">' . e(ucfirst($status)) . '</span>'; }
function audit(string $action, string $description): void {
    Database::insert('audit_events', ['workspace_id' => workspace_id(), 'user_id' => current_user()['id'], 'action' => $action, 'description' => mb_substr($description, 0, 500)]);
}
function scoped(string $table, int $id): array {
    $row = Database::one("SELECT * FROM $table WHERE id=? AND workspace_id=?", [$id, workspace_id()]);
    if (!$row) abort_request(404, 'Record not found');
    return $row;
}
function id_param(): int { return filter_var($_GET['id'] ?? $_POST['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0; }
function month_range(): array {
    $month = $_GET['month'] ?? date('Y-m');
    if (!is_string($month) || !preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/D', $month)) $month = date('Y-m');
    $start = new DateTimeImmutable($month . '-01');
    return [$start->format('Y-m-d'), $start->modify('+1 month')->format('Y-m-d'), $month];
}
function csv_download(string $filename, array $headers, iterable $rows): never {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'wb');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        $row = array_map(static fn($v) => is_string($v) && preg_match('/^[=+@\-\t\r\n]/', $v) ? "'" . $v : $v, $row);
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}
