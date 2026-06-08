<?php
declare(strict_types=1);

namespace Syncro\Services;

use Syncro\Models\Database;
use Exception;

class GuestService
{
    public function getAllGuests(int $hotelId): array
    {
        return Database::table('guests')
            ->where('hotel_id', $hotelId)
            ->orderBy('last_visit_date', 'DESC')
            ->get();
    }

    public function getGuestProfile(int $hotelId, int $guestId): ?array
    {
        $guest = Database::table('guests')
            ->where('id', $guestId)
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$guest) {
            return null;
        }

        // Fetch the guest's past stays
        $history = Database::table('bookings')
            ->where('guest_id', $guestId)
            ->where('hotel_id', $hotelId)
            ->orderBy('check_in_date', 'DESC')
            ->get();

        $guest['history'] = $history;
        return $guest;
    }

    /**
     * Called during checkout or booking creation to automatically build the CRM.
     */
    public function createOrUpdateGuest(int $hotelId, array $data): int
    {
        // Attempt to find an existing guest by Email or Phone
        $existing = Database::table('guests')
            ->where('email', $data['email'])
            ->where('hotel_id', $hotelId)
            ->first();

        if (!$existing && !empty($data['phone'])) {
            $existing = Database::table('guests')
                ->where('phone', $data['phone'])
                ->where('hotel_id', $hotelId)
                ->first();
        }

        if ($existing) {
            // Update existing guest CRM stats
            Database::table('guests')
                ->where('id', $existing['id'])
                ->update([
                    'total_stays'     => (int)$existing['total_stays'] + 1,
                    'last_visit_date' => $data['check_in_date'] ?? $existing['last_visit_date'],
                    'full_name'       => $data['full_name'] // Update name in case of marriage/correction
                ]);
            return (int)$existing['id'];
        }

        // Create a brand new guest profile
        return Database::table('guests')->insert([
            'hotel_id'        => $hotelId,
            'full_name'       => $data['full_name'],
            'email'           => $data['email'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'total_stays'     => 1,
            'last_visit_date' => $data['check_in_date'] ?? date('Y-m-d')
        ]);
    }
}