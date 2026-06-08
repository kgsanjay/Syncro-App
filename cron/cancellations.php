<?php
/**
 * cron/cancellations.php
 * 
 * Run via cron (e.g., hourly)
 * - Auto-cancels pending bookings without payment after 24 hours.
 * - Processes refunds for cancelled bookings that were paid (mock refund logic).
 * - Releases inventory back to the calendar (automatically handled by the fact that the booking status is 'cancelled' so it won't consume inventory, but we can also trigger a sync if needed).
 */

require_once __DIR__ . '/../core/init.php';

use Syncro\Models\Database;
use Syncro\Services\EmailService;

try {
    echo "[Cancellations Engine] Starting run...\n";
    $db = Database::getConnection();

    // 1. Auto-cancel pending bookings > 24 hours old
    $db->beginTransaction();
    
    $stmt = $db->prepare("
        SELECT id, guest_name, guest_email, hotel_id, check_in, check_out, room_type_id
        FROM bookings 
        WHERE status = 'confirmed' 
          AND payment_status = 'pending' 
          AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $pendingToCancel = $stmt->fetchAll();

    $cancelStmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");

    foreach ($pendingToCancel as $booking) {
        $cancelStmt->execute(['id' => $booking['id']]);
        echo "Cancelled pending booking #{$booking['id']} for {$booking['guest_name']}\n";
        
        // Mock email
        if (!empty($booking['guest_email'])) {
            $msg = "Dear {$booking['guest_name']},\nYour booking #{$booking['id']} has been automatically cancelled as we did not receive payment within 24 hours.";
            // EmailService::send($booking['guest_email'], "Booking Cancelled", $msg);
            echo " -> Sent cancellation email to {$booking['guest_email']}\n";
        }
    }
    
    $db->commit();
    echo "[Cancellations Engine] Processed " . count($pendingToCancel) . " auto-cancellations.\n";

    // 2. Process automated refunds for bookings that are cancelled but still 'paid'
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT id, guest_name, guest_email, total_price, transaction_id
        FROM bookings
        WHERE status = 'cancelled' AND payment_status = 'paid'
    ");
    $stmt->execute();
    $refundsToProcess = $stmt->fetchAll();

    $refundStmt = $db->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = :id");

    foreach ($refundsToProcess as $booking) {
        // MOCK REFUND LOGIC (e.g. PhonePe API call)
        echo "Processing refund for booking #{$booking['id']} (Amount: {$booking['total_price']}, Txn: {$booking['transaction_id']})...\n";
        
        // Assume API call succeeds
        $refundStmt->execute(['id' => $booking['id']]);
        echo " -> Refund successful. Booking updated to 'refunded'.\n";

        if (!empty($booking['guest_email'])) {
            $msg = "Dear {$booking['guest_name']},\nA refund of ₹{$booking['total_price']} for your cancelled booking #{$booking['id']} has been initiated.";
            // EmailService::send($booking['guest_email'], "Refund Initiated", $msg);
            echo " -> Sent refund email to {$booking['guest_email']}\n";
        }
    }

    $db->commit();
    echo "[Cancellations Engine] Processed " . count($refundsToProcess) . " automated refunds.\n";
    echo "[Cancellations Engine] Run complete.\n";

} catch (\Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "[Cancellations Engine] Error: " . $e->getMessage() . "\n";
}
