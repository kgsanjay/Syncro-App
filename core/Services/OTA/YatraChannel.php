<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class YatraChannel implements OtaChannelInterface
{
    private const ENDPOINT_INVENTORY = 'https://b2bapi.yatra.com/inventory/update';
    private const ENDPOINT_BOOKINGS = 'https://b2bapi.yatra.com/bookings/fetch';
    
    private string $apiKey;

    public function __construct(string $apiKey) 
    {
        $this->apiKey = $apiKey;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool 
    {
        $dailyUpdates = [];
        
        foreach ($inventoryData as $inv) {
            $availableRooms = (int)$inv['available_rooms'];
            
            // If inventory drops to 0 or stop_sell is triggered, block the room
            $isBlocked = ($availableRooms <= 0 || !empty($inv['stop_sell']));
            
            $dailyUpdates[] = [
                'room_code' => $otaRoomCode,
                'date'      => $inv['target_date'],
                'inventory' => $availableRooms,
                'price'     => (float)$inv['dynamic_price'],
                'block'     => $isBlocked
            ];
        }

        $jsonPayload = json_encode([
            'hotel_id' => $hotelId,
            'data'     => $dailyUpdates
        ]);

        return $this->sendRequest(self::ENDPOINT_INVENTORY, $jsonPayload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        // Request payload to fetch new unread bookings from Yatra
        $jsonPayload = json_encode([
            'hotel_id' => $hotelId,
            'status'   => 'UNREAD'
        ]);

        $responseJson = $this->sendRequest(self::ENDPOINT_BOOKINGS, $jsonPayload, true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses Yatra's JSON response into the standard PMS array format
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
                // Yatra typically stores guest info like this
                $firstName = $booking['guest_details']['first_name'] ?? '';
                $lastName = $booking['guest_details']['last_name'] ?? '';

                $newBookings[] = [
                    'ota_source'     => 'Yatra',
                    'ota_booking_id' => (string)($booking['booking_ref'] ?? ''),
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($booking['checkin_date'] ?? '')),
                    'check_out'      => date('Y-m-d', strtotime($booking['checkout_date'] ?? '')),
                    'ota_room_code'  => (string)($booking['room_code'] ?? ''),
                    'total_price'    => (float)($booking['total_amount'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("Yatra JSON Parse Error: " . $e->getMessage());
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
            'Api-Key: ' . $this->apiKey
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
            error_log("Yatra API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}