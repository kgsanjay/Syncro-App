<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Throwable;

class GuestPortalController extends BaseController
{
    public function portal(): void
    {
        $bookingId = (int)($_GET['booking_ref'] ?? 0);

        if (!$bookingId) {
            die("Invalid Booking Reference.");
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.*, h.name as hotel_name, r.name as room_name 
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                JOIN room_types r ON b.room_type_id = r.id
                WHERE b.id = :id
            ");
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                die("Booking not found.");
            }

            // Fetch ancillary sales for the invoice
            $stmt = $db->prepare("SELECT * FROM ancillary_sales WHERE booking_id = :bid");
            $stmt->execute(['bid' => $bookingId]);
            $ancillarySales = $stmt->fetchAll();

            $this->render('guest/portal', [
                'pageTitle' => 'Guest Self-Service Portal',
                'booking' => $booking,
                'ancillarySales' => $ancillarySales
            ]);

        } catch (Throwable $e) {
            die("Error: " . $e->getMessage());
        }
    }

    public function updateCheckin(array $postData, array $files): void
    {
        $bookingId = (int)($postData['booking_id'] ?? 0);
        $arrivalTime = $postData['arrival_time'] ?? null;

        if (!$bookingId) {
            die("Invalid Request.");
        }

        try {
            $db = Database::getConnection();

            $updateQuery = "UPDATE bookings SET arrival_time = :atime";
            $params = ['atime' => $arrivalTime, 'bid' => $bookingId];

            // Handle ID upload
            if (isset($files['id_document']) && $files['id_document']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = time() . '_' . basename($files['id_document']['name']);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($files['id_document']['tmp_name'], $targetFile)) {
                    $updateQuery .= ", guest_id_document_url = :idurl";
                    $params['idurl'] = '/uploads/' . $fileName;
                }
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
