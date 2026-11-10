<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductModel;
use finfo;
use PDOException;
use RuntimeException;
use Throwable;

final class ApiController
{
    private const MAX_IMAGE_SIZE = 2097152;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private ProductModel $products
    ) {
    }

    public function indexProducts(): void
    {
        try {
            $search = trim((string) ($_GET['search'] ?? ''));

            $categoryId = null;
            $rawCategoryId = $_GET['category_id'] ?? '';

            if ($rawCategoryId !== '') {
                if (
                    !is_string($rawCategoryId) ||
                    !ctype_digit($rawCategoryId) ||
                    (int) $rawCategoryId <= 0
                ) {
                    $this->jsonResponse(400, [
                        'success' => false,
                        'data' => null,
                        'errors' => [
                            'category_id' => 'Invalid category.',
                        ],
                        'message' => 'Invalid request.',
                    ]);

                    return;
                }

                $categoryId = (int) $rawCategoryId;
            }

            if (mb_strlen($search) > 200) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'data' => null,
                    'errors' => [
                        'search' => 'Search term is too long.',
                    ],
                    'message' => 'Invalid request.',
                ]);

                return;
            }

            $rawPage = $_GET['page'] ?? '1';

            if (
                !is_string($rawPage) ||
                !ctype_digit($rawPage) ||
                (int) $rawPage < 1
            ) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'data' => null,
                    'errors' => [
                        'page' => 'Invalid page number.',
                    ],
                    'message' => 'Invalid request.',
                ]);

                return;
            }

            $page = (int) $rawPage;

            $result = $this->products->paginate(
                $search,
                $categoryId,
                $page
            );

            $this->jsonResponse(200, [
                'success' => true,
                'data' => [
                    'products' => $result['items'],
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $page,
                        'pages' => $result['pages'],
                    ],
                ],
                'errors' => [],
                'message' => 'Products loaded successfully.',
            ]);
        } catch (Throwable $e) {
            error_log('API product list failed: ' . $e->getMessage());

            $this->serverError();
        }
    }

    public function indexCategories(): void
    {
        try {
            $this->jsonResponse(200, [
                'success' => true,
                'data' => [
                    'categories' => $this->products->categories(),
                ],
                'errors' => [],
                'message' => 'Categories loaded successfully.',
            ]);
        } catch (Throwable $e) {
            error_log('API category list failed: ' . $e->getMessage());

            $this->serverError();
        }
    }

    public function storeProduct(): void
    {
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => trim((string) ($_POST['price'] ?? '')),
            'category_id' => trim((string) ($_POST['category_id'] ?? '')),
        ];

        $errors = $this->validateProduct($data);

        if ($errors) {
            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => $errors,
                'message' => 'Validation failed.',
            ]);

            return;
        }

        $image = null;

        try {
            $image = $this->uploadImage();

            $productData = [
                'name' => $data['name'],
                'description' => $data['description'] !== ''
                    ? $data['description']
                    : null,
                'price' => $data['price'],
                'category_id' => $data['category_id'] !== ''
                    ? (int) $data['category_id']
                    : null,
                'image' => $image,
            ];

            $id = $this->products->createReturningId($productData);
            $product = $this->products->find($id);

            if (!$product) {
                throw new RuntimeException(
                    'Created product could not be retrieved.'
                );
            }

            $this->jsonResponse(201, [
                'success' => true,
                'data' => [
                    'product' => $product,
                ],
                'errors' => [],
                'message' => 'Product created successfully.',
            ]);
        } catch (RuntimeException $e) {
            $this->deleteImage($image);

            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => [
                    'image' => $e->getMessage(),
                ],
                'message' => 'Unable to upload image.',
            ]);
        } catch (PDOException $e) {
            $this->deleteImage($image);

            error_log('API product creation failed: ' . $e->getMessage());

            $this->serverError();
        } catch (Throwable $e) {
            $this->deleteImage($image);

            error_log('API product creation failed: ' . $e->getMessage());

            $this->serverError();
        }
    }

    public function deleteProduct(string $id): void
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => [
                    'id' => 'Invalid product ID.',
                ],
                'message' => 'Invalid request.',
            ]);

            return;
        }

        $productId = (int) $id;

        try {
            $product = $this->products->find($productId);

            if (!$product) {
                $this->jsonResponse(404, [
                    'success' => false,
                    'data' => null,
                    'errors' => [],
                    'message' => 'Product not found.',
                ]);

                return;
            }

            $this->products->delete($productId);
            $this->deleteImage($product['image'] ?? null);

            $this->jsonResponse(200, [
                'success' => true,
                'data' => [
                    'id' => $productId,
                ],
                'errors' => [],
                'message' => 'Product deleted successfully.',
            ]);
        } catch (Throwable $e) {
            error_log('API product deletion failed: ' . $e->getMessage());

            $this->serverError();
        }
    }

    private function validateProduct(array $data): array
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
            if (
                !ctype_digit($data['category_id']) ||
                (int) $data['category_id'] <= 0
            ) {
                $errors['category_id'] = 'Select a valid category.';
            } else {
                $validIds = array_map(
                    'intval',
                    array_column($this->products->categories(), 'id')
                );

                if (!in_array((int) $data['category_id'], $validIds, true)) {
                    $errors['category_id'] = 'Select a valid category.';
                }
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

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $temporaryPath);

        if (!is_string($mime) || !isset(self::ALLOWED_IMAGE_TYPES[$mime])) {
            throw new RuntimeException(
                'Only valid JPEG, PNG, and GIF images are allowed.'
            );
        }

        $imageInfo = @getimagesize($temporaryPath);

        if (
            $imageInfo === false ||
            ($imageInfo['mime'] ?? '') !== $mime
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

        $filename = bin2hex(random_bytes(16))
            . '.'
            . self::ALLOWED_IMAGE_TYPES[$mime];

        if (!move_uploaded_file(
            $temporaryPath,
            $uploadDirectory . '/' . $filename
        )) {
            throw new RuntimeException('Unable to save uploaded image.');
        }

        return $filename;
    }

    private function deleteImage(?string $filename): void
    {
        if (
            !$filename ||
            !is_string($filename) ||
            basename($filename) !== $filename
        ) {
            return;
        }

        $path = dirname(__DIR__, 2)
            . '/public/uploads/'
            . $filename;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function serverError(): void
    {
        $this->jsonResponse(500, [
            'success' => false,
            'data' => null,
            'errors' => [],
            'message' => 'Something went wrong. Please try again.',
        ]);
    }

    private function jsonResponse(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function showProduct(string $id): void
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => ['id' => 'Invalid product ID.'],
                'message' => 'Invalid request.',
            ]);

            return;
        }

        try {
            $product = $this->products->find((int) $id);

            if (!$product) {
                $this->jsonResponse(404, [
                    'success' => false,
                    'data' => null,
                    'errors' => [],
                    'message' => 'Product not found.',
                ]);

                return;
            }

            $this->jsonResponse(200, [
                'success' => true,
                'data' => ['product' => $product],
                'errors' => [],
                'message' => 'Product loaded successfully.',
            ]);
        } catch (Throwable $e) {
            error_log('API product retrieval failed: ' . $e->getMessage());
            $this->serverError();
        }
    }

    public function updateProduct(string $id): void
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => ['id' => 'Invalid product ID.'],
                'message' => 'Invalid request.',
            ]);

            return;
        }

        $productId = (int) $id;

        try {
            $existing = $this->products->find($productId);

            if (!$existing) {
                $this->jsonResponse(404, [
                    'success' => false,
                    'data' => null,
                    'errors' => [],
                    'message' => 'Product not found.',
                ]);

                return;
            }

            $data = [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'price' => trim((string) ($_POST['price'] ?? '')),
                'category_id' => trim((string) ($_POST['category_id'] ?? '')),
            ];

            $errors = $this->validateProduct($data);

            if ($errors) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'data' => null,
                    'errors' => $errors,
                    'message' => 'Validation failed.',
                ]);

                return;
            }

            $newImage = $this->uploadImage();

            $productData = [
                'name' => $data['name'],
                'description' => $data['description'] !== ''
                    ? $data['description']
                    : null,
                'price' => $data['price'],
                'category_id' => $data['category_id'] !== ''
                    ? (int) $data['category_id']
                    : null,
                'image' => $newImage ?? ($existing['image'] ?? null),
            ];

            $this->products->update($productId, $productData);

            if ($newImage !== null) {
                $this->deleteImage($existing['image'] ?? null);
            }

            $updatedProduct = $this->products->find($productId);

            $this->jsonResponse(200, [
                'success' => true,
                'data' => ['product' => $updatedProduct],
                'errors' => [],
                'message' => 'Product updated successfully.',
            ]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(400, [
                'success' => false,
                'data' => null,
                'errors' => ['image' => $e->getMessage()],
                'message' => 'Unable to upload image.',
            ]);
        } catch (Throwable $e) {
            error_log('API product update failed: ' . $e->getMessage());
            $this->serverError();
        }
    }
}