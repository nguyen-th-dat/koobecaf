<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 tối giản, không phụ thuộc thư viện ngoài.
 */
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => dirname(__DIR__) . '/app/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require dirname(__DIR__) . '/app/Helpers/helpers.php';
