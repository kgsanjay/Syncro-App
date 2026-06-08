<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

interface OtaChannelInterface
{
    /**
     * Push inventory (availability) and pricing to the OTA.
     * * @param string $hotelId The OTA's internal ID for the hotel.
     * @param string $otaRoomCode The specific room code mapped to the OTA.
     * @param array $inventoryData Array of dates, available rooms, and dynamic prices.
     * @return bool True if sync was successful, false otherwise.
     */
    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool;

    /**
     * Fetch new bookings from the OTA.
     * * @param string $hotelId
     * @return array List of new bookings to be inserted into your PMS.
     */
    public function fetchBookings(string $hotelId): array;
}