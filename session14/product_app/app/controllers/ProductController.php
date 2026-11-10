<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductModel;
use finfo;
use RuntimeException;
use Throwable;

final class ProductController
{
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
    ];

    private const IMAGE_TYPES = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
    ];

    public function __construct(
        private ProductModel $products
    ) {
    }

    public function index(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));

        $categoryId = ($_GET['category_id'] ?? '') !== ''
            ? (int) $_GET['category_id']
            : null;

        if ($categoryId !== null && $categoryId <= 0) {
            $categoryId = null;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = $this->products->paginate(
            $search,
            $categoryId,
            $page
        );

        if ($page > $result['pages']) {
            redirect(app_url('products', [
                'q' => $search,
                'category_id' => $categoryId,
                'page' => $result['pages'],
            ]));
        }

        render('products/index', [
            'pageTitle' => 'Products',
            'products' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'search' => $search,
            'selectedCategory' => $categoryId,
            'categories' => $this->products->categories(),
        ]);
    }

    public function create(): void
    {
        $state = consume_form_state();

        render('products/create', [
            'pageTitle' => 'Add product',
            'formData' => $state['data'],
            'errors' => $state['errors'],
            'categories' => $this->products->categories(),
        ]);
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = $this->products->find($id);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect(app_url('products'));
        }

        $state = consume_form_state();

        render('products/edit', [
            'pageTitle' => 'Edit product',
            'product' => $product,
            'formData' => $state['data'],
            'errors' => $state['errors'],
            'categories' => $this->products->categories(),
        ]);
    }

    private function formData(): array
    {
        return [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => trim((string) ($_POST['price'] ?? '')),
            'category_id' => trim((string) ($_POST['category_id'] ?? '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'Product name is required.';
        } elseif (mb_strlen($data['name']) > 200) {
            $errors['name'] = 'Product name must not exceed 200 characters.';
        }

        if (mb_strlen($data['description']) > 10000) {
            $errors['description'] = 'Description is too long.';
        }

        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $data['price'])) {
            $errors['price'] = 'Enter a valid non-negative price with up to 2 decimal places.';
        }

        if ($data['category_id'] !== '') {
            $validIds = array_map(
                'intval',
                array_column($this->products->categories(), 'id')
            );

            if (
                !ctype_digit($data['category_id']) ||
                !in_array((int) $data['category_id'], $validIds, true)
            ) {
                $errors['category_id'] = 'Select a valid category.';
            }
        }

        return $errors;
    }

    private function uploadImage(): ?string
    {
        $file = $_FILES['image'] ?? null;

        if (
            !is_array($file) ||
            ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed.');
        }

        $temporaryPath = $file['tmp_name'] ?? '';

        if (
            !is_string($temporaryPath) ||
            $temporaryPath === '' ||
            !is_uploaded_file($temporaryPath)
        ) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        $actualSize = filesize($temporaryPath);

        if (
            $actualSize === false ||
            $actualSize <= 0 ||
            $actualSize > self::MAX_IMAGE_SIZE
        ) {
            throw new RuntimeException('Image must not exceed 2 MB.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException(
                'Only JPEG, PNG, and GIF images are allowed.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $temporaryPath);

        if (
            !is_string($mime) ||
            !isset(self::IMAGE_TYPES[$mime]) ||
            !in_array($extension, self::IMAGE_TYPES[$mime], true)
        ) {
            throw new RuntimeException(
                'Only valid JPEG, PNG, and GIF images are allowed.'
            );
        }

        $imageInfo = @getimagesize($temporaryPath);

        if (
            $imageInfo === false ||
            !isset($imageInfo['mime']) ||
            $imageInfo['mime'] !== $mime
        ) {
            throw new RuntimeException(
                'The uploaded file is not a valid image.'
            );
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads';

        if (
            !is_dir($uploadDirectory) ||
            !is_writable($uploadDirectory)
        ) {
            throw new RuntimeException(
                'The image upload directory is not writable.'
            );
        }

        $trustedExtension = $mime === 'image/jpeg'
            ? 'jpg'
            : self::IMAGE_TYPES[$mime][0];

        $filename = bin2hex(random_bytes(16))
            . '.' . $trustedExtension;

        $destination = $uploadDirectory . '/' . $filename;

        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('Unable to save uploaded image.');
        }

        return $filename;
    }

    private function deleteImage(?string $filename): void
    {
        if (!$filename || basename($filename) !== $filename) {
            return;
        }

        $path = dirname(__DIR__, 2)
            . '/public/uploads/'
            . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function formRedirect(int $id): never
    {
        if ($id > 0) {
            redirect(app_url('products/edit', ['id' => $id]));
        }

        redirect(app_url('products/create'));
    }

    public function save(): void
    {
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);

        $existing = $id > 0
            ? $this->products->find($id)
            : null;

        if ($id > 0 && !$existing) {
            flash('error', 'Product not found.');
            redirect(app_url('products'));
        }

        $data = $this->formData();
        $errors = $this->validate($data);

        $newImage = null;

        if (!$errors) {
            try {
                $newImage = $this->uploadImage();
            } catch (RuntimeException $e) {
                $errors['image'] = $e->getMessage();
            }
        }

        if ($errors) {
            remember_form($data, $errors);
            $this->formRedirect($id);
        }

        $image = $newImage ?? ($existing['image'] ?? null);

        $productData = [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'price' => $data['price'],
            'category_id' => $data['category_id'] !== ''
                ? (int) $data['category_id']
                : null,
            'image' => $image,
        ];

        try {
            if ($id > 0) {
                $this->products->update($id, $productData);

                if ($newImage !== null) {
                    $this->deleteImage($existing['image']);
                }

                flash('success', 'Product updated successfully.');
            } else {
                $this->products->create($productData);

                flash('success', 'Product created successfully.');
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());

            $this->deleteImage($newImage);

            remember_form($data, [
                'general' => 'Unable to save product. Please try again.',
            ]);

            $this->formRedirect($id);
        }

        redirect(app_url('products'));
    }

    public function delete(): void
    {
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        $product = $this->products->find($id);

        if (!$product) {
            flash('error', 'Product not found.');
            redirect(app_url('products'));
        }

        try {
            $this->products->delete($id);
            $this->deleteImage($product['image']);

            flash('success', 'Product deleted successfully.');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Unable to delete product.');
        }

        redirect(app_url('products'));
    }
}