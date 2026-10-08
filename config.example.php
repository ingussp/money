<?php
return [
    'environment' => 'local',
    'url' => 'http://localhost/money',
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'money_app',
        'user' => 'root',
        'password' => '',
    ],
    // For production, choose a private directory outside the web root.
    // 'storage' => 'C:/private/money-storage',
];
