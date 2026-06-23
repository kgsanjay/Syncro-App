<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use PDO;
use Exception;

class SupportController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function index(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        $db = $this->db->getPDO();
        
        $stmt = $db->prepare("
            SELECT * FROM support_tickets 
            WHERE hotel_id = :hid 
            ORDER BY FIELD(status, 'waiting_on_customer', 'open', 'in_progress', 'resolved', 'closed'), updated_at DESC
        ");
        $stmt->execute(['hid' => $this->hotelId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('user/support_index', [
            'pageTitle' => 'Helpdesk Support',
            'tickets'   => $tickets
        ], 'user_layout');
    }

    public function create(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $subject = strip_tags(trim($postData['subject'] ?? ''));
        $message = strip_tags(trim($postData['message'] ?? ''));
        
        $priority = in_array($postData['priority'] ?? 'normal', ['low', 'normal', 'high', 'urgent']) 
            ? $postData['priority'] 
            : 'normal';

        if (empty($subject) || empty($message)) {
            SessionManager::setFlash('error', 'Subject and message are required.');
            $this->redirect('/user/support');
            return;
        }

        try {
            $db = $this->db->getPDO();
            $db->beginTransaction();

            $attachmentPath = $this->handleSecureImageUpload($_FILES['attachment'] ?? null);

            $stmt = $db->prepare("INSERT INTO support_tickets (hotel_id, user_id, subject, priority, status, created_at, updated_at) VALUES (:hid, :uid, :sub, :pri, 'open', NOW(), NOW())");
            $stmt->execute([
                'hid' => $this->hotelId, 
                'uid' => $this->userId, 
                'sub' => $subject,
                'pri' => $priority
            ]);
            $ticketId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin_reply, attachment_path) VALUES (:tid, :uid, :msg, 0, :path)");
            $stmt->execute([
                'tid'  => $ticketId,
                'uid'  => $this->userId,
                'msg'  => $message,
                'path' => $attachmentPath
            ]);

            $db->commit();
            SessionManager::setFlash('success', 'Ticket created successfully.');
            $this->redirect("/user/support/view?id={$ticketId}");

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/support');
        }
    }

    public function view(array $getData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $ticketId = (int)($getData['id'] ?? 0);
        if (!$ticketId) {
            $this->redirect('/user/support');
            return;
        }

        $db = $this->db->getPDO();
        
        // Verify ownership for security
        $stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = :id AND hotel_id = :hid");
        $stmt->execute(['id' => $ticketId, 'hid' => $this->hotelId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            $this->redirect('/user/support');
            return;
        }

        $stmt = $db->prepare("
            SELECT r.*, u.name as sender_name 
            FROM ticket_replies r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.ticket_id = :tid 
            ORDER BY r.created_at ASC
        ");
        $stmt->execute(['tid' => $ticketId]);
        $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('user/support_view', [
            'pageTitle' => 'Ticket #' . $ticketId,
            'ticket'    => $ticket,
            'replies'   => $replies
        ], 'user_layout');
    }

    public function reply(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $ticketId = (int)($postData['ticket_id'] ?? 0);
        $message = strip_tags(trim($postData['message'] ?? ''));

        if (!$ticketId || empty($message)) {
            SessionManager::setFlash('error', 'Message cannot be empty.');
            $this->redirect("/user/support/view?id={$ticketId}");
            return;
        }

        try {
            $db = $this->db->getPDO();
            
            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM support_tickets WHERE id = :id AND hotel_id = :hid");
            $stmt->execute(['id' => $ticketId, 'hid' => $this->hotelId]);
            if (!$stmt->fetch()) throw new Exception("Unauthorized access.");

            $db->beginTransaction();

            $attachmentPath = $this->handleSecureImageUpload($_FILES['attachment'] ?? null);

            $stmt = $db->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin_reply, attachment_path) VALUES (:tid, :uid, :msg, 0, :path)");
            $stmt->execute([
                'tid'  => $ticketId,
                'uid'  => $this->userId,
                'msg'  => $message,
                'path' => $attachmentPath
            ]);

            $stmt = $db->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $ticketId]);

            $db->commit();
            SessionManager::setFlash('success', 'Reply sent.');
            $this->redirect("/user/support/view?id={$ticketId}");

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect("/user/support/view?id={$ticketId}");
        }
    }

    // --- SECURITY PROTOCOL FOR IMAGES ---
    private function handleSecureImageUpload(?array $file): ?string
    {
        // 1. If no file was selected at all, just return null (normal text-only reply)
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
            return null;
        }

        // 2. If there IS a file, but it has an error, THROW an exception so we can see it on screen!
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'Image is too large. It exceeds the upload_max_filesize setting in your server php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'Image is too large.',
                UPLOAD_ERR_PARTIAL    => 'Image was only partially uploaded. Try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
            ];
            $errMessage = $uploadErrors[$file['error']] ?? 'Unknown upload error.';
            throw new Exception("Upload Failed: " . $errMessage);
        }

        $maxSize = 5 * 1024 * 1024; // 5MB Limit
        if ($file['size'] > $maxSize) {
            throw new Exception("Upload failed: Image exceeds the 5MB limit.");
        }

        // Use strict MIME sniffing to prevent malicious scripts disguised as images
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            throw new Exception("Security Violation: Invalid file type ({$mime}). Only JPG, PNG, and WEBP are allowed.");
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg'
        };

        // 3. Bulletproof Pathing using the absolute Document Root
        // This ensures the folder is always created in the public assets directory reliably
        $uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/uploads/tickets/';
        
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception("Server Error: Could not create upload directory. Check file permissions.");
            }
        }

        // Randomize filename to prevent directory traversal attacks
        $fileName = 'tkt_' . bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Server Error: Failed to save the image to disk. Check folder permissions.");
        }

        // Return the public URL path
        return '/assets/uploads/tickets/' . $fileName;
    }
}