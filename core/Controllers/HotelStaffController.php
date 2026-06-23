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

class HotelStaffController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function staffManager(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = $this->db->getPDO();

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

        $name = strip_tags(trim($postData['name'] ?? ''));
        $email = trim(filter_var($postData['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $postData['password'] ?? '';
        $role = $postData['role'] ?? '';

        if (empty($name) || empty($email) || empty($password) || !in_array($role, ['receptionist', 'housekeeper'])) {
            SessionManager::setFlash('error', 'Invalid input provided.');
            $this->redirect('/user/staff'); return;
        }

        try {
            $db = $this->db->getPDO();
            
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

        $staffId = (int)($postData['staff_id'] ?? 0);
        if (!$staffId) { $this->redirect('/user/staff'); return; }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("DELETE FROM users WHERE id = :uid AND hotel_id = :hid AND role IN ('receptionist', 'housekeeper')");
            $stmt->execute(['uid' => $staffId, 'hid' => $this->hotelId]);
            
            SessionManager::setFlash('success', 'Staff account revoked.');
            $this->redirect('/user/staff');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'Failed to delete staff account.');
            $this->redirect('/user/staff');
        }
    }

}
