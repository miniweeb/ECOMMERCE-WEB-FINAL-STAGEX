<?php
namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static $pdo = null;
    protected static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        try {
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
        return self::$pdo;
    }
    public static function connect(): PDO
    {
        return self::getConnection();
    }
}