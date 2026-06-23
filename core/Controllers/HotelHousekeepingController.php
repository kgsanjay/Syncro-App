<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Models\AuditLogger;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Syncro\Services\TwoFactorService;
use Syncro\Services\CacheManager;
use Syncro\Services\EmailService; 
use PDO;
use Exception;

class HotelHousekeepingController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function housekeeping(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);
        $db = $this->db->getPDO();

        $stmt = $db->prepare("
            SELECT r.id, r.room_number, r.housekeeping_status, r.assigned_housekeeper_id, rt.name as type_name, u.name as housekeeper_name
            FROM rooms r 
            JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN users u ON r.assigned_housekeeper_id = u.id
            WHERE r.hotel_id = :hid ORDER BY r.room_number ASC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $rooms = $stmt->fetchAll();

        // Fetch staff (housekeepers & admins) for assignment dropdown
        $stmtStaff = $db->prepare("SELECT id, name FROM users WHERE hotel_id = :hid AND status = 'active' AND role IN ('housekeeper', 'hotel_admin')");
        $stmtStaff->execute(['hid' => $this->hotelId]);
        $staffMembers = $stmtStaff->fetchAll();

        // Fetch maintenance tickets
        $stmtTickets = $db->prepare("
            SELECT m.*, r.room_number, u.name as reporter_name
            FROM maintenance_tickets m
            JOIN rooms r ON m.room_id = r.id
            LEFT JOIN users u ON m.reported_by = u.id
            WHERE m.hotel_id = :hid AND m.status != 'resolved'
            ORDER BY m.created_at DESC
        ");
        $stmtTickets->execute(['hid' => $this->hotelId]);
        $tickets = $stmtTickets->fetchAll();

        $this->render('user/housekeeping', [
            'pageTitle' => 'Housekeeping Dashboard',
            'rooms' => $rooms,
            'staffMembers' => $staffMembers,
            'tickets' => $tickets
        ], 'user_layout');
    }

    public function assignHousekeeper(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $roomId = (int)($postData['room_id'] ?? 0);
        $staffId = (int)($postData['staff_id'] ?? 0);

        if (!$roomId) {
            SessionManager::setFlash('error', 'Invalid room.');
            $this->redirect('/user/housekeeping');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE rooms SET assigned_housekeeper_id = :sid WHERE id = :rid AND hotel_id = :hid");
            $stmt->execute([
                'sid' => $staffId > 0 ? $staffId : null,
                'rid' => $roomId,
                'hid' => $this->hotelId
            ]);
            SessionManager::setFlash('success', 'Housekeeper assigned successfully.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Database error: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

    public function createMaintenanceTicket(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);

        $roomId = (int)($postData['room_id'] ?? 0);
        $description = strip_tags(trim($postData['description'] ?? ''));

        if (!$roomId || empty($description)) {
            SessionManager::setFlash('error', 'Invalid room or description.');
            $this->redirect('/user/housekeeping');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("INSERT INTO maintenance_tickets (hotel_id, room_id, reported_by, issue_description) VALUES (:hid, :rid, :uid, :desc)");
            $stmt->execute([
                'hid' => $this->hotelId,
                'rid' => $roomId,
                'uid' => $this->userId,
                'desc' => $description
            ]);

            // Auto-update room status to maintenance
            $stmtUpdate = $db->prepare("UPDATE rooms SET housekeeping_status = 'maintenance' WHERE id = :rid AND hotel_id = :hid");
            $stmtUpdate->execute(['rid' => $roomId, 'hid' => $this->hotelId]);

            SessionManager::setFlash('success', 'Maintenance ticket created.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to create ticket: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

    public function resolveMaintenanceTicket(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);

        $ticketId = (int)($postData['ticket_id'] ?? 0);

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE maintenance_tickets SET status = 'resolved' WHERE id = :tid AND hotel_id = :hid");
            $stmt->execute(['tid' => $ticketId, 'hid' => $this->hotelId]);
            SessionManager::setFlash('success', 'Maintenance ticket resolved.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to resolve ticket: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

}
