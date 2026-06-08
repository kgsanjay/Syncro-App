<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\ApiAuth;
use Syncro\Services\BookingService;
use Exception;
use PDO;

class ApiController extends BaseController
{
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * GET /api/v1/availability
     * Fetch available inventory for a date range
     */
    public function getAvailability(): void
    {
        $hotelId = ApiAuth::verify();
        if (!$hotelId) {
            $this->jsonResponse(['error' => 'Unauthorized or invalid API token'], 401);
        }

        $startDate = $_GET['start_date'] ?? date('Y-m-d');
        $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT rt.id as room_type_id, rt.name, i.target_date, i.available_rooms, i.dynamic_price, i.stop_sell
            FROM inventory_tape i
            JOIN room_types rt ON i.room_type_id = rt.id
            WHERE rt.hotel_id = ? AND i.target_date BETWEEN ? AND ?
            ORDER BY rt.id, i.target_date
        ");
        $stmt->execute([$hotelId, $startDate, $endDate]);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($inventory as $row) {
            $results[$row['name']][] = [
                'date' => $row['target_date'],
                'available' => (int)$row['available_rooms'],
                'price' => (float)$row['dynamic_price'],
                'stop_sell' => (bool)$row['stop_sell']
            ];
        }

        $this->jsonResponse([
            'hotel_id' => $hotelId,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'inventory' => $results
        ]);
    }

    /**
     * POST /api/v1/bookings
     * OTA pushes a new booking to Syncro
     */
    public function createBooking(): void
    {
        $hotelId = ApiAuth::verify();
        if (!$hotelId) {
            $this->jsonResponse(['error' => 'Unauthorized or invalid API token'], 401);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid JSON payload'], 400);
        }

        // Basic validation
        $required = ['guest_name', 'room_type_id', 'check_in', 'check_out', 'total_price', 'source'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->jsonResponse(['error' => "Missing required field: {$field}"], 400);
            }
        }

        try {
            $bookingService = new BookingService();
            $bookingId = $bookingService->createDirectBooking($hotelId, $data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Booking created successfully',
                'booking_id' => $bookingId
            ], 201);
            
        } catch (Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 422);
        }
    }
}
