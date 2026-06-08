<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    http_response_code(403);
    die('Forbidden: Must be run via CLI.');
}

require_once __DIR__ . '/../core/init.php';

use Syncro\Models\Database;

try {
    $db = Database::getConnection();

    // Find the first hotel
    $stmt = $db->query("SELECT id FROM hotels LIMIT 1");
    $hotel = $stmt->fetch();
    if (!$hotel) {
        die("No hotel found. Please create a hotel first.\n");
    }
    $hotelId = $hotel['id'];

    // Ensure we have at least one room type and room
    $stmt = $db->query("SELECT id, base_price FROM room_types WHERE hotel_id = $hotelId LIMIT 1");
    $roomType = $stmt->fetch();
    if (!$roomType) {
        $db->query("INSERT INTO room_types (hotel_id, name, local_room_code, base_price) VALUES ($hotelId, 'Deluxe Room', 'DLX', 2500)");
        $roomTypeId = $db->lastInsertId();
        $basePrice = 2500;
    } else {
        $roomTypeId = $roomType['id'];
        $basePrice = $roomType['base_price'];
    }

    $stmt = $db->query("SELECT id FROM rooms WHERE hotel_id = $hotelId LIMIT 1");
    $room = $stmt->fetch();
    if (!$room) {
        $db->query("INSERT INTO rooms (hotel_id, room_type_id, room_number, housekeeping_status) VALUES ($hotelId, $roomTypeId, '101', 'clean')");
        $roomId = $db->lastInsertId();
    } else {
        $roomId = $room['id'];
    }

    echo "Cleaning existing dummy data...\n";
    $db->query("DELETE FROM pos_charges");
    $db->query("DELETE FROM bookings WHERE source IN ('Direct', 'Booking.com', 'Agoda', 'MakeMyTrip')");
    $db->query("DELETE FROM guests");
    $db->query("DELETE FROM expenses");

    echo "Generating Guests...\n";
    $guestNames = ['John Doe', 'Jane Smith', 'Amit Patel', 'Priya Sharma', 'Rahul Singh', 'Anita Desai', 'Vikram Malhotra', 'Neha Gupta'];
    $guestIds = [];
    foreach ($guestNames as $name) {
        $email = strtolower(str_replace(' ', '.', $name)) . rand(10, 99) . '@example.com';
        $phone = '98' . rand(10000000, 99999999);
        $db->query("INSERT INTO guests (hotel_id, full_name, email, phone, total_stays, total_revenue) VALUES ($hotelId, '$name', '$email', '$phone', 0, 0)");
        $guestIds[] = $db->lastInsertId();
    }

    echo "Generating Bookings...\n";
    $sources = ['Direct', 'Booking.com', 'Agoda', 'MakeMyTrip'];
    $statuses = ['confirmed', 'checked_out', 'checked_in', 'cancelled'];

    $insertBooking = $db->prepare("
        INSERT INTO bookings (hotel_id, guest_id, room_type_id, assigned_room_id, guest_name, guest_email, guest_phone, check_in, check_out, total_price, source, status, payment_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $insertPos = $db->prepare("
        INSERT INTO pos_charges (booking_id, description, amount, created_at)
        VALUES (?, ?, ?, ?)
    ");

    $startDate = new DateTime('-30 days');
    
    for ($i = 0; $i < 60; $i++) {
        $guestId = $guestIds[array_rand($guestIds)];
        // Get guest details
        $gStmt = $db->query("SELECT full_name, email, phone FROM guests WHERE id = $guestId");
        $guest = $gStmt->fetch();

        $checkInDate = clone $startDate;
        $checkInDate->modify('+' . rand(0, 60) . ' days');
        $nights = rand(1, 5);
        $checkOutDate = clone $checkInDate;
        $checkOutDate->modify('+' . $nights . ' days');

        $source = $sources[array_rand($sources)];
        
        // Status logic
        $now = new DateTime();
        if ($checkOutDate < $now) {
            $status = 'checked_out';
            $paymentStatus = 'paid';
        } elseif ($checkInDate <= $now && $checkOutDate >= $now) {
            $status = 'checked_in';
            $paymentStatus = rand(0,1) ? 'paid' : 'pending';
        } else {
            $status = 'confirmed';
            $paymentStatus = rand(0,1) ? 'paid' : 'pending';
        }

        $totalPrice = $basePrice * $nights;
        if ($source !== 'Direct') {
            $totalPrice = $totalPrice * 1.1; // Mark up for OTAs
        }

        // Random created_at
        $createdAt = clone $checkInDate;
        $createdAt->modify('-' . rand(1, 15) . ' days');

        $insertBooking->execute([
            $hotelId, $guestId, $roomTypeId, $roomId, 
            $guest['full_name'], $guest['email'], $guest['phone'],
            $checkInDate->format('Y-m-d'), $checkOutDate->format('Y-m-d'),
            $totalPrice, $source, $status, $paymentStatus, $createdAt->format('Y-m-d H:i:s')
        ]);
        $bookingId = $db->lastInsertId();

        // Update guest total
        if ($status === 'checked_out') {
            $db->query("UPDATE guests SET total_stays = total_stays + 1, total_revenue = total_revenue + $totalPrice, last_visit_date = '{$checkInDate->format('Y-m-d')}' WHERE id = $guestId");
            
            // Add some POS charges
            if (rand(0, 1) === 1) {
                $posAmount = rand(500, 2000);
                $insertPos->execute([
                    $bookingId,
                    'Restaurant & Bar',
                    $posAmount,
                    $checkInDate->format('Y-m-d H:i:s')
                ]);
                $db->query("UPDATE guests SET total_revenue = total_revenue + $posAmount WHERE id = $guestId");
            }
        }
    }

    echo "Generating Expenses...\n";
    $categories = ['Electricity', 'Payroll', 'Maintenance', 'Marketing', 'Supplies'];
    $insertExp = $db->prepare("INSERT INTO expenses (hotel_id, category, amount, date, description) VALUES (?, ?, ?, ?, ?)");
    for ($i = 0; $i < 20; $i++) {
        $cat = $categories[array_rand($categories)];
        $amt = rand(1000, 15000);
        $date = clone $startDate;
        $date->modify('+' . rand(0, 30) . ' days');
        $insertExp->execute([
            $hotelId, $cat, $amt, $date->format('Y-m-d'), "Monthly $cat Expense"
        ]);
    }

    echo "Seeding completed successfully!\n";

} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
