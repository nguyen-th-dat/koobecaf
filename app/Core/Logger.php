<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    private static function write(string $level, string $message): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = sprintf("[%s] %s: %s%s", date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND);
    }
}
