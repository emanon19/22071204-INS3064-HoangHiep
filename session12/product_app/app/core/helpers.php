<?php
declare(strict_types=1);

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
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

    $basePath = rtrim(
        str_replace('\\', '/', dirname($script)),
        '/'
    );

    $url = $basePath . '/index.php';

    $query = [];

    if ($route !== '') {
        $query['route'] = trim($route, '/');
    }

    $query = array_merge($query, $params);

    return $url . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function render(string $view, array $data = []): void
{
    $viewPath = dirname(__DIR__) . '/views/' . $view . '.php';

    if (!is_file($viewPath)) {
        throw new RuntimeException('View not found: ' . $view);
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $viewPath;
    $content = ob_get_clean();

    require dirname(__DIR__) . '/views/layout.php';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function get_flash(string $type): ?string
{
    $message = $_SESSION['flash'][$type] ?? null;

    unset($_SESSION['flash'][$type]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submittedToken = (string) ($_POST['_csrf_token'] ?? '');

    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

    if (
        $submittedToken === '' ||
        $sessionToken === '' ||
        !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(419);

        exit('Your session has expired. Please refresh the page and try again.');
    }
}

function remember_form(array $data, array $errors): void
{
    $_SESSION['form_data'] = $data;
    $_SESSION['form_errors'] = $errors;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['form_data'][$key] ?? $default;
}

function form_errors(): array
{
    return $_SESSION['form_errors'] ?? [];
}

function clear_form_state(): void
{
    unset(
        $_SESSION['form_data'],
        $_SESSION['form_errors']
    );
}

function consume_form_state(): array
{
    $state = [
        'data' => $_SESSION['form_data'] ?? [],
        'errors' => $_SESSION['form_errors'] ?? [],
    ];

    clear_form_state();

    return $state;
}