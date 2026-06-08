<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Syncro\Models\Database;
use PDO;

class DatabaseTest extends TestCase
{
    public function testConnectionReturnsPDOInstance()
    {
        // Setup mock environment variables for testing if they aren't loaded
        if (!getenv('DB_HOST')) {
            $_ENV['DB_HOST'] = '127.0.0.1';
            $_ENV['DB_PORT'] = '3306';
            $_ENV['DB_NAME'] = 'syncro_db';
            $_ENV['DB_USER'] = 'root';
            $_ENV['DB_PASS'] = '';
        }

        $pdo = Database::getConnection();
        $this->assertInstanceOf(PDO::class, $pdo, 'Database::getConnection() should return a PDO instance.');
    }

    public function testSingletonPattern()
    {
        $pdo1 = Database::getConnection();
        $pdo2 = Database::getConnection();
        
        $this->assertSame($pdo1, $pdo2, 'Database should follow the Singleton pattern and return the exact same PDO instance.');
    }

    public function testQueryExecution()
    {
        // Check if we can execute a simple query
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetchColumn();
            
            $this->assertEquals(1, $result, 'Database should be able to execute a basic SELECT query.');
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database connection failed. Is the test database running? ' . $e->getMessage());
        }
    }
}
