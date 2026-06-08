<?php
declare(strict_types=1);

// This script should be run via CLI via a cron job at 23:59 or 00:00.
if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    http_response_code(403);
    die('Forbidden: Must be run via CLI.');
}

require_once __DIR__ . '/../core/init.php';

use Syncro\Models\Database;

try {
    $db = Database::getConnection();
    
    // 1. Check if audit was already run today
    $auditDate = date('Y-m-d');
    $stmt = $db->prepare("SELECT id FROM night_audit_logs WHERE audit_date = ? AND status = 'success'");
    $stmt->execute([$auditDate]);
    if ($stmt->fetch()) {
        echo "Night audit for $auditDate has already been processed.\n";
        exit(0);
    }

    $db->beginTransaction();

    // 2. Fetch all hotels to process their daily reports individually
    $stmt = $db->query("SELECT id FROM hotels");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processedCount = 0;
    $totalPosted = 0.0;

    $insertChargeStmt = $db->prepare("
        INSERT INTO pos_charges (booking_id, description, amount, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $insertReportStmt = $db->prepare("
        INSERT INTO daily_reports (hotel_id, report_date, total_revenue, occupancy_rate, total_checkins, total_checkouts)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($hotels as $hotel) {
        $hotelId = $hotel['id'];

        // Get total physical rooms for occupancy calc
        $roomStmt = $db->prepare("SELECT COUNT(id) FROM rooms WHERE hotel_id = ?");
        $roomStmt->execute([$hotelId]);
        $totalRooms = (int)$roomStmt->fetchColumn();

        // Checkins today
        $ciStmt = $db->prepare("SELECT COUNT(id) FROM bookings WHERE hotel_id = ? AND DATE(check_in) = ?");
        $ciStmt->execute([$hotelId, $auditDate]);
        $checkinsToday = (int)$ciStmt->fetchColumn();

        // Checkouts today
        $coStmt = $db->prepare("SELECT COUNT(id) FROM bookings WHERE hotel_id = ? AND DATE(check_out) = ?");
        $coStmt->execute([$hotelId, $auditDate]);
        $checkoutsToday = (int)$coStmt->fetchColumn();

        // Get bookings currently checked in
        $bStmt = $db->prepare("SELECT id, total_price, check_in, check_out FROM bookings WHERE hotel_id = ? AND status = 'checked_in'");
        $bStmt->execute([$hotelId]);
        $bookings = $bStmt->fetchAll(PDO::FETCH_ASSOC);

        $hotelRevenue = 0.0;
        $occupiedRooms = count($bookings);

        foreach ($bookings as $booking) {
            $checkInDate = new DateTime($booking['check_in']);
            $checkOutDate = new DateTime($booking['check_out']);
            $nights = $checkInDate->diff($checkOutDate)->days;
            if ($nights < 1) $nights = 1;

            $nightlyRate = round((float)$booking['total_price'] / $nights, 2);

            $desc = "Room Charge - Night of " . $auditDate;
            $insertChargeStmt->execute([
                $booking['id'],
                $desc,
                $nightlyRate
            ]);

            $processedCount++;
            $totalPosted += $nightlyRate;
            $hotelRevenue += $nightlyRate;
        }

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0.0;

        // Insert Daily Report
        $insertReportStmt->execute([
            $hotelId,
            $auditDate,
            $hotelRevenue,
            $occupancyRate,
            $checkinsToday,
            $checkoutsToday
        ]);
    }

    // 4. Log the audit success
    $logStmt = $db->prepare("
        INSERT INTO night_audit_logs (audit_date, processed_bookings, total_charges_posted, status, created_at)
        VALUES (?, ?, ?, 'success', NOW())
    ");
    $logStmt->execute([$auditDate, $processedCount, $totalPosted]);

    $db->commit();
    echo "Night audit completed successfully for $auditDate. Processed $processedCount bookings. Total posted: ₹$totalPosted.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log failure
    try {
        $logStmt = $db->prepare("
            INSERT INTO night_audit_logs (audit_date, processed_bookings, total_charges_posted, status, created_at)
            VALUES (?, 0, 0, 'failed', NOW())
        ");
        $logStmt->execute([date('Y-m-d')]);
    } catch (Exception $ex) {
        // Suppress failure logging errors
    }

    echo "Night audit failed: " . $e->getMessage() . "\n";
    exit(1);
}
