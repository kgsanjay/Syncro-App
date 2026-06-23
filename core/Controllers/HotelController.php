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
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    // =========================================================================
    // 1. DASHBOARD & REVENUE (CACHED)
    // =========================================================================

    public function dashboard(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $cacheKey = 'dashboard_metrics_hotel_' . $this->hotelId;
        $dashboardData = CacheManager::get($cacheKey);

        if (!$dashboardData) {
            $db = $this->db->getPDO();
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

    // =========================================================================
    // 3. RATE MANAGER
    // =========================================================================

    // =========================================================================
    // 4. CHANNEL MANAGER & OTA MAPPINGS
    // =========================================================================

    // =========================================================================
    // 5. HOUSEKEEPING & STAFF
    // =========================================================================

    // =========================================================================
    // 6. SETTINGS & 2FA SECURITY
    // =========================================================================

    public function settings(): void
    {
        $this->requireRole(['hotel_admin']);
        $db = $this->db->getPDO();
        $cache = \Syncro\Services\CacheManager::getInstance();
        $cacheKey = "hotel_settings_{$this->hotelId}";

        $cachedData = $cache->get($cacheKey);

        if ($cachedData !== null && is_array($cachedData)) {
            $hotel = $cachedData['hotel'];
            $settings = $cachedData['settings'];
        } else {
            $hotel = $this->db->getTable('hotels')->where('id', $this->hotelId)->first();

            // Fetch Global Settings for pricing
            $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
            $settings = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $cache->set($cacheKey, [
                'hotel' => $hotel,
                'settings' => $settings
            ]);
        }

        $currentUser = $this->db->getTable('users')->where('id', $this->userId)->first();

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

        $db = $this->db->getPDO();
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

        $keyId = strip_tags(trim($postData['phonepe_merchant_id'] ?? ''));
        $keySecret = strip_tags(trim($postData['phonepe_salt_key'] ?? ''));
        $env = strip_tags(trim($postData['phonepe_env'] ?? 'uat'));
        if (!in_array($env, ['uat', 'prod'])) {
            $env = 'uat';
        }

        try {
            $this->db->getTable('hotels')->where('id', $this->hotelId)->update([
                'phonepe_merchant_id' => $keyId,
                'phonepe_salt_key'    => $keySecret,
                'phonepe_env'         => $env
            ]);

            \Syncro\Services\CacheManager::getInstance()->delete("hotel_settings_{$this->hotelId}");

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
            $this->db->getTable('hotels')->where('id', $this->hotelId)->update($updateData);

            \Syncro\Services\CacheManager::getInstance()->delete("hotel_settings_{$this->hotelId}");

            SessionManager::setFlash('success', 'OTA API credentials updated successfully.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error occurred while updating OTA credentials.');
            $this->redirect('/user/settings');
        }
    }

    public function updateProfile(array $postData): void
    {
        $this->requireRole(['hotel_admin']);

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

            $this->db->getTable('hotels')->where('id', $this->hotelId)->update($updateData);
            $_SESSION['property_name'] = $propertyName;

            \Syncro\Services\CacheManager::getInstance()->delete("hotel_settings_{$this->hotelId}");

            SessionManager::setFlash('success', 'Property profile updated successfully.');
            $this->redirect('/user/settings');
        } catch (Exception $e) {
            SessionManager::setFlash('error', 'System error updating profile.');
            $this->redirect('/user/settings');
        }
    }

    public function updatePassword(array $postData): void
    {

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
            $user = $this->db->getTable('users')->where('id', $this->userId)->first();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                SessionManager::setFlash('error', 'Invalid current password.');
                $this->redirect('/user/settings'); return;
            }

            $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $this->db->getTable('users')->where('id', $this->userId)->update(['password_hash' => $newHash]);

            $this->db->getTable('audit_logs')->insert([
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
        
        $user = $this->db->getTable('users')->where('id', $this->userId)->first();
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

        $code = preg_replace('/[^0-9]/', '', $postData['code'] ?? '');
        $expectedOtp = $_SESSION['pending_email_otp'] ?? '';

        if (empty($expectedOtp)) {
            SessionManager::setFlash('error', 'Session expired. Please click "Enable 2FA" again.');
            $this->redirect('/user/settings');
            return;
        }

        if ($code === $expectedOtp) {
            $this->db->getTable('users')
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

        $this->db->getTable('users')
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

        $db = $this->db->getPDO();
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
        $db = $this->db->getPDO();
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

        $planMonths = (int)($postData['plan_months'] ?? 1);
        
        $db = $this->db->getPDO();
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
        $db = $this->db->getPDO();

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
            $db = $this->db->getPDO();
            
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
        $db = $this->db->getPDO();
        
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
        
        $db = $this->db->getPDO();
        $stmt = $db->prepare("UPDATE hotels SET api_token = ? WHERE id = ?");
        $stmt->execute([$token, $this->hotelId]);
        
        AuditLogger::log($this->hotelId, $_SESSION['user_id'] ?? null, 'SECURITY_UPDATE', 'Generated new Syncro API Token.');
        
        $this->redirectWithMessage('/user/settings', 'API Token generated successfully.', 'success');
    }
}