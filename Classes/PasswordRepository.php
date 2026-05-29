<?php
namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * PasswordRepository — all DB operations for the passwords table.
 */
class PasswordRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Return all saved records for a user (encrypted blobs). */
    public function findByUser(int $userId): array
    {
        $st = $this->db->prepare(
            'SELECT * FROM passwords WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $st->execute([':uid' => $userId]);
        return $st->fetchAll();
    }

    /** Return a single record (must belong to user for safety). */
    public function findOne(int $id, int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT * FROM passwords WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $st->execute([':id' => $id, ':uid' => $userId]);
        return $st->fetch() ?: null;
    }

    /** Insert a new password record. Password and notes arrive pre-encrypted. */
    public function create(
        int    $userId,
        string $siteName,
        string $siteUsername,
        string $encryptedPassword,
        string $encryptedNotes = ''
    ): int {
        $st = $this->db->prepare(
            'INSERT INTO passwords (user_id, site_name, username, password, notes)
             VALUES (:uid, :site, :uname, :pwd, :notes)'
        );
        $st->execute([
            ':uid'   => $userId,
            ':site'  => $siteName,
            ':uname' => $siteUsername,
            ':pwd'   => $encryptedPassword,
            ':notes' => $encryptedNotes,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Delete a record (only if it belongs to the user). */
    public function delete(int $id, int $userId): bool
    {
        $st = $this->db->prepare(
            'DELETE FROM passwords WHERE id = :id AND user_id = :uid'
        );
        $st->execute([':id' => $id, ':uid' => $userId]);
        return $st->rowCount() > 0;
    }

    /** Log a generation event. */
    public function logGeneration(
        int $userId, int $length,
        int $uppercase, int $lowercase, int $numbers, int $special
    ): void {
        $st = $this->db->prepare(
            'INSERT INTO generation_log (user_id, length, uppercase, lowercase, numbers, special)
             VALUES (:uid,:len,:up,:lo,:num,:sp)'
        );
        $st->execute([
            ':uid' => $userId, ':len' => $length,
            ':up'  => $uppercase, ':lo' => $lowercase,
            ':num' => $numbers,   ':sp' => $special,
        ]);
    }
}
