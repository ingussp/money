<?php
declare(strict_types=1);

$config = [
    'name' => 'Money',
    'environment' => getenv('MONEY_ENV') ?: 'local',
    'url' => getenv('MONEY_URL') ?: 'http://localhost/money',
    'db' => [
        'host' => getenv('MONEY_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('MONEY_DB_PORT') ?: '3306',
        'name' => getenv('MONEY_DB_NAME') ?: 'money_app',
        'user' => getenv('MONEY_DB_USER') ?: 'root',
        'password' => getenv('MONEY_DB_PASSWORD') ?: '',
    ],
    'storage' => getenv('MONEY_STORAGE') ?: __DIR__ . '/storage',
];
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
