<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class GuestMiddleware extends Middleware
{
    public function handle(Request $request): void
    {
        if (!Session::has('user_id')) {
            return;
        }

        Response::redirect('/');
    }
}
