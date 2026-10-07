<?php
declare(strict_types=1);

/**
 * Global application configuration.
 */

define('APP_NAME', 'Money');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('DATA_PATH', __DIR__ . '/data');
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('DB_FILE', DATA_PATH . '/money.sqlite');
define('SESSION_NAME', 'money_session');
