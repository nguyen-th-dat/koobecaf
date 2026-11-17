<?php

declare(strict_types=1);

return [
    'host'     => (string) \App\Core\Env::get('DB_HOST', '127.0.0.1'),
    'port'     => (int) \App\Core\Env::get('DB_PORT', 3306),
    'database' => (string) \App\Core\Env::get('DB_DATABASE', 'koobecaf'),
    'username' => (string) \App\Core\Env::get('DB_USERNAME', 'root'),
    'password' => (string) \App\Core\Env::get('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
];
