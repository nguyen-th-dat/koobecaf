<?php

declare(strict_types=1);

return [
    'host'       => (string) \App\Core\Env::get('MAIL_HOST', 'smtp.gmail.com'),
    'port'       => (int) \App\Core\Env::get('MAIL_PORT', 587),
    'username'   => (string) \App\Core\Env::get('MAIL_USERNAME', ''),
    'password'   => (string) \App\Core\Env::get('MAIL_PASSWORD', ''),
    'encryption' => (string) \App\Core\Env::get('MAIL_ENCRYPTION', 'tls'),
    'from_email' => (string) \App\Core\Env::get('MAIL_FROM_EMAIL', 'no-reply@koobecaf.local'),
    'from_name'  => (string) \App\Core\Env::get('MAIL_FROM_NAME', 'Koobecaf'),
    // Đặt MAIL_DRIVER=log để ghi nội dung mail vào storage/logs thay vì gửi thật (môi trường dev).
    'driver'     => (string) \App\Core\Env::get('MAIL_DRIVER', 'smtp'),
    'debug'      => \App\Core\Env::bool('MAIL_DEBUG', false),
];
