<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class CategoryModel
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function all(): array
    {
        $sql = '
            SELECT
                c.id,
                c.name,
                c.description,
                c.created_at,
                COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id
            ORDER BY c.name ASC
        ';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM categories WHERE id = :id'
        );

        $stmt->execute(['id' => $id]);

        $category = $stmt->fetch();

        return $category ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (name, description)
             VALUES (:name, :description)'
        );

        return $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE categories
             SET name = :name,
                 description = :description
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'],
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM categories WHERE id = :id'
        );

        return $stmt->execute(['id' => $id]);
    }
}