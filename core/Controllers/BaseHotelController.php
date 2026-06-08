<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use PDO;

abstract class BaseHotelController extends BaseController
{
    protected int $hotelId;
    protected int $userId;

    public function __construct()
    {
        SessionManager::start();
        
        $allowedRoles = ['hotel_admin', 'receptionist', 'housekeeper'];
        if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
            $this->redirect('/login');
            exit;
        }

        $this->userId = (int)$_SESSION['user_id'];
        $this->initializeTenantContext($this->userId, $_SESSION['role']);
    }

    private function initializeTenantContext(int $userId, string $role): void
    {
        $db = Database::getConnection();
        $activeHotelData = null;
        
        if ($role === 'hotel_admin') {
            // Fetch ALL properties owned by this admin, now including next_billing_date
            $stmt = $db->prepare("SELECT id, property_name, status, next_billing_date FROM hotels WHERE user_id = :uid AND status = 'active' ORDER BY property_name ASC");
            $stmt->execute(['uid' => $userId]);
            $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($hotels)) {
                SessionManager::destroy();
                $this->redirect('/login?error=' . urlencode('No active properties found.'));
                exit;
            }

            // Save list for the UI dropdown
            $_SESSION['user_properties'] = $hotels;

            // Determine active hotel
            $requestedHotelId = $_SESSION['active_hotel_id'] ?? $hotels[0]['id'];
            
            // Security: Validate the requested hotel actually belongs to this admin
            $isValid = false;
            foreach ($hotels as $h) {
                if ((int)$h['id'] === (int)$requestedHotelId) {
                    $isValid = true;
                    $_SESSION['property_name'] = $h['property_name'];
                    $activeHotelData = $h;
                    break;
                }
            }

            if (!$isValid) {
                $requestedHotelId = $hotels[0]['id'];
                $_SESSION['property_name'] = $hotels[0]['property_name'];
                $activeHotelData = $hotels[0];
            }

            $_SESSION['active_hotel_id'] = $requestedHotelId;
            $this->hotelId = (int)$requestedHotelId;

        } else {
            // Staff members are strictly bound to a single hotel
            $stmt = $db->prepare("
                SELECT h.id, h.property_name, h.status, h.next_billing_date 
                FROM hotels h 
                JOIN users u ON h.id = u.hotel_id 
                WHERE u.id = :uid LIMIT 1
            ");
            $stmt->execute(['uid' => $userId]);
            $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$hotel || $hotel['status'] !== 'active') {
                SessionManager::destroy();
                $this->redirect('/login');
                exit;
            }

            $this->hotelId = (int)$hotel['id'];
            $_SESSION['active_hotel_id'] = $this->hotelId;
            $_SESSION['property_name'] = $hotel['property_name'];
            $_SESSION['user_properties'] = []; 
            $activeHotelData = $hotel;
        }

        // =====================================================================
        // STANDARD SAAS EXPIRATION LOGIC
        // =====================================================================
        if ($activeHotelData && !empty($activeHotelData['next_billing_date'])) {
            $billingTimestamp = strtotime($activeHotelData['next_billing_date']);
            $timeNow = time();
            
            // Calculate full days remaining
            $daysRemaining = (int)floor(($billingTimestamp - $timeNow) / 86400);
            
            $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
            
            // Allowed paths when expired (Settings page, Logout, AND Helpdesk)
            $allowedPaths = ['/user/settings', '/logout', '/user/support'];
            $isAllowed = false;
            foreach ($allowedPaths as $path) {
                if (strpos($currentUri, $path) === 0) {
                    $isAllowed = true;
                    break;
                }
            }

            if ($timeNow > $billingTimestamp) {
                // 1. COMPLETELY EXPIRED (HARD LOCK)
                $_SESSION['account_expired'] = true;
                unset($_SESSION['account_expiring_soon']);
                
                // If they try to visit any page other than settings/support/logout, redirect them back
                if (!$isAllowed) {
                    SessionManager::setFlash('error', 'Your license has expired. Please process a renewal or request an extension to restore full system access.');
                    $this->redirect('/user/settings');
                    exit;
                }
            } else {
                $_SESSION['account_expired'] = false;

                if ($daysRemaining <= 3 && $daysRemaining >= 0) {
                    // 2. EXPIRING SOON (3 days or less)
                    $_SESSION['account_expiring_soon'] = $daysRemaining;
                } else {
                    // 3. SAFE (More than 3 days left)
                    unset($_SESSION['account_expiring_soon']);
                }
            }
        }
    }

    protected function requireRole(array $roles): void
    {
        if (!in_array($_SESSION['role'], $roles)) {
            $this->redirect('/user/dashboard');
            exit;
        }
    }
}