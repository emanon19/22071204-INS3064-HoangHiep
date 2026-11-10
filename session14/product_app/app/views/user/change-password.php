<?php
declare(strict_types=1);

$errors = $errors ?? [];
?>

<section class="page-heading">
    <h1>Change Password</h1>
    <p class="muted">Update your account password.</p>
</section>

<section class="form-container">

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form
        action="<?= e(app_url('change-password')) ?>"
        method="POST"
    >
        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">
            <label for="current_password">Current Password</label>

            <input
                type="password"
                id="current_password"
                name="current_password"
                autocomplete="current-password"
                required
            >

            <?php if (isset($errors['current_password'])): ?>
                <small class="field-error">
                    <?= e($errors['current_password']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>

            <input
                type="password"
                id="new_password"
                name="new_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <?php if (isset($errors['new_password'])): ?>
                <small class="field-error">
                    <?= e($errors['new_password']) ?>
                </small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>

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
            Update Password
        </button>

        <a
            href="<?= e(app_url('profile')) ?>"
            class="btn btn-secondary"
        >
            Cancel
        </a>
    </form>
</section>