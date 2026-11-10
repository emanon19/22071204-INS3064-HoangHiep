<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\UserModel;
use PDOException;

final class UserController
{
    public function __construct(
        private UserModel $users,
        private Auth $auth
    ) {
    }

    public function profile(): void
    {
        $user = $this->auth->user();

        if (!$user) {
            $this->auth->logout();
            redirect(app_url('login'));
        }

        $state = consume_form_state();

        render('user/profile', [
            'pageTitle' => 'My Profile',
            'user' => $user,
            'formData' => $state['data'],
            'errors' => $state['errors'],
        ]);
    }

    public function updateEmail(): void
    {
        verify_csrf();

        $user = $this->auth->user();

        if (!$user) {
            redirect(app_url('login'));
        }

        $email = strtolower(
            trim((string) ($_POST['email'] ?? ''))
        );

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (
            mb_strlen($email) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $existingUser = $this->users->findByEmail($email);

            if (
                $existingUser &&
                (int) $existingUser['id'] !== (int) $user['id']
            ) {
                $errors['email'] = 'This email is already registered.';
            }
        }

        if ($errors) {
            remember_form(['email' => $email], $errors);
            redirect(app_url('profile'));
        }

        try {
            $this->users->updateEmail(
                (int) $user['id'],
                $email
            );

            flash('success', 'Email address updated successfully.');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            remember_form(
                ['email' => $email],
                ['email' => 'Unable to update email address.']
            );

            redirect(app_url('profile'));
        }

        redirect(app_url('profile'));
    }

    public function changePasswordPage(): void
    {
        $state = consume_form_state();

        render('user/change-password', [
            'pageTitle' => 'Change Password',
            'errors' => $state['errors'],
        ]);
    }

    public function changePassword(): void
    {
        verify_csrf();

        $user = $this->auth->user();

        if (!$user) {
            redirect(app_url('login'));
        }

        $currentPassword = (string) (
            $_POST['current_password'] ?? ''
        );

        $newPassword = (string) (
            $_POST['new_password'] ?? ''
        );

        $confirmPassword = (string) (
            $_POST['confirm_password'] ?? ''
        );

        $errors = [];

        $fullUser = $this->users->findById(
            (int) $user['id']
        );

        if (
            !$fullUser ||
            !password_verify(
                $currentPassword,
                $fullUser['password']
            )
        ) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'New password must contain at least 8 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'New passwords do not match.';
        }

        if (
            $currentPassword !== '' &&
            $currentPassword === $newPassword
        ) {
            $errors['new_password'] = 'New password must be different from your current password.';
        }

        if ($errors) {
            remember_form([], $errors);
            redirect(app_url('change-password'));
        }

        try {
            $this->users->updatePassword(
                (int) $user['id'],
                password_hash($newPassword, PASSWORD_DEFAULT)
            );

            flash('success', 'Password changed successfully.');
        } catch (PDOException $e) {
            error_log($e->getMessage());

            remember_form([], [
                'general' => 'Unable to change password. Please try again.',
            ]);

            redirect(app_url('change-password'));
        }

        redirect(app_url('profile'));
    }
}