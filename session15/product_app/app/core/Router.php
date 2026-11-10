<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
        'DELETE' => [],
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

    public function delete(
        string $path,
        callable|array $handler,
        bool $requiresAuth = true
    ): void {
        $this->add('DELETE', $path, $handler, $requiresAuth);
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

        $matchedRoute = null;
        $routeParameters = [];

        foreach ($this->routes[$method] ?? [] as $routePath => $route) {
            $parameterNames = [];
            $pattern = '';
            $offset = 0;

            preg_match_all(
                '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
                $routePath,
                $placeholders,
                PREG_OFFSET_CAPTURE
            );

            foreach ($placeholders[0] as $index => $placeholder) {
                $placeholderText = $placeholder[0];
                $placeholderOffset = $placeholder[1];

                $staticPart = substr(
                    $routePath,
                    $offset,
                    $placeholderOffset - $offset
                );

                $pattern .= preg_quote($staticPart, '#');
                $pattern .= '([^/]+)';

                $parameterNames[] = $placeholders[1][$index][0];

                $offset = $placeholderOffset + strlen($placeholderText);
            }

            $pattern .= preg_quote(
                substr($routePath, $offset),
                '#'
            );

            if (preg_match('#^' . $pattern . '$#', $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);

            foreach ($parameterNames as $index => $name) {
                $routeParameters[$name] = rawurldecode($matches[$index]);
            }

            $matchedRoute = $route;
            break;
        }

        if ($matchedRoute === null) {
            $this->notFound($path);
            return;
        }

        if ($method === 'POST' || $method === 'DELETE') {
            Csrf::validateRequest();
        }

        if (
            $matchedRoute['requiresAuth'] &&
            !$this->auth->check()
        ) {
            if ($this->isApiPath($path)) {
                http_response_code(401);
                header('Content-Type: application/json; charset=UTF-8');

                echo json_encode([
                    'success' => false,
                    'data' => null,
                    'errors' => [],
                    'message' => 'Your session has expired. Please log in again.',
                ]);

                return;
            }

            redirect(app_url('login'));
        }

        call_user_func_array(
            $matchedRoute['handler'],
            array_values($routeParameters)
        );
    }

    private function notFound(string $path): void
    {
        http_response_code(404);

        if ($this->isApiPath($path)) {
            header('Content-Type: application/json; charset=UTF-8');

            echo json_encode([
                'success' => false,
                'data' => null,
                'errors' => [],
                'message' => 'API endpoint not found.',
            ]);

            return;
        }

        header('Content-Type: text/html; charset=UTF-8');

        echo '<h1>404 - Page not found</h1>';
        echo '<p><a href="' . e(app_url('products')) . '">Back to products</a></p>';
    }

    private function isApiPath(string $path): bool
    {
        return str_starts_with($path, 'api/');
    }
}