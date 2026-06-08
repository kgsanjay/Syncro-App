<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class MakeMyTripChannel implements OtaChannelInterface
{
    private const ENDPOINT_PUSH = 'https://api.makemytrip.com/cm/v1/update';
    private const ENDPOINT_PULL = 'https://api.makemytrip.com/cm/v1/bookings'; // Standard MMT pull endpoint
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool
    {
        $updates = [];
        foreach ($inventoryData as $inv) {
            $availableRooms = (int)$inv['available_rooms'];
            
            $updates[] = [
                'roomTypeCode' => $otaRoomCode,
                'startDate'    => $inv['target_date'],
                'endDate'      => $inv['target_date'],
                'inventory'    => $availableRooms,
                'price'        => (float)$inv['dynamic_price'],
                'currency'     => 'INR',
                // If inventory drops to 0 or stop_sell is triggered, block the room
                'block'        => ($availableRooms <= 0 || !empty($inv['stop_sell']))
            ];
        }

        // json_encode natively protects against injection payloads
        $jsonPayload = json_encode([
            'hotelId' => $hotelId,
            'updates' => $updates
        ]);

        return $this->sendRequest(self::ENDPOINT_PUSH, $jsonPayload);
    }

    public function fetchBookings(string $hotelId): array
    {
        // Example Payload to fetch unread bookings from MMT
        $jsonPayload = json_encode([
            'hotelId' => $hotelId,
            'status'  => 'UNREAD' // MMT specific flag for new bookings
        ]);

        $responseJson = $this->sendRequest(self::ENDPOINT_PULL, $jsonPayload, true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses MMT's JSON response into the standard PMS array format
     */
    private function parseBookingResponse(string $jsonString): array
    {
        $newBookings = [];
        try {
            $data = json_decode($jsonString, true);

            if (empty($data['bookings'])) {
                return [];
            }

            foreach ($data['bookings'] as $booking) {
                $newBookings[] = [
                    'ota_source'     => 'MakeMyTrip',
                    'ota_booking_id' => (string)$booking['bookingId'],
                    'guest_name'     => trim(($booking['guestFirstName'] ?? '') . ' ' . ($booking['guestLastName'] ?? '')),
                    'check_in'       => date('Y-m-d', strtotime($booking['checkInDate'])),
                    'check_out'      => date('Y-m-d', strtotime($booking['checkOutDate'])),
                    'ota_room_code'  => (string)$booking['roomTypeCode'],
                    'total_price'    => (float)($booking['totalAmount'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("MakeMyTrip JSON Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * Executes the secure cURL request
     * * @param string $url
     * @param string $payload
     * @param bool $returnResponse If true, returns the raw JSON string
     * @return bool|string
     */
    private function sendRequest(string $url, string $payload, bool $returnResponse = false)
    {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        
        // Enforce strict TLS verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("MakeMyTrip API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}