<?php

declare(strict_types=1);

?>

<div class="page-heading">
    <div>
        <p class="eyebrow">ADDRESS BOOK</p>
        <h1>Add Contact</h1>
        <p class="muted">Add someone new to your contact list.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="form-errors" role="alert">
        <strong>Please correct the following errors:</strong>

        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="panel form-panel">

    <form method="POST" action="<?= e(app_url('store')) ?>">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-grid">

            <div class="form-field">
                <label for="name">
                    Full name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="100"
                    value="<?= e($contact['name'] ?? '') ?>"
                    placeholder="Enter full name"
                    required
                    autofocus
                >
            </div>

            <div class="form-field">
                <label for="email">Email address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    value="<?= e($contact['email'] ?? '') ?>"
                    placeholder="example@email.com"
                >
            </div>

            <div class="form-field">
                <label for="phone">Phone number</label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    value="<?= e($contact['phone'] ?? '') ?>"
                    placeholder="+84 90 123 4567"
                >
            </div>

            <div class="form-field">
                <label for="group_name">
                    Contact group <span class="required">*</span>
                </label>

                <select id="group_name" name="group_name" required>
                    <?php foreach ($groups as $group): ?>
                        <option
                            value="<?= e($group) ?>"
                            <?= ($contact['group_name'] ?? 'General') === $group
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($group) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field form-field-full">
                <label for="notes">Notes</label>

                <textarea
                    id="notes"
                    name="notes"
                    placeholder="Add any additional information..."
                ><?= e($contact['notes'] ?? '') ?></textarea>

                <small>Optional information about this contact.</small>
            </div>

        </div>

        <div class="form-actions">

            <button class="button button-primary" type="submit">
                Save contact
            </button>

            <a
                class="button button-secondary"
                href="<?= e(app_url()) ?>"
            >
                Cancel
            </a>

        </div>

    </form>

</section>