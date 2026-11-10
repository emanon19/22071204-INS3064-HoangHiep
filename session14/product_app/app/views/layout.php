<?php
declare(strict_types=1);

$successMessage = get_flash('success');
$errorMessage = get_flash('error');

$scriptDirectory = rtrim(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')),
    '/'
);

$cssPath = $scriptDirectory . '/css/style.css';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle ?? 'Product Management') ?> | Product Manager</title>

    <link rel="stylesheet" href="<?= e($cssPath) ?>">
</head>
<body>

<header class="site-header">
    <div class="container header-content">

        <a
            class="brand"
            href="<?= e(app_url($isLoggedIn ? 'products' : 'login')) ?>"
        >
            Product Manager
        </a>

        <nav class="main-nav">
            <?php if ($isLoggedIn): ?>

                <a href="<?= e(app_url('products')) ?>">
                    Products
                </a>

                <a href="<?= e(app_url('categories')) ?>">
                    Categories
                </a>

                <a href="<?= e(app_url('profile')) ?>">
                    My Profile
                </a>

                <span class="muted">
                    <?= e($username) ?>
                </span>

                <form
                    action="<?= e(app_url('logout')) ?>"
                    method="POST"
                    style="display:inline"
                >
                    <input
                        type="hidden"
                        name="_csrf_token"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <button type="submit" class="btn btn-secondary">
                        Logout
                    </button>
                </form>

            <?php else: ?>

                <a href="<?= e(app_url('login')) ?>">
                    Login
                </a>

                <a href="<?= e(app_url('register')) ?>">
                    Register
                </a>

            <?php endif; ?>
        </nav>

    </div>
</header>

<main class="container main-content">

    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?= $content ?>

</main>

<footer class="site-footer">
    <div class="container">
        <p>Product Management System</p>
    </div>
</footer>

</body>
</html>