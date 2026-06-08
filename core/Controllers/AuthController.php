<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Syncro\Services\TwoFactorService;
use PDO;

class AuthController extends BaseController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct()
    {
        // Fix: Ensure session and CSRF tokens are loaded before ANY method runs
        SessionManager::start();
    }

    public function showLoginForm(): void
    {
        if (isset($_SESSION['user_id'])) {
            $redirect = ($_SESSION['role'] === 'super_admin') ? '/admin/dashboard' : '/user/dashboard';
            $this->redirect($redirect);
            return;
        }

        $this->render('admin/login', [
            'pageTitle' => 'Secure Login | Syncro Enterprise'
        ], 'blank_layout');
    }

    public function processLogin(array $postData, string $ipAddress): void
    {
        $email = filter_var(trim($postData['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $postData['password'] ?? '';
        $csrfToken = $postData['csrf_token'] ?? '';

        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            $this->redirect('/login?error=' . urlencode('Security Violation: CSRF token mismatch.'));
            return;
        }

        if (empty($email) || empty($password)) {
            $this->redirect('/login?error=' . urlencode('Email and password are required.'));
            return;
        }

        if ($this->isRateLimited($email, $ipAddress)) {
            $this->logAttempt($email, $ipAddress, false);
            $this->redirect('/login?error=' . urlencode('Too many failed attempts. Please try again in 15 minutes.'));
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $dummyHash = '$argon2id$v=19$m=65536,t=4,p=2$c29tZWR1bW15c2FsdHRlc3Q$dummyhashvaluefortimingattackmitigation'; 
        $isValidPassword = false;
        
        if ($user) {
            $isValidPassword = password_verify($password, $user['password_hash']);
        } else {
            password_verify($password, $dummyHash); 
        }

        if ($user && $isValidPassword) {
            
            // Check for pending approval
            if ($user['status'] === 'pending' || $user['status'] === 'pending_approval') {
                $this->logAttempt($email, $ipAddress, false);
                $this->redirect('/login?error=' . urlencode('Account pending admin approval.'));
                return;
            }

            if ($user['status'] !== 'active') {
                $this->logAttempt($email, $ipAddress, false);
                $this->redirect('/login?error=' . urlencode('Account is suspended.'));
                return;
            }

            // --- 2FA INTERCEPTION LOGIC ---
            if (!empty($user['two_factor_enabled'])) {
                SessionManager::regenerate();
                $_SESSION['2fa_pending_user_id'] = (int)$user['id'];
                
                // If using Email OTP, generate and send the code using the Enterprise Email Service
                if ($user['two_factor_secret'] === 'EMAIL_OTP') {
                    $otp = (string)random_int(100000, 999999);
                    $_SESSION['2fa_expected_otp'] = $otp;
                    
                    $subject = "Your Secure Login Code - Syncro PMS";
                    
                    $htmlMessage = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #eef1f6; border-radius: 12px; background-color: #ffffff;'>
                        <h2 style='color: #002244; text-align: center; margin-bottom: 5px;'>Secure Login Verification</h2>
                        <p style='color: #555555; font-size: 16px; text-align: center; margin-top: 0;'>Syncro Enterprise PMS</p>
                        
                        <div style='background-color: #f8f9fa; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 25px; text-align: center; margin: 30px 0;'>
                            <p style='color: #555555; font-size: 14px; margin-top: 0; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;'>Your 6-Digit Code</p>
                            <span style='font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #003366; font-family: monospace;'>{$otp}</span>
                        </div>
                        
                        <p style='color: #888888; font-size: 13px; text-align: center; border-top: 1px solid #eef1f6; padding-top: 20px;'>
                            This code will expire in 15 minutes. If you did not request this login, please contact your System Administrator immediately.
                        </p>
                    </div>";

                    // USING THE NEW STATIC EMAIL SERVICE
                    \Syncro\Services\EmailService::sendTransactionalEmail($user['email'], $subject, $htmlMessage);
                }

                $this->redirect('/login/2fa');
                return;
            }

            $this->completeLogin(
                (int)$user['id'], 
                $user['role'], 
                $user['hotel_id'] ? (int)$user['hotel_id'] : null, 
                $user['name'], 
                $ipAddress
            );

        } else {
            $this->logAttempt($email, $ipAddress, false);
            $this->redirect('/login?error=' . urlencode('Invalid system credentials.'));
        }
    }

    private function completeLogin(int $userId, string $role, ?int $hotelId, ?string $name, string $ipAddress): void
    {
        SessionManager::regenerate(); 
        $_SESSION['user_id']       = $userId;
        $_SESSION['role']          = $role;
        $_SESSION['hotel_id']      = $hotelId;
        $_SESSION['name']          = $name;
        $_SESSION['last_activity'] = time();

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO audit_logs (hotel_id, user_id, action_type, description, ip_address) VALUES (:hid, :uid, 'USER_LOGIN', 'Successful login via web portal.', :ip)");
        $stmt->execute([
            'hid' => $hotelId, // now allows NULL for super_admin
            'uid' => $userId, 
            'ip' => $ipAddress
        ]);

        $redirect = ($role === 'super_admin') ? '/admin/dashboard' : '/user/dashboard';
        $this->redirect($redirect);
    }

    public function show2faForm(): void
    {
        if (!isset($_SESSION['2fa_pending_user_id'])) {
            $this->redirect('/login');
            return;
        }

        $this->render('admin/login_2fa', [
            'pageTitle' => 'Two-Factor Authentication | Syncro'
        ], 'blank_layout');
    }

    public function verify2fa(array $postData, string $ipAddress): void
    {
        if (!isset($_SESSION['2fa_pending_user_id'])) {
            $this->redirect('/login');
            return;
        }

        $csrfToken = $postData['csrf_token'] ?? '';
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            $this->redirect('/login?error=' . urlencode('Security Violation: CSRF token mismatch.'));
            return;
        }

        $code = preg_replace('/[^0-9]/', '', $postData['code'] ?? '');
        $userId = (int)$_SESSION['2fa_pending_user_id'];

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || empty($user['two_factor_secret'])) {
            $this->redirect('/login?error=' . urlencode('2FA is not configured correctly.'));
            return;
        }

        if ($this->isRateLimited($user['email'], $ipAddress)) {
            $this->logAttempt($user['email'], $ipAddress, false);
            $this->render('admin/login_2fa', [
                'error' => 'Too many failed attempts. Please try again in 15 minutes.',
                'pageTitle' => 'Two-Factor Authentication | Syncro'
            ], 'blank_layout');
            return;
        }

        $isValid = false;

        // --- VERIFY BASED ON 2FA TYPE ---
        if ($user['two_factor_secret'] === 'EMAIL_OTP') {
            $expectedOtp = $_SESSION['2fa_expected_otp'] ?? '';
            if ($code !== '' && $code === $expectedOtp) {
                $isValid = true;
                unset($_SESSION['2fa_expected_otp']);
            }
        } else {
            // Fallback for app-based authenticator (if any staff are still using it)
            $twoFactorService = new TwoFactorService();
            if ($twoFactorService->verifyCode($user['two_factor_secret'], $code)) {
                $isValid = true;
            }
        }

        if ($isValid) {
            unset($_SESSION['2fa_pending_user_id']);
            $this->completeLogin($userId, $user['role'], $user['hotel_id'] ? (int)$user['hotel_id'] : null, $user['name'], $ipAddress);
        } else {
            $this->logAttempt($user['email'], $ipAddress, false);
            $this->render('admin/login_2fa', [
                'error' => 'Invalid verification code. Please check your email and try again.',
                'pageTitle' => 'Two-Factor Authentication | Syncro'
            ], 'blank_layout');
        }
    }

    // =========================================================================
    // REGISTRATION & ONBOARDING DYNAMICS
    // =========================================================================

    public function showRegister(): void
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT setting_key, setting_value FROM platform_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $this->render('public/register', [
            'pageTitle' => 'Enterprise License | Syncro',
            'settings'  => $settings
        ], 'blank_layout');
    }

    public function processTrialRegistration(array $postData): void
    {
        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            die("Security Violation");
        }

        $propertyName = strip_tags(trim($postData['property_name'] ?? ''));
        $slug = strip_tags(trim($postData['slug'] ?? ''));
        $adminName = strip_tags(trim($postData['admin_name'] ?? ''));
        $email = trim(filter_var($postData['admin_email'] ?? '', FILTER_SANITIZE_EMAIL));
        $password = $postData['admin_password'] ?? '';
        
        // We capture the plan they selected, but we will put them on a generic 14-Day Trial first
        $planMonths = (int)($postData['plan_months'] ?? 12);

        if (empty($propertyName) || empty($email) || strlen($password) < 8) {
            $this->redirect('/register?error=missing');
            return;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $this->redirect('/register?error=exists');
                return;
            }

            // 1. Create User instantly (Active for Trial)
            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("INSERT INTO users (role, name, email, password_hash, status) VALUES ('hotel_admin', :name, :email, :hash, 'active')");
            $stmt->execute(['name' => $adminName, 'email' => $email, 'hash' => $hashedPassword]);
            $userId = (int)$db->lastInsertId();

            // 2. Generate secure M2M API Keys
            $rawApiKey = 'syncro_live_' . bin2hex(random_bytes(16));
            $apiSecret = bin2hex(random_bytes(32));
            $hashedApiKey = hash('sha256', $rawApiKey);
            
            // 3. Set exact 14-day trial window with clean plan name
            $planName = "14-Day Free Trial";
            $nextBillingDate = date('Y-m-d H:i:s', strtotime('+14 days'));

            // 4. Create Hotel instantly (Active for Trial)
            $stmt = $db->prepare("
                INSERT INTO hotels (user_id, property_name, slug, api_key, api_secret, status, subscription_plan, next_billing_date) 
                VALUES (:uid, :name, :slug, :api_key, :api_secret, 'active', :plan, :billing)
            ");
            $stmt->execute([
                'uid' => $userId,
                'name' => $propertyName,
                'slug' => $slug,
                'api_key' => $hashedApiKey,
                'api_secret' => $apiSecret,
                'plan' => $planName,
                'billing' => $nextBillingDate
            ]);
            $hotelId = (int)$db->lastInsertId();

            // Link user to hotel
            $stmt = $db->prepare("UPDATE users SET hotel_id = :hid WHERE id = :uid");
            $stmt->execute(['hid' => $hotelId, 'uid' => $userId]);

            // Log event
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (:uid, 'TRIAL_STARTED', :desc, :ip)");
            $stmt->execute([
                'uid'  => $userId,
                'desc' => "Hotel onboarded. 14-Day Free Trial started for {$propertyName}.",
                'ip'   => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);

            $db->commit();

            // 5. Automatically log the user in
            SessionManager::regenerate(); 
            $_SESSION['user_id']       = $userId;
            $_SESSION['role']          = 'hotel_admin';
            $_SESSION['hotel_id']      = $hotelId;
            $_SESSION['name']          = $adminName;
            $_SESSION['last_activity'] = time();

            // Add a flash welcome message to display on the dashboard
            SessionManager::setFlash('success', "Welcome to Syncro! Your 14-day free trial is now active.");
            
            // Redirect straight to their new PMS Dashboard!
            $this->redirect('/user/dashboard');

        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->redirect('/register?error=system');
        }
    }

    public function showRegisterSuccess(): void
    {
        $this->render('public/register_success', [
            'pageTitle' => 'Registration Received | Syncro'
        ], 'blank_layout');
    }

    // =========================================================================
    // STAFF INVITATIONS
    // =========================================================================

    public function showAcceptInvite(string $token): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT email FROM staff_invitations WHERE token = :token AND expires_at > NOW()");
        $stmt->execute(['token' => $token]);
        $invite = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invite) {
            $this->redirect('/login?error=' . urlencode('Invalid or expired invitation link.'));
            return;
        }

        $this->render('public/staff_accept', [
            'pageTitle' => 'Accept Invitation | Syncro',
            'token' => $token,
            'email' => $invite['email']
        ], 'blank_layout');
    }

    public function processAcceptInvite(array $postData): void
    {
        $token = $postData['token'] ?? '';
        $name = trim($postData['name'] ?? '');
        $password = $postData['password'] ?? '';

        if (empty($name) || strlen($password) < 8) {
            $this->redirect('/staff/accept?token=' . urlencode($token) . '&error=' . urlencode('Name and an 8+ character password are required.'));
            return;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT id, hotel_id, email, role FROM staff_invitations WHERE token = :token AND expires_at > NOW()");
            $stmt->execute(['token' => $token]);
            $invite = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invite) {
                throw new Exception("Invalid or expired invitation link.");
            }

            // Create user
            $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("
                INSERT INTO users (hotel_id, role, name, email, password_hash, status) 
                VALUES (:hotel_id, :role, :name, :email, :hash, 'active')
            ");
            $stmt->execute([
                'hotel_id' => $invite['hotel_id'],
                'role' => $invite['role'],
                'name' => $name,
                'email' => $invite['email'],
                'hash' => $hashedPassword
            ]);

            // Delete invite
            $stmt = $db->prepare("DELETE FROM staff_invitations WHERE id = :id");
            $stmt->execute(['id' => $invite['id']]);

            $db->commit();
            $this->redirect('/login?success=' . urlencode('Account created successfully. You can now log in.'));

        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->redirect('/staff/accept?token=' . urlencode($token) . '&error=' . urlencode($e->getMessage()));
        }
    }

    // =========================================================================
    // SECURITY UTILITIES
    // =========================================================================

    private function isRateLimited(string $email, string $ipAddress): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE (ip_address = :ip OR email = :email) 
            AND success = 0 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
        ");
        $stmt->execute(['ip' => $ipAddress, 'email' => $email, 'minutes' => self::LOCKOUT_MINUTES]);
        return (int)$stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    private function logAttempt(string $email, string $ipAddress, bool $success): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (:email, :ip, :success)");
        $stmt->execute(['email' => $email, 'ip' => $ipAddress, 'success' => $success ? 1 : 0]);
    }
}