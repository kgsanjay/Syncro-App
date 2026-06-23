<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use PDO;
use Exception;

class AdminController extends BaseController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        SessionManager::start();
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }

        if ($_SESSION['role'] !== 'super_admin') {
            $this->logUnauthorizedAccess();
            $this->redirect('/user/dashboard');
            exit;
        }
    }

    // =========================================================================
    // 1. DASHBOARD & BILLING METRICS
    // =========================================================================

    public function dashboard(): void
    {
        $db = $this->db->getPDO();

        // 1. Get Hotel Statistics & MRR
        $stmt = $db->query("
            SELECT h.*, u.name as admin_name, u.email as admin_email 
            FROM hotels h 
            LEFT JOIN users u ON h.user_id = u.id 
            ORDER BY h.created_at DESC
        ");
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mrr = 0;
        $activeHotels = 0;
        
        foreach ($hotels as $hotel) {
            if ($hotel['status'] === 'active') {
                $activeHotels++;
                $name = strtolower($hotel['subscription_plan'] ?? '');
                
                if (strpos($name, '1 month') !== false) {
                    $mrr += 2000;
                } elseif (strpos($name, '3 month') !== false) {
                    $mrr += (4000 / 3);
                } elseif (strpos($name, '6 month') !== false) {
                    $mrr += (6000 / 6);
                } elseif (strpos($name, '1 year') !== false || strpos($name, '12 month') !== false) {
                    $mrr += (10000 / 12);
                } else {
                    $mrr += 2000; 
                }
            }
        }

        // 2. Calculate PLATFORM GMV (Total value of all bookings processed)
        $stmt = $db->query("SELECT SUM(total_price) FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')");
        $platformGmv = (float)$stmt->fetchColumn();

        // 3. Get Active Broadcasts
        $announcements = $this->db->getTable('announcements')->orderBy('created_at', 'DESC')->get();

        $this->render('admin/dashboard', [
            'pageTitle'     => 'SaaS Command Center',
            'totalHotels'   => count($hotels),
            'activeHotels'  => $activeHotels,
            'mrr'           => round($mrr),
            'platformGmv'   => $platformGmv,
            'recentHotels'  => array_slice($hotels, 0, 5), // Pass only the 5 most recent for the table
            'announcements' => $announcements,
            'csrfToken'     => $_SESSION['csrf_token'] ?? ''
        ], 'admin_layout');
    }

    public function createBroadcast(array $postData): void
    {

        $message = strip_tags(trim($postData['message'] ?? ''));
        $type = $postData['type'] ?? 'info';

        if (!empty($message)) {
            $this->db->getTable('announcements')->insert([
                'title' => 'System Update',
                'message' => $message,
                'type' => $type,
                'is_active' => 1
            ]);
            SessionManager::setFlash('success', 'Broadcast sent to all hotels successfully!');
        }
        $this->redirect('/admin/dashboard');
    }

    public function deleteBroadcast(array $postData): void
    {

        $id = (int)($postData['id'] ?? 0);
        
        if ($id) {
            $this->db->getTable('announcements')->where('id', $id)->delete();
            SessionManager::setFlash('success', 'Broadcast removed.');
        }
        $this->redirect('/admin/dashboard');
    }

    // =========================================================================
    // 2. PROPERTY ONBOARDING
    // =========================================================================

    public function createHotel(): void
    {
        $this->render('admin/hotel_create', [
            'pageTitle' => 'Onboard Property'
        ], 'admin_layout');
    }

    public function storeHotel(array $postData): void
    {
        $dataToValidate = $postData;
        $dataToValidate['admin_email'] = $postData['admin_email'] ?? $postData['email'] ?? '';
        $dataToValidate['admin_name'] = $postData['admin_name'] ?? 'Hotel Admin';

        $validated = \Syncro\Security\Validator::validate($dataToValidate, [
            'property_name' => 'required|min:2|max:150',
            'admin_name' => 'required|max:100',
            'admin_email' => 'required|email'
        ]);

        $propertyName = $validated['property_name'];
        $slug = strip_tags(trim($postData['slug'] ?? strtolower(str_replace(' ', '-', $propertyName))));
        $adminName = $validated['admin_name'];
        $email = $validated['admin_email'];

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                throw new \Exception("A user with this email already exists.");
            }

            $rawPassword = !empty($postData['admin_password']) ? $postData['admin_password'] : bin2hex(random_bytes(6));
            $hashedPassword = password_hash($rawPassword, PASSWORD_ARGON2ID);

            $stmt = $db->prepare("INSERT INTO users (role, name, email, password_hash, status) VALUES ('hotel_admin', :name, :email, :hash, 'active')");
            $stmt->execute(['name' => $adminName, 'email' => $email, 'hash' => $hashedPassword]);
            $userId = (int)$db->lastInsertId();

            $rawApiKey = 'syncro_live_' . bin2hex(random_bytes(16));
            $apiSecret = bin2hex(random_bytes(32));
            $hashedApiKey = hash('sha256', $rawApiKey);

            $nextBillingDate = date('Y-m-d', strtotime('+14 days'));

            $stmt = $db->prepare("
                INSERT INTO hotels (user_id, property_name, slug, api_key, api_secret, status, next_billing_date, subscription_plan) 
                VALUES (:uid, :name, :slug, :api_key, :api_secret, 'active', :billing, 'Pro License (Trial)')
            ");
            $stmt->execute([
                'uid' => $userId,
                'name' => $propertyName,
                'slug' => $slug,
                'api_key' => $hashedApiKey,
                'api_secret' => $apiSecret,
                'billing' => $nextBillingDate
            ]);
            $hotelId = (int)$db->lastInsertId();

            $stmt = $db->prepare("UPDATE users SET hotel_id = :hid WHERE id = :uid");
            $stmt->execute(['hid' => $hotelId, 'uid' => $userId]);

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'HOTEL_ONBOARDED', :desc, :ip)");
            $stmt->execute([
                'uid' => $_SESSION['user_id'],
                'desc' => "Onboarded Hotel ID: $hotelId ($propertyName)",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();

            $_SESSION['flash_success'] = "Property Onboarded Successfully!";
            $_SESSION['flash_credentials'] = [
                'email' => $email,
                'password' => $rawPassword,
                'api_key' => $rawApiKey,
                'api_secret' => $apiSecret
            ];

            $this->redirect('/admin/hotels');

        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("Hotel Onboarding Error: " . $e->getMessage());
            $this->redirect('/admin/dashboard?error=system');
        }
    }

    // =========================================================================
    // 3. PROPERTY MANAGEMENT & EDITING
    // =========================================================================

    public function hotels(): void
    {
        $db = $this->db->getPDO();
        
        $stmt = $db->query("
            SELECT h.id, h.property_name, h.slug, h.status, h.created_at, u.email as admin_email 
            FROM hotels h 
            JOIN users u ON h.user_id = u.id 
            ORDER BY h.created_at DESC
        ");
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/hotels', [
            'pageTitle' => 'Property Management',
            'hotels'    => $hotels
        ], 'admin_layout');
    }

    public function editHotel($id): void
    {
        $hotelId = (int)$id;
        if (!$hotelId) {
            $this->redirect('/admin/hotels');
            return;
        }

        $db = $this->db->getPDO();
        
        $stmt = $db->prepare("
            SELECT h.*, u.email 
            FROM hotels h 
            JOIN users u ON h.user_id = u.id 
            WHERE h.id = :id
        ");
        $stmt->execute(['id' => $hotelId]);
        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$hotel) {
            $this->redirect('/admin/hotels');
            return;
        }

        $stmt = $db->prepare("
            SELECT m.channel_name, m.ota_room_code, m.sync_status, m.last_sync_time, r.name as local_room
            FROM ota_mappings m
            JOIN room_types r ON m.room_type_id = r.id
            WHERE r.hotel_id = :hid
            ORDER BY m.last_sync_time DESC
        ");
        $stmt->execute(['hid' => $hotelId]);
        $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/hotel_edit', [
            'pageTitle' => 'Manage: ' . $hotel['property_name'],
            'hotel'     => $hotel,
            'mappings'  => $mappings
        ], 'admin_layout');
    }

    public function updateHotel(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        
        // Handle custom offline activation logic if passing from pending_approval
        $status = $postData['status'] ?? '';
        if ($status !== 'suspended' && $status !== 'pending_approval') {
            $status = 'active';
        }

        if (!$hotelId) {
            $this->redirect('/admin/hotels');
            return;
        }

        $db = $this->db->getPDO();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE hotels SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $hotelId]);

            // For 'pending_approval', users are stored as 'pending'
            $userStatus = ($status === 'pending_approval') ? 'pending' : $status;
            
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = (SELECT user_id FROM hotels WHERE id = :hid)");
            $stmt->execute(['status' => $userStatus, 'hid' => $hotelId]);

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'HOTEL_STATUS_CHANGED', :desc, :ip)");
            $stmt->execute([
                'uid' => $_SESSION['user_id'],
                'desc' => "Hotel ID {$hotelId} status changed to {$status}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&success=1');

        } catch (\Exception $e) {
            $db->rollBack();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=1');
        }
    }

    // =========================================================================
    // 4. SAAS BILLING ENGINE 
    // =========================================================================

    public function extendSubscription(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        if (!$hotelId) { $this->redirect('/admin/dashboard'); return; }

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction(); 

            $stmt = $db->prepare("SELECT next_billing_date FROM hotels WHERE id = :hid");
            $stmt->execute(['hid' => $hotelId]);
            $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentDate = $hotel['next_billing_date'] ? strtotime($hotel['next_billing_date']) : time();
            $baseDate = ($currentDate > time()) ? $currentDate : time();
            $newDate = date('Y-m-d', strtotime('+1 month', $baseDate));

            $stmt = $db->prepare("
                UPDATE hotels 
                SET next_billing_date = :nd, status = 'active', subscription_plan = 'Pro License (Paid)' 
                WHERE id = :hid
            ");
            $stmt->execute(['nd' => $newDate, 'hid' => $hotelId]);

            // Ensure user is also active if they were pending
            $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE hotel_id = :hid AND role = 'hotel_admin'");
            $stmt->execute(['hid' => $hotelId]);

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'SUBSCRIPTION_MANUAL_EXTENSION', :desc, :ip)");
            $stmt->execute([
                'uid' => $_SESSION['user_id'],
                'desc' => "Super Admin manually extended billing date for Hotel ID {$hotelId} to {$newDate}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();

            $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/dashboard';
            $separator = parse_url($referer, PHP_URL_QUERY) ? '&' : '?';
            $this->redirect($referer . $separator . 'success=extended');
        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->redirect('/admin/dashboard?error=system');
        }
    }
    
    public function updateHotelBilling(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        $plan = strip_tags(trim($postData['subscription_plan'] ?? ''));
        $billingDate = $postData['next_billing_date'] ?? '';

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE hotels SET subscription_plan = :plan, next_billing_date = :date WHERE id = :hid");
            $stmt->execute(['plan' => $plan, 'date' => $billingDate, 'hid' => $hotelId]);

            SessionManager::setFlash('success', 'Billing and subscription details updated.');
            $this->redirect('/admin/hotels/edit?id=' . $hotelId);
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to update billing details.');
            $this->redirect('/admin/hotels/edit?id=' . $hotelId);
        }
    }

    public function toggleStatus(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        $action = $postData['action'] ?? '';
        $newStatus = ($action === 'suspend') ? 'suspended' : 'active';

        if (!$hotelId) { $this->redirect('/admin/dashboard'); return; }

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction(); 

            $stmt = $db->prepare("UPDATE hotels SET status = :status WHERE id = :hid");
            $stmt->execute(['status' => $newStatus, 'hid' => $hotelId]);

            $userStatus = ($newStatus === 'suspended') ? 'suspended' : 'active';
            $stmt = $db->prepare("UPDATE users SET status = :status WHERE hotel_id = :hid");
            $stmt->execute(['status' => $userStatus, 'hid' => $hotelId]);

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'STATUS_TOGGLE', :desc, :ip)");
            $stmt->execute([
                'uid' => $_SESSION['user_id'],
                'desc' => "Super Admin toggled Hotel ID {$hotelId} status to {$newStatus}",
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();

            $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/dashboard';
            $separator = parse_url($referer, PHP_URL_QUERY) ? '&' : '?';
            $this->redirect($referer . $separator . 'success=status_updated');
        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->redirect('/admin/dashboard?error=system');
        }
    }

    // =========================================================================
    // 5. SECURITY
    // =========================================================================

    private function logUnauthorizedAccess(): void
    {
        if (!empty($_SESSION['user_id'])) {
            try {
                $db = $this->db->getPDO();
                $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'SECURITY_VIOLATION', 'Attempted unauthorized access to Super Admin portal', :ip)");
                $stmt->execute([
                    'uid' => $_SESSION['user_id'],
                    'ip'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);
            } catch (\Exception $e) {}
        }
    }

    // =========================================================================
    // 6. SUPER ADMIN "FULL CONTROL" FEATURES
    // =========================================================================

    public function updateHotelDetails(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        $propertyName = strip_tags(trim($postData['property_name'] ?? ''));
        $slug = strip_tags(trim($postData['slug'] ?? ''));
        $email = trim(filter_var($postData['admin_email'] ?? '', FILTER_SANITIZE_EMAIL));

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE hotels SET property_name = :name, slug = :slug WHERE id = :hid");
            $stmt->execute(['name' => $propertyName, 'slug' => $slug, 'hid' => $hotelId]);

            $stmt = $db->prepare("UPDATE users SET email = :email WHERE hotel_id = :hid AND role = 'hotel_admin'");
            $stmt->execute(['email' => $email, 'hid' => $hotelId]);

            $db->commit();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&success=details_updated');
        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=1');
        }
    }

    public function forcePasswordReset(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);
        $newPassword = $postData['new_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=password_length');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction();

            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            
            $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE hotel_id = :hid AND role = 'hotel_admin'");
            $stmt->execute(['hash' => $hashedPassword, 'hid' => $hotelId]);

            $logStmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'SUPER_ADMIN_FORCED_RESET', :desc, :ip)");
            $logStmt->execute([
                'uid'  => $_SESSION['user_id'],
                'desc' => "Super Admin forcibly reset password for Hotel ID: {$hotelId}",
                'ip'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&success=password_reset');
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=1');
        }
    }

    public function impersonateHotel(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("
                SELECT u.id, u.name, h.property_name 
                FROM users u 
                JOIN hotels h ON u.hotel_id = h.id 
                WHERE h.id = :hid AND u.role = 'hotel_admin' LIMIT 1
            ");
            $stmt->execute(['hid' => $hotelId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                SessionManager::regenerate();

                $logStmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'IMPERSONATION_STARTED', :desc, :ip)");
                $logStmt->execute([
                    'uid' => $_SESSION['user_id'],
                    'desc' => "Super Admin initiated impersonation session for Hotel ID {$hotelId}",
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ]);

                $_SESSION['impersonator_id'] = $_SESSION['user_id'];
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['hotel_id'] = $hotelId;
                $_SESSION['role'] = 'hotel_admin';
                $_SESSION['name'] = $user['name'];
                $_SESSION['property_name'] = $user['property_name'];

                $this->redirect('/user/dashboard');
            } else {
                $this->redirect('/admin/hotels');
            }
        } catch (\Exception $e) {
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=1');
        }
    }

    public function deleteHotel(array $postData): void
    {

        $hotelId = (int)($postData['hotel_id'] ?? 0);

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction();
            
            $db->prepare("DELETE FROM pos_charges WHERE booking_id IN (SELECT id FROM bookings WHERE hotel_id = :hid)")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM bookings WHERE hotel_id = :hid")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM inventory_tape WHERE room_type_id IN (SELECT id FROM room_types WHERE hotel_id = :hid)")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM ota_mappings WHERE room_type_id IN (SELECT id FROM room_types WHERE hotel_id = :hid)")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM rooms WHERE hotel_id = :hid")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM room_types WHERE hotel_id = :hid")->execute(['hid' => $hotelId]);
            
            $db->prepare("DELETE FROM users WHERE hotel_id = :hid")->execute(['hid' => $hotelId]);
            $db->prepare("DELETE FROM hotels WHERE id = :hid")->execute(['hid' => $hotelId]);

            $db->commit();
            $this->redirect('/admin/hotels?success=deleted');
        } catch (\Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log("Hotel Deletion Error: " . $e->getMessage());
            $this->redirect('/admin/hotels/edit?id=' . $hotelId . '&error=delete_failed');
        }
    }
    
    // =========================================================================
    // 7. SUPER ADMIN HELPDESK INBOX
    // =========================================================================

    public function supportInbox(): void
    {
        $db = $this->db->getPDO();
        
        $stmt = $db->query("
            SELECT t.*, h.property_name, u.name as user_name 
            FROM support_tickets t
            JOIN hotels h ON t.hotel_id = h.id
            JOIN users u ON t.user_id = u.id
            ORDER BY FIELD(t.status, 'open', 'in_progress', 'waiting_on_customer', 'resolved', 'closed'), t.updated_at DESC
        ");
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/support_index', [
            'pageTitle' => 'Support Inbox | Syncro Command Center',
            'tickets' => $tickets
        ], 'admin_layout');
    }

    public function supportView(array $getData): void
    {
        $id = (int)($getData['id'] ?? 0);
        if (!$id) {
            $this->redirect('/admin/support');
            return;
        }

        $db = $this->db->getPDO();
        
        $stmt = $db->prepare("
            SELECT t.*, h.property_name, u.name as user_name, u.email as user_email
            FROM support_tickets t
            JOIN hotels h ON t.hotel_id = h.id
            JOIN users u ON t.user_id = u.id
            WHERE t.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $this->redirect('/admin/support');
            return;
        }

        $stmt = $db->prepare("
            SELECT r.*, u.name as sender_name 
            FROM ticket_replies r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.ticket_id = :tid 
            ORDER BY r.created_at ASC
        ");
        $stmt->execute(['tid' => $id]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/support_view', [
            'pageTitle' => 'Ticket #' . $id . ' | Support Inbox',
            'ticket' => $ticket,
            'replies' => $replies
        ], 'admin_layout');
    }

    public function supportReply(array $postData): void
    {

        $ticketId = (int)($postData['ticket_id'] ?? 0);
        $message = strip_tags(trim($postData['message'] ?? ''));

        if (!$ticketId || empty($message)) {
            SessionManager::setFlash('error', 'Message cannot be empty.');
            $this->redirect('/admin/support/view?id=' . $ticketId);
            return;
        }

        $db = $this->db->getPDO();
        $db->beginTransaction();

        try {
            // PROCESS THE IMAGE UPLOAD FIRST
            $attachmentPath = $this->handleSecureImageUpload($_FILES['attachment'] ?? null);

            // Insert admin reply with the attachment path
            $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin_reply, attachment_path) VALUES (:tid, :uid, :msg, 1, :path)");
            $stmt->execute([
                'tid' => $ticketId,
                'uid' => $_SESSION['user_id'],
                'msg' => $message,
                'path' => $attachmentPath
            ]);

            // Update ticket status to 'waiting_on_customer'
            $stmt = $db->prepare("UPDATE support_tickets SET status = 'waiting_on_customer', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $ticketId]);

            $db->commit();
            SessionManager::setFlash('success', 'Reply sent to the customer successfully.');
            $this->redirect('/admin/support/view?id=' . $ticketId);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/admin/support/view?id=' . $ticketId);
        }
    }

    public function supportChangeStatus(array $postData): void
    {

        $ticketId = (int)($postData['ticket_id'] ?? 0);
        $status = $postData['status'] ?? '';

        $validStatuses = ['open', 'in_progress', 'waiting_on_customer', 'resolved', 'closed'];
        if (!$ticketId || !in_array($status, $validStatuses)) {
            $this->redirect('/admin/support/view?id=' . $ticketId);
            return;
        }

        $db = $this->db->getPDO();
        $stmt = $db->prepare("UPDATE support_tickets SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $ticketId]);

        SessionManager::setFlash('success', 'Ticket status updated to ' . str_replace('_', ' ', $status));
        $this->redirect('/admin/support/view?id=' . $ticketId);
    }

    // =========================================================================
    // 8. PLATFORM SETTINGS & SECURITY
    // =========================================================================

    public function settings(): void
    {
        $db = $this->db->getPDO();
        
        // Fetch all global settings
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->render('admin/settings', [
            'pageTitle' => 'Platform Settings',
            'settings'  => $settings,
            'currentUser' => $currentUser
        ], 'admin_layout');
    }

    public function updateSettings(array $postData): void
    {

        $db = $this->db->getPDO();

        // UPDATED: Now dynamically stores PhonePe keys instead of Razorpay
        $settingsToUpdate = [
            'payment_gateway_enabled' => isset($postData['payment_gateway_enabled']) ? '1' : '0',
            'phonepe_merchant_id'     => strip_tags(trim($postData['phonepe_merchant_id'] ?? '')),
            'phonepe_salt_key'        => strip_tags(trim($postData['phonepe_salt_key'] ?? '')),
            'plan_1_month'            => (int)($postData['plan_1_month'] ?? 2000),
            'plan_3_month'            => (int)($postData['plan_3_month'] ?? 4000),
            'plan_6_month'            => (int)($postData['plan_6_month'] ?? 6000),
            'plan_12_month'           => (int)($postData['plan_12_month'] ?? 10000),
        ];

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val");
            
            foreach ($settingsToUpdate as $key => $val) {
                $stmt->execute(['key' => $key, 'val' => (string)$val]);
            }
            
            $db->commit();
            
            // Log this critical change
            $logStmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'PLATFORM_SETTINGS_UPDATED', 'Super Admin updated PhonePe credentials & SaaS pricing.', :ip)");
            $logStmt->execute([
                'uid' => $_SESSION['user_id'],
                'ip'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            SessionManager::setFlash('success', 'Global platform settings updated successfully.');
            $this->redirect('/admin/settings');
        } catch (\Exception $e) {
            $db->rollBack();
            SessionManager::setFlash('error', 'Database error updating settings.');
            $this->redirect('/admin/settings');
        }
    }

    public function updatePassword(array $postData): void
    {

        $current = $postData['current_password'] ?? '';
        $new = $postData['new_password'] ?? '';
        $confirm = $postData['confirm_password'] ?? '';

        if (empty($current) || empty($new) || $new !== $confirm || strlen($new) < 8) {
            SessionManager::setFlash('error', 'Invalid password. Must match and be 8+ characters.');
            $this->redirect('/admin/settings'); 
            return;
        }

        $db = $this->db->getPDO();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current, $user['password_hash'])) {
            SessionManager::setFlash('error', 'Incorrect current password.');
            $this->redirect('/admin/settings'); 
            return;
        }

        $hash = password_hash($new, PASSWORD_ARGON2ID);
        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $stmt->execute(['hash' => $hash, 'id' => $_SESSION['user_id']]);

        SessionManager::setFlash('success', 'Super Admin password updated securely.');
        $this->redirect('/admin/settings');
    }

    // --- SECURE IMAGE UPLOAD PROTOCOL ---
    private function handleSecureImageUpload(?array $file): ?string
    {
        // 1. If no file was selected at all, return null (text-only reply)
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
            return null;
        }

        // 2. If there IS a file, but it has a server error, THROW it to the screen!
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Image is too large. It exceeds the upload_max_filesize limit in php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'Image is too large for the form.',
                UPLOAD_ERR_PARTIAL    => 'Image was only partially uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.'
            ];
            $errMessage = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
            throw new \Exception("Upload Failed: " . $errMessage);
        }

        // 3. Size Limit (5MB)
        $maxSize = 5 * 1024 * 1024; 
        if ($file['size'] > $maxSize) {
            throw new \Exception("Upload failed: Image exceeds the 5MB limit.");
        }

        // 4. Strict MIME Type checking
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            throw new \Exception("Security Violation: Invalid file type. Only JPG, PNG, and WEBP allowed.");
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        // 5. Build the Absolute Server Path
        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/uploads/tickets/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new \Exception("Server Error: Could not create the 'tickets' upload directory.");
            }
        }

        // 6. Save the file with a secure random name
        $fileName = 'admin_tkt_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception("Server Error: Could not save the image to disk.");
        }

        return '/assets/uploads/tickets/' . $fileName;
    }

    public function setupGoogle2fa(): void
    {
        $db = $this->db->getPDO();
        $stmt = $db->prepare("SELECT name, email, two_factor_enabled FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user['two_factor_enabled']) {
            SessionManager::setFlash('info', 'Two-Factor Authentication is already enabled.');
            $this->redirect('/admin/settings');
            return;
        }

        $twoFactorService = new \Syncro\Services\TwoFactorService();
        $secret = $twoFactorService->generateSecret();
        
        $_SESSION['pending_google_2fa_secret'] = $secret;
        
        $qrCodeUrl = $twoFactorService->getQRCodeUrl('Syncro Enterprise', $user['email'], $secret);
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrCodeUrl);

        $this->render('admin/2fa_setup', [
            'pageTitle' => 'Setup Google Authenticator',
            'secret'    => $secret,
            'qrImage'   => $qrImage
        ], 'admin_layout');
    }

    public function verifyAndEnableGoogle2fa(array $postData): void
    {

        $code = preg_replace('/[^0-9]/', '', $postData['code'] ?? '');
        $secret = $_SESSION['pending_google_2fa_secret'] ?? '';

        if (empty($secret)) {
            SessionManager::setFlash('error', 'Session expired. Please start the 2FA setup again.');
            $this->redirect('/admin/settings');
            return;
        }

        $twoFactorService = new \Syncro\Services\TwoFactorService();
        if ($twoFactorService->verifyCode($secret, $code)) {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("UPDATE users SET two_factor_secret = :secret, two_factor_enabled = 1 WHERE id = :id");
            $stmt->execute(['secret' => $secret, 'id' => $_SESSION['user_id']]);

            unset($_SESSION['pending_google_2fa_secret']);
            SessionManager::setFlash('success', 'Google Authenticator 2FA successfully enabled!');
            $this->redirect('/admin/settings');
        } else {
            SessionManager::setFlash('error', 'Invalid code. Please check your authenticator app and try again.');
            $this->redirect('/admin/settings/2fa/setup');
        }
    }

    public function disableGoogle2fa(array $postData): void
    {

        $db = $this->db->getPDO();
        $stmt = $db->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);

        SessionManager::setFlash('success', 'Two-Factor Authentication disabled.');
        $this->redirect('/admin/settings');
    }

    /**
     * Override render to use 'admin_layout' by default for superadmin pages
     */
    protected function render(string $view, array $data = [], string $layout = 'admin_layout'): void
    {
        parent::render($view, $data, $layout);
    }
}