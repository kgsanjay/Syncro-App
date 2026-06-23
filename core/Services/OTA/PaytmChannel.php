<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class PaytmChannel implements OtaChannelInterface
{
    private const ENDPOINT_INVENTORY = 'https://travel.paytm.com/api/v1/hotel/inventory';
    private const ENDPOINT_BOOKINGS = 'https://travel.paytm.com/api/v1/hotel/bookings';
    
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
            
            // If inventory drops to 0 or stop_sell is triggered, block the room
            $isBlocked = ($availableRooms <= 0 || !empty($inv['stop_sell']));
            
            $updates[] = [
                'roomId'    => $otaRoomCode,
                'date'      => $inv['target_date'],
                'inventory' => $availableRooms,
                'price'     => (float)$inv['dynamic_price'],
                'block'     => $isBlocked
            ];
        }

        $jsonPayload = json_encode([
            'hotelId' => $hotelId,
            'updates' => $updates
        ]);

        return $this->sendRequest(self::ENDPOINT_INVENTORY, $jsonPayload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        // Request payload to fetch new unread bookings from Paytm
        $jsonPayload = json_encode([
            'hotelId' => $hotelId,
            'status'  => 'NEW'
        ]);

        $responseJson = $this->sendRequest(self::ENDPOINT_BOOKINGS, $jsonPayload, true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses Paytm's JSON response into the standard PMS array format
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
                // Paytm usually nests guest data
                $firstName = $booking['guestDetails']['firstName'] ?? '';
                $lastName = $booking['guestDetails']['lastName'] ?? '';

                $newBookings[] = [
                    'ota_source'     => 'Paytm',
                    'ota_booking_id' => (string)($booking['bookingId'] ?? ''),
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($booking['checkIn'] ?? '')),
                    'check_out'      => date('Y-m-d', strtotime($booking['checkOut'] ?? '')),
                    'ota_room_code'  => (string)($booking['roomId'] ?? ''),
                    'total_price'    => (float)($booking['totalAmount'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("Paytm JSON Parse Error: " . $e->getMessage());
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
            'client_id: ' . $this->apiKey // Paytm specific authentication header
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        // Enforce strict TLS verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            \Syncro\Services\OtaIntegrationService::handleApiError(0, defined('self::ENDPOINT') ? self::ENDPOINT : (defined('self::ENDPOINT_PUSH') ? self::ENDPOINT_PUSH : 'OTA'), $payload ?? '', 0, "cURL Error: " . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 500) {
            \Syncro\Services\OtaIntegrationService::handleApiError(0, defined('self::ENDPOINT') ? self::ENDPOINT : (defined('self::ENDPOINT_PUSH') ? self::ENDPOINT_PUSH : 'OTA'), $payload ?? '', $httpCode, "HTTP 50x Error");
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("Paytm API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}