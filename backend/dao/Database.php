<?php

/**
 * Creates and shares one PDO connection for the whole request.
 *
 * Credentials are read from backend/config/config.php, which is NOT
 * committed to Git (see config.example.php for the template).
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        // Reuse the existing connection instead of opening a new one for every DAO
        if (self::$connection !== null) {
            return self::$connection;
        }

        $configFile = __DIR__ . '/../config/config.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException(
                'Missing backend/config/config.php. Copy config.example.php to config.php and fill in your database credentials.'
            );
        }

        $config = require $configFile;
        $db = $config['db'];

        $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";

        self::$connection = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows as associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
        ]);

        return self::$connection;
    }
}
