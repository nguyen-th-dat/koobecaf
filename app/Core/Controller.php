<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function view(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        echo View::render($template, $data, $layout);
    }
}
