<?php
declare(strict_types=1);

// This script should be called by the system crontab every minute:
// * * * * * php /Applications/XAMPP/xamppfiles/htdocs/syncro/cron/communications.php >> /var/log/syncro_cron.log 2>&1

// Adjust path as needed for CLI environment
require_once __DIR__ . '/../core/Models/Database.php';

use Syncro\Models\Database;

// Basic mocked Email Service for now
class MockEmailService {
    public static function send(string $to, string $subject, string $body): bool {
        // Simulate sending an email
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] EMAIL SENT to {$to} | Subject: {$subject} | Length: " . strlen($body) . " chars\n";
        
        // Log to a file locally so we can verify it works
        file_put_contents(__DIR__ . '/../logs/emails.log', $logMessage, FILE_APPEND);
        
        return true;
    }
}

try {
    // Create logs dir if it doesn't exist
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }

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
        exit(0);
    }

    $updateStmt = $db->prepare("UPDATE communication_queue SET status = 'sent', sent_at = NOW() WHERE id = :id");
    $failStmt = $db->prepare("UPDATE communication_queue SET status = 'failed' WHERE id = :id");

    $sentCount = 0;
    foreach ($messages as $msg) {
        if (MockEmailService::send($msg['guest_email'], $msg['subject'], $msg['message'])) {
            $updateStmt->execute(['id' => $msg['id']]);
            $sentCount++;
        } else {
            $failStmt->execute(['id' => $msg['id']]);
        }
    }

    echo "Processed {$sentCount} automated communications.\n";

} catch (Exception $e) {
    echo "CRON Error: " . $e->getMessage() . "\n";
    exit(1);
}
