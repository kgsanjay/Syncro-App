<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use Syncro\Services\OTA\OtaChannelFactory; 
use Syncro\Services\OtaIntegrationService; 
use PDO;
use Exception;

class SyncEngineJob extends BaseJob
{
    public function getName(): string
    {
        return 'sync_engine';
    }

    public function isDue(): bool
    {
        // Sync engine should run frequently, e.g., every 5 minutes
        $minute = (int)date('i');
        return ($minute % 5 === 0);
    }

    public function handle(): void
    {
        $db = Database::getConnection();

        // Fetch active OTA connections
        $stmt = $db->query("
            SELECT m.id as mapping_id, m.ota_room_code, m.channel_name, 
                   r.hotel_id, r.id as room_type_id, r.name as local_room
            FROM ota_mappings m
            JOIN room_types r ON m.room_type_id = r.id
            WHERE m.sync_status = 'active'
        ");
        
        $mappings = $stmt->fetchAll();

        if (empty($mappings)) {
            return;
        }

        $processed = 0;
        $totalNewBookings = 0;

        foreach ($mappings as $map) {
            try {
                // STEP A: Fetch the API credentials for this specific hotel
                $credStmt = $db->prepare("SELECT * FROM hotels WHERE id = :hid");
                $credStmt->execute(['hid' => $map['hotel_id']]);
                $hotelCredentials = $credStmt->fetch();

                if (!$hotelCredentials) {
                    throw new Exception("Hotel credentials not found.");
                }

                // STEP B: Instantiate the correct OTA channel via the Factory
                $otaChannel = OtaChannelFactory::create($map['channel_name'], $hotelCredentials);

                // ACTION 1: FETCH RESERVATIONS (DOWN)
                $newBookings = $otaChannel->fetchBookings((string)$map['hotel_id']);
                
                if (!empty($newBookings)) {
                    $inserted = OtaIntegrationService::processFetchedBookings(
                        (int)$map['hotel_id'], 
                        (int)$map['room_type_id'], 
                        $newBookings
                    );
                    $totalNewBookings += $inserted;
                }

                // ACTION 2: PUSH INVENTORY & RATES (UP)
                $invStmt = $db->prepare("
                    SELECT target_date, available_rooms, dynamic_price, stop_sell 
                    FROM inventory_tape 
                    WHERE room_type_id = :rid AND target_date >= CURDATE() 
                    ORDER BY target_date ASC
                    LIMIT 14
                ");
                $invStmt->execute(['rid' => $map['room_type_id']]);
                $inventoryList = $invStmt->fetchAll();

                if (!empty($inventoryList)) {
                    $success = OtaIntegrationService::pushInventoryChunked($otaChannel, (string)$map['hotel_id'], $map['ota_room_code'], $inventoryList);

                    if ($success) {
                        $updateStmt = $db->prepare("UPDATE ota_mappings SET last_sync_time = NOW(), sync_status_message = 'Sync Successful' WHERE id = :id");
                        $updateStmt->execute(['id' => $map['mapping_id']]);
                        $processed++;
                    } else {
                        $updateStmt = $db->prepare("UPDATE ota_mappings SET sync_status_message = 'Push failed. API rejected payload.' WHERE id = :id");
                        $updateStmt->execute(['id' => $map['mapping_id']]);
                    }
                }

            } catch (Exception $e) {
                $err = "Error: " . mb_substr($e->getMessage(), 0, 250);
                $updateStmt = $db->prepare("UPDATE ota_mappings SET sync_status_message = :err WHERE id = :id");
                $updateStmt->execute(['err' => $err, 'id' => $map['mapping_id']]);
                error_log("Syncro Engine Error [{$map['channel_name']}]: " . $e->getMessage());
            }
        }
    }
}
