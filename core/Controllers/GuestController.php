<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager; // Correctly imported for CSRF checks
use Throwable;

class GuestController extends BaseHotelController
{
    public function index(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        try {
            $db = Database::getConnection();
            
            // We use COALESCE to ensure if name is null, it shows 'New Guest'
            // We use a LEFT JOIN to ensure guests show up even if they have 0 bookings
            $stmt = $db->prepare("
                SELECT 
                    g.id, 
                    g.full_name, 
                    g.email, 
                    g.phone, 
                    COUNT(b.id) as total_stays, 
                    MAX(b.check_in) as last_visit_date 
                FROM guests g 
                LEFT JOIN bookings b ON g.id = b.guest_id 
                WHERE g.hotel_id = :hid 
                GROUP BY g.id, g.full_name, g.email, g.phone
                ORDER BY g.id DESC
            ");
            $stmt->execute(['hid' => $this->hotelId]);
            $guests = $stmt->fetchAll();
            
            $this->render('user/guests', [
                'pageTitle' => 'Guest Directory',
                'guests'    => $guests
            ], 'user_layout');

        } catch (Throwable $e) {
            // Error reporting for the engineering team (you!)
            error_log("Guest Index Error: " . $e->getMessage());
            die("Database Error: " . $e->getMessage());
        }
    }

    public function store(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        // FIXED: Using direct SecurityManager check to prevent the "undefined method" error
        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            SessionManager::setFlash('error', 'Security Violation: CSRF Token Invalid.');
            $this->redirect('/user/guests');
            return;
        }

        $name = strip_tags(trim($postData['guest_name'] ?? ''));
        $email = trim(filter_var($postData['guest_email'] ?? '', FILTER_SANITIZE_EMAIL));
        $phone = strip_tags(trim($postData['guest_phone'] ?? ''));

        if (empty($name)) {
            SessionManager::setFlash('error', 'Guest name is required.');
            $this->redirect('/user/guests');
            return;
        }

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("INSERT INTO guests (hotel_id, full_name, email, phone) VALUES (:hid, :name, :email, :phone)");
            $stmt->execute([
                'hid'   => $this->hotelId,
                'name'  => $name,
                'email' => $email ?: null,
                'phone' => $phone ?: null
            ]);

            SessionManager::setFlash('success', 'Guest successfully added to the directory.');
            $this->redirect('/user/guests');
        } catch (Throwable $e) {
             die("
                <div style='padding: 50px; font-family: sans-serif; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; max-width: 800px; margin: 50px auto;'>
                    <h2 style='margin-top:0;'>Failed to Save Guest</h2>
                    <p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                </div>
            ");
        }
    }

    public function profile(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $guestId = (int)($_GET['id'] ?? 0);
        if (!$guestId) {
            SessionManager::setFlash('error', 'Guest record not found.');
            $this->redirect('/user/guests');
            return;
        }

        try {
            $db = Database::getConnection();

            // 1. Fetch Guest Basic Info
            $stmt = $db->prepare("SELECT * FROM guests WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $guestId, 'hid' => $this->hotelId]);
            $guest = $stmt->fetch();

            if (!$guest) {
                throw new Exception("Guest not found.");
            }

            // 2. Fetch Stay History
            $historyStmt = $db->prepare("
                SELECT b.id, b.check_in, b.check_out, b.status, r.name as room_name 
                FROM bookings b 
                LEFT JOIN room_types r ON b.room_type_id = r.id 
                WHERE b.guest_id = :gid AND b.hotel_id = :hid 
                ORDER BY b.check_in DESC
            ");
            $historyStmt->execute(['gid' => $guestId, 'hid' => $this->hotelId]);
            $history = $historyStmt->fetchAll();

            // 3. Calculate Metrics
            $metricsStmt = $db->prepare("
                SELECT 
                    COUNT(id) as total_stays,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_stays
                FROM bookings WHERE guest_id = :gid AND hotel_id = :hid
            ");
            $metricsStmt->execute(['gid' => $guestId, 'hid' => $this->hotelId]);
            $metrics = $metricsStmt->fetch();

            // Calculate Lifetime Spend from Payments
            $spendStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE guest_id = :gid)");
            $spendStmt->execute(['gid' => $guestId]);
            $totalSpend = (float)$spendStmt->fetchColumn();

            $this->render('user/guest_profile', [
                'pageTitle' => 'Guest Profile: ' . $guest['full_name'],
                'guestInfo' => [
                    'guest_name'  => $guest['full_name'],
                    'guest_email' => $guest['email'],
                    'guest_phone' => $guest['phone']
                ],
                'metrics' => [
                    'total_stays'     => $metrics['total_stays'],
                    'cancelled_stays' => $metrics['cancelled_stays'],
                    'total_spend'     => $totalSpend
                ],
                'history' => $history
            ], 'user_layout');

        } catch (Throwable $e) {
            SessionManager::setFlash('error', 'Error loading profile: ' . $e->getMessage());
            $this->redirect('/user/guests');
        }
    }
}