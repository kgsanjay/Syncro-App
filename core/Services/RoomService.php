<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use PDO;
use Exception;

class RoomService
{
    public function getRoomTypes(int $hotelId): array
    {
        return Database::table('room_types')
            ->where('hotel_id', $hotelId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getPhysicalRooms(int $hotelId): array
    {
        // We keep raw PDO for this specific method because it utilizes a JOIN
        // which is better handled via raw SQL in a lightweight ORM.
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT pr.*, rt.name as type_name 
            FROM rooms pr 
            JOIN room_types rt ON pr.room_type_id = rt.id 
            WHERE pr.hotel_id = :hid 
            ORDER BY rt.name ASC, pr.room_number ASC
        ");
        $stmt->execute(['hid' => $hotelId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRoomType(int $hotelId, array $data, string $imageUrl): bool
    {
        Database::table('room_types')->insert([
            'hotel_id'        => $hotelId,
            'name'            => $data['name'],
            'local_room_code' => $data['local_room_code'],
            'base_price'      => $data['base_price'],
            'description'     => $data['description'],
            'image_url'       => $imageUrl,
            'amenities'       => $data['amenities']
        ]);

        return true;
    }

    public function updateRoomType(int $hotelId, int $roomId, array $data, string $imageUrl): bool
    {
        return Database::table('room_types')
            ->where('id', $roomId)
            ->where('hotel_id', $hotelId)
            ->update([
                'name'            => $data['name'],
                'local_room_code' => $data['local_room_code'],
                'base_price'      => $data['base_price'],
                'description'     => $data['description'],
                'image_url'       => $imageUrl,
                'amenities'       => $data['amenities']
            ]);
    }

    public function deleteRoomType(int $hotelId, int $roomId): void
    {
        // Check for active folios before deletion
        $activeBookings = Database::table('bookings')
            ->where('room_type_id', $roomId)
            ->where('hotel_id', $hotelId)
            ->first();

        if ($activeBookings) {
            throw new Exception('Cannot delete a room type with active folios.');
        }

        Database::table('room_types')
            ->where('id', $roomId)
            ->where('hotel_id', $hotelId)
            ->delete();
    }

    public function createPhysicalRoom(int $hotelId, array $data, string $imageUrl): bool
    {
        Database::table('rooms')->insert([
            'hotel_id'            => $hotelId,
            'room_type_id'        => $data['room_type_id'],
            'room_number'         => $data['room_number'],
            'housekeeping_status' => 'clean',
            'image_url'           => $imageUrl
        ]);

        return true;
    }

    public function updatePhysicalRoom(int $hotelId, int $roomId, array $data): bool
    {
        return Database::table('rooms')
            ->where('id', $roomId)
            ->where('hotel_id', $hotelId)
            ->update([
                'room_type_id' => $data['room_type_id'],
                'room_number'  => $data['room_number']
            ]);
    }

    public function deletePhysicalRoom(int $hotelId, int $roomId): void
    {
        // Check if room is currently assigned to a folio
        $activeAssignments = Database::table('bookings')
            ->where('assigned_room_id', $roomId)
            ->where('hotel_id', $hotelId)
            ->first();
        
        if ($activeAssignments) {
            throw new Exception('Room is currently assigned to a folio.');
        }

        Database::table('rooms')
            ->where('id', $roomId)
            ->where('hotel_id', $hotelId)
            ->delete();
    }
}