<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Exception;

class CRMController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
    }

    public function directory(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $hotelId = $_SESSION['hotel_id'];
        $db = $this->db->getPDO();

        // Get all guests for this hotel ordered by lifetime value
        $stmt = $db->prepare("
            SELECT id, full_name, email, phone, total_stays, total_revenue, last_visit_date 
            FROM guests
            WHERE hotel_id = ?
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$hotelId]);
        $guests = $stmt->fetchAll();

        // Get top 10 highest value guests
        $vipGuests = array_slice($guests, 0, 10);

        $this->render('crm/directory', [
            'pageTitle' => 'Guest CRM',
            'guests' => $guests,
            'vipGuests' => $vipGuests
        ]);
    }
}
