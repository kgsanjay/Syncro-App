<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/init.php'; 

use Syncro\Models\Database;
use Syncro\Security\SecurityManager;

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

$headers = getallheaders();
$apiKey = $headers['X-Syncro-Api-Key'] ?? '';
$signature = $headers['X-Syncro-Signature'] ?? ''; 

if (empty($apiKey) || empty($signature)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing security credentials']);
    exit();
}

$rawPayload = file_get_contents('php://input');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

try {
    $db = Database::getConnection();
    
    // Enterprise Rate Limiter (DDoS Shield)
    $rateLimitWindow = 60;
    $maxRequests = 60;
    
    $stmt = $db->prepare("SELECT id, request_count, window_start FROM api_rate_limits WHERE ip_address = :ip AND endpoint = 'webhook' LIMIT 1");
    $stmt->execute(['ip' => $ipAddress]);
    $rateData = $stmt->fetch();

    if ($rateData) {
        $windowStart = strtotime($rateData['window_start']);
        if (time() - $windowStart > $rateLimitWindow) {
            $db->prepare("UPDATE api_rate_limits SET request_count = 1, window_start = CURRENT_TIMESTAMP WHERE id = :id")
               ->execute(['id' => $rateData['id']]);
        } else {
            if ($rateData['request_count'] >= $maxRequests) {
                http_response_code(429);
                echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded.']);
                exit();
            }
            $db->prepare("UPDATE api_rate_limits SET request_count = request_count + 1 WHERE id = :id")
               ->execute(['id' => $rateData['id']]);
        }
    } else {
        $db->prepare("INSERT INTO api_rate_limits (ip_address, endpoint, request_count) VALUES (:ip, 'webhook', 1)")
           ->execute(['ip' => $ipAddress]);
    }

    $stmt = $db->prepare("SELECT id, api_secret, status FROM hotels WHERE api_key = :key LIMIT 1");
    $stmt->execute(['key' => hash('sha256', $apiKey)]); 
    $hotel = $stmt->fetch();

    if (!$hotel || $hotel['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or suspended API Key']);
        exit();
    }

    if (!SecurityManager::verifyWebhookSignature($rawPayload, $signature, $hotel['api_secret'])) {
        $logStmt = $db->prepare("INSERT INTO audit_logs (action, description, ip_address) VALUES ('WEBHOOK_SPOOF_ATTEMPT', 'Invalid HMAC signature for Hotel ID: ' . :hid, :ip)");
        $logStmt->execute(['hid' => $hotel['id'], 'ip' => $ipAddress]);
        
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Signature verification failed']);
        exit();
    }

    $data = json_decode($rawPayload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException('Malformed JSON payload');
    }

    $channel = $data['channel'] ?? '';
    $otaRoomCode = $data['ota_room_code'] ?? '';
    $checkInStr = $data['check_in'] ?? '';
    $checkOutStr = $data['check_out'] ?? '';
    $roomsBooked = (int)($data['rooms_booked'] ?? 0);
    $eventType = $data['event_type'] ?? '';

    if (!$otaRoomCode || !$checkInStr || !$checkOutStr || $roomsBooked <= 0) {
        throw new \RuntimeException('Missing required booking data in payload');
    }

    $stmt = $db->prepare("
        SELECT m.room_type_id, r.base_price 
        FROM ota_mappings m 
        JOIN room_types r ON m.room_type_id = r.id 
        WHERE m.ota_room_code = :ota_code 
        AND m.channel_name = :channel 
        AND r.hotel_id = :hid 
        LIMIT 1
    ");
    $stmt->execute([
        'ota_code' => $otaRoomCode,
        'channel'  => $channel,
        'hid'      => $hotel['id']
    ]);
    
    $mappedRoom = $stmt->fetch();

    if (!$mappedRoom) {
        error_log("Webhook Sync Failed: Unmapped room code {$otaRoomCode} from {$channel}");
        http_response_code(200); 
        echo json_encode(['status' => 'warning', 'message' => 'Unmapped room code ignored']);
        exit();
    }

    $roomId = (int)$mappedRoom['room_type_id'];
    $basePrice = (float)$mappedRoom['base_price'];
    
    // FIX 1: Pre-calculate the total physical baseline for this room type
    $countStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :rid AND hotel_id = :hid");
    $countStmt->execute(['rid' => $roomId, 'hid' => $hotel['id']]);
    $totalPhysicalRooms = (int)$countStmt->fetchColumn();

    $checkIn = new \DateTime($checkInStr);
    $checkOut = new \DateTime($checkOutStr);
    $interval = \DateInterval::createFromDateString('1 day');
    $period = new \DatePeriod($checkIn, $interval, $checkOut);

    $db->beginTransaction();

    foreach ($period as $dt) {
        $targetDate = $dt->format('Y-m-d');
        
        if ($eventType === 'reservation_created') {
            // FIX 2: Dynamically deduct from the physical baseline for uninitialized dates
            $initialAvailability = max(0, $totalPhysicalRooms - $roomsBooked);
            
            $updateStmt = $db->prepare("
                INSERT INTO inventory_tape (room_type_id, target_date, available_rooms, dynamic_price, stop_sell, version) 
                VALUES (:rid, :date, :initial_avail, :price, 0, 1) 
                ON DUPLICATE KEY UPDATE 
                available_rooms = GREATEST(0, available_rooms - :booked),
                version = version + 1
            ");
            
            $updateStmt->execute([
                'rid'           => $roomId,
                'date'          => $targetDate,
                'initial_avail' => $initialAvailability,
                'price'         => $basePrice,
                'booked'        => $roomsBooked
            ]);
        } elseif ($eventType === 'reservation_cancelled') {
            // FIX 3: Use LEAST() to enforce a mathematical ceiling, preventing duplicate cancellation payloads from inflating inventory beyond physical capacity
            $updateStmt = $db->prepare("
                UPDATE inventory_tape 
                SET available_rooms = LEAST(:total_physical, available_rooms + :booked),
                    version = version + 1
                WHERE room_type_id = :rid AND target_date = :date
            ");
            $updateStmt->execute([
                'total_physical' => $totalPhysicalRooms,
                'booked'         => $roomsBooked,
                'rid'            => $roomId,
                'date'           => $targetDate
            ]);
        }
    }
    
    $syncStmt = $db->prepare("UPDATE ota_mappings SET last_sync_time = NOW() WHERE room_type_id = :rid AND channel_name = :channel");
    $syncStmt->execute(['rid' => $roomId, 'channel' => $channel]);

    $db->commit();

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Inventory synchronized']);

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error processing webhook']);
}