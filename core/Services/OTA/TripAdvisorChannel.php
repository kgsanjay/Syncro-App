<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class TripAdvisorChannel implements OtaChannelInterface
{
    private const ENDPOINT_INVENTORY = 'https://api.tripadvisor.com/v1/inventory';
    private const ENDPOINT_BOOKINGS = 'https://api.tripadvisor.com/v1/bookings';
    
    private string $apiKey;

    public function __construct(string $apiKey) 
    {
        $this->apiKey = $apiKey;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool 
    {
        $ratesAndAvailability = [];
        
        foreach ($inventoryData as $inv) {
            $availableRooms = (int)$inv['available_rooms'];
            
            // If inventory drops to 0 or stop_sell is triggered, block the room
            $isClosed = ($availableRooms <= 0 || !empty($inv['stop_sell']));
            
            $ratesAndAvailability[] = [
                'date'      => $inv['target_date'],
                'inventory' => $availableRooms,
                'price'     => (float)$inv['dynamic_price'],
                'currency'  => 'INR',
                'closed'    => $isClosed
            ];
        }

        $jsonPayload = json_encode([
            'property_id' => $hotelId,
            'room_type'   => $otaRoomCode,
            'rates'       => $ratesAndAvailability
        ]);

        return $this->sendRequest(self::ENDPOINT_INVENTORY, $jsonPayload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        // Request payload to fetch new unread bookings from TripAdvisor
        $jsonPayload = json_encode([
            'property_id' => $hotelId,
            'status'      => 'NEW'
        ]);

        $responseJson = $this->sendRequest(self::ENDPOINT_BOOKINGS, $jsonPayload, true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses TripAdvisor's JSON response into the standard PMS array format
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
                $firstName = $booking['guest']['first_name'] ?? '';
                $lastName = $booking['guest']['last_name'] ?? '';

                $newBookings[] = [
                    'ota_source'     => 'TripAdvisor',
                    'ota_booking_id' => (string)($booking['booking_id'] ?? ''),
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($booking['check_in_date'] ?? '')),
                    'check_out'      => date('Y-m-d', strtotime($booking['check_out_date'] ?? '')),
                    'ota_room_code'  => (string)($booking['room_type'] ?? ''),
                    'total_price'    => (float)($booking['total_price'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("TripAdvisor JSON Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * Executes the secure cURL request
     *
     * @param string $url
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-TripAdvisor-API-Key: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        // Enforce strict TLS verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("TripAdvisor API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}