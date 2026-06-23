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
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $payload = json_encode([
                'hotel_id' => $hotelId,
                'user_id' => $userId,
                'action_type' => $actionType,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($payload === false) {
                return;
            }

            $logFile = __DIR__ . '/../../storage/logs/audit_queue.jsonl';
            file_put_contents($logFile, $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Silently fail logging rather than breaking the application
            error_log("AuditLog Error: " . $e->getMessage());
        }
    }
}
