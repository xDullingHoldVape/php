<?php
namespace App\Core;
 // Database - a palce used for storing user's data
use PDO; //using singleton PDO
use PDOException;
 
class Database
{
    private static ?PDO $instance = null;
 
    private static string $host   = '127.0.0.1';
    private static string $dbname = 'artemgrozniy';
    private static string $user   = 'root';
    private static string $pass   = '';
 
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=utf8mb4',
                    self::$host,
                    self::$dbname
                );
                self::$instance = new PDO($dsn, self::$user, self::$pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die('<b>DB Error:</b> ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
 