<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use PDO;

class ReportService
{
    /**
     * Calculates total payments collected on a specific date, grouped by payment method.
     * Crucial for front-desk "End of Shift" drawer reconciliation.
     */
    public function getDailyReconciliation(int $hotelId, string $date): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT payment_method, SUM(amount) as total_collected, COUNT(*) as transaction_count
            FROM payments 
            WHERE hotel_id = :hid AND DATE(payment_date) = :date
            GROUP BY payment_method
            ORDER BY total_collected DESC
        ");
        
        $stmt->execute(['hid' => $hotelId, 'date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculates key hotel metrics: Occupancy %, ADR (Average Daily Rate), and RevPAR
     */
    public function getMonthlyPerformance(int $hotelId, int $month, int $year): array
    {
        $db = Database::getConnection();
        
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));
        $daysInMonth = (int)date('t', strtotime($startDate));

        // 1. Total Physical Rooms
        $stmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE hotel_id = :hid");
        $stmt->execute(['hid' => $hotelId]);
        $totalRooms = (int)$stmt->fetchColumn();
        
        $totalAvailableRoomNights = $totalRooms * $daysInMonth;

        // 2. Total Booked Nights & Room Revenue for the month
        // We look at all payments made in this month as a proxy for revenue (Cash Accounting)
        $stmt = $db->prepare("
            SELECT SUM(amount) as total_revenue 
            FROM payments 
            WHERE hotel_id = :hid AND DATE(payment_date) BETWEEN :start AND :end
        ");
        $stmt->execute(['hid' => $hotelId, 'start' => $startDate, 'end' => $endDate]);
        $monthlyRevenue = (float)$stmt->fetchColumn();

        // Calculate actual occupied nights
        $stmt = $db->prepare("
            SELECT check_in, check_out 
            FROM bookings 
            WHERE hotel_id = :hid AND status IN ('confirmed', 'checked_in', 'checked_out')
            AND check_in <= :end AND check_out >= :start
        ");
        $stmt->execute(['hid' => $hotelId, 'start' => $startDate, 'end' => $endDate]);
        
        $occupiedNights = 0;
        $monthStartDt = new \DateTime($startDate);
        $monthEndDt = new \DateTime($endDate);
        
        while ($b = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cin = new \DateTime($b['check_in']);
            $cout = new \DateTime($b['check_out']);
            
            // Constrain dates to the current month boundary
            $effectiveStart = $cin > $monthStartDt ? $cin : $monthStartDt;
            $effectiveEnd = $cout < $monthEndDt ? $cout : $monthEndDt;
            
            if ($effectiveStart < $effectiveEnd) {
                $occupiedNights += $effectiveEnd->diff($effectiveStart)->days;
            }
        }

        // 3. KPIs
        $occupancyRate = $totalAvailableRoomNights > 0 ? ($occupiedNights / $totalAvailableRoomNights) * 100 : 0;
        $adr = $occupiedNights > 0 ? ($monthlyRevenue / $occupiedNights) : 0; // Average Daily Rate
        $revPar = $totalAvailableRoomNights > 0 ? ($monthlyRevenue / $totalAvailableRoomNights) : 0; // Rev Per Available Room

        return [
            'total_revenue'   => $monthlyRevenue,
            'occupied_nights' => $occupiedNights,
            'available_nights'=> $totalAvailableRoomNights,
            'occupancy_rate'  => round($occupancyRate, 2),
            'adr'             => round($adr, 2),
            'revpar'          => round($revPar, 2)
        ];
    }
}