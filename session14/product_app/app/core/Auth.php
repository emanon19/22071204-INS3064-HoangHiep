<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UserModel;

final class Auth
{
    private const COOKIE_NAME = 'remember_me';
    private const COOKIE_LIFETIME = 2592000;
    private const SESSION_TIMEOUT = 1800;

    public function __construct(
        private UserModel $users
    ) {
        $this->validateRememberMe();
    }

    public function login(array $user, bool $remember = false): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();

        if ($remember) {
            $this->rememberMe($user);
        } else {
            $this->users->updateRememberToken(
                (int) $user['id'],
                null
            );

            $this->clearRememberCookie();
        }
    }

    public function logout(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $cookieToken = $_COOKIE[self::COOKIE_NAME] ?? '';

        if ($userId > 0) {
            $this->users->updateRememberToken($userId, null);
        }

        if (
            is_string($cookieToken) &&
            preg_match('/^[a-f0-9]{64}$/D', $cookieToken)
        ) {
            $this->users->clearRememberTokenByHash(
                hash('sha256', $cookieToken)
            );
        }

        $this->clearRememberCookie();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        $oldSessionName = session_name();

        session_destroy();

        unset($_COOKIE[$oldSessionName]);

        session_start();
        session_regenerate_id(true);
    }

    public function check(): bool
    {
        if (
            !isset($_SESSION['user_id']) ||
            (int) $_SESSION['user_id'] <= 0
        ) {
            return false;
        }

        $now = time();
        $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);

        if (($now - $lastActivity) > self::SESSION_TIMEOUT) {
            unset(
                $_SESSION['user_id'],
                $_SESSION['username'],
                $_SESSION['last_activity']
            );

            session_regenerate_id(true);

            return false;
        }

        $_SESSION['last_activity'] = $now;

        return true;
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        $user = $this->users->findById(
            (int) $_SESSION['user_id']
        );

        if (!$user) {
            unset(
                $_SESSION['user_id'],
                $_SESSION['username'],
                $_SESSION['last_activity']
            );

            return null;
        }

        unset($user['password'], $user['remember_token']);

        return $user;
    }

    public function rememberMe(array $user): void
    {
        $token = bin2hex(random_bytes(32));

        $this->users->updateRememberToken(
            (int) $user['id'],
            hash('sha256', $token)
        );

        $this->setRememberCookie($token);
    }

    public function validateRememberMe(): void
    {
        if ($this->check()) {
            return;
        }

        $token = $_COOKIE[self::COOKIE_NAME] ?? '';

        if (
            !is_string($token) ||
            !preg_match('/^[a-f0-9]{64}$/D', $token)
        ) {
            if ($token !== '') {
                $this->clearRememberCookie();
            }

            return;
        }

        $user = $this->users->findByRememberToken(
            hash('sha256', $token)
        );

        if (!$user) {
            $this->clearRememberCookie();
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();

        // Rotate the persistent token after successful restoration.
        $this->rememberMe($user);
    }

    private function setRememberCookie(string $token): void
    {
        $isSecure = !empty($_SERVER['HTTPS'])
            && strtolower((string) $_SERVER['HTTPS']) !== 'off';

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[self::COOKIE_NAME] = $token;
    }

    private function clearRememberCookie(): void
    {
        $isSecure = !empty($_SERVER['HTTPS'])
            && strtolower((string) $_SERVER['HTTPS']) !== 'off';

        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[self::COOKIE_NAME]);
    }
}