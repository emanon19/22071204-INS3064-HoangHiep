<?php
declare(strict_types=1);

$uploadBase = rtrim(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')),
    '/'
) . '/uploads';

$productsApiUrl = app_url('api/products');
$categoriesApiUrl = app_url('api/categories');
?>

<section
    class="page-heading"
    id="products-page"
    data-products-url="<?= e($productsApiUrl) ?>"
    data-categories-url="<?= e($categoriesApiUrl) ?>"
    data-upload-base="<?= e($uploadBase) ?>"
>
    <div>
        <h1>Products</h1>
        <p class="muted">
            Manage your product inventory.
            <span id="product-count"><?= e($total) ?></span> product(s) found.
        </p>
    </div>

    <div class="page-heading-actions">
        <button
            type="button"
            class="btn btn-primary"
            id="open-product-modal"
        >
            Add product
        </button>

        <a
            class="btn btn-secondary"
            href="<?= e(app_url('products/create')) ?>"
        >
            Open standard form
        </a>
    </div>
</section>

<div class="card filter-card">
    <form
        method="GET"
        action="<?= e(app_url()) ?>"
        class="filter-form"
        id="product-filter-form"
    >
        <input type="hidden" name="route" value="products">

        <div class="form-group filter-search">
            <label for="q">Search products</label>
            <input
                type="search"
                id="q"
                name="q"
                placeholder="Enter product name..."
                value="<?= e($search) ?>"
                autocomplete="off"
            >
        </div>

        <div class="form-group filter-category">
            <label for="category_id">Category</label>

            <select id="category_id" name="category_id">
                <option value="">All categories</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= e($category['id']) ?>"
                        <?= (string) $selectedCategory === (string) $category['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-primary">
                Search
            </button>

            <a
                class="btn btn-secondary"
                href="<?= e(app_url('products')) ?>"
                id="reset-product-filter"
            >
                Reset
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Created</th>
                    <th class="actions-column">Actions</th>
                </tr>
            </thead>

            <tbody id="products-table-body">
                <?php foreach ($products as $product): ?>
                    <?php require __DIR__ . '/_product_row.php'; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="products-empty-state" class="empty-state" <?= empty($products) ? '' : 'hidden' ?>>
        <h2>No products found</h2>
        <p>Try changing your search or category filter.</p>
    </div>

    <div id="products-pagination" class="pagination">
        <?php if ($pages > 1): ?>
            <?php if ($page > 1): ?>
                <a href="<?= e(app_url('products', [
                    'q' => $search,
                    'category_id' => $selectedCategory,
                    'page' => $page - 1,
                ])) ?>">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a
                    class="<?= $i === $page ? 'active' : '' ?>"
                    href="<?= e(app_url('products', [
                        'q' => $search,
                        'category_id' => $selectedCategory,
                        'page' => $i,
                    ])) ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <a href="<?= e(app_url('products', [
                    'q' => $search,
                    'category_id' => $selectedCategory,
                    'page' => $page + 1,
                ])) ?>">Next</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div
    class="modal-backdrop"
    id="product-modal"
    aria-hidden="true"
>
    <section
        class="product-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="product-modal-title"
    >
        <div class="modal-header">
            <div>
                <h2 id="product-modal-title">Add product</h2>
                <p class="muted">Enter the product details below.</p>
            </div>

            <button
                type="button"
                class="modal-close"
                id="close-product-modal"
                aria-label="Close"
            >
                &times;
            </button>
        </div>

        <form
            id="product-form"
            method="POST"
            action="<?= e(app_url('products/store')) ?>"
            enctype="multipart/form-data"
        >
            <input
                type="hidden"
                name="_csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div id="product-form-general-error" class="form-error-summary" hidden></div>

            <div class="form-group">
                <label for="modal-product-name">
                    Product name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="modal-product-name"
                    name="name"
                    maxlength="200"
                    required
                >

                <span class="error-text" data-error-for="name"></span>
            </div>

            <div class="form-group">
                <label for="modal-product-description">Description</label>

                <textarea
                    id="modal-product-description"
                    name="description"
                    maxlength="10000"
                ></textarea>

                <span class="error-text" data-error-for="description"></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="modal-product-price">
                        Price <span class="required">*</span>
                    </label>

                    <input
                        type="number"
                        id="modal-product-price"
                        name="price"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        required
                    >

                    <span class="error-text" data-error-for="price"></span>
                </div>

                <div class="form-group">
                    <label for="modal-product-category">Category</label>

                    <select
                        id="modal-product-category"
                        name="category_id"
                    >
                        <option value="">Uncategorized</option>

                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category['id']) ?>">
                                <?= e($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <span class="error-text" data-error-for="category_id"></span>
                </div>
            </div>

            <div class="form-group">
                <label for="modal-product-image">Product image</label>

                <input
                    type="file"
                    id="modal-product-image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.gif,image/jpeg,image/png,image/gif"
                >

                <span class="help-text">JPEG, PNG, or GIF. Maximum size: 2 MB.</span>
                <span class="error-text" data-error-for="image"></span>
            </div>

            <div class="modal-actions">
                <button
                    type="button"
                    class="btn btn-secondary"
                    id="cancel-product-modal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="save-product-button"
                >
                    Save product
                </button>
            </div>
        </form>
    </section>
</div>