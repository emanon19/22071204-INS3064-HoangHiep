<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function __construct(
        private Auth $auth
    ) {
    }

    public function get(
        string $path,
        callable|array $handler,
        bool $requiresAuth = true
    ): void {
        $this->add('GET', $path, $handler, $requiresAuth);
    }

    public function post(
        string $path,
        callable|array $handler,
        bool $requiresAuth = true
    ): void {
        $this->add('POST', $path, $handler, $requiresAuth);
    }

    private function add(
        string $method,
        string $path,
        callable|array $handler,
        bool $requiresAuth
    ): void {
        $path = trim($path, '/');

        $this->routes[$method][$path] = [
            'handler' => $handler,
            'requiresAuth' => $requiresAuth,
        ];
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = trim((string) ($_GET['route'] ?? ''), '/');

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            header('Content-Type: text/html; charset=UTF-8');

            echo '<h1>404 - Page not found</h1>';
            echo '<p><a href="' . e(app_url('products')) . '">Back to products</a></p>';

            return;
        }

        $route = $this->routes[$method][$path];

        if ($method === 'POST') {
            Csrf::validateRequest();
        }

        if ($route['requiresAuth'] && !$this->auth->check()) {
            redirect(app_url('login'));
        }

        call_user_func($route['handler']);
    }
}