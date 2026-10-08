<?php
declare(strict_types=1);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~^/assets/[a-zA-Z0-9_./-]+\.(css|js|jpg|png|webp|svg|woff2|txt|csv)$~', $path)
    && !str_contains($path, '..') && is_file(__DIR__ . $path)) {
    return false;
}
if ($path !== '/' && $path !== '/index.php') {
    http_response_code(404);
    exit('Not found');
}
require __DIR__ . '/index.php';
