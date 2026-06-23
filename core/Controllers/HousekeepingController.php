<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Models\AuditLogger;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Exception;

class HousekeepingController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function dashboard(): void
    {
        $this->requireRole(['hotel_admin', 'housekeeper']);

        $hotelId = $_SESSION['hotel_id'];
        $db = $this->db->getPDO();

        // Get all rooms for this hotel
        $stmt = $db->prepare("
            SELECT r.id, r.room_number, r.housekeeping_status, rt.name as room_type 
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.hotel_id = ?
            ORDER BY r.room_number ASC
        ");
        $stmt->execute([$hotelId]);
        $rooms = $stmt->fetchAll();

        // Calculate stats
        $stats = [
            'total' => count($rooms),
            'clean' => 0,
            'dirty' => 0,
            'maintenance' => 0
        ];
        
        foreach ($rooms as $room) {
            if ($room['housekeeping_status'] === 'clean') $stats['clean']++;
            elseif ($room['housekeeping_status'] === 'dirty' || $room['housekeeping_status'] === 'cleaning') $stats['dirty']++;
            elseif ($room['housekeeping_status'] === 'maintenance') $stats['maintenance']++;
        }

        $this->render('housekeeping/dashboard', [
            'pageTitle' => 'Housekeeping',
            'rooms' => $rooms,
            'stats' => $stats
        ]);
    }

    public function updateStatus(array $postData = []): void
    {
        $this->requireRole(['hotel_admin', 'housekeeper']);

        $roomId = (int)($postData['room_id'] ?? 0);
        $status = $postData['status'] ?? '';

        $allowedStatuses = ['clean', 'dirty', 'cleaning', 'maintenance'];
        if (!in_array($status, $allowedStatuses)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $hotelId = $_SESSION['hotel_id'];
        $db = $this->db->getPDO();

        try {
            $stmt = $db->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$status, $roomId, $hotelId]);
            
            AuditLogger::log(
                $hotelId, 
                $_SESSION['user_id'] ?? null, 
                'HOUSEKEEPING_UPDATE', 
                "Updated room ID {$roomId} to status: {$status}"
            );

            // Fetch room number for nice notification
            $rmStmt = $db->prepare("SELECT room_number FROM rooms WHERE id = ?");
            $rmStmt->execute([$roomId]);
            $roomNum = $rmStmt->fetchColumn() ?: "ID $roomId";

            \Syncro\Services\NotificationService::send(
                $hotelId,
                "Housekeeping Update",
                "Room {$roomNum} status changed to: " . strtoupper($status)
            );
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
