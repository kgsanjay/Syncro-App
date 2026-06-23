<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class ExpediaChannel implements OtaChannelInterface
{
    // Expedia QuickConnect (EQC) Endpoints
    private const ENDPOINT_INVENTORY = 'https://services.expediapartnercentral.com/eqc/ar'; // Avail & Rate Update
    private const ENDPOINT_BOOKINGS = 'https://services.expediapartnercentral.com/eqc/br';  // Booking Retrieval
    
    private string $username;
    private string $password;

    public function __construct(string $username, string $password) 
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function pushInventory(string $hotelId, string $otaRoomCode, array $inventoryData): bool 
    {
        $safeHotelId = htmlspecialchars($hotelId, ENT_XML1, 'UTF-8');
        $safeOtaCode = htmlspecialchars($otaRoomCode, ENT_XML1, 'UTF-8');
        
        $safeUser = htmlspecialchars($this->username, ENT_XML1, 'UTF-8');
        $safePass = htmlspecialchars($this->password, ENT_XML1, 'UTF-8');

        $xmlPayload = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xmlPayload .= "<AvailRateUpdateRQ xmlns=\"http://www.expediaconnect.com/EQC/AR/2011/06\">\n";
        $xmlPayload .= "    <Authentication username=\"{$safeUser}\" password=\"{$safePass}\"/>\n";
        $xmlPayload .= "    <Hotel id=\"{$safeHotelId}\">\n";

        foreach ($inventoryData as $inv) {
            $date = htmlspecialchars((string)$inv['target_date'], ENT_XML1, 'UTF-8');
            $price = htmlspecialchars((string)$inv['dynamic_price'], ENT_XML1, 'UTF-8');
            $availableRooms = (int)$inv['available_rooms'];
            
            // If inventory drops to 0 or stop_sell is triggered, close the room
            $status = ($availableRooms <= 0 || !empty($inv['stop_sell'])) ? 'Close' : 'Open';

            // Note: In Expedia, usually RoomType and RatePlan are mapped together. 
            // We use the otaRoomCode for the RoomType ID in this implementation.
            $xmlPayload .= <<<XML
        <RoomType id="{$safeOtaCode}">
            <Inventory status="{$status}" totalInventoryAvailable="{$availableRooms}">
                <PerDay start="{$date}" end="{$date}"/>
            </Inventory>
            <RatePlan id="{$safeOtaCode}">
                <Rate currency="INR">
                    <PerDay start="{$date}" end="{$date}" amount="{$price}"/>
                </Rate>
            </RatePlan>
        </RoomType>
XML;
        }

        $xmlPayload .= "    </Hotel>\n</AvailRateUpdateRQ>";

        return $this->sendRequest(self::ENDPOINT_INVENTORY, $xmlPayload);
    }

    public function fetchBookings(string $hotelId): array 
    {
        $safeUser = htmlspecialchars($this->username, ENT_XML1, 'UTF-8');
        $safePass = htmlspecialchars($this->password, ENT_XML1, 'UTF-8');

        // Request unread bookings (Type="Fetch")
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<BookingRetrievalRQ xmlns="http://www.expediaconnect.com/EQC/BR/2014/01">
    <Authentication username="{$safeUser}" password="{$safePass}"/>
    <ParamSet>
        <Hotel id="{$hotelId}"/>
    </ParamSet>
</BookingRetrievalRQ>
XML;

        $responseXml = $this->sendRequest(self::ENDPOINT_BOOKINGS, $xmlPayload, true);

        if (!$responseXml) {
            return [];
        }

        return $this->parseBookingResponse((string)$responseXml);
    }

    /**
     * Parses the Expedia EQC XML response into the standard PMS array format
     */
    private function parseBookingResponse(string $xmlString): array
    {
        $newBookings = [];
        try {
            $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml || !isset($xml->Bookings->Booking)) {
                return [];
            }

            foreach ($xml->Bookings->Booking as $booking) {
                // Extract Guest Details
                $primaryGuest = $booking->PrimaryGuest;
                $firstName = (string)$primaryGuest->Name['givenName'];
                $lastName = (string)$primaryGuest->Name['surname'];
                
                // Extract Stay Details
                $roomStay = $booking->RoomStay;
                $checkIn = (string)$roomStay->StayDate['arrival'];
                $checkOut = (string)$roomStay->StayDate['departure'];
                $otaRoomCode = (string)$roomStay->RoomType['id'];
                
                // Extract Financials & Booking ID
                $totalPrice = (float)$roomStay->Total['amount'];
                $otaBookingId = (string)$booking['id'];
                
                // 'status' could be Commit, Modify, Cancel
                $expediaStatus = strtolower((string)$booking['type']); 

                // We only handle new commits for this implementation
                if ($expediaStatus === 'commit' || $expediaStatus === 'book') {
                    $newBookings[] = [
                        'ota_source'     => 'Expedia',
                        'ota_booking_id' => $otaBookingId,
                        'guest_name'     => trim("$firstName $lastName"),
                        'check_in'       => date('Y-m-d', strtotime($checkIn)),
                        'check_out'      => date('Y-m-d', strtotime($checkOut)),
                        'ota_room_code'  => $otaRoomCode,
                        'total_price'    => $totalPrice,
                        'status'         => 'confirmed'
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Expedia XML Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * Executes the secure cURL request
     *
     * @param string $url
     * @param string $payload
     * @param bool $returnResponse If true, returns the raw XML string instead of boolean success
     * @return bool|string
     */
    private function sendRequest(string $url, string $payload, bool $returnResponse = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        
        // Expedia EQC also supports HTTP Basic Auth in headers in addition to the XML <Authentication> node
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml',
            'Accept: text/xml'
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

        if ($httpCode !== 200) {
            error_log("Expedia API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}