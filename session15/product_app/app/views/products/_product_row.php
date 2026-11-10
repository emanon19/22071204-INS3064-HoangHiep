<?php
declare(strict_types=1);
?>

<tr data-product-id="<?= e($product['id']) ?>">
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
                <?= e(mb_strimwidth($product['description'], 0, 90, '...')) ?>
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
            <button
                type="button"
                class="btn btn-small btn-secondary ajax-edit-product"
                data-id="<?= e($product['id']) ?>"
            >
                Edit
            </button>

            <button
                type="button"
                class="btn btn-small btn-danger ajax-delete-product"
                data-id="<?= e($product['id']) ?>"
            >
                Delete
            </button>
        </div>
    </td>
</tr>