<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        header(
            "Content-Security-Policy: default-src 'self'; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            . "script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com"
        );
    }
}