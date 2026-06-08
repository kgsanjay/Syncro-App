<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use PDO;
use Exception;
use DatePeriod;
use DateInterval;
use DateTime;

class BillingService
{
    public function getFolioDetails(int $hotelId, int $bookingId): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT b.*, r.name as room_name, r.base_price, h.property_name 
            FROM bookings b 
            JOIN room_types r ON b.room_type_id = r.id 
            JOIN hotels h ON b.hotel_id = h.id
            WHERE b.id = :bid AND b.hotel_id = :hid
        ");
        $stmt->execute(['bid' => $bookingId, 'hid' => $hotelId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception("Folio not found or access denied.");
        }

        $taxRules = [];
        try {
            $taxStmt = $db->prepare("SELECT * FROM tax_rules WHERE hotel_id = :hid AND is_active = 1");
            $taxStmt->execute(['hid' => $hotelId]);
            $taxRules = $taxStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Proceed without taxes if the table hasn't been created in the database yet
        }

        $posStmt = $db->prepare("SELECT * FROM pos_charges WHERE booking_id = :bid ORDER BY created_at ASC");
        $posStmt->execute(['bid' => $bookingId]);
        $posCharges = $posStmt->fetchAll(PDO::FETCH_ASSOC);

        $payStmt = $db->prepare("SELECT * FROM payments WHERE booking_id = :bid ORDER BY payment_date ASC");
        $payStmt->execute(['bid' => $bookingId]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        $cin = new DateTime($booking['check_in']);
        $cout = new DateTime($booking['check_out']);
        $nights = max(1, $cout->diff($cin)->days);

        $roomTotal = 0.0;
        $taxTotal = 0.0;
        $nightlyBreakdown = [];

        $period = new DatePeriod($cin, DateInterval::createFromDateString('1 day'), $cout);
        
        $rateStmt = $db->prepare("SELECT target_date, dynamic_price FROM inventory_tape WHERE room_type_id = :rid AND target_date >= :cin AND target_date < :cout");
        $rateStmt->execute(['rid' => $booking['room_type_id'], 'cin' => $booking['check_in'], 'cout' => $booking['check_out']]);
        
        $dailyRates = [];
        while ($row = $rateStmt->fetch(PDO::FETCH_ASSOC)) {
            $dailyRates[$row['target_date']] = (float)$row['dynamic_price'];
        }

        foreach ($period as $dt) {
            $dateStr = $dt->format('Y-m-d');
            $nightRate = (isset($dailyRates[$dateStr]) && $dailyRates[$dateStr] > 0) ? $dailyRates[$dateStr] : (float)$booking['base_price'];
            
            $nightTax = 0.0;
            $appliedRule = 'Exempt';

            foreach ($taxRules as $rule) {
                if ($nightRate >= (float)$rule['min_amount'] && $nightRate <= (float)$rule['max_amount']) {
                    $nightTax = $nightRate * ((float)$rule['percentage'] / 100);
                    $appliedRule = $rule['name'] . ' (' . (float)$rule['percentage'] . '%)';
                    break; 
                }
            }

            $roomTotal += $nightRate;
            $taxTotal += $nightTax;

            $nightlyBreakdown[] = [
                'date' => $dateStr,
                'rate' => $nightRate,
                'tax'  => $nightTax,
                'rule' => $appliedRule
            ];
        }
        
        $posTotal = array_reduce($posCharges, fn($carry, $item) => $carry + (float)$item['amount'], 0.0);
        
        $posTax = $posTotal * 0.18; 
        $taxTotal += $posTax;

        $grandTotal = $roomTotal + $posTotal + $taxTotal;
        $totalPaid = array_reduce($payments, fn($carry, $item) => $carry + (float)$item['amount'], 0.0);
        $balanceDue = $grandTotal - $totalPaid;

        return [
            'booking'          => $booking,
            'posCharges'       => $posCharges,
            'payments'         => $payments,
            'nights'           => $nights,
            'nightlyBreakdown' => $nightlyBreakdown,
            'roomTotal'        => $roomTotal,
            'posTotal'         => $posTotal,
            'taxTotal'         => $taxTotal,
            'grandTotal'       => $grandTotal,
            'totalPaid'        => $totalPaid,
            'balanceDue'       => $balanceDue
        ];
    }

    public function addPayment(int $hotelId, int $bookingId, array $data): void
    {
        $db = Database::getConnection();
        
        // Verify ownership (IDOR protection)
        $stmt = $db->prepare("SELECT id FROM bookings WHERE id = :bid AND hotel_id = :hid");
        $stmt->execute(['bid' => $bookingId, 'hid' => $hotelId]);
        if (!$stmt->fetch()) throw new Exception("Unauthorized folio access.");

        // Insert Payment securely
        $insertStmt = $db->prepare("
            INSERT INTO payments (hotel_id, booking_id, amount, payment_method, transaction_id, notes) 
            VALUES (:hid, :bid, :amt, :method, :trans, :notes)
        ");
        $insertStmt->execute([
            'hid'    => $hotelId,
            'bid'    => $bookingId,
            'amt'    => (float)$data['amount'],
            'method' => $data['payment_method'],
            'trans'  => $data['transaction_id'] ?? null,
            'notes'  => null
        ]);

        // Auto-update booking payment status based on new balance
        $folio = $this->getFolioDetails($hotelId, $bookingId);
        $newStatus = ($folio['balanceDue'] <= 0) ? 'paid' : 'pending';
        
        $updateStmt = $db->prepare("UPDATE bookings SET payment_status = :status WHERE id = :bid");
        $updateStmt->execute(['status' => $newStatus, 'bid' => $bookingId]);
    }

    public function addPosCharge(int $hotelId, int $bookingId, string $description, float $amount): void
    {
        $db = Database::getConnection();
        
        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM bookings WHERE id = :bid AND hotel_id = :hid");
        $stmt->execute(['bid' => $bookingId, 'hid' => $hotelId]);
        if (!$stmt->fetch()) throw new Exception("Unauthorized folio access.");

        $insertStmt = $db->prepare("INSERT INTO pos_charges (booking_id, description, amount) VALUES (:bid, :desc, :amt)");
        $insertStmt->execute([
            'bid'  => $bookingId,
            'desc' => $description,
            'amt'  => $amount
        ]);
    }
}