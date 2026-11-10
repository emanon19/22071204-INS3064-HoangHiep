<?php
declare(strict_types=1);

$formData = $formData ?? [];
$errors = $errors ?? [];
?>

<section class="form-container">
    <div class="page-heading">
        <h1>Login</h1>
        <p class="muted">Sign in to your Product Management account.</p>
    </div>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form action="<?= e(app_url('login')) ?>" method="POST">
        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">
            <label for="login">Username or Email</label>

            <input
                type="text"
                id="login"
                name="login"
                value="<?= e($formData['login'] ?? '') ?>"
                autocomplete="username"
                required
            >

            <?php if (isset($errors['login'])): ?>
                <small class="field-error">
                    <?= e($errors['login']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <?php if (isset($errors['password'])): ?>
                <small class="field-error">
                    <?= e($errors['password']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input
                    type="checkbox"
                    name="remember_me"
                    value="1"
                    style="width:15px; height:15px; margin:0;"
                >
                <span>Remember me</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            Login
        </button>
    </form>

    <p class="form-footer">
        Don't have an account?
        <a href="<?= e(app_url('register')) ?>">Register here</a>
    </p>
</section>