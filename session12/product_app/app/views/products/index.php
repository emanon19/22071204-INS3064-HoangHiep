<?php
declare(strict_types=1);

$uploadBase = rtrim(
    str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')),
    '/'
) . '/uploads';
?>

<section class="page-heading">
    <div>
        <h1>Products</h1>
        <p class="muted">
            Manage your product inventory.
            <?= e($total) ?> product(s) found.
        </p>
    </div>

    <a class="btn btn-primary" href="<?= e(app_url('products/create')) ?>">
        Add product
    </a>
</section>

<div class="card filter-card">
    <form method="GET" action="<?= e(app_url()) ?>" class="filter-form">
        <input type="hidden" name="route" value="products">

        <div class="form-group filter-search">
            <label for="q">Search products</label>
            <input
                type="search"
                id="q"
                name="q"
                placeholder="Enter product name..."
                value="<?= e($search) ?>"
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

            <a class="btn btn-secondary" href="<?= e(app_url('products')) ?>">
                Reset
            </a>
        </div>
    </form>
</div>

<div class="card">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <h2>No products found</h2>
            <p>Try changing your search or category filter.</p>
        </div>
    <?php else: ?>
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

                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img
                                        class="product-thumbnail"
                                        src="<?= e($uploadBase . '/' . rawurlencode($product['image'])) ?>"
                                        alt="<?= e($product['name']) ?>"
                                    >
                                <?php else: ?>
                                    <div class="image-placeholder">No image</div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?= e($product['name']) ?></strong>

                                <?php if (!empty($product['description'])): ?>
                                    <p class="table-description">
                                        <?= e(mb_strimwidth(
                                            $product['description'],
                                            0,
                                            90,
                                            '...'
                                        )) ?>
                                    </p>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($product['category_name'] ?? 'Uncategorized') ?>
                            </td>

                            <td class="price">
                                $<?= e(number_format((float) $product['price'], 2)) ?>
                            </td>

                            <td>
                                <?= e(date('Y-m-d', strtotime($product['created_at']))) ?>
                            </td>

                            <td>
                                <div class="action-group">
                                    <a
                                        class="btn btn-small btn-secondary"
                                        href="<?= e(app_url('products/edit', [
                                            'id' => $product['id'],
                                        ])) ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="<?= e(app_url('products/delete')) ?>"
                                        onsubmit="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="_csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= e($product['id']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-small btn-danger"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Product pages">
                <?php if ($page > 1): ?>
                    <a
                        href="<?= e(app_url('products', [
                            'q' => $search,
                            'category_id' => $selectedCategory,
                            'page' => $page - 1,
                        ])) ?>"
                    >
                        Previous
                    </a>
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
                    <a
                        href="<?= e(app_url('products', [
                            'q' => $search,
                            'category_id' => $selectedCategory,
                            'page' => $page + 1,
                        ])) ?>"
                    >
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>