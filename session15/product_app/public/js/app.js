
(function ($) {
    'use strict';

    $(function () {
        const $page = $('#products-page');

        if (!$page.length) {
            return;
        }

        const productsUrl = $page.data('products-url');
        const categoriesUrl = $page.data('categories-url');
        const uploadBase = String($page.data('upload-base') || '').replace(/\/$/, '');
        const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';

        const $tableBody = $('#products-table-body');
        const $search = $('#q');
        const $categoryFilter = $('#category_id');
        const $productCount = $('#product-count');
        const $emptyState = $('#products-empty-state');
        const $pagination = $('#products-pagination');
        const $modal = $('#product-modal');
        const $form = $('#product-form');
        const $saveButton = $('#save-product-button');
        const $modalTitle = $('#product-modal-title');

        let searchTimer = null;
        let activeRequest = null;
        let requestSequence = 0;
        let currentPage = 1;
        let totalPages = 1;
        let editingProductId = null;

        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        });

        // Keep the spinner visible until all concurrent AJAX requests finish.
        $(document).ajaxStart(showLoading);
        $(document).ajaxStop(hideLoading);

        function showLoading() {
            $('#ajax-loading')
                .addClass('is-visible')
                .attr('aria-hidden', 'false');
        }

        function hideLoading() {
            $('#ajax-loading')
                .removeClass('is-visible')
                .attr('aria-hidden', 'true');
        }

        function notify(message, type) {
            const notificationType = type === 'success' ? 'success' : 'error';

            const $notification = $('<div>', {
                class: 'ajax-notification alert-' + notificationType,
                role: 'status',
                text: message
            });

            $('#ajax-notifications').append($notification);

            window.setTimeout(function () {
                $notification.fadeOut(250, function () {
                    $(this).remove();
                });
            }, 3500);
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
                const entities = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };

                return entities[character];
            });
        }

        function formatPrice(value) {
            const number = Number(value);

            return Number.isFinite(number) ? number.toFixed(2) : '0.00';
        }

        function formatDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return date.toISOString().slice(0, 10);
        }

        function getProductUrl(productId) {
            return productsUrl + '/' + encodeURIComponent(productId);
        }

        // Render one product row.
        function renderProduct(product) {
            const id = escapeHtml(product.id);
            const name = escapeHtml(product.name);
            const description = escapeHtml(product.description || '');
            const category = escapeHtml(product.category_name || 'Uncategorized');
            const price = escapeHtml(formatPrice(product.price));
            const createdAt = escapeHtml(formatDate(product.created_at));

            let imageHtml = '<div class="image-placeholder">No image</div>';

            if (product.image) {
                const imageUrl = uploadBase + '/' + encodeURIComponent(product.image);

                imageHtml = `
                    <img
                        class="product-thumbnail"
                        src="${escapeHtml(imageUrl)}"
                        alt="${name}"
                    >
                `;
            }

            const descriptionHtml = description
                ? `<p class="table-description">${
                    description.length > 90
                        ? description.slice(0, 90) + '...'
                        : description
                }</p>`
                : '';

            return `
                <tr data-product-id="${id}">
                    <td>${imageHtml}</td>
                    <td>
                        <strong>${name}</strong>
                        ${descriptionHtml}
                    </td>
                    <td>${category}</td>
                    <td class="price">$${price}</td>
                    <td>${createdAt}</td>
                    <td>
                        <div class="action-group">
                            <button
                                type="button"
                                class="btn btn-small btn-secondary ajax-edit-product"
                                data-product-id="${id}"
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                class="btn btn-small btn-danger ajax-delete-product"
                                data-product-id="${id}"
                            >
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }

        // Render AJAX pagination.
        function renderPagination(pagination) {
            $pagination.empty();

            currentPage = Math.max(1, Number(pagination.page) || 1);
            totalPages = Math.max(1, Number(pagination.pages) || 1);

            if (totalPages <= 1) {
                return;
            }

            const $container = $('<div>', {
                class: 'pagination'
            });

            function addButton(label, page, disabled, active) {
                const $button = $('<button>', {
                    type: 'button',
                    class: 'pagination-button',
                    text: label,
                    disabled: disabled
                });

                if (active) {
                    $button.addClass('active');
                    $button.attr('aria-current', 'page');
                }

                $button.on('click', function () {
                    if (
                        disabled ||
                        page === currentPage ||
                        page < 1 ||
                        page > totalPages
                    ) {
                        return;
                    }

                    currentPage = page;
                    loadProducts();
                });

                $container.append($button);
            }

            addButton(
                'Previous',
                currentPage - 1,
                currentPage <= 1,
                false
            );

            for (let page = 1; page <= totalPages; page++) {
                addButton(
                    String(page),
                    page,
                    false,
                    page === currentPage
                );
            }

            addButton(
                'Next',
                currentPage + 1,
                currentPage >= totalPages,
                false
            );

            $pagination.append($container);
        }

        // Render the current product page.
        function renderProducts(products, pagination) {
            $tableBody.empty();

            const productCount = Number(pagination.total) || 0;

            $productCount.text(productCount);

            currentPage = Math.max(1, Number(pagination.page) || 1);
            totalPages = Math.max(1, Number(pagination.pages) || 1);

            if (!Array.isArray(products) || products.length === 0) {
                $emptyState.prop('hidden', false);
                $pagination.empty();
                return;
            }

            $emptyState.prop('hidden', true);

            $tableBody.html(products.map(renderProduct).join(''));

            renderPagination(pagination);

            $tableBody.children('tr').hide().each(function (index) {
                $(this).delay(index * 25).fadeIn(180);
            });
        }

        // Load products with search, category and pagination parameters.
        function loadProducts() {
            const currentSequence = ++requestSequence;

            if (activeRequest && activeRequest.readyState !== 4) {
                activeRequest.abort();
            }

            activeRequest = $.ajax({
                url: productsUrl,
                method: 'GET',
                dataType: 'json',
                data: {
                    search: $search.val().trim(),
                    category_id: $categoryFilter.val(),
                    page: currentPage
                },
                success: function (response) {
                    if (currentSequence !== requestSequence) {
                        return;
                    }

                    if (
                        !response ||
                        response.success !== true ||
                        !response.data ||
                        !Array.isArray(response.data.products) ||
                        !response.data.pagination
                    ) {
                        notify(
                            response && response.message
                                ? response.message
                                : 'Unexpected server response.',
                            'error'
                        );

                        return;
                    }

                    const pagination = response.data.pagination;

                    // If the current page no longer exists, load the last valid page.
                    if (
                        response.data.products.length === 0 &&
                        Number(pagination.total) > 0 &&
                        currentPage > 1
                    ) {
                        currentPage = Math.max(
                            1,
                            Number(pagination.pages) || currentPage - 1
                        );

                        loadProducts();
                        return;
                    }

                    renderProducts(
                        response.data.products,
                        pagination
                    );
                },
                error: function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }

                    let message = 'Something went wrong. Please try again.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        message = 'Network error. Check your connection and try again.';
                    }

                    notify(message, 'error');
                }
            });
        }

        // Load categories for the filter and product form.
        function loadCategories() {
            $.ajax({
                url: categoriesUrl,
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (
                        !response ||
                        response.success !== true ||
                        !response.data ||
                        !Array.isArray(response.data.categories)
                    ) {
                        return;
                    }

                    const selectedValue = $categoryFilter.val();
                    const $modalCategory = $('#modal-product-category');

                    $modalCategory.empty().append(
                        $('<option>', {
                            value: '',
                            text: 'Uncategorized'
                        })
                    );

                    response.data.categories.forEach(function (category) {
                        $modalCategory.append(
                            $('<option>', {
                                value: category.id,
                                text: category.name
                            })
                        );

                        if (
                            !$categoryFilter.find(
                                'option[value="' + category.id + '"]'
                            ).length
                        ) {
                            $categoryFilter.append(
                                $('<option>', {
                                    value: category.id,
                                    text: category.name
                                })
                            );
                        }
                    });

                    $categoryFilter.val(selectedValue);
                },
                error: function () {
                    notify('Unable to load categories.', 'error');
                }
            });
        }

        // Clear validation messages.
        function clearFormErrors() {
            $form.find('.error-text').text('');
            $form.find('.is-invalid').removeClass('is-invalid');

            $('#product-form-general-error')
                .prop('hidden', true)
                .text('');
        }

        // Display validation messages returned by the API.
        function displayFormErrors(errors) {
            clearFormErrors();

            if (!errors || typeof errors !== 'object') {
                return;
            }

            Object.keys(errors).forEach(function (field) {
                const message = errors[field];

                if (field === 'general') {
                    $('#product-form-general-error')
                        .text(message)
                        .prop('hidden', false);

                    return;
                }

                const $input = $form.find('[name="' + field + '"]');

                if ($input.length) {
                    $input.addClass('is-invalid');
                }

                $form.find('[data-error-for="' + field + '"]').text(message);
            });
        }

        // Reset the modal to Add Product mode.
        function resetModalMode() {
            editingProductId = null;

            $modalTitle.text('Add product');
            $saveButton.text('Save product');
        }

        function openModal() {
            clearFormErrors();

            $modal
                .addClass('is-open')
                .attr('aria-hidden', 'false');

            $('body').addClass('modal-open');

            $('#modal-product-name').trigger('focus');
        }

        // Open the Add Product modal.
        function openAddModal() {
            $form[0].reset();
            resetModalMode();
            clearFormErrors();
            openModal();
        }

        // Load an existing product and open the Edit modal.
        function openEditModal(productId) {
            if (!productId) {
                return;
            }

            $form[0].reset();
            clearFormErrors();

            editingProductId = productId;

            $modalTitle.text('Loading product...');
            $saveButton
                .text('Loading...')
                .prop('disabled', true);

            $.ajax({
                url: getProductUrl(productId),
                method: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (
                        !response ||
                        response.success !== true ||
                        !response.data ||
                        !response.data.product
                    ) {
                        resetModalMode();

                        notify(
                            response && response.message
                                ? response.message
                                : 'Unable to load product.',
                            'error'
                        );

                        return;
                    }

                    const product = response.data.product;

                    $('#modal-product-name').val(product.name || '');
                    $('#modal-product-description').val(
                        product.description || ''
                    );
                    $('#modal-product-price').val(product.price || '');

                    $('#modal-product-category').val(
                        product.category_id == null
                            ? ''
                            : String(product.category_id)
                    );

                    $modalTitle.text('Edit product');
                    $saveButton
                        .text('Update product')
                        .prop('disabled', false);

                    openModal();
                },
                error: function (xhr) {
                    resetModalMode();

                    notify(
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Unable to load product.',
                        'error'
                    );
                },
                complete: function () {
                    $saveButton.prop('disabled', false);
                }
            });
        }

        function closeModal() {
            $modal
                .removeClass('is-open')
                .attr('aria-hidden', 'true');

            $('body').removeClass('modal-open');

            $form[0].reset();
            clearFormErrors();
            resetModalMode();
        }

        // Add Product button.
        $('#open-product-modal').on('click', openAddModal);

        // Edit Product buttons.
        $(document).on('click', '.ajax-edit-product', function () {
            const productId = $(this).data('product-id');

            openEditModal(productId);
        });

        // Close modal.
        $('#close-product-modal, #cancel-product-modal').on(
            'click',
            closeModal
        );

        $modal.on('click', function (event) {
            if (event.target === this) {
                closeModal();
            }
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape' && $modal.hasClass('is-open')) {
                closeModal();
            }
        });

        // Live search with a 300 ms debounce.
        $search.on('input', function () {
            window.clearTimeout(searchTimer);

            searchTimer = window.setTimeout(function () {
                currentPage = 1;
                loadProducts();
            }, 300);
        });

        // Category filter.
        $categoryFilter.on('change', function () {
            currentPage = 1;
            loadProducts();
        });

        // Filter form.
        $('#product-filter-form').on('submit', function (event) {
            event.preventDefault();

            window.clearTimeout(searchTimer);
            currentPage = 1;
            loadProducts();
        });

        // Reset filters.
        $('#reset-product-filter').on('click', function (event) {
            event.preventDefault();

            $search.val('');
            $categoryFilter.val('');

            window.clearTimeout(searchTimer);
            currentPage = 1;
            loadProducts();
        });

        // Clear individual validation errors when editing a field.
        $form.on('input change', 'input, select, textarea', function () {
            const field = $(this).attr('name');

            if (!field) {
                return;
            }

            $(this).removeClass('is-invalid');

            $form.find('[data-error-for="' + field + '"]').text('');
        });

        // Create or update product using multipart FormData.
        $form.on('submit', function (event) {
            event.preventDefault();

            clearFormErrors();

            const isEditing = editingProductId !== null;
            const requestUrl = isEditing
                ? getProductUrl(editingProductId)
                : productsUrl;

            const formData = new FormData(this);

            $saveButton
                .prop('disabled', true)
                .text(isEditing ? 'Updating...' : 'Saving...');

            $.ajax({
                url: requestUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',

                success: function (response) {
                    if (
                        !response ||
                        response.success !== true ||
                        !response.data ||
                        !response.data.product
                    ) {
                        displayFormErrors(
                            response ? response.errors : {}
                        );

                        notify(
                            response && response.message
                                ? response.message
                                : 'Unable to save product.',
                            'error'
                        );

                        return;
                    }

                    const wasEditing = isEditing;

                    closeModal();

                    notify(
                        response.message || (
                            wasEditing
                                ? 'Product updated successfully.'
                                : 'Product created successfully.'
                        ),
                        'success'
                    );

                    // A newly created product is normally at the beginning.
                    if (!wasEditing) {
                        currentPage = 1;
                    }

                    loadProducts();
                },

                error: function (xhr) {
                    const response = xhr.responseJSON;

                    if (response && response.errors) {
                        displayFormErrors(response.errors);
                    }

                    notify(
                        response && response.message
                            ? response.message
                            : 'Something went wrong. Please try again.',
                        'error'
                    );
                },

                complete: function () {
                    $saveButton.prop('disabled', false);

                    if (editingProductId !== null) {
                        $saveButton.text('Update product');
                    } else {
                        $saveButton.text('Save product');
                    }
                }
            });
        });

        // Delete product using AJAX.
        function deleteProduct(productId, $row) {
            $.ajax({
                url: getProductUrl(productId),
                method: 'DELETE',
                dataType: 'json',

                success: function (response) {
                    if (!response || response.success !== true) {
                        notify(
                            response && response.message
                                ? response.message
                                : 'Unable to delete product.',
                            'error'
                        );

                        return;
                    }

                    $row.fadeOut(250, function () {
                        $(this).remove();

                        const remainingTotal = Math.max(
                            0,
                            Number($productCount.text()) - 1
                        );

                        $productCount.text(remainingTotal);

                        notify(
                            response.message || 'Product deleted successfully.',
                            'success'
                        );

                        // Reload the page to keep pagination and counts accurate.
                        loadProducts();
                    });
                },

                error: function (xhr) {
                    notify(
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Something went wrong. Please try again.',
                        'error'
                    );
                }
            });
        }

        // Standard delete forms remain usable without JavaScript.
        $(document).on('submit', '.delete-product-form', function (event) {
            event.preventDefault();

            const $formElement = $(this);
            const productId = $formElement.find('[name="id"]').val();

            if (!window.confirm('Are you sure you want to delete this product?')) {
                return;
            }

            deleteProduct(
                productId,
                $formElement.closest('tr')
            );
        });

        // AJAX-rendered delete buttons.
        $(document).on('click', '.ajax-delete-product', function () {
            const productId = $(this).data('product-id');
            const $row = $(this).closest('tr');

            if (!window.confirm('Are you sure you want to delete this product?')) {
                return;
            }

            deleteProduct(productId, $row);
        });

        // Initial page load.
        loadCategories();
        loadProducts();
    });
})(jQuery);