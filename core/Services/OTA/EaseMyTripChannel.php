<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class EaseMyTripChannel implements OtaChannelInterface
{
    private const ENDPOINT_INVENTORY = 'https://api.easemytrip.com/extranet/v1/inventory';
    private const ENDPOINT_BOOKINGS = 'https://api.easemytrip.com/extranet/v1/bookings';
    
    private string $apiKey;

    public function __construct(string $apiKey) 
    {
        $this->apiKey = $apiKey;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool 
    {
        $inventoryList = [];
        
        foreach ($inventoryData as $inv) {
            $availableRooms = (int)$inv['available_rooms'];
            
            // If inventory drops to 0 or stop_sell is triggered, block the room
            $isBlocked = ($availableRooms <= 0 || !empty($inv['stop_sell']));
            
            $inventoryList[] = [
                'RoomCode'  => $otaRoomCode,
                'FromDate'  => $inv['target_date'],
                'ToDate'    => $inv['target_date'],
                'Inventory' => $availableRooms,
                'Price'     => (float)$inv['dynamic_price'],
                'Block'     => $isBlocked
            ];
        }

        // EaseMyTrip expects the token inside the JSON body
        $jsonPayload = json_encode([
            'Token'     => $this->apiKey, 
            'HotelCode' => $hotelId, 
            'Inventory' => $inventoryList
        ]);

        return $this->sendRequest(self::ENDPOINT_INVENTORY, $jsonPayload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        // Payload to fetch unread/new bookings
        $jsonPayload = json_encode([
            'Token'     => $this->apiKey,
            'HotelCode' => $hotelId,
            'Status'    => 'NEW'
        ]);

        $responseJson = $this->sendRequest(self::ENDPOINT_BOOKINGS, $jsonPayload, true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses EaseMyTrip's JSON response into the standard PMS array format
     */
    private function parseBookingResponse(string $jsonString): array
    {
        $newBookings = [];
        try {
            $data = json_decode($jsonString, true);

            if (empty($data['Bookings'])) {
                return [];
            }

            foreach ($data['Bookings'] as $booking) {
                $firstName = $booking['GuestFirstName'] ?? '';
                $lastName = $booking['GuestLastName'] ?? '';

                $newBookings[] = [
                    'ota_source'     => 'EaseMyTrip',
                    'ota_booking_id' => (string)($booking['BookingId'] ?? ''),
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($booking['CheckInDate'])),
                    'check_out'      => date('Y-m-d', strtotime($booking['CheckOutDate'])),
                    'ota_room_code'  => (string)($booking['RoomCode'] ?? ''),
                    'total_price'    => (float)($booking['TotalAmount'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("EaseMyTrip JSON Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * Executes the secure cURL request
     * * @param string $url
     * @param string $payload
     * @param bool $returnResponse If true, returns the raw JSON string instead of boolean success
     * @return bool|string
     */
    private function sendRequest(string $url, string $payload, bool $returnResponse = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        // Enforce strict TLS verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("EaseMyTrip API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}