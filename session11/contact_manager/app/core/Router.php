<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\ContactController;

class Router
{
    private array $routes = [];

    public function get(string $route, callable $callback): void
    {
        $this->routes['GET'][$this->normalize($route)] = $callback;
    }

    public function post(string $route, callable $callback): void
    {
        $this->routes['POST'][$this->normalize($route)] = $callback;
    }

    /**
     * Dispatch the current HTTP request.
     */
    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        $route = (string) ($_GET['route'] ?? '/');

        $route = $this->normalize($route);

        $callback = $this->routes[$method][$route] ?? null;

        if ($callback === null) {
            http_response_code(404);
            echo '404 - Page Not Found';
            return;
        }

        call_user_func($callback);
    }

    /**
     * Normalize route paths.
     */
    private function normalize(string $route): string
    {
        $route = trim($route);

        if ($route === '' || $route === '/') {
            return '/';
        }

        return '/' . trim($route, '/');
    }
}