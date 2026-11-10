<?php
declare(strict_types=1);

$formData = $formData ?? [];
$errors = $errors ?? [];
?>

<section class="page-heading">
    <h1>My Profile</h1>
    <p class="muted">View and manage your account information.</p>
</section>

<section class="form-container">
    <h2>Account Information</h2>

    <div class="form-group">
        <label>Username</label>
        <input
            type="text"
            value="<?= e($user['username']) ?>"
            readonly
        >
    </div>

    <div class="form-group">
        <label>Registration Date</label>
        <input
            type="text"
            value="<?= e(date(
                'd/m/Y H:i',
                strtotime($user['created_at'])
            )) ?>"
            readonly
        >
    </div>

    <hr>

    <h2>Update Email Address</h2>

    <form
        action="<?= e(app_url('profile/update-email')) ?>"
        method="POST"
    >
        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">
            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                value="<?= e($formData['email'] ?? $user['email']) ?>"
                required
            >

            <?php if (isset($errors['email'])): ?>
                <small class="field-error">
                    <?= e($errors['email']) ?>
                </small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary">
            Update Email
        </button>
    </form>

    <hr>

    <a
        href="<?= e(app_url('change-password')) ?>"
        class="btn btn-secondary"
    >
        Change Password
    </a>
</section>