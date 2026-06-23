<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Syncro\Models\Database;
use PDO;
use DateTime;
use Exception;

class YieldEngineJob extends BaseJob
{
    public function getName(): string
    {
        return 'yield_engine';
    }

    public function isDue(): bool
    {
        // For demonstration, yield engine might run every hour at minute 0.
        // We'll run it every 15 minutes to keep pricing updated frequently.
        $minute = (int)date('i');
        return ($minute % 15 === 0);
    }

    public function handle(): void
    {
        $db = Database::getConnection();
        
        // 1. Get all room types and their total active physical rooms
        $rtStmt = $db->query("
            SELECT rt.id, rt.hotel_id, rt.base_price, COUNT(r.id) as total_rooms 
            FROM room_types rt 
            LEFT JOIN rooms r ON r.room_type_id = rt.id 
            GROUP BY rt.id
        ");
        $roomTypes = $rtStmt->fetchAll();

        // 2. Fetch active pricing rules
        $rulesStmt = $db->query("SELECT * FROM pricing_rules WHERE status = 'active' ORDER BY rule_type ASC, condition_value DESC");
        $allRules = $rulesStmt->fetchAll();

        // Prepare update statement for inventory_tape
        $updateStmt = $db->prepare("
            UPDATE inventory_tape 
            SET dynamic_price = :price 
            WHERE room_type_id = :rtid AND target_date = :date
        ");

        $today = new DateTime();
        $db->beginTransaction();

        foreach ($roomTypes as $rt) {
            $rtId = (int)$rt['id'];
            $hotelId = (int)$rt['hotel_id'];
            $basePrice = (float)$rt['base_price'];
            $totalRooms = (int)$rt['total_rooms'];

            if ($totalRooms === 0) continue;

            // Filter rules that apply to this room type
            $applicableRules = array_filter($allRules, function($r) use ($rtId, $hotelId) {
                return $r['hotel_id'] == $hotelId && (empty($r['room_type_id']) || $r['room_type_id'] == $rtId);
            });

            // Loop next 90 days
            for ($i = 0; $i <= 90; $i++) {
                $dateObj = clone $today;
                $dateObj->modify("+$i days");
                $targetDate = $dateObj->format('Y-m-d');

                // Get inventory for this date
                $invStmt = $db->prepare("SELECT available_rooms FROM inventory_tape WHERE room_type_id = :rtid AND target_date = :date");
                $invStmt->execute(['rtid' => $rtId, 'date' => $targetDate]);
                $inv = $invStmt->fetch();
                
                if (!$inv) {
                    // No inventory tape generated yet
                    continue;
                }

                $availableRooms = (int)$inv['available_rooms'];
                $occupancyPercent = (($totalRooms - $availableRooms) / $totalRooms) * 100;
                
                $finalPrice = $basePrice;

                // Apply rules (Time-based first, then occupancy)
                foreach ($applicableRules as $rule) {
                    $apply = false;
                    
                    if ($rule['rule_type'] === 'time_based') {
                        // condition_value is days in advance
                        if ($i >= (int)$rule['condition_value']) {
                            $apply = true;
                        }
                    } elseif ($rule['rule_type'] === 'occupancy_based') {
                        // condition_value is occupancy percentage threshold
                        if ($occupancyPercent >= (float)$rule['condition_value']) {
                            $apply = true;
                        }
                    }

                    if ($apply) {
                        $adjValue = (float)$rule['adjustment_value'];
                        if ($rule['adjustment_type'] === 'percentage') {
                            $finalPrice += $basePrice * ($adjValue / 100);
                        } else {
                            $finalPrice += $adjValue;
                        }
                    }
                }

                // Ensure price doesn't go below 0
                if ($finalPrice < 0) $finalPrice = 0;

                // Update tape
                $updateStmt->execute([
                    'price' => round($finalPrice, 2),
                    'rtid' => $rtId,
                    'date' => $targetDate
                ]);
            }
        }

        $db->commit();
    }
}
