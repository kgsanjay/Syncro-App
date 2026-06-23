<?php
declare(strict_types=1);

namespace Syncro\Controllers\Api;

use Syncro\Middleware\ApiAuthMiddleware;
use Syncro\Models\Database;
use Throwable;

class ChannelManagerController
{
    private int $hotelId;
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        // Require API Authentication
        $this->hotelId = (new \Syncro\Middleware\ApiAuthMiddleware())->handle();
    }

    /**
     * GET /api/inventory
     * Returns available rooms for the next 30 days.
     */
    public function getInventory(): void
    {
        header('Content-Type: application/json');

        try {
            $db = $this->db->getPDO();
            
            // Example: Simple availability logic
            // In a real channel manager, this would involve complex date-range calculations
            $stmt = $db->prepare("
                SELECT rt.id, rt.name as type, rt.base_price, COUNT(r.id) as available_rooms
                FROM room_types rt
                LEFT JOIN rooms r ON r.room_type_id = rt.id
                WHERE rt.hotel_id = :hotel_id
                GROUP BY rt.id
            ");
            $stmt->execute(['hotel_id' => $this->hotelId]);
            $inventory = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'inventory' => $inventory
            ]);

        } catch (Throwable $e) {
            $this->errorResponse($e->getMessage());
        }
    }

    /**
     * POST /api/reservations
     * Receives a reservation payload from an OTA and creates a booking.
     */
    public function postReservation(): void
    {
        header('Content-Type: application/json');

        // Only allow POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->errorResponse('Method Not Allowed', 405);
            return;
        }

        try {
            // Get raw POST payload
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || !isset($data['guest_name'], $data['check_in'], $data['check_out'], $data['total_price'], $data['room_type'])) {
                $this->errorResponse('Invalid payload. Missing required fields.', 400);
                return;
            }

            $db = $this->db->getPDO();
            $db->beginTransaction();

            // 1. Find the requested room type
            $rtStmt = $db->prepare("SELECT id FROM room_types WHERE hotel_id = :hotel_id AND name = :type");
            $rtStmt->execute(['hotel_id' => $this->hotelId, 'type' => $data['room_type']]);
            $roomTypeId = $rtStmt->fetchColumn();
            
            if (!$roomTypeId) {
                $db->rollBack();
                $this->errorResponse('Requested room type not found', 404);
                return;
            }

            // 1b. Find an available physical room (assigned_room_id)
            $roomStmt = $db->prepare("
                SELECT r.id 
                FROM rooms r
                WHERE r.hotel_id = :hotel_id AND r.room_type_id = :room_type_id 
                AND r.id NOT IN (
                    SELECT assigned_room_id FROM bookings 
                    WHERE hotel_id = :hotel_id_2 AND status != 'cancelled'
                    AND check_in < :check_out AND check_out > :check_in
                    AND assigned_room_id IS NOT NULL
                )
                LIMIT 1 FOR UPDATE
            ");
            $roomStmt->execute([
                'hotel_id' => $this->hotelId,
                'hotel_id_2' => $this->hotelId,
                'room_type_id' => $roomTypeId,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out']
            ]);
            $roomId = $roomStmt->fetchColumn();

            if (!$roomId) {
                $db->rollBack();
                $this->errorResponse('No availability for the requested room type', 409);
                return;
            }

            // 2. Create Guest
            $guestStmt = $db->prepare("
                INSERT INTO guests (hotel_id, full_name, email, phone) 
                VALUES (:hotel_id, :name, :email, :phone)
            ");
            $guestStmt->execute([
                'hotel_id' => $this->hotelId,
                'name'     => $data['guest_name'],
                'email'    => $data['guest_email'] ?? 'ota_guest@example.com',
                'phone'    => $data['guest_phone'] ?? '0000000000'
            ]);
            $guestId = $db->lastInsertId();

            // 3. Create Booking
            $bookingStmt = $db->prepare("
                INSERT INTO bookings (hotel_id, guest_id, room_type_id, assigned_room_id, guest_name, guest_email, guest_phone, check_in, check_out, status, total_price, source)
                VALUES (:hotel_id, :guest_id, :room_type_id, :assigned_room_id, :guest_name, :guest_email, :guest_phone, :check_in, :check_out, 'confirmed', :total, :source)
            ");
            $bookingStmt->execute([
                'hotel_id'         => $this->hotelId,
                'guest_id'         => $guestId,
                'room_type_id'     => $roomTypeId,
                'assigned_room_id' => $roomId,
                'guest_name'       => $data['guest_name'],
                'guest_email'      => $data['guest_email'] ?? '',
                'guest_phone'      => $data['guest_phone'] ?? '',
                'check_in'         => $data['check_in'],
                'check_out'        => $data['check_out'],
                'total'            => $data['total_price'],
                'source'           => $data['source'] ?? 'OTA'
            ]);
            $bookingId = $db->lastInsertId();

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Reservation created successfully',
                'booking_id' => $bookingId
            ]);

        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->errorResponse('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }

    private function errorResponse(string $message, int $code = 500): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message]);
    }
}
