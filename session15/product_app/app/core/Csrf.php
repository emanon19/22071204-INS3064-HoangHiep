<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (
            !isset($_SESSION['csrf_token']) ||
            !is_string($_SESSION['csrf_token']) ||
            strlen($_SESSION['csrf_token']) !== 64
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateRequest(): void
    {
        $submittedToken = $_POST['_csrf_token'] ?? '';

        if ($submittedToken === '') {
            $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (
            !is_string($submittedToken) ||
            !is_string($sessionToken) ||
            $submittedToken === '' ||
            $sessionToken === '' ||
            !hash_equals($sessionToken, $submittedToken)
        ) {
            $isAjax = strtolower(
                (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')
            ) === 'xmlhttprequest';

            http_response_code(403);

            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');

                echo json_encode([
                    'success' => false,
                    'data' => null,
                    'errors' => [],
                    'message' => 'Your session token has expired. Refresh the page and try again.',
                ]);

                exit;
            }

            header('Content-Type: text/plain; charset=UTF-8');

            exit('Forbidden: invalid or expired CSRF token.');
        }
    }
}