<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Throwable;

class GuestPortalController extends BaseController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
    }

    private function requireGuest(): int
    {
        $guestId = (int)($_SESSION['guest_id'] ?? 0);
        if (!$guestId) {
            $this->redirect('/guest/login');
            exit;
        }
        return $guestId;
    }

    public function dashboard(): void
    {
        $guestId = $this->requireGuest();

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("
                SELECT b.*, h.name as hotel_name, h.property_name, r.name as room_name 
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                JOIN room_types r ON b.room_type_id = r.id
                WHERE b.guest_id = :gid
                ORDER BY b.check_in DESC
            ");
            $stmt->execute(['gid' => $guestId]);
            $bookings = $stmt->fetchAll();

            $this->render('guest/dashboard', [
                'pageTitle' => 'My Bookings',
                'bookings' => $bookings
            ]);

        } catch (Throwable $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function portal(): void
    {
        $guestId = $this->requireGuest();
        $bookingId = (int)($_GET['booking_ref'] ?? 0);

        if (!$bookingId) {
            die("Invalid Booking Reference.");
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("
                SELECT b.*, h.name as hotel_name, r.name as room_name 
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                JOIN room_types r ON b.room_type_id = r.id
                WHERE b.id = :id AND b.guest_id = :gid
            ");
            $stmt->execute(['id' => $bookingId, 'gid' => $guestId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                die("Booking not found or access denied.");
            }

            // Fetch ancillary sales for the invoice
            $stmt = $db->prepare("SELECT * FROM ancillary_sales WHERE booking_id = :bid");
            $stmt->execute(['bid' => $bookingId]);
            $ancillarySales = $stmt->fetchAll();

            $this->render('guest/portal', [
                'pageTitle' => 'Booking Details',
                'booking' => $booking,
                'ancillarySales' => $ancillarySales
            ]);

        } catch (Throwable $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function updateCheckin(array $postData, array $files): void
    {
        $guestId = $this->requireGuest();
        $bookingId = (int)($postData['booking_id'] ?? 0);
        $arrivalTime = $postData['arrival_time'] ?? null;

        if (!$bookingId) {
            die("Invalid Request.");
        }

        try {
            $db = $this->db->getPDO();

            // Verify ownership first
            $verify = $db->prepare("SELECT id FROM bookings WHERE id = :bid AND guest_id = :gid");
            $verify->execute(['bid' => $bookingId, 'gid' => $guestId]);
            if (!$verify->fetch()) {
                die("Access denied.");
            }

            $updateQuery = "UPDATE bookings SET arrival_time = :atime";
            $params = ['atime' => $arrivalTime, 'bid' => $bookingId];

            // Handle ID upload
            if (isset($files['id_document']) && $files['id_document']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../storage/uploads/guest_documents/';
                $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
                $targetFile = \Syncro\Services\FileUploader::upload($files['id_document'], $uploadDir, $allowedMimes);

                $updateQuery .= ", guest_id_document_url = :idurl";
                $params['idurl'] = $targetFile;
            }

            $updateQuery .= " WHERE id = :bid";
            
            $stmt = $db->prepare($updateQuery);
            $stmt->execute($params);

            header("Location: /guest/portal?booking_ref=" . $bookingId . "&success=1");
            exit;

        } catch (Throwable $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
