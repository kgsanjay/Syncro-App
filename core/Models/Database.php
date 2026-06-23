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
    private ?PDO $pdo = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? self::getConnection();
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

            $socket = $_ENV['DB_SOCKET'] ?? getenv('DB_SOCKET');
            
            if (!empty($socket)) {
                $dsn = "mysql:unix_socket=$socket;dbname=$db;charset=$charset";
            } else {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            }
            
            $connectionType = $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION');
            if ($connectionType === 'sqlite') {
                $dsn = "sqlite:" . ($host === 'memory' ? ':memory:' : $db);
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, 
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ];

            try {
                self::$instance = new PDO($dsn, $connectionType === 'sqlite' ? null : $user, $connectionType === 'sqlite' ? null : $pass, $options);
            } catch (PDOException $e) {
                // Log the sensitive PDO error internally.
                error_log("CRITICAL: Database Connection Failure: " . $e->getMessage());
                
                // Throw a generic, safe exception to halt execution without leaking data.
                throw new RuntimeException("A critical infrastructure error occurred: " . $e->getMessage());
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

    public function getPDO(): PDO
    {
        return $this->pdo ?? self::getConnection();
    }

    public function getTable(string $tableName): QueryBuilder
    {
        return clone (new QueryBuilder($this->getPDO()))->table($tableName);
    }
}