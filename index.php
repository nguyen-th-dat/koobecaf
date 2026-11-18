<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

define('BASE_PATH', __DIR__);

require BASE_PATH . '/app/bootstrap.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

/**
 * Tự nhận diện base path khi APP_BASE_PATH chưa được khai báo.
 *
 * Ứng dụng có thể nằm ở nhiều vị trí khác nhau:
 *   - PHP built-in server: SCRIPT_NAME = /index.php          → base rỗng
 *   - Apache trong htdocs: SCRIPT_NAME = /koobecaf/index.php → base = /koobecaf
 *
 * dirname() của SCRIPT_NAME chính là thư mục chứa entrypoint, tức phần
 * tiền tố URL mà mọi route của ứng dụng sẽ nằm sau.
 */
if (rtrim((string) Env::get('APP_BASE_PATH', ''), '/') === '') {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $detected = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    Env::set('APP_BASE_PATH', $detected === '/' ? '' : $detected);
}

date_default_timezone_set((string) Config::get('app.timezone', 'Asia/Ho_Chi_Minh'));

Session::start();

$request = new Request();
Csrf::validate($request);

$router = new Router();
(require BASE_PATH . '/routes/web.php')($router);

$router->dispatch($request);
