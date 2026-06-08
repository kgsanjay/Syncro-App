<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;

class NotificationService
{
    /**
     * Send a notification to specific user or all staff in a hotel
     * @param int $hotelId
     * @param string $title
     * @param string $message
     * @param int|null $userId If null, sends to all hotel admins and receptionists
     */
    public static function send(int $hotelId, string $title, string $message, ?int $userId = null): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO notifications (hotel_id, user_id, title, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$hotelId, $userId, $title, $message]);
    }
}
