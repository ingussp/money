<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require dirname(__DIR__) . '/config.php';
$db = $config['db'];
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $db['name'])) throw new RuntimeException('Invalid database name.');
$pdo = new PDO("mysql:host={$db['host']};port={$db['port']};charset=utf8mb4", $db['user'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $db['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `' . $db['name'] . '`');
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
echo "Money schema is ready in {$db['name']}.\n";
