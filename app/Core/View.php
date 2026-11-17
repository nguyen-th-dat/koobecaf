<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layouts/main'): string
    {
        $viewFile = self::path($template);
        if (!is_file($viewFile)) {
            throw new \RuntimeException("Không tìm thấy view: {$template}");
        }

        $content = view_include($viewFile, $data);

        if ($layout === '') {
            return $content;
        }

        $layoutFile = self::path($layout);
        if (!is_file($layoutFile)) {
            throw new \RuntimeException("Không tìm thấy layout: {$layout}");
        }

        return view_include($layoutFile, array_merge($data, ['content' => $content]));
    }

    public static function path(string $template): string
    {
        return dirname(__DIR__) . '/Views/' . str_replace('.', '/', $template) . '.php';
    }
}
