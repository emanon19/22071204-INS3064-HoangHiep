<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(
        string $method,
        string $path,
        callable|array $handler
    ): void {
        $path = trim($path, '/');

        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = trim((string) ($_GET['route'] ?? ''), '/');

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);

            echo '<h1>404 - Page not found</h1>';
            echo '<p><a href="' . e(app_url('products')) . '">Back to products</a></p>';

            return;
        }

        call_user_func($this->routes[$method][$path]);
    }
}