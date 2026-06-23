<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Syncro\Services\EmailService;
use Throwable;

class GuestAuthController extends BaseController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
    }

    public function showLoginForm(): void
    {
        $this->render('public/guest_login', [
            'pageTitle' => 'Guest Portal Login'
        ], 'blank_layout');
    }

    public function requestOtp(array $postData): void
    {

        $email = trim(filter_var($postData['email'] ?? '', FILTER_SANITIZE_EMAIL));
        if (!$email) {
            SessionManager::setFlash('error', 'Valid email address is required.');
            $this->redirect('/guest/login');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("SELECT id, full_name FROM guests WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $guest = $stmt->fetch();

            $otp = sprintf("%06d", mt_rand(1, 999999));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $fullName = $guest ? $guest['full_name'] : 'Guest';

            if ($guest) {
                // Generate a 6-digit OTP
                $updateStmt = $db->prepare("UPDATE guests SET otp_code = :otp, otp_expires_at = :expires WHERE id = :id");
                $updateStmt->execute([
                    'otp' => password_hash($otp, PASSWORD_DEFAULT),
                    'expires' => $expiresAt,
                    'id' => $guest['id']
                ]);
            } else {
                // Store in session for unverified/new users
                $_SESSION['guest_otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
                $_SESSION['guest_otp_expires'] = time() + 900;
            }

            // Send Email
            $subject = "Your Syncro Guest Portal Login Code";
            $body = "<p>Hello " . htmlspecialchars($fullName) . ",</p>
                     <p>Your one-time login code is: <strong>{$otp}</strong></p>
                     <p>This code will expire in 15 minutes.</p>";

            EmailService::sendTransactionalEmail($email, $subject, $body);

            SessionManager::setFlash('success', 'A login code has been sent to your email.');
            // Store email in session to verify on next screen
            $_SESSION['guest_otp_email'] = $email;
            
            // Capture return_to
            if (!empty($postData['return_to'])) {
                $_SESSION['guest_return_to'] = $postData['return_to'];
            }
            
            $this->redirect('/guest/login?verify=1');

        } catch (Throwable $e) {
            SessionManager::setFlash('error', 'An error occurred. Please try again.');
            $this->redirect('/guest/login');
        }
    }

    public function verifyOtp(array $postData): void
    {

        $email = $_SESSION['guest_otp_email'] ?? '';
        $otp = trim($postData['otp'] ?? '');

        if (!$email || !$otp) {
            SessionManager::setFlash('error', 'Invalid request.');
            $this->redirect('/guest/login');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("SELECT id, otp_code, otp_expires_at FROM guests WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $guest = $stmt->fetch();

            $isValid = false;
            if ($guest && $guest['otp_code'] && strtotime($guest['otp_expires_at']) > time()) {
                if (password_verify($otp, $guest['otp_code'])) {
                    $isValid = true;
                    $_SESSION['guest_id'] = $guest['id'];
                    
                    // Clear OTP
                    $clearStmt = $db->prepare("UPDATE guests SET otp_code = NULL, otp_expires_at = NULL WHERE id = :id");
                    $clearStmt->execute(['id' => $guest['id']]);
                }
            } elseif (isset($_SESSION['guest_otp_hash']) && isset($_SESSION['guest_otp_expires']) && $_SESSION['guest_otp_expires'] > time()) {
                if (password_verify($otp, $_SESSION['guest_otp_hash'])) {
                    $isValid = true;
                    $_SESSION['verified_email'] = $email;
                    $_SESSION['verified_name'] = 'Guest'; // Default since we don't have it
                    
                    unset($_SESSION['guest_otp_hash']);
                    unset($_SESSION['guest_otp_expires']);
                }
            }

            if ($isValid) {
                unset($_SESSION['guest_otp_email']);
                $returnTo = $_SESSION['guest_return_to'] ?? '/guest/dashboard';
                unset($_SESSION['guest_return_to']);
                $this->redirect($returnTo);
                return;
            }

            SessionManager::setFlash('error', 'Invalid or expired code.');
            $this->redirect('/guest/login?verify=1');

        } catch (Throwable $e) {
            SessionManager::setFlash('error', 'An error occurred.');
            $this->redirect('/guest/login?verify=1');
        }
    }

    public function googleLogin(): void
    {
        $clientId = getenv('GOOGLE_CLIENT_ID');
        $redirectUri = getenv('GOOGLE_REDIRECT_URI');

        if (!$clientId || !$redirectUri) {
            SessionManager::setFlash('error', 'Google Login is not configured.');
            $this->redirect('/guest/login');
            return;
        }

        // Capture return_to
        if (!empty($_GET['return_to'])) {
            $_SESSION['google_return_to'] = $_GET['return_to'];
        }

        // Generate state to prevent CSRF
        $_SESSION['google_oauth_state'] = bin2hex(random_bytes(16));

        $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $_SESSION['google_oauth_state'],
            'access_type' => 'online'
        ]);

        header('Location: ' . $authUrl);
        exit;
    }

    public function googleCallback(): void
    {
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            SessionManager::setFlash('error', 'Google login was cancelled or failed.');
            $this->redirect('/guest/login');
            return;
        }

        if (!$code || !$state || $state !== ($_SESSION['google_oauth_state'] ?? '')) {
            SessionManager::setFlash('error', 'Invalid Google OAuth state.');
            $this->redirect('/guest/login');
            return;
        }

        // Unset state after verifying
        unset($_SESSION['google_oauth_state']);

        $clientId = getenv('GOOGLE_CLIENT_ID');
        $clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $redirectUri = getenv('GOOGLE_REDIRECT_URI');

        // Exchange code for token
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            SessionManager::setFlash('error', 'Failed to retrieve access token from Google.');
            $this->redirect('/guest/login');
            return;
        }

        // Get user profile info
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
        $response = curl_exec($ch);
        curl_close($ch);

        $userData = json_decode($response, true);

        if (!isset($userData['email'])) {
            SessionManager::setFlash('error', 'Failed to retrieve email from Google.');
            $this->redirect('/guest/login');
            return;
        }

        $email = $userData['email'];

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("SELECT id FROM guests WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $guest = $stmt->fetch();

            $returnTo = $_SESSION['google_return_to'] ?? '/guest/dashboard';
            unset($_SESSION['google_return_to']);

            if ($guest) {
                // Success! Existing booking
                $_SESSION['guest_id'] = $guest['id'];
                SessionManager::setFlash('success', 'Logged in successfully.');
            } else {
                // Verify identity without a booking
                $_SESSION['verified_email'] = $email;
                $_SESSION['verified_name'] = $userData['name'] ?? 'Guest';
                SessionManager::setFlash('success', 'Identity verified successfully.');
            }
            
            $this->redirect($returnTo);
            return;

        } catch (Throwable $e) {
            SessionManager::setFlash('error', 'An error occurred during Google Login.');
            $this->redirect('/guest/login');
        }
    }

    public function logout(): void
    {
        unset($_SESSION['guest_id']);
        SessionManager::setFlash('success', 'You have been logged out.');
        $this->redirect('/guest/login');
    }
}
