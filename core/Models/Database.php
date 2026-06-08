<?php
declare(strict_types=1);

namespace Syncro\Models;

use PDO;
use PDOException;
use RuntimeException;
use Exception;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Private constructor to enforce Singleton
    }

    private function __clone()
    {
        // Prevent cloning
    }

    public function __wakeup()
    {
        // Prevent unserializing to strictly enforce Singleton
        throw new Exception("Cannot unserialize a singleton.");
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            
            // Credentials MUST be securely loaded from the server environment.
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
            $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306'; // Port 3306 is safe to default
            $charset = 'utf8mb4';

            // Fail closed. If critical secrets are missing, halt execution.
            if (empty($host) || empty($db) || empty($user) || !isset($pass)) {
                error_log("CRITICAL BOOT ERROR: Database environment variables are missing or empty.");
                throw new RuntimeException("System configuration error. Service unavailable.");
            }

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, 
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Log the sensitive PDO error internally.
                error_log("CRITICAL: Database Connection Failure: " . $e->getMessage());
                
                // Throw a generic, safe exception to halt execution without leaking data.
                throw new RuntimeException("A critical infrastructure error occurred. Service is temporarily unavailable.");
            }
        }

        return self::$instance;
    }

    /**
     * Spawns a new fluent Query Builder instance.
     * * @param string $tableName
     * @return QueryBuilder
     */
    public static function table(string $tableName): QueryBuilder
    {
        return (new QueryBuilder(self::getConnection()))->table($tableName);
    }
}