<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Router;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Controllers\CategoryController;
use App\Controllers\ProductController;

session_start();

define('BASE_PATH', dirname(__DIR__));

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

    $categoryModel = new CategoryModel($pdo);
    $productModel = new ProductModel($pdo);

    $categoryController = new CategoryController($categoryModel);
    $productController = new ProductController($productModel);

    $router = new Router();

    // Products
    $router->get('', [$productController, 'index']);
    $router->get('products', [$productController, 'index']);
    $router->get('products/create', [$productController, 'create']);
    $router->get('products/edit', [$productController, 'edit']);

    $router->post('products/store', [$productController, 'save']);
    $router->post('products/update', [$productController, 'save']);
    $router->post('products/delete', [$productController, 'delete']);

    // Categories
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
    echo 'An unexpected error occurred. Please try again later.';
}