<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\Router;
use App\Core\Security;
use App\Models\UserModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\CategoryController;
use App\Controllers\ProductController;

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/core/Security.php';

Security::sendHeaders();

// Do not expose PHP errors to browser users.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Session security configuration.
$isSecure = !empty($_SERVER['HTTPS'])
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Autoload application classes.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $directories = array_map('strtolower', $parts);

    $path = BASE_PATH
        . '/app/'
        . ($directories ? implode('/', $directories) . '/' : '')
        . $className
        . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

require_once BASE_PATH . '/app/core/helpers.php';

try {
    $pdo = Database::getInstance()->getConnection();

    // Models.
    $userModel = new UserModel($pdo);
    $categoryModel = new CategoryModel($pdo);
    $productModel = new ProductModel($pdo);

    // Authentication and rate limiting.
    $auth = new Auth($userModel);

    $rateLimiter = new RateLimiter(
        BASE_PATH . '/storage/ratelimit'
    );

    // Controllers.
    $authController = new AuthController(
        $userModel,
        $auth,
        $rateLimiter
    );

    $userController = new UserController($userModel, $auth);
    $categoryController = new CategoryController($categoryModel);
    $productController = new ProductController($productModel);

    // Router.
    $router = new Router($auth);

    // Authentication routes.
    $router->get('login', [$authController, 'loginPage'], false);
    $router->post('login', [$authController, 'login'], false);

    $router->get('register', [$authController, 'registerPage'], false);
    $router->post('register', [$authController, 'register'], false);

    // Logout.
    $router->post('logout', [$authController, 'logout']);

    // User account routes.
    $router->get('profile', [$userController, 'profile']);
    $router->post('profile/update-email', [$userController, 'updateEmail']);

    $router->get('change-password', [$userController, 'changePasswordPage']);
    $router->post('change-password', [$userController, 'changePassword']);

    // Products.
    $router->get('', [$productController, 'index']);
    $router->get('products', [$productController, 'index']);
    $router->get('products/create', [$productController, 'create']);
    $router->get('products/edit', [$productController, 'edit']);

    $router->post('products/store', [$productController, 'save']);
    $router->post('products/update', [$productController, 'save']);
    $router->post('products/delete', [$productController, 'delete']);

    // Categories.
    $router->get('categories', [$categoryController, 'index']);
    $router->get('categories/create', [$categoryController, 'create']);
    $router->get('categories/edit', [$categoryController, 'edit']);

    $router->post('categories/store', [$categoryController, 'store']);
    $router->post('categories/update', [$categoryController, 'update']);
    $router->post('categories/delete', [$categoryController, 'delete']);

    $router->dispatch();
} catch (Throwable $e) {
    error_log(sprintf(
        "[%s] %s in %s:%d\n%s",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');

    echo 'An unexpected error occurred. Please try again later.';
}