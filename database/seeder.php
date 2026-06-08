<?php
/**
 * Database Seeder for Syncro PMS
 * Run via CLI: php database/seeder.php
 */

require_once __DIR__ . '/../core/init.php';

use Syncro\Models\Database;

try {
    $db = Database::getConnection();
    
    echo "Starting Database Seeder...\n";

    // 1. Create a dummy user (Admin for this hotel)
    $rand = rand(1000, 9999);
    $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'hotel_admin', 'active')");
    $stmt->execute([
        'M. Gustave',
        "gustave{$rand}@grandbudapest.com",
        password_hash('password123', PASSWORD_DEFAULT)
    ]);
    $userId = $db->lastInsertId();
    echo "User created: ID $userId\n";

    // 2. Create a dummy hotel
    $hotelName = 'Grand Budapest Hotel';
    $stmt = $db->prepare("INSERT INTO hotels (user_id, property_name, api_token) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $hotelName, bin2hex(random_bytes(32))]);
    $hotelId = $db->lastInsertId();
    echo "Hotel created: ID $hotelId\n";
    
    // Update user with hotel ID
    $db->query("UPDATE users SET hotel_id = $hotelId WHERE id = $userId");

    // 3. Create room types
    $roomTypes = [
        ['name' => 'Standard Room', 'base_price' => 150.00, 'code' => 'STD'],
        ['name' => 'Deluxe Suite', 'base_price' => 300.00, 'code' => 'DLX'],
        ['name' => 'Presidential Suite', 'base_price' => 850.00, 'code' => 'PRS']
    ];
    
    $roomTypeIds = [];
    foreach ($roomTypes as $rt) {
        $stmt = $db->prepare("INSERT INTO room_types (hotel_id, name, base_price, local_room_code) VALUES (?, ?, ?, ?)");
        $stmt->execute([$hotelId, $rt['name'], $rt['base_price'], $rt['code']]);
        $roomTypeIds[] = $db->lastInsertId();
    }
    echo "Room types created.\n";

    // 4. Create rooms
    $rooms = [
        ['room_number' => '101', 'type_id' => $roomTypeIds[0]],
        ['room_number' => '102', 'type_id' => $roomTypeIds[0]],
        ['room_number' => '201', 'type_id' => $roomTypeIds[1]],
        ['room_number' => '202', 'type_id' => $roomTypeIds[1]],
        ['room_number' => '301', 'type_id' => $roomTypeIds[2]]
    ];
    
    $roomIds = [];
    foreach ($rooms as $r) {
        $stmt = $db->prepare("INSERT INTO rooms (hotel_id, room_type_id, room_number, housekeeping_status) VALUES (?, ?, ?, 'clean')");
        $stmt->execute([$hotelId, $r['type_id'], $r['room_number']]);
        $roomIds[] = $db->lastInsertId();
    }
    echo "Rooms created.\n";

    // 5. Create some bookings
    $statuses = ['confirmed', 'checked_in', 'checked_out', 'cancelled'];
    
    for ($i = 1; $i <= 10; $i++) {
        $checkIn = date('Y-m-d', strtotime(sprintf('-%d days', rand(1, 30))));
        $checkOut = date('Y-m-d', strtotime($checkIn . ' + ' . rand(1, 5) . ' days'));
        
        $status = $statuses[array_rand($statuses)];
        $roomTypeIdx = array_rand($roomTypeIds);
        $typeId = $roomTypeIds[$roomTypeIdx];
        $roomId = $roomIds[array_rand($roomIds)];
        $price = $roomTypes[$roomTypeIdx]['base_price'] * ((strtotime($checkOut) - strtotime($checkIn)) / 86400);

        $stmt = $db->prepare("INSERT INTO bookings (hotel_id, room_type_id, assigned_room_id, guest_name, guest_email, guest_phone, check_in, check_out, status, total_price, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $hotelId,
            $typeId,
            $roomId,
            "Guest $i",
            "guest$i@example.com",
            "555-000$i",
            $checkIn,
            $checkOut,
            $status,
            $price,
            'paid'
        ]);
        $bookingId = $db->lastInsertId();

        // Generate payments for this booking
        $stmt = $db->prepare("INSERT INTO payments (hotel_id, booking_id, amount, payment_method, transaction_id) VALUES (?, ?, ?, 'Credit Card', ?)");
        $stmt->execute([$hotelId, $bookingId, $price, 'pi_dummy_' . bin2hex(random_bytes(8))]);
    }

    echo "Bookings and payments created.\n";
    echo "\nSeeding Completed Successfully!\n";
    
} catch (\Exception $e) {
    echo "Seeder failed: " . $e->getMessage() . "\n";
}
