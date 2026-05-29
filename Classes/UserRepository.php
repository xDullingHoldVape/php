<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * UserRepository — all DB operations for the users table.
 */
class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $st = $this->db->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $st->execute([':u' => $username]);
        return $st->fetch() ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
        $st->execute([':u' => $username]);
        return (bool) $st->fetchColumn();
    }

    public function emailExists(string $email): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        $st->execute([':e' => $email]);
        return (bool) $st->fetchColumn();
    }

    /**
     * Create a new user. The enc_key must already be wrapped before calling this.
     */
    public function create(string $username, string $email, string $password, string $wrappedKey): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $st   = $this->db->prepare(
            'INSERT INTO users (username, email, password, enc_key)
             VALUES (:u, :e, :p, :k)'
        );
        $st->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => $hash,
            ':k' => $wrappedKey,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update stored password hash and re-wrapped key after a password change.
     */
    public function updatePassword(int $userId, string $newPassword, string $newWrappedKey): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $st   = $this->db->prepare(
            'UPDATE users SET password = :p, enc_key = :k WHERE id = :id'
        );
        $st->execute([':p' => $hash, ':k' => $newWrappedKey, ':id' => $userId]);
    }
}
