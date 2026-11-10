<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password)
             VALUES (:username, :email, :password)
             RETURNING id, username, email, created_at'
        );

        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $stmt->fetch();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password, remember_token, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute(['id' => $id]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE username = :username
                OR email = :email
             LIMIT 1'
        );

        $stmt->execute([
            'username' => $login,
            'email' => $login,
        ]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET email = :email
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'email' => $email,
        ]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password = :password
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'password' => $password,
        ]);
    }

    public function updateRememberToken(
        int $id,
        ?string $tokenHash
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET remember_token = :token
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'token' => $tokenHash,
        ]);
    }

    public function findByRememberToken(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE remember_token = :token
             LIMIT 1'
        );

        $stmt->execute(['token' => $tokenHash]);

        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function clearRememberTokenByHash(string $tokenHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET remember_token = NULL
             WHERE remember_token = :token'
        );

        return $stmt->execute(['token' => $tokenHash]);
    }
}