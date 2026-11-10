<?php
declare(strict_types=1);

$formData = $formData ?? [];
$errors = $errors ?? [];
?>

<section class="form-container">
    <div class="page-heading">
        <h1>Create Account</h1>
        <p class="muted">Register to access the Product Management System.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form action="<?= e(app_url('register')) ?>" method="POST">
        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">
            <label for="username">Username</label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                value="<?= e($formData['username'] ?? '') ?>"
                autocomplete="username"
                required
            >

            <?php if (isset($errors['username'])): ?>
                <small class="field-error">
                    <?= e($errors['username']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                value="<?= e($formData['email'] ?? '') ?>"
                autocomplete="email"
                required
            >

            <?php if (isset($errors['email'])): ?>
                <small class="field-error">
                    <?= e($errors['email']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <?php if (isset($errors['password'])): ?>
                <small class="field-error">
                    <?= e($errors['password']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <?php if (isset($errors['confirm_password'])): ?>
                <small class="field-error">
                    <?= e($errors['confirm_password']) ?>
                </small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">
            Register
        </button>
    </form>

    <p class="form-footer">
        Already have an account?
        <a href="<?= e(app_url('login')) ?>">Login here</a>
    </p>
</section>