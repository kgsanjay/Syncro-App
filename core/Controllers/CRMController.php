<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Exception;

class CRMController extends BaseController
{
    public function directory(): void
    {
        SessionManager::requireLogin();
        // Allow hotel_admin or receptionist
        if ($_SESSION['role'] !== 'hotel_admin' && $_SESSION['role'] !== 'receptionist') {
            http_response_code(403);
            die("Forbidden: CRM access requires admin or front desk role.");
        }

        $hotelId = $_SESSION['hotel_id'];
        $db = Database::getConnection();

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
