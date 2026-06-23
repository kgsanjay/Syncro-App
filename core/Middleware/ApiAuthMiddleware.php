<?php
declare(strict_types=1);

namespace Syncro\Middleware;

use Syncro\Models\Database;
use Syncro\Security\UnauthorizedException;

class ApiAuthMiddleware implements MiddlewareInterface
{
    /**
     * Authenticate API request using Bearer token.
     * Throws UnauthorizedException if authentication fails.
     */
    public function handle(): void
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new UnauthorizedException('Missing or invalid Authorization header');
        }

        $token = $matches[1];

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM hotels WHERE api_token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        
        $hotelId = $stmt->fetchColumn();

        if (!$hotelId) {
            throw new UnauthorizedException('Invalid API token');
        }
        
        // Optionally, we could store the authenticated hotelId somewhere for the controller to use,
        // e.g. $_SERVER['HTTP_X_AUTH_HOTEL_ID'] = $hotelId;
        // but for now, we just pass the pipeline.
    }
}
