<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\UserModel;
use PDOException;

final class AuthController
{
    public function __construct(
        private UserModel $users,
        private Auth $auth
    ) {
    }

    public function loginPage(): void
    {
        if ($this->auth->check()) {
            redirect(app_url('products'));
        }

        $state = consume_form_state();

        render('auth/login', [
            'pageTitle' => 'Login',
            'formData' => $state['data'],
            'errors' => $state['errors'],
        ]);
    }

    public function login(): void
    {
        verify_csrf();

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember_me'])
            && $_POST['remember_me'] === '1';

        $errors = [];

        if ($login === '') {
            $errors['login'] = 'Username or email is required.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        }

        if ($errors) {
            remember_form([
                'login' => $login,
            ], $errors);

            redirect(app_url('login'));
        }

        $user = $this->users->findByLogin($login);

        if (!$user || !password_verify($password, $user['password'])) {
            remember_form([
                'login' => $login,
            ], [
                'general' => 'Invalid username/email or password.',
            ]);

            redirect(app_url('login'));
        }

        $this->auth->login($user, $remember);

        flash('success', 'Welcome back, ' . $user['username'] . '!');

        redirect(app_url('products'));
    }

    public function registerPage(): void
    {
        if ($this->auth->check()) {
            redirect(app_url('products'));
        }

        $state = consume_form_state();

        render('auth/register', [
            'pageTitle' => 'Register',
            'formData' => $state['data'],
            'errors' => $state['errors'],
        ]);
    }

    public function register(): void
    {
        verify_csrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $username = strtolower($username);

        $data = [
            'username' => $username,
            'email' => $email,
        ];

        $errors = [];

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (mb_strlen($username) > 50) {
            $errors['username'] = 'Username must not exceed 50 characters.';
        } elseif (!preg_match('/^[a-z0-9_.-]+$/', $username)) {
            $errors['username'] = 'Username may contain lowercase letters, numbers, dots, underscores, and hyphens only.';
        } elseif ($this->users->findByUsername($username)) {
            $errors['username'] = 'This username is already taken.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (
            mb_strlen($email) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif ($this->users->findByEmail($email)) {
            $errors['email'] = 'This email is already registered.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must contain at least 8 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors) {
            remember_form($data, $errors);
            redirect(app_url('register'));
        }

        try {
            $this->users->create([
                'username' => $username,
                'email' => $email,
                'password' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
            ]);

            flash(
                'success',
                'Registration successful. You can now log in.'
            );

            redirect(app_url('login'));
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $errors = $e->getCode() === '23505'
                ? ['general' => 'Username or email is already registered.']
                : ['general' => 'Unable to create your account. Please try again.'];

            remember_form($data, $errors);

            redirect(app_url('register'));
        }
    }

    public function logout(): void
    {
        verify_csrf();

        $this->auth->logout();

        flash('success', 'You have been logged out successfully.');

        redirect(app_url('login'));
    }
}