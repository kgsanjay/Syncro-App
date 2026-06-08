<?php
declare(strict_types=1);

namespace Syncro\Models;

use Syncro\Models\Database;
use Exception;

class AuditLogger
{
    /**
     * Log an action to the audit_logs table.
     */
    public static function log(int $hotelId, ?int $userId, string $actionType, string $description): void
    {
        try {
            $db = Database::getConnection();
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt = $db->prepare("
                INSERT INTO audit_logs (hotel_id, user_id, action_type, description, ip_address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$hotelId, $userId, $actionType, $description, $ipAddress]);
        } catch (Exception $e) {
            // Silently fail logging rather than breaking the application, 
            // but in a real enterprise app this might log to a local file.
            error_log("AuditLog Error: " . $e->getMessage());
        }
    }
}
