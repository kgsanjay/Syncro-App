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

class HotelRateController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function rateManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = $this->db->getPDO();
        
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
            $db = $this->db->getPDO();
            
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
            $db = $this->db->getPDO();
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

        $ruleId = (int)($postData['rule_id'] ?? 0);

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE pricing_rules SET status = IF(status='active', 'inactive', 'active') WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $ruleId, 'hid' => $this->hotelId]);
            SessionManager::setFlash('success', 'Rule status toggled.');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Database error.');
        }
        $this->redirect('/user/rates');
    }

}
