<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Exception;

class NotificationController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    /**
     * Endpoint for Server-Sent Events (SSE)
     * Streams real-time notifications to the client browser.
     */
    public function stream(): void
    {
        // Must be logged in
        SessionManager::start();
        $hotelId = (int)($_SESSION['hotel_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);

        if (!$hotelId || !$userId) {
            http_response_code(403);
            exit;
        }

        // Close session write lock so other requests from same user aren't blocked
        session_write_close();

        // Set Headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        // Prevent buffering from Nginx/Apache
        header('X-Accel-Buffering: no'); 

        $db = $this->db->getPDO();

        // Client can pass Last-Event-ID to resume stream
        $lastId = isset($_SERVER["HTTP_LAST_EVENT_ID"]) ? (int)$_SERVER["HTTP_LAST_EVENT_ID"] : 0;
        if (isset($_GET['last_id'])) {
            $lastId = max($lastId, (int)$_GET['last_id']);
        }

        // If no last ID, we just fetch the max id currently so we only push NEW events
        if ($lastId === 0) {
            $stmt = $db->prepare("SELECT MAX(id) FROM notifications WHERE hotel_id = ?");
            $stmt->execute([$hotelId]);
            $lastId = (int)$stmt->fetchColumn();
        }

        // Send a connected event
        echo "event: connected\n";
        echo "data: {\"status\": \"Listening\"}\n\n";
        ob_flush();
        flush();

        // Prevent infinite execution on shared hosting
        $maxExecutionTime = 60; // 60 seconds loop limit before client needs to reconnect
        $startTime = time();

        $stmt = $db->prepare("
            SELECT id, title, message, created_at 
            FROM notifications 
            WHERE hotel_id = ? 
            AND id > ? 
            AND (user_id IS NULL OR user_id = ?)
            ORDER BY id ASC
        ");

        while ((time() - $startTime) < $maxExecutionTime) {
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }

            $stmt->execute([$hotelId, $lastId, $userId]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    $lastId = (int)$notification['id'];
                    
                    echo "id: {$lastId}\n";
                    echo "event: notification\n";
                    echo "data: " . json_encode([
                        'id' => $notification['id'],
                        'title' => $notification['title'],
                        'message' => $notification['message'],
                        'time' => $notification['created_at']
                    ]) . "\n\n";
                }
                ob_flush();
                flush();
            }

            sleep(3); // Poll every 3 seconds
        }

        // Send a reconnect event telling the client the server gracefully stopped
        echo "event: close\n";
        echo "data: {\"status\": \"Server closing connection to free resources\"}\n\n";
        ob_flush();
        flush();
        exit;
    }

    /**
     * Mark a notification as read (AJAX)
     */
    public function markRead(): void
    {
        SessionManager::start();
        $this->requireRole(['hotel_admin', 'receptionist']);

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $notifId = (int)($data['id'] ?? 0);

        if ($notifId > 0) {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$notifId, $_SESSION['hotel_id']]);
        }

        echo json_encode(['success' => true]);
    }
}
