<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use Exception;
use PDO;

class OtaIntegrationService
{
    /**
     * Takes an array of standardized bookings fetched from an OTA Channel
     * and securely inserts them into the PMS database, avoiding duplicates.
     *
     * @param int $hotelId The local Hotel ID
     * @param int $roomTypeId The local Room Type ID
     * @param array $bookings The parsed array of bookings from the OTA
     * @return int The number of successfully inserted new bookings
     */
    public static function processFetchedBookings(int $hotelId, int $roomTypeId, array $bookings): int
    {
        if (empty($bookings)) {
            return 0;
        }

        $db = Database::getConnection();
        $insertedCount = 0;

        foreach ($bookings as $booking) {
            try {
                // 1. Prevent Duplicates: Check if this exact OTA booking ID already exists
                $checkStmt = $db->prepare("SELECT id FROM bookings WHERE ota_booking_id = :ota_id AND ota_source = :source LIMIT 1");
                $checkStmt->execute([
                    'ota_id' => $booking['ota_booking_id'],
                    'source' => $booking['ota_source']
                ]);

                if ($checkStmt->fetch()) {
                    continue; // Booking already exists, skip to the next one
                }

                $db->beginTransaction();

                // 2. Insert the new Reservation into the PMS
                $insertStmt = $db->prepare("
                    INSERT INTO bookings (
                        hotel_id, room_type_id, guest_name, check_in, check_out, 
                        total_price, status, payment_status, ota_source, ota_booking_id, created_at
                    ) VALUES (
                        :hid, :rid, :guest, :checkin, :checkout, 
                        :price, :status, 'pending', :source, :ota_id, NOW()
                    )
                ");

                $insertStmt->execute([
                    'hid'      => $hotelId,
                    'rid'      => $roomTypeId,
                    'guest'    => strip_tags(trim($booking['guest_name'])),
                    'checkin'  => $booking['check_in'],
                    'checkout' => $booking['check_out'],
                    'price'    => (float)$booking['total_price'],
                    'status'   => $booking['status'] ?? 'confirmed',
                    'source'   => $booking['ota_source'],
                    'ota_id'   => $booking['ota_booking_id']
                ]);

                $newBookingId = (int)$db->lastInsertId();

                // 3. Add to Guest CRM Directory (if they don't exist)
                $guestCheck = $db->prepare("SELECT id FROM guests WHERE full_name = :name AND hotel_id = :hid");
                $guestCheck->execute(['name' => $booking['guest_name'], 'hid' => $hotelId]);
                $guest = $guestCheck->fetch();

                if (!$guest) {
                    $guestInsert = $db->prepare("
                        INSERT INTO guests (hotel_id, full_name, lifetime_revenue, created_at) 
                        VALUES (:hid, :name, :rev, NOW())
                    ");
                    $guestInsert->execute([
                        'hid'  => $hotelId,
                        'name' => strip_tags(trim($booking['guest_name'])),
                        'rev'  => (float)$booking['total_price']
                    ]);
                } else {
                    $guestUpdate = $db->prepare("UPDATE guests SET lifetime_revenue = lifetime_revenue + :rev WHERE id = :id");
                    $guestUpdate->execute(['rev' => (float)$booking['total_price'], 'id' => $guest['id']]);
                }

                // 4. Update the Tape Chart (Inventory) to deduct 1 room for these dates
                $period = new \DatePeriod(
                    new \DateTime($booking['check_in']), 
                    \DateInterval::createFromDateString('1 day'), 
                    new \DateTime($booking['check_out'])
                );

                $deductStmt = $db->prepare("
                    UPDATE inventory_tape 
                    SET available_rooms = GREATEST(0, available_rooms - 1), version = version + 1 
                    WHERE room_type_id = :rid AND target_date = :date
                ");

                foreach ($period as $dt) {
                    $deductStmt->execute([
                        'rid'  => $roomTypeId, 
                        'date' => $dt->format('Y-m-d')
                    ]);
                }

                $db->commit();
                $insertedCount++;

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Failed to insert OTA booking {$booking['ota_booking_id']}: " . $e->getMessage());
            }
        }

        // Clear dashboard cache if new bookings were added so the receptionist sees them instantly
        if ($insertedCount > 0) {
            \Syncro\Services\CacheManager::clear('dashboard_metrics_hotel_' . $hotelId);
        }

        return $insertedCount;
    }

    /**
     * Chunk inventory pushes into 5-day batches.
     */
    public static function pushInventoryChunked(\Syncro\Services\OTA\OtaChannelInterface $otaChannel, string $hotelId, string $otaRoomCode, array $inventoryList): bool
    {
        $chunks = array_chunk($inventoryList, 5);
        $overallSuccess = true;

        foreach ($chunks as $chunk) {
            $success = $otaChannel->pushInventory($hotelId, $otaRoomCode, $chunk);
            if (!$success) {
                $overallSuccess = false;
            }
        }

        return $overallSuccess;
    }

    /**
     * Log API error and throw TemporarySyncException.
     */
    public static function handleApiError(int $hotelId, string $endpoint, string $payload, int $httpCode, string $errorMessage)
    {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO api_error_logs (hotel_id, endpoint, payload, response_code, error_message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$hotelId, $endpoint, $payload, $httpCode, $errorMessage]);
        } catch (Exception $e) {
            error_log("Failed to log API error: " . $e->getMessage());
        }

        throw new \Syncro\Exceptions\TemporarySyncException("API Error ($httpCode): $errorMessage");
    }
}