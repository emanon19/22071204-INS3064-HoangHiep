<section class="page-heading">
    <div>
        <h1>Add product</h1>
        <p class="muted">Enter the details of your new product.</p>
    </div>

    <a class="btn btn-secondary" href="<?= e(app_url('products')) ?>">
        Back
    </a>
</section>

<div class="card form-card">

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form
        method="POST"
        action="<?= e(app_url('products/store')) ?>"
        enctype="multipart/form-data"
    >
        <input
            type="hidden"
            name="_csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="form-group">
            <label for="name">Product name <span class="required">*</span></label>

            <input
                type="text"
                id="name"
                name="name"
                maxlength="200"
                required
                value="<?= e($formData['name'] ?? '') ?>"
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
            ><?= e($formData['description'] ?? '') ?></textarea>

            <?php if (isset($errors['description'])): ?>
                <small class="error-text"><?= e($errors['description']) ?></small>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Price <span class="required">*</span></label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    required
                    value="<?= e($formData['price'] ?? '') ?>"
                    class="<?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                >

                <?php if (isset($errors['price'])): ?>
                    <small class="error-text"><?= e($errors['price']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>

                <select
                    id="category_id"
                    name="category_id"
                    class="<?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                >
                    <option value="">Uncategorized</option>

                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= e($category['id']) ?>"
                            <?= (string) ($formData['category_id'] ?? '') === (string) $category['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (isset($errors['category_id'])): ?>
                    <small class="error-text"><?= e($errors['category_id']) ?></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="image">Product image</label>

            <input
                type="file"
                id="image"
                name="image"
                accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
            >

            <small class="help-text">
                JPEG, PNG, or GIF. Maximum file size: 2 MB.
            </small>

            <?php if (isset($errors['image'])): ?>
                <small class="error-text"><?= e($errors['image']) ?></small>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Create product
            </button>

            <a class="btn btn-secondary" href="<?= e(app_url('products')) ?>">
                Cancel
            </a>
        </div>
    </form>
</div>