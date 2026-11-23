<?php
/**
 * Campus Market Hub - Database Connection Manager (PDO Singleton)
 */

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    /**
     * Get active PDO connection instance
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__) . '/config/database.php';
            $config = file_exists($configPath) ? require $configPath : [
                'host'     => '127.0.0.1',
                'port'     => '3306',
                'database' => 'campus_market_hub',
                'username' => 'root',
                'password' => '',
                'charset'  => 'utf8mb4'
            ];

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );

            $options = $config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                // If database does not exist, provide clear error
                if ($e->getCode() == 1049) {
                    die("<h3>Database Error:</h3> <p>The database '<strong>{$config['database']}</strong>' does not exist yet. Please import <code>database/schema.sql</code> into your MySQL/phpMyAdmin server.</p><pre>{$e->getMessage()}</pre>");
                }
                die("<h3>Database Connection Error:</h3> <p>Could not connect to MySQL server at {$config['host']}:{$config['port']}. Please ensure MySQL is running in XAMPP/WampServer.</p><pre>{$e->getMessage()}</pre>");
            }
        }

        return self::$instance;
    }

    /**
     * Prevent instantiation and cloning
     */
    private function __construct() {}
    private function __clone() {}
}
