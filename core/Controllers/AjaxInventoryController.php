<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;

class AjaxInventoryController
{
    private \Syncro\Models\Database $db;

    public function __construct()
    {
        $this->db = new \Syncro\Models\Database();
    }
    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        SessionManager::start();
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'hotel_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $roomId = (int)($input['room_id'] ?? 0);
        $targetDate = $input['target_date'] ?? '';
        $field = $input['field'] ?? '';
        $value = $input['value'] ?? '';

        try {
            $db = $this->db->getPDO();
            
            $stmt = $db->prepare("
                SELECT r.id FROM room_types r 
                JOIN hotels h ON r.hotel_id = h.id 
                WHERE r.id = :rid AND h.user_id = :uid
            ");
            $stmt->execute(['rid' => $roomId, 'uid' => $_SESSION['user_id']]);
            
            if (!$stmt->fetch()) {
                throw new \RuntimeException('IDOR Attack Prevented: Room does not belong to tenant.');
            }

            $query = "INSERT INTO inventory_tape (room_type_id, target_date, available_rooms, dynamic_price, stop_sell) 
                      VALUES (:rid, :date, :inv, :price, :stop) 
                      ON DUPLICATE KEY UPDATE ";

            $params = ['rid' => $roomId, 'date' => $targetDate, 'inv' => 0, 'price' => 0.00, 'stop' => 0];

            if ($field === 'inv') {
                $query .= "available_rooms = :val, version = version + 1";
                $params['inv'] = (int)$value;
                $params['val'] = (int)$value;
            } elseif ($field === 'price') {
                $query .= "dynamic_price = :val, version = version + 1";
                $params['price'] = (float)$value;
                $params['val'] = (float)$value;
            } elseif ($field === 'stop') {
                $query .= "stop_sell = :val, version = version + 1";
                $params['stop'] = (int)$value;
                $params['val'] = (int)$value;
            } else {
                throw new \InvalidArgumentException('Invalid update field');
            }

            $updateStmt = $db->prepare($query);
            $updateStmt->execute($params);

            echo json_encode(['success' => true, 'message' => 'Inventory updated successfully.']);

        } catch (\Exception $e) {
            error_log('AJAX Update Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'System error updating inventory']);
        }
    }
    
    public function syncToChannels(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        SessionManager::start();
        if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'hotel_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        try {
            // OPTIMIZED: Reliable absolute path generation to the root cron folder
            $documentRoot = realpath(__DIR__ . '/../../..'); 
            $cronPath = $documentRoot . '/syncro/cron/sync_engine.php';
            
            $secretKey = getenv('CRON_SECRET_KEY') ?: 'syncro_cron_alpha_99X'; 
            
            if (!file_exists($cronPath)) {
                throw new \Exception("Sync engine script not found at expected path: {$cronPath}");
            }

            // Execute the PHP script in the background natively.
            $command = "php " . escapeshellarg($cronPath) . " key=" . escapeshellarg($secretKey) . " > /dev/null 2>&1 &";
            exec($command);

            echo json_encode([
                'success' => true, 
                'message' => 'Sync process started in the background. Channels will reflect changes momentarily.'
            ]);

        } catch (\Exception $e) {
            error_log('AJAX Sync Dispatch Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'System error dispatching sync job']);
        }
    }
    
    public function updateHousekeeping(array $postData = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        SessionManager::start();
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        // Parse JSON payload correctly
        $input = json_decode(file_get_contents('php://input'), true) ?? $postData;

        try {
            $db = $this->db->getPDO();
            
            // Robust tenant lookup supporting both Hotel Admins and Staff
            $stmt = $db->prepare("
                SELECT COALESCE(h.id, u.hotel_id) as hotel_id 
                FROM users u 
                LEFT JOIN hotels h ON u.id = h.user_id 
                WHERE u.id = :uid LIMIT 1
            ");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            $hotelId = $stmt->fetchColumn();

            if (!$hotelId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized: No hotel found.']);
                return;
            }

            $roomId = (int)($input['room_id'] ?? 0);
            $status = $input['status'] ?? '';

            $validStatuses = ['clean', 'dirty', 'cleaning', 'maintenance'];
            if (!in_array($status, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                return;
            }

            $updateStmt = $db->prepare("UPDATE rooms SET housekeeping_status = :status WHERE id = :rid AND hotel_id = :hid");
            $updateStmt->execute(['status' => $status, 'rid' => $roomId, 'hid' => $hotelId]);
            
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } catch (\Exception $e) {
            error_log('AJAX Housekeeping Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}