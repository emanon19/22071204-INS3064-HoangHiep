<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class ProductModel
{
    private const PER_PAGE = 10;

    public function __construct(
        private PDO $pdo
    ) {
    }

    public function categories(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name FROM categories ORDER BY name ASC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                p.*,
                c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id'
        );

        $stmt->execute(['id' => $id]);

        $product = $stmt->fetch();

        return $product ?: null;
    }

    public function allFiltered(
        string $search = '',
        ?int $categoryId = null
    ): array {
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = 'p.name ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId !== null) {
            $conditions[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $where = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $sql = "
            SELECT
                p.id,
                p.name,
                p.description,
                p.price,
                p.category_id,
                p.image,
                p.created_at,
                p.updated_at,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            $where
            ORDER BY p.created_at DESC, p.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function paginate(
        string $search = '',
        ?int $categoryId = null,
        int $page = 1
    ): array {
        $conditions = [];
        $params = [];

        if ($search !== '') {
            $conditions[] = 'p.name ILIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId !== null) {
            $conditions[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $where = $conditions
            ? 'WHERE ' . implode(' AND ', $conditions)
            : '';

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM products p $where"
        );

        $countStmt->execute($params);

        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * self::PER_PAGE;

        $sql = "
            SELECT
                p.id,
                p.name,
                p.description,
                p.price,
                p.category_id,
                p.image,
                p.created_at,
                p.updated_at,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            $where
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(
                ':' . $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        $stmt->bindValue(':limit', self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (
                name,
                description,
                price,
                category_id,
                image
             ) VALUES (
                :name,
                :description,
                :price,
                :category_id,
                :image
             )'
        );

        return $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'image' => $data['image'],
        ]);
    }

    public function createReturningId(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (
                name,
                description,
                price,
                category_id,
                image
             ) VALUES (
                :name,
                :description,
                :price,
                :category_id,
                :image
             )
             RETURNING id'
        );

        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'image' => $data['image'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products
             SET name = :name,
                 description = :description,
                 price = :price,
                 category_id = :category_id,
                 image = :image,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'category_id' => $data['category_id'],
            'image' => $data['image'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM products WHERE id = :id'
        );

        return $stmt->execute(['id' => $id]);
    }
}