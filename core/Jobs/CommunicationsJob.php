<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use PDO;
use Exception;

// Basic mocked Email Service for now
class MockEmailService {
    public static function send(string $to, string $subject, string $body): bool {
        // Simulate sending an email
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] EMAIL SENT to {$to} | Subject: {$subject} | Length: " . strlen($body) . " chars\n";
        
        // Log to a file locally so we can verify it works
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/emails.log', $logMessage, FILE_APPEND);
        
        return true;
    }
}

class CommunicationsJob extends BaseJob
{
    public function getName(): string
    {
        return 'communications';
    }

    public function isDue(): bool
    {
        // Communications engine runs every minute to process email queue
        return true;
    }

    public function handle(): void
    {
        $db = Database::getConnection();

        // Fetch messages ready to send
        $stmt = $db->query("
            SELECT id, guest_email, subject, message 
            FROM communication_queue 
            WHERE status = 'pending' 
              AND scheduled_for <= NOW()
            LIMIT 50
        ");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($messages)) {
            // Nothing to process
            return;
        }

        $updateStmt = $db->prepare("UPDATE communication_queue SET status = 'sent', sent_at = NOW() WHERE id = :id");
        $failStmt = $db->prepare("UPDATE communication_queue SET status = 'failed' WHERE id = :id");

        foreach ($messages as $msg) {
            if (MockEmailService::send($msg['guest_email'], $msg['subject'], $msg['message'])) {
                $updateStmt->execute(['id' => $msg['id']]);
            } else {
                $failStmt->execute(['id' => $msg['id']]);
            }
        }
    }
}
