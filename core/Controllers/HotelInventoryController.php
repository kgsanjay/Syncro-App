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

class HotelInventoryController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function inventory(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = $this->db->getPDO();

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

    public function calendar(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $db = $this->db->getPDO();

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

    public function channelManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = $this->db->getPDO();
        
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

        $roomId = (int)($postData['room_type_id'] ?? 0);
        $channelName = strip_tags(trim($postData['channel_name'] ?? ''));
        $otaRoomCode = strip_tags(trim($postData['ota_room_code'] ?? ''));

        if (!$roomId || empty($channelName) || empty($otaRoomCode)) { 
            SessionManager::setFlash('error', 'Please fill all required fields.');
            $this->redirect('/user/channel-manager'); 
            return; 
        }

        try {
            $db = $this->db->getPDO();
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

        $mappingId = (int)($postData['mapping_id'] ?? 0);
        if (!$mappingId) { $this->redirect('/user/channel-manager'); return; }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("DELETE o FROM ota_mappings o JOIN room_types r ON o.room_type_id = r.id WHERE o.id = :mid AND r.hotel_id = :hid");
            $stmt->execute(['mid' => $mappingId, 'hid' => $this->hotelId]);
            
            SessionManager::setFlash('success', 'OTA Mapping deleted.');
            $this->redirect('/user/channel-manager');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to delete mapping.');
            $this->redirect('/user/channel-manager');
        }
    }

}
