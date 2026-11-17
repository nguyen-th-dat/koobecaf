<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Ho_Chi_Minh'));

Session::start();

$request = new Request();
Csrf::validate($request);

$router = new Router();
(require BASE_PATH . '/routes/web.php')($router);

$router->dispatch($request);
