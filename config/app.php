<?php

declare(strict_types=1);

return [
    'name'        => (string) \App\Core\Env::get('APP_NAME', 'Koobecaf'),
    'url'         => rtrim((string) \App\Core\Env::get('APP_URL', 'http://localhost'), '/'),
    'env'         => (string) \App\Core\Env::get('APP_ENV', 'development'),
    'debug'       => \App\Core\Env::bool('APP_DEBUG', true),
    'timezone'    => (string) \App\Core\Env::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'base_path'   => rtrim((string) \App\Core\Env::get('APP_BASE_PATH', ''), '/'),
    'per_page'    => (int) \App\Core\Env::get('APP_PER_PAGE', 10),
    'session_name' => (string) \App\Core\Env::get('SESSION_NAME', 'koobecaf_session'),
];
