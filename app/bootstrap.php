<?php
declare(strict_types=1);

/**
 * Bootstraps the request: session, language, database, and helper functions.
 */

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name(SESSION_NAME);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/lang.php';

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Services.php';

$db = new Database(DB_FILE);
$db->migrate();
$db->seed();

$pdo = $db->pdo();
