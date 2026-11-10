<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class ContactModel extends Model
{
    /**
     * Retrieve all contacts, optionally filtering by search keyword.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAll(string $search = ''): array
    {
        $sql = '
            SELECT
                id,
                name,
                email,
                phone,
                group_name,
                notes,
                created_at,
                updated_at
            FROM contacts
        ';

        $params = [];

        if ($search !== '') {
            $sql .= '
                WHERE
                    name ILIKE :name
                    OR email ILIKE :email
                    OR phone ILIKE :phone
                    OR group_name ILIKE :group_name
            ';

            $keyword = '%' . $search . '%';

            $params = [
                ':name' => $keyword,
                ':email' => $keyword,
                ':phone' => $keyword,
                ':group_name' => $keyword,
            ];
        }

        $sql .= ' ORDER BY created_at DESC, id DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    /**
     * Retrieve a contact by ID.
     *
     * @return array<string, mixed>|false
     */
    public function findById(int $id): array|false
    {
        $statement = $this->db->prepare(
            'SELECT * FROM contacts WHERE id = :id'
        );

        $statement->execute([':id' => $id]);

        return $statement->fetch();
    }

    /**
     * Check whether an email is already used by another contact.
     */
    public function emailExists(
        string $email,
        ?int $excludeId = null
    ): bool {
        $sql = 'SELECT id FROM contacts WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetch() !== false;
    }

    /**
     * Create a new contact.
     */
    public function create(array $data): bool
    {
        $sql = '
            INSERT INTO contacts (
                name,
                email,
                phone,
                group_name,
                notes
            )
            VALUES (
                :name,
                :email,
                :phone,
                :group_name,
                :notes
            )
        ';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            ':name' => $data['name'],
            ':email' => $data['email'] !== '' ? $data['email'] : null,
            ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
            ':group_name' => $data['group_name'],
            ':notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
    }

    /**
     * Update an existing contact.
     */
    public function update(int $id, array $data): bool
    {
        $sql = '
            UPDATE contacts
            SET
                name = :name,
                email = :email,
                phone = :phone,
                group_name = :group_name,
                notes = :notes
            WHERE id = :id
        ';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':email' => $data['email'] !== '' ? $data['email'] : null,
            ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
            ':group_name' => $data['group_name'],
            ':notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
    }

    /**
     * Delete a contact by ID.
     */
    public function delete(int $id): bool
    {
        $statement = $this->db->prepare(
            'DELETE FROM contacts WHERE id = :id'
        );

        $statement->execute([':id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Count all contacts.
     */
    public function count(): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM contacts'
        );

        $statement->execute();

        return (int) $statement->fetchColumn();
    }
}