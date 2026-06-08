<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Models\AuditLogger;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Syncro\Services\TwoFactorService;
use Syncro\Services\CacheManager;
use Syncro\Services\EmailService; 
use PDO;
use Exception;

class HotelController extends BaseHotelController
{
    // =========================================================================
    // 1. DASHBOARD & REVENUE (CACHED)
    // =========================================================================

    public function dashboard(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $cacheKey = 'dashboard_metrics_hotel_' . $this->hotelId;
        $dashboardData = CacheManager::get($cacheKey);

        if (!$dashboardData) {
            $db = Database::getConnection();
            $today = date('Y-m-d');

            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = :hid AND check_in = :today AND status = 'confirmed'");
            $stmt->execute(['hid' => $this->hotelId, 'today' => $today]);
            $arrivalsToday = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = :hid AND check_out = :today AND status = 'confirmed'");
            $stmt->execute(['hid' => $this->hotelId, 'today' => $today]);
            $departuresToday = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = :hid AND check_in <= :today1 AND check_out > :today2 AND status = 'confirmed'");
            $stmt->execute(['hid' => $this->hotelId, 'today1' => $today, 'today2' => $today]);
            $inHouse = (int)$stmt->fetchColumn();

            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
            
            $stmt = $db->prepare("
                SELECT b.id, b.room_type_id, b.check_in, b.check_out, r.base_price
                FROM bookings b
                JOIN room_types r ON b.room_type_id = r.id
                WHERE b.hotel_id = :hid AND b.check_in >= :tda AND b.status != 'cancelled'
            ");
            $stmt->execute(['hid' => $this->hotelId, 'tda' => $thirtyDaysAgo]);
            $recentBookings = $stmt->fetchAll();

            $tapeStmt = $db->prepare("
                SELECT t.room_type_id, t.target_date, t.dynamic_price 
                FROM inventory_tape t
                JOIN room_types r ON t.room_type_id = r.id
                WHERE r.hotel_id = :hid
            ");
            $tapeStmt->execute(['hid' => $this->hotelId]);
            $tapeData = [];
            while ($t = $tapeStmt->fetch()) {
                $tapeData[$t['room_type_id']][$t['target_date']] = (float)$t['dynamic_price'];
            }

            $roomRevenue = 0;
            foreach ($recentBookings as $b) {
                $period = new \DatePeriod(new \DateTime($b['check_in']), \DateInterval::createFromDateString('1 day'), new \DateTime($b['check_out']));
                foreach ($period as $dt) {
                    $d = $dt->format('Y-m-d');
                    if (isset($tapeData[$b['room_type_id']][$d]) && $tapeData[$b['room_type_id']][$d] > 0) {
                        $roomRevenue += $tapeData[$b['room_type_id']][$d]; 
                    } else {
                        $roomRevenue += (float)$b['base_price']; 
                    }
                }
            }

            $stmt = $db->prepare("
                SELECT SUM(a.total_amount) 
                FROM ancillary_sales a
                JOIN bookings b ON a.booking_id = b.id
                WHERE a.hotel_id = :hid AND a.sale_date >= :tda
            ");
            $stmt->execute(['hid' => $this->hotelId, 'tda' => $thirtyDaysAgo]);
            $ancillaryRevenue = (float)$stmt->fetchColumn();

            $totalRevenue = $roomRevenue + $ancillaryRevenue;

            // Chart Data: Last 7 Days Revenue
            $chartDates = [];
            $revenueByDay = [];
            for($i=6; $i>=0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $chartDates[] = date('D', strtotime($d)); 
                $revenueByDay[$d] = 0;
            }

            foreach ($recentBookings as $b) {
                $period = new \DatePeriod(new \DateTime($b['check_in']), \DateInterval::createFromDateString('1 day'), new \DateTime($b['check_out']));
                foreach ($period as $dt) {
                    $d = $dt->format('Y-m-d');
                    if (isset($revenueByDay[$d])) {
                        if (isset($tapeData[$b['room_type_id']][$d]) && $tapeData[$b['room_type_id']][$d] > 0) {
                            $revenueByDay[$d] += $tapeData[$b['room_type_id']][$d]; 
                        } else {
                            $revenueByDay[$d] += (float)$b['base_price']; 
                        }
                    }
                }
            }
            $chartRevenue = array_values($revenueByDay);

            // Occupancy Rate
            $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE hotel_id = :hid");
            $stmt->execute(['hid' => $this->hotelId]);
            $totalRooms = (int)$stmt->fetchColumn();
            $occupancyRate = $totalRooms > 0 ? round(($inHouse / $totalRooms) * 100) : 0;

            $stmt = $db->prepare("
                SELECT b.id, b.guest_name, r.name as room_name, b.payment_status 
                FROM bookings b
                JOIN room_types r ON b.room_type_id = r.id
                WHERE b.hotel_id = :hid AND b.check_in = :today AND b.status = 'confirmed'
            ");
            $stmt->execute(['hid' => $this->hotelId, 'today' => $today]);
            $arrivalList = $stmt->fetchAll();

            $stmt = $db->prepare("
                SELECT b.id, b.guest_name, r.name as room_name, b.payment_status, 
                       p.room_number as physical_room_name
                FROM bookings b
                JOIN room_types r ON b.room_type_id = r.id
                LEFT JOIN rooms p ON b.assigned_room_id = p.id
                WHERE b.hotel_id = :hid AND b.check_out = :today AND b.status = 'confirmed'
            ");
            $stmt->execute(['hid' => $this->hotelId, 'today' => $today]);
            $departureList = $stmt->fetchAll();

            // Fetch OTA Sync Pulse Data
            $stmt = $db->prepare("
                SELECT channel_name, sync_status, last_sync_time 
                FROM ota_mappings o
                JOIN room_types r ON o.room_type_id = r.id
                WHERE r.hotel_id = :hid
                ORDER BY last_sync_time DESC
            ");
            $stmt->execute(['hid' => $this->hotelId]);
            $otaMappings = $stmt->fetchAll();

            $dashboardData = [
                'arrivalsToday'   => $arrivalsToday,
                'departuresToday' => $departuresToday,
                'inHouse'         => $inHouse,
                'occupancyRate'   => $occupancyRate,
                'totalRevenue'    => $totalRevenue,
                'roomRevenue'     => $roomRevenue,
                'ancillaryRevenue'=> $ancillaryRevenue,
                'chartDates'      => $chartDates,
                'chartRevenue'    => $chartRevenue,
                'arrivalList'     => $arrivalList,
                'departureList'   => $departureList,
                'otaMappings'     => $otaMappings,
                'cachedTime'      => date('g:i A')
            ];
            CacheManager::set($cacheKey, $dashboardData, 900);
        }

        $this->render('user/dashboard', array_merge([
            'pageTitle' => 'Property Overview'
        ], $dashboardData), 'user_layout');
    }

    // =========================================================================
    // 2. CALENDAR & INVENTORY TAPE
    // =========================================================================

    public function calendar(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $db = Database::getConnection();

        $startDate = new \DateTime();
        $dates = [];
        for ($i = 0; $i < 14; $i++) {
            $dates[] = clone $startDate;
            $startDate->modify('+1 day');
        }
        $startStr = $dates[0]->format('Y-m-d');
        $endStr = $dates[13]->format('Y-m-d');

        $stmt = $db->prepare("
            SELECT r.id as physical_id, r.room_number, r.housekeeping_status, rt.name as type_name, rt.id as type_id
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.id
            WHERE r.hotel_id = :hid
            ORDER BY rt.name ASC, r.room_number ASC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $groupedRooms = [];
        foreach ($stmt->fetchAll() as $pr) $groupedRooms[$pr['type_name']][] = $pr;

        $stmt = $db->prepare("
            SELECT b.id, b.room_type_id, b.assigned_room_id, b.guest_name, b.check_in, b.check_out, b.status
            FROM bookings b
            WHERE b.hotel_id = :hid AND b.check_out > :start AND b.check_in <= :end AND b.status != 'cancelled'
        ");
        $stmt->execute(['hid' => $this->hotelId, 'start' => $startStr, 'end' => $endStr]);
        
        $this->render('user/calendar', [
            'pageTitle'    => 'Front Desk Timeline',
            'dates'        => $dates,
            'groupedRooms' => $groupedRooms,
            'bookings'     => $stmt->fetchAll()
        ], 'user_layout');
    }

    public function inventory(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id, name, local_room_code, base_price FROM room_types WHERE hotel_id = :hid ORDER BY name ASC");
        $stmt->execute(['hid' => $this->hotelId]);
        $roomTypes = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT room_type_id, COUNT(*) as total_rooms FROM rooms WHERE hotel_id = :hid GROUP BY room_type_id");
        $stmt->execute(['hid' => $this->hotelId]);
        $physicalCounts = [];
        while ($row = $stmt->fetch()) {
            $physicalCounts[$row['room_type_id']] = (int)$row['total_rooms'];
        }

        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+13 days'));
        
        $dates = [];
        for ($i = 0; $i < 14; $i++) $dates[] = date('Y-m-d', strtotime("+$i days"));

        $inventoryData = [];
        if (!empty($roomTypes)) {
            $roomTypeIds = array_column($roomTypes, 'id');
            $inClause = implode(',', array_fill(0, count($roomTypeIds), '?'));
            
            $query = "SELECT room_type_id, target_date, available_rooms, dynamic_price, stop_sell 
                      FROM inventory_tape WHERE room_type_id IN ($inClause) AND target_date BETWEEN ? AND ?";
            
            $params = array_merge($roomTypeIds, [$startDate, $endDate]);
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $tapeOverrides = [];
            while ($row = $stmt->fetch()) {
                $tapeOverrides[$row['room_type_id']][$row['target_date']] = $row;
            }

            $bookStmt = $db->prepare("
                SELECT room_type_id, check_in, check_out 
                FROM bookings 
                WHERE hotel_id = ? AND status = 'confirmed' 
                AND check_out > ? AND check_in <= ?
            ");
            $bookStmt->execute([$this->hotelId, $startDate, $endDate]);
            $bookings = $bookStmt->fetchAll();

            foreach ($roomTypes as $type) {
                $rId = $type['id'];
                $baseCount = $physicalCounts[$rId] ?? 0;

                foreach ($dates as $dateStr) {
                    $bookedCount = 0;
                    foreach ($bookings as $b) {
                        if ($b['room_type_id'] == $rId && $b['check_in'] <= $dateStr && $b['check_out'] > $dateStr) {
                            $bookedCount++;
                        }
                    }

                    $calculatedAvailability = max(0, $baseCount - $bookedCount);

                    if (isset($tapeOverrides[$rId][$dateStr])) {
                        $inventoryData[$rId][$dateStr] = $tapeOverrides[$rId][$dateStr];
                    } else {
                        $inventoryData[$rId][$dateStr] = [
                            'available_rooms' => $calculatedAvailability,
                            'dynamic_price' => $type['base_price'],
                            'stop_sell' => 0
                        ];
                    }
                }
            }
        }

        $this->render('user/inventory', [
            'pageTitle'     => 'Tape Chart',
            'roomTypes'     => $roomTypes,
            'dates'         => $dates,
            'inventoryData' => $inventoryData
        ], 'user_layout');
    }

    // =========================================================================
    // 3. RATE MANAGER
    // =========================================================================

    public function rateManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = Database::getConnection();
        
        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $db->prepare("SELECT id, name, base_price FROM room_types WHERE hotel_id = :hid");
        $stmt->execute(['hid' => $this->hotelId]);
        $rooms = $stmt->fetchAll();

        $rates = [];
        if (!empty($rooms)) {
            $roomIds = array_column($rooms, 'id');
            $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
            
            $sql = "SELECT room_type_id, target_date, dynamic_price FROM inventory_tape 
                    WHERE target_date BETWEEN ? AND ? AND room_type_id IN ($placeholders)";
            $params = array_merge([$startDate, $endDate], $roomIds);
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            while ($row = $stmt->fetch()) $rates[$row['room_type_id']][$row['target_date']] = $row['dynamic_price'];
        }

        // Fetch pricing rules
        $rulesStmt = $db->prepare("SELECT pr.*, rt.name as room_type_name FROM pricing_rules pr LEFT JOIN room_types rt ON pr.room_type_id = rt.id WHERE pr.hotel_id = :hid ORDER BY pr.created_at DESC");
        $rulesStmt->execute(['hid' => $this->hotelId]);
        $pricingRules = $rulesStmt->fetchAll();

        $this->render('user/rates', [
            'pageTitle' => 'Dynamic Rate Manager', 'rooms' => $rooms, 'rates' => $rates,
            'pricingRules' => $pricingRules,
            'month' => $month, 'year' => $year, 'startDate' => $startDate, 'endDate' => $endDate
        ], 'user_layout');
    }

    public function updateRates(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_type_id'] ?? 0);
        $startDate = $postData['start_date'] ?? '';
        $endDate = $postData['end_date'] ?? '';
        $newPrice = (float)($postData['new_price'] ?? 0);

        if (!$roomId || empty($startDate) || empty($endDate) || $newPrice <= 0) {
            SessionManager::setFlash('error', 'Invalid form submission.');
            $this->redirect('/user/rates'); 
            return;
        }

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT id FROM room_types WHERE id = :rid AND hotel_id = :hid");
            $stmt->execute(['rid' => $roomId, 'hid' => $this->hotelId]);
            if (!$stmt->fetch()) throw new Exception("Unauthorized: Room category does not belong to you.");

            $countStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :rid AND hotel_id = :hid");
            $countStmt->execute(['rid' => $roomId, 'hid' => $this->hotelId]);
            $totalPhysical = (int)$countStmt->fetchColumn();

            $period = new \DatePeriod(new \DateTime($startDate), \DateInterval::createFromDateString('1 day'), (new \DateTime($endDate))->modify('+1 day'));
            
            $db->beginTransaction();
            
            $updateStmt = $db->prepare("
                INSERT INTO inventory_tape (room_type_id, target_date, available_rooms, dynamic_price, stop_sell, version) 
                VALUES (:rid, :date, :avail, :price1, 0, 1) 
                ON DUPLICATE KEY UPDATE 
                dynamic_price = :price2,
                version = version + 1
            ");

            foreach ($period as $dt) {
                $updateStmt->execute([
                    'rid'    => $roomId, 
                    'date'   => $dt->format('Y-m-d'), 
                    'avail'  => $totalPhysical, 
                    'price1' => $newPrice,
                    'price2' => $newPrice
                ]);
            }
            
            $db->commit();
            
            AuditLogger::log(
                $this->hotelId,
                $_SESSION['user_id'] ?? null,
                'RATE_UPDATE',
                "Updated rates for room_type_id: {$roomId} from {$startDate} to {$endDate} at price {$newPrice}"
            );
            
            $d = new \DateTime($startDate);
            SessionManager::setFlash('success', 'Rates successfully updated.');
            $this->redirect('/user/rates?month=' . $d->format('n') . '&year=' . $d->format('Y'));
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            SessionManager::setFlash('error', 'Failed to update rates.');
            $this->redirect('/user/rates');
        }
    }

    public function createPricingRule(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $roomTypeId = !empty($postData['room_type_id']) ? (int)$postData['room_type_id'] : null;
        $ruleType = $postData['rule_type'] ?? '';
        $conditionValue = (int)($postData['condition_value'] ?? 0);
        $adjustmentType = $postData['adjustment_type'] ?? '';
        $adjustmentValue = (float)($postData['adjustment_value'] ?? 0);

        if (!in_array($ruleType, ['occupancy_based', 'time_based']) || !in_array($adjustmentType, ['percentage', 'fixed'])) {
            SessionManager::setFlash('error', 'Invalid rule data.');
            $this->redirect('/user/rates');
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO pricing_rules (hotel_id, room_type_id, rule_type, condition_value, adjustment_type, adjustment_value) VALUES (:hid, :rtid, :rtype, :cval, :atype, :aval)");
            $stmt->execute([
                'hid' => $this->hotelId,
                'rtid' => $roomTypeId,
                'rtype' => $ruleType,
                'cval' => $conditionValue,
                'atype' => $adjustmentType,
                'aval' => $adjustmentValue
            ]);
            SessionManager::setFlash('success', 'Pricing rule created successfully.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Database error: ' . $e->getMessage());
        }
        $this->redirect('/user/rates');
    }

    public function togglePricingRule(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        $ruleId = (int)($postData['rule_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE pricing_rules SET status = IF(status='active', 'inactive', 'active') WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $ruleId, 'hid' => $this->hotelId]);
            SessionManager::setFlash('success', 'Rule status toggled.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Database error.');
        }
        $this->redirect('/user/rates');
    }

    // =========================================================================
    // 4. CHANNEL MANAGER & OTA MAPPINGS
    // =========================================================================

    public function channelManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM hotels WHERE id = :hid");
        $stmt->execute(['hid' => $this->hotelId]);
        $hotelSettings = $stmt->fetch();

        $activeChannels = [];
        if (!empty($hotelSettings['booking_com_username'])) $activeChannels['Booking.com'] = 'Booking.com';
        if (!empty($hotelSettings['mmt_api_key'])) $activeChannels['MakeMyTrip'] = 'MakeMyTrip';
        if (!empty($hotelSettings['agoda_api_key'])) $activeChannels['Agoda'] = 'Agoda';
        if (!empty($hotelSettings['cleartrip_api_key'])) $activeChannels['Cleartrip'] = 'Cleartrip';
        if (!empty($hotelSettings['yatra_api_key'])) $activeChannels['Yatra'] = 'Yatra';
        if (!empty($hotelSettings['expedia_username'])) $activeChannels['Expedia'] = 'Expedia';
        if (!empty($hotelSettings['easemytrip_api_key'])) $activeChannels['EaseMyTrip'] = 'EaseMyTrip';
        if (!empty($hotelSettings['paytm_api_key'])) $activeChannels['Paytm'] = 'Paytm';
        if (!empty($hotelSettings['airbnb_api_key'])) $activeChannels['Airbnb'] = 'Airbnb';
        if (!empty($hotelSettings['tripadvisor_api_key'])) $activeChannels['TripAdvisor'] = 'TripAdvisor';

        $stmt = $db->prepare("SELECT id, name, local_room_code FROM room_types WHERE hotel_id = :hid ORDER BY name ASC");
        $stmt->execute(['hid' => $this->hotelId]);
        $rooms = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT o.id, o.channel_name, o.ota_room_code, o.sync_status, o.last_sync_time, o.sync_status_message, r.name as local_room_name 
            FROM ota_mappings o JOIN room_types r ON o.room_type_id = r.id 
            WHERE r.hotel_id = :hid ORDER BY o.channel_name ASC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        
        $this->render('user/channel_manager', [
            'pageTitle'      => 'OTA Mappings', 
            'rooms'          => $rooms, 
            'mappings'       => $stmt->fetchAll(),
            'activeChannels' => $activeChannels
        ], 'user_layout');
    }

    public function storeMapping(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_type_id'] ?? 0);
        $channelName = strip_tags(trim($postData['channel_name'] ?? ''));
        $otaRoomCode = strip_tags(trim($postData['ota_room_code'] ?? ''));

        if (!$roomId || empty($channelName) || empty($otaRoomCode)) { 
            SessionManager::setFlash('error', 'Please fill all required fields.');
            $this->redirect('/user/channel-manager'); 
            return; 
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM room_types WHERE id = :rid AND hotel_id = :hid");
            $stmt->execute(['rid' => $roomId, 'hid' => $this->hotelId]);
            if (!$stmt->fetch()) throw new Exception("Unauthorized");

            $stmt = $db->prepare("INSERT INTO ota_mappings (room_type_id, channel_name, ota_room_code, sync_status) VALUES (:rid, :channel, :ota_code, 'active')");
            $stmt->execute(['rid' => $roomId, 'channel' => $channelName, 'ota_code' => $otaRoomCode]);

            SessionManager::setFlash('success', 'OTA Mapping added successfully.');
            $this->redirect('/user/channel-manager');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to save mapping.');
            $this->redirect('/user/channel-manager');
        }
    }

    public function deleteMapping(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        
        $mappingId = (int)($postData['mapping_id'] ?? 0);
        if (!$mappingId) { $this->redirect('/user/channel-manager'); return; }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE o FROM ota_mappings o JOIN room_types r ON o.room_type_id = r.id WHERE o.id = :mid AND r.hotel_id = :hid");
            $stmt->execute(['mid' => $mappingId, 'hid' => $this->hotelId]);
            
            SessionManager::setFlash('success', 'OTA Mapping deleted.');
            $this->redirect('/user/channel-manager');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to delete mapping.');
            $this->redirect('/user/channel-manager');
        }
    }

    // =========================================================================
    // 5. HOUSEKEEPING & STAFF
    // =========================================================================

    public function housekeeping(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT r.id, r.room_number, r.housekeeping_status, r.assigned_housekeeper_id, rt.name as type_name, u.name as housekeeper_name
            FROM rooms r 
            JOIN room_types rt ON r.room_type_id = rt.id
            LEFT JOIN users u ON r.assigned_housekeeper_id = u.id
            WHERE r.hotel_id = :hid ORDER BY r.room_number ASC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $rooms = $stmt->fetchAll();

        // Fetch staff (housekeepers & admins) for assignment dropdown
        $stmtStaff = $db->prepare("SELECT id, name FROM users WHERE hotel_id = :hid AND status = 'active' AND role IN ('housekeeper', 'hotel_admin')");
        $stmtStaff->execute(['hid' => $this->hotelId]);
        $staffMembers = $stmtStaff->fetchAll();

        // Fetch maintenance tickets
        $stmtTickets = $db->prepare("
            SELECT m.*, r.room_number, u.name as reporter_name
            FROM maintenance_tickets m
            JOIN rooms r ON m.room_id = r.id
            LEFT JOIN users u ON m.reported_by = u.id
            WHERE m.hotel_id = :hid AND m.status != 'resolved'
            ORDER BY m.created_at DESC
        ");
        $stmtTickets->execute(['hid' => $this->hotelId]);
        $tickets = $stmtTickets->fetchAll();

        $this->render('user/housekeeping', [
            'pageTitle' => 'Housekeeping Dashboard',
            'rooms' => $rooms,
            'staffMembers' => $staffMembers,
            'tickets' => $tickets
        ], 'user_layout');
    }

    public function assignHousekeeper(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_id'] ?? 0);
        $staffId = (int)($postData['staff_id'] ?? 0);

        if (!$roomId) {
            SessionManager::setFlash('error', 'Invalid room.');
            $this->redirect('/user/housekeeping');
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE rooms SET assigned_housekeeper_id = :sid WHERE id = :rid AND hotel_id = :hid");
            $stmt->execute([
                'sid' => $staffId > 0 ? $staffId : null,
                'rid' => $roomId,
                'hid' => $this->hotelId
            ]);
            SessionManager::setFlash('success', 'Housekeeper assigned successfully.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Database error: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

    public function createMaintenanceTicket(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);
        $this->validateCsrf($postData);

        $roomId = (int)($postData['room_id'] ?? 0);
        $description = strip_tags(trim($postData['description'] ?? ''));

        if (!$roomId || empty($description)) {
            SessionManager::setFlash('error', 'Invalid room or description.');
            $this->redirect('/user/housekeeping');
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO maintenance_tickets (hotel_id, room_id, reported_by, issue_description) VALUES (:hid, :rid, :uid, :desc)");
            $stmt->execute([
                'hid' => $this->hotelId,
                'rid' => $roomId,
                'uid' => $this->userId,
                'desc' => $description
            ]);

            // Auto-update room status to maintenance
            $stmtUpdate = $db->prepare("UPDATE rooms SET housekeeping_status = 'maintenance' WHERE id = :rid AND hotel_id = :hid");
            $stmtUpdate->execute(['rid' => $roomId, 'hid' => $this->hotelId]);

            SessionManager::setFlash('success', 'Maintenance ticket created.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to create ticket: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

    public function resolveMaintenanceTicket(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist', 'housekeeper']);
        $this->validateCsrf($postData);

        $ticketId = (int)($postData['ticket_id'] ?? 0);

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE maintenance_tickets SET status = 'resolved' WHERE id = :tid AND hotel_id = :hid");
            $stmt->execute(['tid' => $ticketId, 'hid' => $this->hotelId]);
            SessionManager::setFlash('success', 'Maintenance ticket resolved.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to resolve ticket: ' . $e->getMessage());
        }
        $this->redirect('/user/housekeeping');
    }

    public function staffManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM users WHERE hotel_id = :hid ORDER BY created_at DESC");
        $stmt->execute(['hid' => $this->hotelId]);

        $this->render('user/staff', [
            'pageTitle'    => 'Staff Accounts',
            'staffMembers' => $stmt->fetchAll()
        ], 'user_layout');
    }

    public function createStaff(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $name = strip_tags(trim($postData['name'] ?? ''));
        $email = trim(filter_var($postData['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $postData['password'] ?? '';
        $role = $postData['role'] ?? '';

        if (empty($name) || empty($email) || empty($password) || !in_array($role, ['receptionist', 'housekeeper'])) {
            SessionManager::setFlash('error', 'Invalid input provided.');
            $this->redirect('/user/staff'); return;
        }

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) { 
                SessionManager::setFlash('error', 'Email already exists in the system.');
                $this->redirect('/user/staff'); return; 
            }

            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("INSERT INTO users (role, name, email, password_hash, status, hotel_id) VALUES (:role, :name, :email, :hash, 'active', :hid)");
            $stmt->execute(['role' => $role, 'name' => $name, 'email' => $email, 'hash' => $hash, 'hid' => $this->hotelId]);

            SessionManager::setFlash('success', 'Staff account created successfully.');
            $this->redirect('/user/staff');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error occurred.');
            $this->redirect('/user/staff');
        }
    }

    public function deleteStaff(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $staffId = (int)($postData['staff_id'] ?? 0);
        if (!$staffId) { $this->redirect('/user/staff'); return; }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM users WHERE id = :uid AND hotel_id = :hid AND role IN ('receptionist', 'housekeeper')");
            $stmt->execute(['uid' => $staffId, 'hid' => $this->hotelId]);
            
            SessionManager::setFlash('success', 'Staff account revoked.');
            $this->redirect('/user/staff');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to delete staff account.');
            $this->redirect('/user/staff');
        }
    }

    // =========================================================================
    // 6. SETTINGS & 2FA SECURITY
    // =========================================================================

    public function settings(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = Database::getConnection();
        
        $hotel = Database::table('hotels')->where('id', $this->hotelId)->first();
        $currentUser = Database::table('users')->where('id', $this->userId)->first();

        // Fetch Global Settings for pricing
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $settings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $this->render('user/settings', [
            'pageTitle'   => 'Account Settings',
            'hotel'       => $hotel,
            'currentUser' => $currentUser,
            'settings'    => $settings
        ], 'user_layout');
    }

    public function auditLogs(): void
    {
        $this->requireRole(['hotel_admin']);

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT a.*, u.full_name 
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.hotel_id = ?
            ORDER BY a.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$this->hotelId]);
        $logs = $stmt->fetchAll();

        $this->render('user/audit', [
            'pageTitle' => 'Audit Logs',
            'logs' => $logs
        ]);
    }

    public function updatePaymentSettings(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $keyId = strip_tags(trim($postData['phonepe_merchant_id'] ?? ''));
        $keySecret = strip_tags(trim($postData['phonepe_salt_key'] ?? ''));
        $env = strip_tags(trim($postData['phonepe_env'] ?? 'uat'));
        if (!in_array($env, ['uat', 'prod'])) {
            $env = 'uat';
        }

        try {
            Database::table('hotels')->where('id', $this->hotelId)->update([
                'phonepe_merchant_id' => $keyId,
                'phonepe_salt_key'    => $keySecret,
                'phonepe_env'         => $env
            ]);

            SessionManager::setFlash('success', 'Payment gateway credentials updated.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error occurred.');
            $this->redirect('/user/settings');
        }
    }

    public function updateOtaCredentials(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $updateData = [
            'mmt_api_key'         => strip_tags(trim($postData['mmt_api_key'] ?? '')),
            'booking_com_username'=> strip_tags(trim($postData['booking_com_username'] ?? '')),
            'agoda_api_key'       => strip_tags(trim($postData['agoda_api_key'] ?? '')),
            'cleartrip_api_key'   => strip_tags(trim($postData['cleartrip_api_key'] ?? '')),
            'yatra_api_key'       => strip_tags(trim($postData['yatra_api_key'] ?? '')),
            'expedia_username'    => strip_tags(trim($postData['expedia_username'] ?? '')),
            'easemytrip_api_key'  => strip_tags(trim($postData['easemytrip_api_key'] ?? '')),
            'paytm_api_key'       => strip_tags(trim($postData['paytm_api_key'] ?? '')),
            'airbnb_api_key'      => strip_tags(trim($postData['airbnb_api_key'] ?? '')),
            'tripadvisor_api_key' => strip_tags(trim($postData['tripadvisor_api_key'] ?? ''))
        ];

        if (!empty($postData['booking_com_password'])) {
            $updateData['booking_com_password'] = $postData['booking_com_password'];
        }
        if (!empty($postData['expedia_password'])) {
            $updateData['expedia_password'] = $postData['expedia_password'];
        }

        try {
            Database::table('hotels')->where('id', $this->hotelId)->update($updateData);
            SessionManager::setFlash('success', 'OTA Channel credentials updated securely.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to update credentials.');
            $this->redirect('/user/settings');
        }
    }

    public function updateProfile(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $propertyName = strip_tags(trim($postData['property_name'] ?? ''));
        $description = strip_tags(trim($postData['description'] ?? ''));
        $amenities = strip_tags(trim($postData['amenities'] ?? ''));
        $heroImage = $this->processImageUpload($_FILES['hero_image'] ?? null);

        if (empty($propertyName)) {
            SessionManager::setFlash('error', 'Property Name is required.');
            $this->redirect('/user/settings'); return;
        }

        try {
            $updateData = [
                'property_name' => $propertyName,
                'description'   => $description,
                'amenities'     => $amenities
            ];
            
            if (!empty($heroImage)) {
                $updateData['hero_image'] = $heroImage;
            }

            Database::table('hotels')->where('id', $this->hotelId)->update($updateData);
            $_SESSION['property_name'] = $propertyName;

            SessionManager::setFlash('success', 'Property profile updated successfully.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error updating profile.');
            $this->redirect('/user/settings');
        }
    }

    public function updatePassword(array $postData): void
    {
        $this->validateCsrf($postData);

        $currentPassword = $postData['current_password'] ?? '';
        $newPassword = $postData['new_password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || $newPassword !== $confirmPassword) {
            SessionManager::setFlash('error', 'Passwords do not match or are missing.');
            $this->redirect('/user/settings'); return;
        }

        if (strlen($newPassword) < 8) {
            SessionManager::setFlash('error', 'New password must be at least 8 characters.');
            $this->redirect('/user/settings'); return;
        }

        try {
            $user = Database::table('users')->where('id', $this->userId)->first();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                SessionManager::setFlash('error', 'Invalid current password.');
                $this->redirect('/user/settings'); return;
            }

            $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            Database::table('users')->where('id', $this->userId)->update(['password_hash' => $newHash]);

            Database::table('audit_logs')->insert([
                'user_id'     => $this->userId,
                'action'      => 'PASSWORD_CHANGE',
                'description' => 'User updated password',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            SessionManager::setFlash('success', 'Your password has been changed securely.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error changing password.');
            $this->redirect('/user/settings');
        }
    }

    public function setup2fa(): void
    {
        $this->requireRole(['super_admin', 'hotel_admin']);
        
        $user = Database::table('users')->where('id', $this->userId)->first();
        $otp = (string)random_int(100000, 999999);
        $_SESSION['pending_email_otp'] = $otp;
        
        $subject = "Your 2FA Setup Code - Syncro PMS";
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; padding: 25px; border: 1px solid #eef1f6; border-radius: 12px;'>
            <h2 style='color: #003366; text-align: center;'>Enable 2FA Security</h2>
            <p style='color: #555555; font-size: 15px;'>Hello {$user['name']},</p>
            <p style='color: #555555;'>Use the code below to finalize your Two-Factor Authentication setup:</p>
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin: 25px 0;'>
                <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #003366;'>{$otp}</span>
            </div>
            <p style='color: #888888; font-size: 12px; text-align: center;'>If you did not request this, please secure your account immediately.</p>
        </div>";

        EmailService::sendTransactionalEmail($user['email'], $subject, $htmlBody);

        $this->render('user/2fa_setup', [
            'pageTitle'  => 'Enable Email 2FA | Syncro',
            'email'      => $user['email']
        ], 'user_layout');
    }

    public function verifyAndEnable2fa(array $postData): void
    {
        $this->requireRole(['super_admin', 'hotel_admin']);
        
        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            SessionManager::setFlash('error', 'Security Violation: CSRF Token Invalid.');
            $this->redirect('/user/settings');
            return;
        }

        $code = preg_replace('/[^0-9]/', '', $postData['code'] ?? '');
        $expectedOtp = $_SESSION['pending_email_otp'] ?? '';

        if (empty($expectedOtp)) {
            SessionManager::setFlash('error', 'Session expired. Please click "Enable 2FA" again.');
            $this->redirect('/user/settings');
            return;
        }

        if ($code === $expectedOtp) {
            Database::table('users')
                ->where('id', $this->userId)
                ->update([
                    'two_factor_secret'  => 'EMAIL_OTP', 
                    'two_factor_enabled' => 1
                ]);
                
            unset($_SESSION['pending_email_otp']);
            SessionManager::setFlash('success', 'Email Two-Factor Authentication is now enabled!');
            $this->redirect('/user/settings');
        } else {
            SessionManager::setFlash('error', 'Invalid OTP code. Please check your email and try again.');
            $this->redirect('/user/settings/2fa/setup');
        }
    }

    public function disable2fa(array $postData): void
    {
        $this->requireRole(['super_admin', 'hotel_admin']);
        
        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            SessionManager::setFlash('error', 'Security Violation.');
            $this->redirect('/user/settings');
            return;
        }

        Database::table('users')
            ->where('id', $this->userId)
            ->update([
                'two_factor_secret'  => null,
                'two_factor_enabled' => 0
            ]);

        SessionManager::setFlash('success', 'Two-Factor Authentication has been disabled.');
        $this->redirect('/user/settings');
    }

    // =========================================================================
    // 7. SAAS SUBSCRIPTION RENEWAL (UPDATED TO PHONEPE)
    // =========================================================================

    public function renewInit(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $platformSettings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $platformSettings[$row['setting_key']] = $row['setting_value'];
        }

        $gatewayEnabled = ($platformSettings['payment_gateway_enabled'] ?? '0') === '1';

        if (!$gatewayEnabled) {
            $this->redirect('/user/settings'); // Failsafe if accessed manually
            return;
        }

        $months = (int)($postData['plan_months'] ?? 12);
        $amount = (int)($platformSettings["plan_{$months}_month"] ?? 10000);

        // Retrieve the Super Admin's PhonePe keys for collecting SaaS fees
        $merchantId = $platformSettings['phonepe_merchant_id'] ?? getenv('PHONEPE_MERCHANT_ID');
        $saltKey = $platformSettings['phonepe_salt_key'] ?? getenv('PHONEPE_SALT_KEY');

        if (!$merchantId || !$saltKey) {
            error_log("Renewal Error: PhonePe credentials missing from platform settings.");
            SessionManager::setFlash('error', 'SaaS Billing is temporarily unavailable.');
            $this->redirect('/user/settings');
            return;
        }

        $amountInPaise = $amount * 100;
        $merchantTransactionId = 'RENEW_' . $this->hotelId . '_' . time();
        $saltIndex = "1";
        $endpoint = "/pg/v1/pay";

        $payload = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'HID_' . $this->hotelId,
            'amount' => $amountInPaise,
            'redirectUrl' => "https://{$_SERVER['HTTP_HOST']}/user/settings/renew/verify",
            'redirectMode' => 'POST',
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ]
        ];

        $base64Payload = base64_encode(json_encode($payload));
        $checksum = hash('sha256', $base64Payload . $endpoint . $saltKey) . '###' . $saltIndex;

        $ch = curl_init('https://api.phonepe.com/apis/hermes/pg/v1/pay');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $base64Payload]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-VERIFY: ' . $checksum
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        $orderData = json_decode($response, true);

        if (isset($orderData['success']) && $orderData['success'] === true) {
            $_SESSION['pending_renewal'] = [
                'months'   => $months,
                'amount'   => $amount,
                'order_id' => $merchantTransactionId
            ];
            
            // Redirect user directly to PhonePe Hosted Checkout Page
            $paymentUrl = $orderData['data']['instrumentResponse']['redirectInfo']['url'];
            header("Location: " . $paymentUrl);
            exit;
        } else {
            SessionManager::setFlash('error', 'Failed to communicate with payment gateway.');
            $this->redirect('/user/settings');
            return;
        }
    }

    public function renewVerify(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        
        $merchantTransactionId = $postData['transactionId'] ?? '';
        $code = $postData['code'] ?? '';
        $pendingData = $_SESSION['pending_renewal'] ?? null;

        if (!$merchantTransactionId || !$pendingData) {
            SessionManager::setFlash('error', 'Payment verification failed.');
            $this->redirect('/user/settings');
            return;
        }

        if ($code !== 'PAYMENT_SUCCESS') {
            SessionManager::setFlash('error', 'Payment failed or was cancelled by user.');
            $this->redirect('/user/settings');
            return;
        }

        // 1. Fetch Super Admin PhonePe Keys
        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $platformSettings = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $platformSettings[$row['setting_key']] = $row['setting_value'];
        }
        
        $merchantId = $platformSettings['phonepe_merchant_id'] ?? getenv('PHONEPE_MERCHANT_ID');
        $saltKey = $platformSettings['phonepe_salt_key'] ?? getenv('PHONEPE_SALT_KEY');
        $saltIndex = "1";

        // 2. Server-to-Server Verification 
        $endpoint = "/pg/v1/status/" . $merchantId . "/" . $merchantTransactionId;
        $checksum = hash('sha256', $endpoint . $saltKey) . '###' . $saltIndex;

        $ch = curl_init("https://api.phonepe.com/apis/hermes" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-VERIFY: ' . $checksum,
            'X-MERCHANT-ID: ' . $merchantId
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $statusData = json_decode($response, true);

        if (isset($statusData['success']) && $statusData['success'] === true && $statusData['data']['state'] === 'COMPLETED') {
            try {
                $db->beginTransaction();

                $months = (int)$pendingData['months'];

                $stmt = $db->prepare("SELECT next_billing_date FROM hotels WHERE id = :hid");
                $stmt->execute(['hid' => $this->hotelId]);
                $hotel = $stmt->fetch();

                $currentBillingDate = strtotime($hotel['next_billing_date'] ?? 'now');
                $now = time();
                
                $baseDate = ($currentBillingDate > $now) ? $currentBillingDate : $now;
                $newBillingDate = date('Y-m-d', strtotime('+' . $months . ' months', $baseDate));
                $planName = $months === 12 ? 'Pro License (1 Year)' : "Pro License ({$months} Months)";

                $stmt = $db->prepare("UPDATE hotels SET next_billing_date = :billing, subscription_plan = :plan WHERE id = :hid");
                $stmt->execute(['billing' => $newBillingDate, 'plan' => $planName, 'hid' => $this->hotelId]);

                $bankTransactionId = $statusData['data']['transactionId'] ?? $merchantTransactionId;

                $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'SUBSCRIPTION_RENEWED', :desc, :ip)");
                $stmt->execute([
                    'uid'  => $_SESSION['user_id'],
                    'desc' => "Renewed license for {$months} months (₹{$pendingData['amount']}). PhonePe ID: {$bankTransactionId}",
                    'ip'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                $db->commit();
                unset($_SESSION['pending_renewal']);
                
                SessionManager::setFlash('success', 'Subscription successfully renewed!');
                $this->redirect('/user/settings');

            } catch (\Exception $e) {
                if (isset($db) && $db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Renewal Error: " . $e->getMessage());
                SessionManager::setFlash('error', 'Database error during renewal processing.');
                $this->redirect('/user/settings');
            }
        } else {
            SessionManager::setFlash('error', 'Payment verification failed at the bank level.');
            $this->redirect('/user/settings');
        }
    }

    public function renewOffline(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        
        $planMonths = (int)($postData['plan_months'] ?? 1);
        
        $db = \Syncro\Models\Database::getConnection();
        $db->beginTransaction();

        try {
            // 1. Log it in the security audit trail
            $desc = "Requested a {$planMonths} month subscription extension via offline payment.";
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'OFFLINE_RENEWAL_REQUEST', :desc, :ip)");
            $stmt->execute([
                'uid' => $this->userId,
                'desc' => $desc,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            // 2. Automatically generate a Support Ticket for the Super Admin
            $subject = "Billing Request: Offline Renewal ({$planMonths} Months)";
            $message = "SYSTEM AUTOMATED REQUEST:\n\nWe would like to renew our SaaS License for {$planMonths} months via offline payment. Please provide your bank account details or UPI QR code so we can process the transaction.";

            // Create the main ticket
            $stmt = $db->prepare("INSERT INTO support_tickets (hotel_id, user_id, subject, status, created_at, updated_at) VALUES (:hid, :uid, :sub, 'open', NOW(), NOW())");
            $stmt->execute([
                'hid' => $this->hotelId,
                'uid' => $this->userId,
                'sub' => $subject
            ]);
            $ticketId = $db->lastInsertId();

            // Insert the initial message body
            $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin_reply) VALUES (:tid, :uid, :msg, 0)");
            $stmt->execute([
                'tid' => $ticketId,
                'uid' => $this->userId,
                'msg' => $message
            ]);

            $db->commit();

            \Syncro\Security\SessionManager::setFlash('success', 'Renewal request created successfully. Our billing team will reply here shortly.');
            
            // Redirect directly to the newly created ticket!
            $this->redirect('/user/support/view?id=' . $ticketId);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            \Syncro\Security\SessionManager::setFlash('error', 'Failed to send request. Please contact support manually.');
            $this->redirect('/user/settings');
        }
    }

    // =========================================================================
    // 8. PROMO CODES
    // =========================================================================

    public function promoCodes(): void
    {
        $this->requireRole(['hotel_admin']);

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE hotel_id = :hid ORDER BY id DESC");
        $stmt->execute(['hid' => $this->hotelId]);
        $promoCodes = $stmt->fetchAll();

        $this->render('user/promo_codes', [
            'pageTitle'  => 'Manage Promo Codes',
            'promoCodes' => $promoCodes,
            'success'    => $_GET['success'] ?? null,
            'error'      => $_GET['error'] ?? null
        ], 'user_layout');
    }

    public function storePromoCode(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);

        // Sanitize and format
        $code = strtoupper(trim(strip_tags($postData['code'] ?? '')));
        $code = preg_replace('/[^A-Z0-9]/', '', $code); // Alphanumeric only
        $type = in_array($postData['discount_type'] ?? '', ['percentage', 'fixed']) ? $postData['discount_type'] : 'percentage';
        $value = (float)($postData['discount_value'] ?? 0);
        $validUntil = !empty($postData['valid_until']) ? $postData['valid_until'] : null;

        if (empty($code) || $value <= 0) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Valid code and discount amount are required.'));
            return;
        }

        $db = Database::getConnection();
        
        // Prevent duplicate active codes
        $stmtCheck = $db->prepare("SELECT id FROM promo_codes WHERE code = :code AND hotel_id = :hid");
        $stmtCheck->execute(['code' => $code, 'hid' => $this->hotelId]);
        if ($stmtCheck->fetch()) {
            $this->redirect('/user/promo-codes?error=' . urlencode('This promo code already exists.'));
            return;
        }

        $stmt = $db->prepare("INSERT INTO promo_codes (hotel_id, code, discount_type, discount_value, valid_until, is_active) VALUES (:hid, :code, :type, :val, :until, 1)");
        
        try {
            $stmt->execute([
                'hid'   => $this->hotelId,
                'code'  => $code,
                'type'  => $type,
                'val'   => $value,
                'until' => $validUntil
            ]);
            $this->redirect('/user/promo-codes?success=created');
        } catch (Exception $e) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Database error while saving promo code.'));
        }
    }

    public function deletePromoCode(array $postData): void
    {
        $this->requireRole(['hotel_admin']);
        $this->validateCsrf($postData);
        
        // Use ID from either the POST body or extract from the end of the URL depending on how you route it
        $id = (int)($postData['id'] ?? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

        if ($id <= 0) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Invalid promo code ID.'));
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $id, 'hid' => $this->hotelId]);
            
            $this->redirect('/user/promo-codes?success=deleted');
        } catch (Exception $e) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Database error while deleting.'));
        }
    }
    
    public function stopImpersonating(): void
    {
        if (!empty($_SESSION['impersonator_id'])) {
            // Restore Super Admin Session
            $_SESSION['user_id'] = $_SESSION['impersonator_id'];
            $_SESSION['role'] = 'super_admin';
            
            // Clear out the Hotel data
            unset($_SESSION['hotel_id']);
            unset($_SESSION['property_name']);
            unset($_SESSION['impersonator_id']);
            
            \Syncro\Security\SessionManager::setFlash('success', 'Impersonation ended. Welcome back, Super Admin.');
            $this->redirect('/admin/hotels');
        } else {
            $this->redirect('/user/dashboard');
        }
    }
        
    // =========================================================================
    // POS & ANCILLARY REVENUE
    // =========================================================================

    public function posDashboard(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $db = Database::getConnection();

        // Get in-house bookings
        $stmt = $db->prepare("
            SELECT b.id, b.guest_name, r.name as room_name, rm.room_number 
            FROM bookings b
            JOIN room_types r ON b.room_type_id = r.id
            LEFT JOIN rooms rm ON b.assigned_room_id = rm.id
            WHERE b.hotel_id = :hid AND b.status = 'confirmed' AND b.check_in <= CURDATE() AND b.check_out > CURDATE()
            ORDER BY rm.room_number ASC, b.guest_name ASC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $activeBookings = $stmt->fetchAll();

        // Get recent sales
        $stmt = $db->prepare("
            SELECT a.*, b.guest_name, rm.room_number
            FROM ancillary_sales a
            JOIN bookings b ON a.booking_id = b.id
            LEFT JOIN rooms rm ON b.assigned_room_id = rm.id
            WHERE a.hotel_id = :hid
            ORDER BY a.sale_date DESC LIMIT 50
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $recentSales = $stmt->fetchAll();

        $this->render('user/pos', [
            'pageTitle' => 'Point of Sale (POS)',
            'activeBookings' => $activeBookings,
            'recentSales' => $recentSales
        ], 'user_layout');
    }

    public function addAncillarySale(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $this->validateCsrf($postData);

        $bookingId = (int)($postData['booking_id'] ?? 0);
        $itemName = trim($postData['item_name'] ?? '');
        $quantity = (int)($postData['quantity'] ?? 1);
        $price = (float)($postData['price'] ?? 0);

        if (!$bookingId || empty($itemName) || $quantity < 1 || $price <= 0) {
            SessionManager::setFlash('error', 'Invalid POS data provided.');
            $this->redirect('/user/pos');
            return;
        }

        $totalAmount = $quantity * $price;

        try {
            $db = Database::getConnection();
            
            // Verify booking belongs to this hotel
            $stmt = $db->prepare("SELECT id FROM bookings WHERE id = :bid AND hotel_id = :hid");
            $stmt->execute(['bid' => $bookingId, 'hid' => $this->hotelId]);
            if (!$stmt->fetch()) {
                throw new \Exception("Invalid booking selected.");
            }

            $stmt = $db->prepare("
                INSERT INTO ancillary_sales (hotel_id, booking_id, item_name, quantity, price, total_amount)
                VALUES (:hid, :bid, :item, :qty, :price, :total)
            ");
            $stmt->execute([
                'hid' => $this->hotelId,
                'bid' => $bookingId,
                'item' => $itemName,
                'qty' => $quantity,
                'price' => $price,
                'total' => $totalAmount
            ]);

            SessionManager::setFlash('success', 'Sale recorded successfully.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('/user/pos');
    }

    public function getAnalyticsData(): void
    {
        header('Content-Type: application/json');
        $db = Database::getConnection();
        
        try {
            // 1. Last 7 days revenue (Chart 1)
            $stmt = $db->prepare("
                SELECT DATE(created_at) as date, SUM(total_price) as revenue 
                FROM bookings 
                WHERE hotel_id = :hid 
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute(['hid' => $this->hotelId]);
            $revenueData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 2. Revenue by Source (Chart 2)
            $stmt = $db->prepare("
                SELECT source, SUM(total_price) as revenue 
                FROM bookings 
                WHERE hotel_id = :hid 
                GROUP BY source
            ");
            $stmt->execute(['hid' => $this->hotelId]);
            $sourceData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 3. KPIs
            // Occupancy Rate (Booked Rooms / Total Rooms for today)
            $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE hotel_id = :hid");
            $stmt->execute(['hid' => $this->hotelId]);
            $totalRooms = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = :hid AND status IN ('confirmed', 'checked_in') AND CURDATE() BETWEEN check_in AND check_out");
            $stmt->execute(['hid' => $this->hotelId]);
            $occupiedRooms = (int)$stmt->fetchColumn();

            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

            // ADR (Average Daily Rate) - average price of active bookings
            $stmt = $db->prepare("SELECT AVG(total_price) FROM bookings WHERE hotel_id = :hid AND status IN ('confirmed', 'checked_in')");
            $stmt->execute(['hid' => $this->hotelId]);
            $adr = round((float)$stmt->fetchColumn(), 2);

            echo json_encode([
                'success' => true,
                'data' => [
                    'revenue_timeline' => $revenueData,
                    'revenue_sources' => $sourceData,
                    'kpis' => [
                        'occupancy_rate' => $occupancyRate,
                        'adr' => $adr,
                        'revpar' => round(($occupancyRate / 100) * $adr, 2)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }

    public function generateApiToken(): void
    {
        $this->requireRole('hotel_admin');
        
        $token = bin2hex(random_bytes(32)); // 64 char token
        
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE hotels SET api_token = ? WHERE id = ?");
        $stmt->execute([$token, $this->hotelId]);
        
        AuditLogger::log($this->hotelId, $_SESSION['user_id'] ?? null, 'SECURITY_UPDATE', 'Generated new Syncro API Token.');
        
        $this->redirectWithMessage('/user/settings', 'API Token generated successfully.', 'success');
    }
}