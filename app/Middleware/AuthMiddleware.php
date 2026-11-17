<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware extends Middleware
{
    public function handle(Request $request): void
    {
        if (Session::has('user_id')) {
            return;
        }

        if ($request->isJson() || str_starts_with($request->path(), '/api/')) {
            Response::error('Bạn cần đăng nhập để thực hiện thao tác này.', 401);
        }

        Session::flash('error', 'Vui lòng đăng nhập để tiếp tục.');
        Response::redirect('/login');
    }
}
