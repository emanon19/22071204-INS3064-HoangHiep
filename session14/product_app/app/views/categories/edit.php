<?php
$name = $formData['name'] ?? $category['name'];
$description = $formData['description'] ?? $category['description'];
?>

<section class="page-heading">
    <div>
        <h1>Edit category</h1>
        <p class="muted">Update category information.</p>
    </div>

    <a class="btn btn-secondary" href="<?= e(app_url('categories')) ?>">
        Back
    </a>
</section>

<div class="card form-card">

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(app_url('categories/update')) ?>">

        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="id"
            value="<?= e($category['id']) ?>"
        >

        <div class="form-group">
            <label for="name">Category name <span class="required">*</span></label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="150"
                required
                value="<?= e($name) ?>"
                class="<?= isset($errors['name']) ? 'is-invalid' : '' ?>"
            >

            <?php if (isset($errors['name'])): ?>
                <small class="error-text"><?= e($errors['name']) ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="description">Description</label>

            <textarea
                id="description"
                name="description"
                rows="5"
            ><?= e($description) ?></textarea>

            <?php if (isset($errors['description'])): ?>
                <small class="error-text"><?= e($errors['description']) ?></small>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Save changes
            </button>

            <a class="btn btn-secondary" href="<?= e(app_url('categories')) ?>">
                Cancel
            </a>
        </div>

    </form>
</div>