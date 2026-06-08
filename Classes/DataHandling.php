<?php
namespace App\Services;
 
use App\Core\Database;
use PDO;
 
class DataHandling
{
    private PDO $db;
 
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
 
    public function findByUser(int $userId): array
    {
        $st = $this->db->prepare(
            'SELECT * FROM passwords WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $st->execute([':uid' => $userId]);
        return $st->fetchAll();
    }
 
    public function findOne(int $id, int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT * FROM passwords WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $st->execute([':id' => $id, ':uid' => $userId]);
        return $st->fetch() ?: null;
    }
 
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
 
    public function delete(int $id, int $userId): bool
    {
        $st = $this->db->prepare(
            'DELETE FROM passwords WHERE id = :id AND user_id = :uid'
        );
        $st->execute([':id' => $id, ':uid' => $userId]);
        return $st->rowCount() > 0;
    }
 
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