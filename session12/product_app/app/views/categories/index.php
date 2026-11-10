<section class="page-heading">
    <div>
        <h1>Categories</h1>
        <p class="muted">Manage your product categories.</p>
    </div>

    <a class="btn btn-primary" href="<?= e(app_url('categories/create')) ?>">
        Add category
    </a>
</section>

<div class="card">
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <h2>No categories yet</h2>
            <p>Create a category to organize your products.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Created</th>
                        <th class="actions-column">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?= e($category['name']) ?></strong>
                            </td>

                            <td>
                                <?= e($category['description'] ?: '—') ?>
                            </td>

                            <td>
                                <?= e($category['product_count']) ?>
                            </td>

                            <td>
                                <?= e(date('Y-m-d', strtotime($category['created_at']))) ?>
                            </td>

                            <td>
                                <div class="action-group">
                                    <a
                                        class="btn btn-small btn-secondary"
                                        href="<?= e(app_url('categories/edit', [
                                            'id' => $category['id'],
                                        ])) ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="<?= e(app_url('categories/delete')) ?>"
                                        onsubmit="return confirm('Delete this category? Its products will remain uncategorized.');"
                                    >
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
    <?php endif; ?>
</div>