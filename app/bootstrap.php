<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
$GLOBALS['config'] = require ROOT . '/config.php';
date_default_timezone_set('UTC');
foreach (['logs', 'uploads', 'sessions', 'outbox'] as $directory) {
    $path = $GLOBALS['config']['storage'] . '/' . $directory;
    if (!is_dir($path)) {
        mkdir($path, 0700, true);
    }
}
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('error_log', $GLOBALS['config']['storage'] . '/logs/application.log');
require __DIR__ . '/Database.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/Ledger.php';
foreach (glob(__DIR__ . '/controllers/*.php') as $file) {
    require $file;
}
if (PHP_SAPI !== 'cli') {
    session_name('money_session');
    session_save_path(config('storage') . '/sessions');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() ?: '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Cache-Control: no-store');
}
