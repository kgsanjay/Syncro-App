<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/init.php'; 
use Syncro\Models\Database;

// 1. Allow Cross-Origin Requests (CORS) so external websites can use this API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

$hotelId = (int)($data['hotel_id'] ?? 0);
$roomId = (int)($data['room_type_id'] ?? 0);
$checkInStr = $data['check_in'] ?? '';
$checkOutStr = $data['check_out'] ?? '';
$guestName = trim(filter_var($data['guest_name'] ?? '', FILTER_SANITIZE_STRING));

if (!$hotelId || !$roomId || !$checkInStr || !$checkOutStr || empty($guestName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

try {
    $db = Database::getConnection();

    // FIX 1: Implement strict IP-based Rate Limiting (DDoS & Spam Shield)
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare("SELECT id, request_count, window_start FROM api_rate_limits WHERE ip_address = :ip AND endpoint = 'direct_book' LIMIT 1");
    $stmt->execute(['ip' => $ipAddress]);
    $rateData = $stmt->fetch();

    if ($rateData) {
        if (time() - strtotime($rateData['window_start']) > 300) { // 5 minute window
            $db->prepare("UPDATE api_rate_limits SET request_count = 1, window_start = CURRENT_TIMESTAMP WHERE id = :id")->execute(['id' => $rateData['id']]);
        } else {
            if ($rateData['request_count'] >= 5) { // Max 5 bookings per 5 minutes per IP
                error_log("Security Alert: Booking Spam Blocked from IP {$ipAddress}");
                http_response_code(429);
                echo json_encode(['success' => false, 'message' => 'Too many booking attempts. Please try again later.']);
                exit();
            }
            $db->prepare("UPDATE api_rate_limits SET request_count = request_count + 1 WHERE id = :id")->execute(['id' => $rateData['id']]);
        }
    } else {
        $db->prepare("INSERT INTO api_rate_limits (ip_address, endpoint, request_count) VALUES (:ip, 'direct_book', 1)")->execute(['ip' => $ipAddress]);
    }

    $db->beginTransaction();

    // 2. Verify Room belongs to Hotel
    $stmt = $db->prepare("SELECT id, base_price FROM room_types WHERE id = :rid AND hotel_id = :hid");
    $stmt->execute(['rid' => $roomId, 'hid' => $hotelId]);
    $room = $stmt->fetch();

    if (!$room) {
        throw new \Exception("Invalid room selection.");
    }

    // 3. STRICT AVAILABILITY CHECK: Ensure NO dates are sold out or stop-sold
    $checkIn = new \DateTime($checkInStr);
    $checkOut = new \DateTime($checkOutStr);
    $period = new \DatePeriod($checkIn, \DateInterval::createFromDateString('1 day'), $checkOut);

    foreach ($period as $dt) {
        $targetDate = $dt->format('Y-m-d');
        
        // CRITICAL FIX: 'FOR UPDATE' applies a pessimistic row lock during the transaction
        $checkStmt = $db->prepare("SELECT available_rooms, stop_sell FROM inventory_tape WHERE room_type_id = :rid AND target_date = :date FOR UPDATE");
        $checkStmt->execute(['rid' => $roomId, 'date' => $targetDate]);
        $inv = $checkStmt->fetch();

        if ($inv) {
            if ((int)$inv['stop_sell'] === 1) {
                throw new \Exception("Sorry, rooms are blocked for {$targetDate}.");
            }
            if ((int)$inv['available_rooms'] <= 0) {
                throw new \Exception("Sorry, we are fully booked on {$targetDate}.");
            }
        }
    }

    // 4. Create the Booking
    $stmt = $db->prepare("
        INSERT INTO bookings (hotel_id, room_type_id, guest_name, check_in, check_out, source, status) 
        VALUES (:hid, :rid, :guest, :cin, :cout, 'Direct Website Widget', 'confirmed')
    ");
    $stmt->execute([
        'hid'   => $hotelId,
        'rid'   => $roomId,
        'guest' => $guestName,
        'cin'   => $checkInStr,
        'cout'  => $checkOutStr
    ]);

    // FIX 2: Calculate the dynamic physical baseline to prevent negative initialization
    $countStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :rid AND hotel_id = :hid");
    $countStmt->execute(['rid' => $roomId, 'hid' => $hotelId]);
    $totalPhysicalRooms = (int)$countStmt->fetchColumn();
    $initialAvailability = max(0, $totalPhysicalRooms - 1);

    // 5. Deduct Inventory (Auto-Sync)
    foreach ($period as $dt) {
        $targetDate = $dt->format('Y-m-d');
        // We also increment the version column for optimistic locking support elsewhere
        $updateStmt = $db->prepare("
            INSERT INTO inventory_tape (room_type_id, target_date, available_rooms, dynamic_price, stop_sell, version) 
            VALUES (:rid, :date, :initial_avail, :price, 0, 1) 
            ON DUPLICATE KEY UPDATE 
            available_rooms = GREATEST(0, available_rooms - 1),
            version = version + 1
        ");
        $updateStmt->execute([
            'rid'           => $roomId, 
            'date'          => $targetDate, 
            'initial_avail' => $initialAvailability, 
            'price'         => $room['base_price']
        ]);
    }

    $db->commit();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Booking Confirmed! We look forward to your stay.']);

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}