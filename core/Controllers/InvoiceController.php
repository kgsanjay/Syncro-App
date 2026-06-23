<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SecurityManager;
use Exception;

class InvoiceController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function show(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('/user/dashboard');
            return;
        }

        $db = $this->db->getPDO();
        
        // Fetch Booking
        $stmt = $db->prepare("
            SELECT b.*, h.name as hotel_name, h.address as hotel_address, h.phone as hotel_phone, h.email as hotel_email, rt.name as room_type_name
            FROM bookings b
            JOIN hotels h ON b.hotel_id = h.id
            JOIN room_types rt ON b.room_type_id = rt.id
            WHERE b.id = :id AND b.hotel_id = :hid
        ");
        $stmt->execute(['id' => $id, 'hid' => $this->hotelId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $this->redirect('/user/dashboard?error=' . urlencode('Invoice not found or access denied.'));
            return;
        }

        // Fetch Payments
        $stmt = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY created_at ASC");
        $stmt->execute(['bid' => $id]);
        $payments = $stmt->fetchAll();

        // Fetch POS Charges if applicable (assuming pos_charges table, else just base it on total_price)
        // If there's no pos_charges table, we will just display room charges.
        
        $this->render('admin/invoice', [
            'pageTitle' => 'Folio #' . str_pad((string)$booking['id'], 5, '0', STR_PAD_LEFT),
            'booking' => $booking,
            'payments' => $payments
        ], 'blank_layout');
    }
}
