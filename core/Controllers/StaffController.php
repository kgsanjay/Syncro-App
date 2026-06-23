<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SecurityManager;
use Syncro\Security\SessionManager;
use Exception;
use PDO;

class StaffController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    /**
     * Show staff management page
     */
    public function index(): void
    {
        $this->requireRole(['hotel_admin']);

        $db = $this->db->getPDO();

        // Get active staff
        $stmt = $db->prepare("
            SELECT id, name, email, role, created_at, status 
            FROM users 
            WHERE hotel_id = :hotel_id AND role IN ('receptionist', 'housekeeper')
            ORDER BY created_at DESC
        ");
        $stmt->execute(['hotel_id' => $this->hotelId]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get pending invites
        $stmt2 = $db->prepare("
            SELECT id, email, role, created_at, expires_at 
            FROM staff_invitations 
            WHERE hotel_id = :hotel_id 
            ORDER BY created_at DESC
        ");
        $stmt2->execute(['hotel_id' => $this->hotelId]);
        $invitations = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $this->render('user/staff', [
            'pageTitle'   => 'Staff Management',
            'staff'       => $staff,
            'invitations' => $invitations
        ]);
    }

    /**
     * Send a staff invitation
     */
    public function invite(array $post): void
    {
        $this->requireRole(['hotel_admin']);

        $email = trim($post['email'] ?? '');
        $role = trim($post['role'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect('/user/staff?error=' . urlencode('Invalid email address.'));
            return;
        }

        if (!in_array($role, ['receptionist', 'housekeeper'])) {
            $this->redirect('/user/staff?error=' . urlencode('Invalid role selected.'));
            return;
        }

        try {
            $db = $this->db->getPDO();
            
            // Check if user already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                throw new Exception("A user with this email already exists in the system.");
            }

            // Clean up old invites for this email
            $db->prepare("DELETE FROM staff_invitations WHERE email = :email AND hotel_id = :hotel_id")
               ->execute(['email' => $email, 'hotel_id' => $this->hotelId]);

            // Create new invite
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

            $stmt = $db->prepare("
                INSERT INTO staff_invitations (hotel_id, email, role, token, expires_at)
                VALUES (:hotel_id, :email, :role, :token, :expires_at)
            ");
            $stmt->execute([
                'hotel_id'   => $this->hotelId,
                'email'      => $email,
                'role'       => $role,
                'token'      => $token,
                'expires_at' => $expiresAt
            ]);

            // Mock sending email
            $inviteLink = "http://{$_SERVER['HTTP_HOST']}/staff/accept?token={$token}";
            // \Syncro\Services\EmailService::send(...);
            error_log("Sent staff invite to {$email}: {$inviteLink}");

            $this->redirect('/user/staff?success=' . urlencode('Invitation sent successfully.'));

        } catch (Exception $e) {
            $this->redirect('/user/staff?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Revoke an active staff member's access
     */
    public function revoke(array $post): void
    {
        $this->requireRole(['hotel_admin']);

        $staffId = (int)($post['staff_id'] ?? 0);
        
        try {
            $db = $this->db->getPDO();
            // Delete staff member (or mark suspended)
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND hotel_id = :hotel_id AND role IN ('receptionist', 'housekeeper')");
            $stmt->execute(['id' => $staffId, 'hotel_id' => $this->hotelId]);
            
            $this->redirect('/user/staff?success=' . urlencode('Staff access revoked.'));
        } catch (Exception $e) {
            $this->redirect('/user/staff?error=' . urlencode('Error revoking access.'));
        }
    }

    /**
     * Revoke pending invitation
     */
    public function revokeInvite(array $post): void
    {
        $this->requireRole(['hotel_admin']);

        $inviteId = (int)($post['invite_id'] ?? 0);
        
        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("DELETE FROM staff_invitations WHERE id = :id AND hotel_id = :hotel_id");
            $stmt->execute(['id' => $inviteId, 'hotel_id' => $this->hotelId]);
            
            $this->redirect('/user/staff?success=' . urlencode('Invitation cancelled.'));
        } catch (Exception $e) {
            $this->redirect('/user/staff?error=' . urlencode('Error cancelling invitation.'));
        }
    }
}
