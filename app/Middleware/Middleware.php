<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

abstract class Middleware
{
    abstract public function handle(Request $request): void;
}
