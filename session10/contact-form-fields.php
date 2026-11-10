<?php
/**
 * Shared contact fields for add.php and edit.php.
 * Expects $values to contain name, email, phone, group_name, and notes.
 */
?>
<div class="form-grid">
    <div class="form-field">
        <label for="name">Name <span class="required">*</span></label>
        <input id="name" name="name" type="text" maxlength="100" required autocomplete="name"
               value="<?= e($values['name'] ?? '') ?>" placeholder="e.g. Alex Morgan">
    </div>
    <div class="form-field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150" autocomplete="email"
               value="<?= e($values['email'] ?? '') ?>" placeholder="name@example.com">
        <small>Optional, but must be valid and unique when provided.</small>
    </div>
    <div class="form-field">
        <label for="phone">Phone</label>
        <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel"
               value="<?= e($values['phone'] ?? '') ?>" placeholder="+84 …">
    </div>
    <div class="form-field">
        <label for="group_name">Group</label>
        <select id="group_name" name="group_name">
            <?php foreach (CONTACT_GROUPS as $group): ?>
                <option value="<?= e($group) ?>" <?= ($values['group_name'] ?? 'General') === $group ? 'selected' : '' ?>>
                    <?= e($group) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field form-field-full">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="5" placeholder="Add a detail you want to remember…"><?= e($values['notes'] ?? '') ?></textarea>
    </div>
</div>
