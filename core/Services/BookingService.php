<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use PDO;
use Exception;
use DatePeriod;
use DateInterval;
use DateTime;

class BookingService
{
    /**
     * Fetch all bookings for a hotel, optionally filtered by status.
     */
    public function getBookings(int $hotelId, string $statusFilter = 'all'): array
    {
        $db = Database::getConnection();
        
        $query = "
            SELECT b.*, r.name as room_name, pr.room_number as physical_room_number 
            FROM bookings b 
            JOIN room_types r ON b.room_type_id = r.id 
            LEFT JOIN rooms pr ON b.assigned_room_id = pr.id
            WHERE b.hotel_id = :hid 
        ";
        
        $params = ['hid' => $hotelId];

        if ($statusFilter !== 'all') {
            $query .= " AND b.status = :status ";
            $params['status'] = $statusFilter;
        }

        $query .= " ORDER BY b.check_in ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a manual, direct booking from the front desk.
     * Returns the new Booking ID so we can use it in confirmation emails.
     */
    public function createDirectBooking(int $hotelId, array $postData): int
    {
        $guestName = strip_tags(trim($postData['guest_name'] ?? ''));
        $guestEmail = trim(filter_var($postData['guest_email'] ?? '', FILTER_SANITIZE_EMAIL));
        $guestPhone = strip_tags(trim($postData['guest_phone'] ?? ''));
        
        $roomId = (int)($postData['room_type_id'] ?? 0);
        $checkIn = $postData['check_in'] ?? '';
        $checkOut = $postData['check_out'] ?? '';
        $source = strip_tags(trim($postData['source'] ?? 'Direct Walk-in'));
        $price = (float)($postData['total_price'] ?? 0); // Added to support manual pricing

        if (empty($guestName) || !$roomId || empty($checkIn) || empty($checkOut)) {
            throw new Exception("Missing required booking details.");
        }

        if (strtotime($checkIn) >= strtotime($checkOut)) {
            throw new Exception("Check-out date must be after check-in date.");
        }

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT id, base_price FROM room_types WHERE id = :rid AND hotel_id = :hid");
            $stmt->execute(['rid' => $roomId, 'hid' => $hotelId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) throw new Exception("IDOR Security Block: Room type invalid.");

            $period = new DatePeriod(new DateTime($checkIn), DateInterval::createFromDateString('1 day'), new DateTime($checkOut));

            // 1. Verify Availability (Prevent Overbooking)
            foreach ($period as $dt) {
                $targetDate = $dt->format('Y-m-d');
                $checkStmt = $db->prepare("SELECT available_rooms, stop_sell FROM inventory_tape WHERE room_type_id = :rid AND target_date = :date FOR UPDATE");
                $checkStmt->execute(['rid' => $roomId, 'date' => $targetDate]);
                $inv = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($inv && ((int)$inv['stop_sell'] === 1 || (int)$inv['available_rooms'] <= 0)) {
                    throw new Exception("Room unavailable on {$targetDate}");
                }
            }

            // 2. Insert the Booking
            $stmt = $db->prepare("
                INSERT INTO bookings (hotel_id, room_type_id, guest_name, guest_email, guest_phone, check_in, check_out, total_price, source, status, payment_status, created_at) 
                VALUES (:hid, :rid, :guest, :email, :phone, :cin, :cout, :price, :src, 'confirmed', 'pending', NOW())
            ");
            $stmt->execute([
                'hid'   => $hotelId, 
                'rid'   => $roomId, 
                'guest' => $guestName,
                'email' => empty($guestEmail) ? null : $guestEmail,
                'phone' => empty($guestPhone) ? null : $guestPhone,
                'cin'   => $checkIn, 
                'cout'  => $checkOut, 
                'price' => $price, // Save the actual price
                'src'   => $source
            ]);
            
            $bookingId = (int)$db->lastInsertId();

            // 3. Deduct from Inventory Tape
            $countStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :rid AND hotel_id = :hid");
            $countStmt->execute(['rid' => $roomId, 'hid' => $hotelId]);
            $totalPhysicalRooms = (int)$countStmt->fetchColumn();
            $initialAvailability = max(0, $totalPhysicalRooms - 1);

            $updateStmt = $db->prepare("
                INSERT INTO inventory_tape (room_type_id, target_date, available_rooms, dynamic_price, stop_sell, version) 
                VALUES (:rid, :date, :initial_avail, :price, 0, 1) 
                ON DUPLICATE KEY UPDATE 
                available_rooms = GREATEST(0, available_rooms - 1),
                version = version + 1
            ");

            foreach ($period as $dt) {
                $updateStmt->execute([
                    'rid'           => $roomId, 
                    'date'          => $dt->format('Y-m-d'), 
                    'initial_avail' => $initialAvailability, 
                    'price'         => $room['base_price']
                ]);
            }

            // 4. Update the CRM Directory (Guests Table)
            // ---> BUG FIXED HERE: Changed 'lifetime_revenue' to 'total_revenue' <---
            $guestCheck = $db->prepare("SELECT id FROM guests WHERE full_name = :name AND hotel_id = :hid");
            $guestCheck->execute(['name' => $guestName, 'hid' => $hotelId]);
            $guest = $guestCheck->fetch();

            if (!$guest) {
                $guestInsert = $db->prepare("INSERT INTO guests (hotel_id, full_name, total_revenue, created_at) VALUES (:hid, :name, :rev, NOW())");
                $guestInsert->execute(['hid' => $hotelId, 'name' => $guestName, 'rev' => $price]);
            } else {
                $guestUpdate = $db->prepare("UPDATE guests SET total_revenue = total_revenue + :rev WHERE id = :id");
                $guestUpdate->execute(['rev' => $price, 'id' => $guest['id']]);
            }

            // 5. Trigger Notification
            \Syncro\Services\NotificationService::send(
                $hotelId,
                "New Booking Received",
                "A new booking (#{$bookingId}) was created for {$guestName}."
            );

            $db->commit();
            return $bookingId;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Assign a physical room to a booking
     */
    public function assignPhysicalRoom(int $hotelId, int $bookingId, int $physicalRoomId): void
    {
        $db = Database::getConnection();
        
        $stmtBooking = $db->prepare("SELECT room_type_id FROM bookings WHERE id = :bid AND hotel_id = :hid");
        $stmtBooking->execute(['bid' => $bookingId, 'hid' => $hotelId]);
        $booking = $stmtBooking->fetch();

        if (!$booking) throw new Exception("Booking not found.");

        $stmtRoom = $db->prepare("SELECT room_type_id FROM rooms WHERE id = :rid AND hotel_id = :hid");
        $stmtRoom->execute(['rid' => $physicalRoomId, 'hid' => $hotelId]);
        $room = $stmtRoom->fetch();

        if (!$room) throw new Exception("Physical room not found.");

        if ((int)$booking['room_type_id'] !== (int)$room['room_type_id']) {
            throw new Exception("Mismatch Block: You cannot assign a physical room that belongs to a different category.");
        }

        $stmtUpdate = $db->prepare("UPDATE bookings SET assigned_room_id = :rid WHERE id = :bid AND hotel_id = :hid");
        $stmtUpdate->execute(['rid' => $physicalRoomId, 'bid' => $bookingId, 'hid' => $hotelId]);
    }

    /**
     * Update the status of a booking (confirmed, cancelled, checked_in, checked_out)
     */
    public function updateStatus(int $hotelId, int $bookingId, string $status): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT status, check_in, check_out, room_type_id, assigned_room_id FROM bookings WHERE id = :bid AND hotel_id = :hid");
            $stmt->execute(['bid' => $bookingId, 'hid' => $hotelId]);
            $booking = $stmt->fetch();

            if (!$booking) throw new Exception("Booking not found");

            $oldStatus = $booking['status'];

            $stmt = $db->prepare("UPDATE bookings SET status = :status WHERE id = :bid AND hotel_id = :hid");
            $stmt->execute(['status' => $status, 'bid' => $bookingId, 'hid' => $hotelId]);

            $period = new DatePeriod(new DateTime($booking['check_in']), DateInterval::createFromDateString('1 day'), new DateTime($booking['check_out']));

            // Handle Inventory Logic on Cancellation
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                $countStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE room_type_id = :rid AND hotel_id = :hid");
                $countStmt->execute(['rid' => $booking['room_type_id'], 'hid' => $hotelId]);
                $totalPhysical = (int)$countStmt->fetchColumn();

                $restoreStmt = $db->prepare("
                    UPDATE inventory_tape 
                    SET available_rooms = LEAST(:total_physical, available_rooms + 1), version = version + 1 
                    WHERE room_type_id = :rid AND target_date = :date
                ");
                foreach ($period as $dt) {
                    $restoreStmt->execute(['total_physical' => $totalPhysical, 'rid' => $booking['room_type_id'], 'date' => $dt->format('Y-m-d')]);
                }
            } 
            // Handle Inventory Logic if Un-cancelled
            elseif ($status === 'confirmed' && $oldStatus === 'cancelled') {
                $deductStmt = $db->prepare("
                    UPDATE inventory_tape 
                    SET available_rooms = GREATEST(0, available_rooms - 1), version = version + 1 
                    WHERE room_type_id = :rid AND target_date = :date
                ");
                foreach ($period as $dt) {
                    $deductStmt->execute(['rid' => $booking['room_type_id'], 'date' => $dt->format('Y-m-d')]);
                }
            }

            // Handle Housekeeping logic on Check-Out
            if ($status === 'checked_out' && $booking['assigned_room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET housekeeping_status = 'dirty' WHERE id = :rid");
                $stmt->execute(['rid' => $booking['assigned_room_id']]);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update the payment status of a booking
     */
    public function updatePaymentStatus(int $hotelId, int $bookingId, string $paymentStatus): void
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT id FROM bookings WHERE id = :bid AND hotel_id = :hid");
        $stmt->execute(['bid' => $bookingId, 'hid' => $hotelId]);
        if (!$stmt->fetch()) throw new Exception("Unauthorized access.");

        $stmtUpdate = $db->prepare("UPDATE bookings SET payment_status = :pstatus WHERE id = :bid AND hotel_id = :hid");
        $stmtUpdate->execute(['pstatus' => $paymentStatus, 'bid' => $bookingId, 'hid' => $hotelId]);
    }
}