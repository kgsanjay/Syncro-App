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

class HotelPromoController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function promoCodes(): void
    {
        $this->requireRole(['hotel_admin']);

        $db = $this->db->getPDO();
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

        $db = $this->db->getPDO();
        
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

        // Use ID from either the POST body or extract from the end of the URL depending on how you route it
        $id = (int)($postData['id'] ?? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

        if ($id <= 0) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Invalid promo code ID.'));
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $id, 'hid' => $this->hotelId]);
            
            $this->redirect('/user/promo-codes?success=deleted');
        } catch (Exception $e) {
            $this->redirect('/user/promo-codes?error=' . urlencode('Database error while deleting.'));
        }
    }

}
