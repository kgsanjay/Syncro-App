<?php
declare(strict_types=1);

// FIX: Enforce zero-exposure policy for errors. Never display errors to the client.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// 1. CRON SECURITY LOCK
$secretKey = getenv('CRON_SECRET_KEY') ?: 'unconfigured_system'; 
$isCli = php_sapi_name() === 'cli';
$isAuthorizedWeb = isset($_GET['key']) && hash_equals($secretKey, $_GET['key']);

if (!$isCli && !$isAuthorizedWeb) {
    http_response_code(403);
    die("Security Violation: Unauthorized Cron Execution.");
}

// 2. Boot the Core Architecture
require_once dirname(__DIR__) . '/core/init.php';

use Syncro\Models\Database;
use Syncro\Services\OTA\OtaChannelFactory; 
use Syncro\Services\OtaIntegrationService; 

try {
    echo '<style>
        :root { 
            --theme: #FFC107; --theme2: #003366; --header: #002244; 
            --text: #555555; --light: #f8f9fa; --border: #eef1f6; --white: #ffffff; 
        }
    </style>';
    echo "<div class='p-8 bg-[var(--light)] text-[var(--text)] font-mono border border-[var(--border)] rounded shadow-sm'>";
    echo "<h2 class='text-xl font-bold text-[var(--header)] mb-4'>[" . date('Y-m-d H:i:s') . "] SYNCRO ENGINE</h2>";
    echo "<p class='mb-4'>Booting automated 2-Way background sync...</p>";
    
    $db = Database::getConnection();

    // 3. Fetch active OTA connections
    $stmt = $db->query("
        SELECT m.id as mapping_id, m.ota_room_code, m.channel_name, 
               r.hotel_id, r.id as room_type_id, r.name as local_room
        FROM ota_mappings m
        JOIN room_types r ON m.room_type_id = r.id
        WHERE m.sync_status = 'active'
    ");
    
    $mappings = $stmt->fetchAll();

    if (empty($mappings)) {
        echo "<p class='font-semibold text-[var(--header)]'>[SYNCRO ENGINE] No active channels to sync right now. Shutting down.</p></div>";
        exit;
    }

    $processed = 0;
    $totalNewBookings = 0;

    // 4. The Live Two-Way Integration Loop
    foreach ($mappings as $map) {
        echo "<div class='mt-2 ml-4 mb-4 p-4 border-l-4 border-[var(--theme2)] bg-[var(--white)] shadow-sm'>";
        echo "<p class='font-semibold text-[var(--header)]'>-> Processing [" . strtoupper($map['channel_name']) . "] for Room: {$map['local_room']}...</p>";
        
        try {
            // STEP A: Fetch the API credentials for this specific hotel
            $credStmt = $db->prepare("SELECT * FROM hotels WHERE id = :hid");
            $credStmt->execute(['hid' => $map['hotel_id']]);
            $hotelCredentials = $credStmt->fetch();

            if (!$hotelCredentials) {
                throw new \Exception("Hotel credentials not found.");
            }

            // STEP B: Instantiate the correct OTA channel via the Factory
            $otaChannel = OtaChannelFactory::create($map['channel_name'], $hotelCredentials);

            // -------------------------------------------------------------------
            // ACTION 1: FETCH RESERVATIONS (DOWN)
            // -------------------------------------------------------------------
            $newBookings = $otaChannel->fetchBookings((string)$map['hotel_id']);
            
            if (!empty($newBookings)) {
                $inserted = OtaIntegrationService::processFetchedBookings(
                    (int)$map['hotel_id'], 
                    (int)$map['room_type_id'], 
                    $newBookings
                );
                $totalNewBookings += $inserted;
                echo "<p class='ml-4 mt-2 text-green-600 font-bold'>[FETCH] Downloaded and saved {$inserted} new bookings.</p>";
            } else {
                echo "<p class='ml-4 mt-2 text-gray-500'>[FETCH] No new bookings found.</p>";
            }

            // -------------------------------------------------------------------
            // ACTION 2: PUSH INVENTORY & RATES (UP)
            // -------------------------------------------------------------------
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
                $success = $otaChannel->pushInventory((string)$map['hotel_id'], $map['ota_room_code'], $inventoryList);

                if ($success) {
                    $updateStmt = $db->prepare("UPDATE ota_mappings SET last_sync_time = NOW(), sync_status_message = 'Sync Successful' WHERE id = :id");
                    $updateStmt->execute(['id' => $map['mapping_id']]);
                    echo "<p class='ml-4 mt-1 text-blue-600 font-bold'>[PUSH] Inventory & Rates updated successfully.</p>";
                    $processed++;
                } else {
                    $updateStmt = $db->prepare("UPDATE ota_mappings SET sync_status_message = 'Push failed. API rejected payload.' WHERE id = :id");
                    $updateStmt->execute(['id' => $map['mapping_id']]);
                    echo "<p class='ml-4 mt-1 font-bold text-red-600'>[PUSH FAILED] The OTA rejected the payload.</p>";
                }
            } else {
                 echo "<p class='ml-4 mt-1'>[PUSH SKIPPED] No future inventory found to push.</p>";
            }

        } catch (\Exception $e) {
            $err = "Error: " . mb_substr($e->getMessage(), 0, 250);
            $updateStmt = $db->prepare("UPDATE ota_mappings SET sync_status_message = :err WHERE id = :id");
            $updateStmt->execute(['err' => $err, 'id' => $map['mapping_id']]);
            echo "<p class='ml-4 mt-2 font-bold text-red-600'>[ERROR] " . htmlspecialchars($e->getMessage()) . "</p>";
            error_log("Syncro Engine Error [{$map['channel_name']}]: " . $e->getMessage());
        }
        echo "</div>";
    }

    echo "<p class='mt-6 font-bold text-[var(--header)] text-lg'>[SYNCRO ENGINE] Execution Complete.</p>";
    echo "<p class='mt-1 text-[var(--text)]'>- {$processed} channels synchronized.</p>";
    echo "<p class='mt-1 text-[var(--text)]'>- {$totalNewBookings} new bookings imported.</p>";
    echo "</div>";

} catch (\Throwable $e) { 
    error_log("[SYNCRO CRON ERROR] " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo "<div class='mt-6 p-4 border-l-4 border-red-500 bg-[var(--white)]'>";
    echo "<strong class='text-red-700'>Execution Halted:</strong> A system error occurred. Please check error.log.";
    echo "</div></div>";
}