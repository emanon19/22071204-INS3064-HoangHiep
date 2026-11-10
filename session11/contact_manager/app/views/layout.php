<?php

declare(strict_types=1);

$flash = $_SESSION['flash'] ?? null;

unset($_SESSION['flash']);

$allowedFlashTypes = ['success', 'error', 'warning'];

if (
    !is_array($flash)
    || !in_array($flash['type'] ?? '', $allowedFlashTypes, true)
) {
    $flash = null;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= e($pageTitle ?? 'Contact Manager') ?> | Contact Manager
    </title>

    <link rel="stylesheet" href="<?= e(APP_BASE_URL) ?>/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(app_url()) ?>">
            <span class="brand-mark">C</span>
            Contact Manager
        </a>

        <nav aria-label="Main navigation">
            <a href="<?= e(app_url()) ?>">All contacts</a>

            <a class="nav-add" href="<?= e(app_url('create')) ?>">
                + Add contact
            </a>
        </nav>
    </div>
</header>

<main class="container">

    <?php if ($flash !== null): ?>
        <div
            class="flash flash-<?= e($flash['type']) ?>"
            role="status"
        >
            <?= e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <?= $content ?>

</main>

<footer class="site-footer">
    Contact Manager <span>·</span> Keep your connections organized.
</footer>

</body>
</html>