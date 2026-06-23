<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class AirbnbChannel implements OtaChannelInterface
{
    // Note: Airbnb official API endpoints for Property Managers
    private const ENDPOINT_CALENDAR = 'https://api.airbnb.com/v2/calendars';
    private const ENDPOINT_RESERVATIONS = 'https://api.airbnb.com/v2/reservations';
    
    private string $oauthToken;

    public function __construct(string $oauthToken) 
    {
        $this->oauthToken = $oauthToken;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool 
    {
        $availabilityArray = [];
        
        foreach ($inventoryData as $inv) {
            $availableRooms = (int)$inv['available_rooms'];
            // Airbnb operates on a "Listing" basis. If rooms > 0 and no stop sell, it is available.
            $isAvailable = ($availableRooms > 0 && empty($inv['stop_sell']));
            
            $availabilityArray[] = [
                'date'      => $inv['target_date'],
                'available' => $isAvailable,
                'price'     => (float)$inv['dynamic_price']
            ];
        }

        // Airbnb maps PMS Room Types to individual Listing IDs ($otaRoomCode)
        $payload = json_encode([
            'listing_id'   => $otaRoomCode,
            'availability' => $availabilityArray
        ]);

        // Airbnb uses PUT for bulk calendar updates
        return $this->sendRequest(self::ENDPOINT_CALENDAR, 'PUT', $payload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        // Fetch new/accepted reservations since the last sync
        // In a real scenario, you might append ?since=<timestamp>
        $url = self::ENDPOINT_RESERVATIONS . "?status=accepted";
        
        $responseJson = $this->sendRequest($url, 'GET', '', true);

        if (!$responseJson) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseJson);
    }

    /**
     * Parses Airbnb's JSON response into the standard PMS array format
     */
    private function parseBookingResponse(string $jsonString): array
    {
        $newBookings = [];
        try {
            $data = json_decode($jsonString, true);

            if (empty($data['reservations'])) {
                return [];
            }

            foreach ($data['reservations'] as $reservation) {
                // Airbnb provides guest details inside a guest object
                $firstName = $reservation['guest']['first_name'] ?? '';
                $lastName = $reservation['guest']['last_name'] ?? '';
                
                $newBookings[] = [
                    'ota_source'     => 'Airbnb',
                    'ota_booking_id' => (string)$reservation['id'],
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($reservation['start_date'])),
                    'check_out'      => date('Y-m-d', strtotime($reservation['end_date'])),
                    'ota_room_code'  => (string)$reservation['listing_id'],
                    'total_price'    => (float)($reservation['payout_price_native'] ?? 0),
                    'status'         => 'confirmed'
                ];
            }
        } catch (Exception $e) {
            error_log("Airbnb JSON Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * Executes the secure cURL request
     * * @param string $url
     * @param string $method GET, POST, or PUT
     * @param string $payload
     * @param bool $returnResponse If true, returns the raw JSON string instead of boolean success
     * @return bool|string
     */
    private function sendRequest(string $url, string $method, string $payload = '', bool $returnResponse = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        
        if (!empty($payload) && in_array(strtoupper($method), ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->oauthToken
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
            error_log("Airbnb API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}