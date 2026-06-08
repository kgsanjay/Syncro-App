<?php
declare(strict_types=1);

namespace Syncro\Security;

use Syncro\Models\Database;

class ApiAuth
{
    /**
     * Verify API Key and return associated hotel_id, or false if invalid.
     * Expects Header: Authorization: Bearer <API_TOKEN>
     */
    public static function verify(): int|false
    {
        if (!function_exists('getallheaders')) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        } else {
            $headers = getallheaders();
        }

        // Test mock injection
        if (isset($GLOBALS['mock_headers'])) {
            $headers = array_merge($headers, $GLOBALS['mock_headers']);
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return false;
        }

        $token = $matches[1];

        $db = Database::getConnection();
        // Assuming api_token is the column
        $stmt = $db->prepare("SELECT id FROM hotels WHERE api_token = ? AND status = 'active'");
        $stmt->execute([$token]);
        $hotelId = $stmt->fetchColumn();

        return $hotelId ? (int)$hotelId : false;
    }
}
