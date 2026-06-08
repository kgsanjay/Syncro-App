<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Syncro\Security\ApiAuth;
use Syncro\Models\Database;

// Mock getallheaders for CLI testing if it doesn't exist
if (!function_exists('getallheaders')) {
    function getallheaders() {
        return $GLOBALS['mock_headers'] ?? [];
    }
}

class ApiAuthTest extends TestCase
{
    protected function setUp(): void
    {
        // Set up dummy DB data for tests if needed, or rely on a known state
        $GLOBALS['mock_headers'] = [];
        
        // Mock DB connection variables just in case
        if (!getenv('DB_HOST')) {
            $_ENV['DB_HOST'] = '127.0.0.1';
            $_ENV['DB_PORT'] = '3306';
            $_ENV['DB_NAME'] = 'syncro_db';
            $_ENV['DB_USER'] = 'root';
            $_ENV['DB_PASS'] = '';
        }
    }

    public function testVerifyFailsWithNoHeaders()
    {
        $GLOBALS['mock_headers'] = [];
        $result = ApiAuth::verify();
        $this->assertFalse($result, 'verify() should return false when no Authorization header is present.');
    }

    public function testVerifyFailsWithMalformedBearer()
    {
        $GLOBALS['mock_headers'] = ['Authorization' => 'InvalidTokenFormat 12345'];
        $result = ApiAuth::verify();
        $this->assertFalse($result, 'verify() should return false when Authorization format is not Bearer.');
    }
    
    public function testVerifyFailsWithInvalidDatabaseToken()
    {
        $GLOBALS['mock_headers'] = ['Authorization' => 'Bearer some-fake-token-that-definitely-does-not-exist'];
        
        try {
            $result = ApiAuth::verify();
            $this->assertFalse($result, 'verify() should return false when token does not exist in DB.');
        } catch (\PDOException $e) {
            $this->markTestSkipped('DB connection failed: ' . $e->getMessage());
        }
    }
}
