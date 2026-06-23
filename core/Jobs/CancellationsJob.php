<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use Syncro\Services\EmailService;
use Exception;

class CancellationsJob extends BaseJob
{
    public function getName(): string
    {
        return 'cancellations';
    }

    public function isDue(): bool
    {
        // Run cancellations engine every hour
        $minute = (int)date('i');
        return ($minute === 0);
    }

    public function handle(): void
    {
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
            
            // Mock email
            if (!empty($booking['guest_email'])) {
                $msg = "Dear {$booking['guest_name']},\nYour booking #{$booking['id']} has been automatically cancelled as we did not receive payment within 24 hours.";
                // EmailService::send($booking['guest_email'], "Booking Cancelled", $msg);
            }
        }
        
        $db->commit();

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
            // Assume API call succeeds
            $refundStmt->execute(['id' => $booking['id']]);

            if (!empty($booking['guest_email'])) {
                $msg = "Dear {$booking['guest_name']},\nA refund of ₹{$booking['total_price']} for your cancelled booking #{$booking['id']} has been initiated.";
                // EmailService::send($booking['guest_email'], "Refund Initiated", $msg);
            }
        }

        $db->commit();
    }
}
