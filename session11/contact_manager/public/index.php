<?php

declare(strict_types=1);

use App\Controllers\ContactController;
use App\Core\Router;

session_start();

/*
|--------------------------------------------------------------------------
| Application paths
|--------------------------------------------------------------------------
*/

define('BASE_URL', rtrim(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])),
    '/'
));

if (BASE_URL === '.' || BASE_URL === '/') {
    define('APP_BASE_URL', '');
} else {
    define('APP_BASE_URL', BASE_URL);
}

/*
|--------------------------------------------------------------------------
| Autoloading
|--------------------------------------------------------------------------
*/

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDirectory = dirname(__DIR__) . '/app/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    $file = $baseDirectory
        . str_replace('\\', '/', $relativeClass)
        . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

/*
|--------------------------------------------------------------------------
| Shared helpers
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function app_url(string $route = '', array $params = []): string
{
    $url = APP_BASE_URL . '/index.php';

    $query = [];

    if ($route !== '') {
        $query['route'] = $route;
    }

    $query = $query + $params;

    return $url . (
        $query !== []
            ? '?' . http_build_query($query)
            : ''
    );
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/*
|--------------------------------------------------------------------------
| Initialize security token
|--------------------------------------------------------------------------
*/

csrf_token();

/*
|--------------------------------------------------------------------------
| Initialize controller, router and routes
|--------------------------------------------------------------------------
*/

try {
    $controller = new ContactController();

    $router = new Router();

    $router->get('/', [$controller, 'index']);
    $router->get('/create', [$controller, 'create']);
    $router->post('/store', [$controller, 'store']);
    $router->get('/edit', [$controller, 'edit']);
    $router->post('/update', [$controller, 'update']);
    $router->post('/delete', [$controller, 'delete']);

    $router->dispatch();

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo 'An unexpected error occurred. Please try again later.';
}
