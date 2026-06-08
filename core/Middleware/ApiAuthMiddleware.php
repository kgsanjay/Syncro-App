<?php
declare(strict_types=1);

namespace Syncro\Middleware;

use Syncro\Models\Database;

class ApiAuthMiddleware
{
    /**
     * Authenticate API request using Bearer token.
     * Returns the Hotel ID if authenticated, otherwise sends 401 response and exits.
     */
    public static function authenticate(): int
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::unauthorized('Missing or invalid Authorization header');
        }

        $token = $matches[1];

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM hotels WHERE api_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        
        $hotelId = $stmt->fetchColumn();

        if (!$hotelId) {
            self::unauthorized('Invalid API token');
        }

        return (int)$hotelId;
    }

    private static function unauthorized(string $message): void
    {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['error' => 'Unauthorized', 'message' => $message]);
        exit;
    }
}
