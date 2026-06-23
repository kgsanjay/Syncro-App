<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use SimpleXMLElement;
use Exception;

class BookingComChannel implements OtaChannelInterface
{
    private const ENDPOINT = 'https://supply-xml.booking.com';
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

        $xmlPayload = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xmlPayload .= "<OTA_HotelAvailNotifRQ xmlns=\"http://www.opentravel.org/OTA/2003/05\" Version=\"1.0\">\n";
        $xmlPayload .= "    <AvailStatusMessages HotelCode=\"{$safeHotelId}\">\n";

        foreach ($inventoryData as $inv) {
            $date = htmlspecialchars((string)$inv['target_date'], ENT_XML1, 'UTF-8');
            $price = htmlspecialchars((string)$inv['dynamic_price'], ENT_XML1, 'UTF-8');
            $availableRooms = (int)$inv['available_rooms'];
            
            // If available rooms is 0 or stop_sell is active, close the room. Otherwise, open it.
            $status = ($availableRooms <= 0 || !empty($inv['stop_sell'])) ? 'Close' : 'Open';

            $xmlPayload .= <<<XML
        <AvailStatusMessage>
            <StatusApplicationControl Start="{$date}" End="{$date}" InvTypeCode="{$safeOtaCode}"/>
            <LengthsOfStay>
                <LengthOfStay Time="1" TimeUnit="Day" MinMaxMessageType="MinLOS"/>
            </LengthsOfStay>
            <RoomRates>
                <RoomRate RoomTypeCode="{$safeOtaCode}">
                    <Rates>
                        <Rate Amount="{$price}" CurrencyCode="INR"/>
                    </Rates>
                </RoomRate>
            </RoomRates>
            <BookingLimit MessageType="SetLimit" BookingLimit="{$availableRooms}"/>
            <RestrictionStatus Status="{$status}"/>
        </AvailStatusMessage>
XML;
        }
        $xmlPayload .= "    </AvailStatusMessages>\n</OTA_HotelAvailNotifRQ>";

        return $this->sendRequest($xmlPayload);
    }

    public function fetchBookings(string $hotelId): array
    {
        $safeHotelId = htmlspecialchars($hotelId, ENT_XML1, 'UTF-8');

        // Standard OTA_ReadRQ to fetch UNREAD bookings from Booking.com
        $xmlPayload = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<OTA_ReadRQ xmlns="http://www.opentravel.org/OTA/2003/05" Version="1.0">
    <ReadRequests>
        <HotelReadRequest HotelCode="{$safeHotelId}">
            <SelectionCriteria DateType="3"/>
        </HotelReadRequest>
    </ReadRequests>
</OTA_ReadRQ>
XML;

        $responseXml = $this->sendRequest($xmlPayload, true);

        if (!$responseXml) {
            return [];
        }

        return $this->parseBookingResponse($responseXml);
    }

    /**
     * Parses the OTA_HotelResNotifRS XML response into a standard array format for your PMS
     */
    private function parseBookingResponse(string $xmlString): array
    {
        $newBookings = [];
        try {
            // Load XML safely
            $xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml || !isset($xml->HotelReservations->HotelReservation)) {
                return [];
            }

            foreach ($xml->HotelReservations->HotelReservation as $reservation) {
                // Extract Guest Details
                $firstName = (string)$reservation->ResGuests->ResGuest->Profiles->ProfileInfo->Profile->Customer->PersonName->GivenName;
                $lastName = (string)$reservation->ResGuests->ResGuest->Profiles->ProfileInfo->Profile->Customer->PersonName->Surname;
                
                // Extract Room & Stay Details
                $roomStay = $reservation->RoomStays->RoomStay;
                $checkIn = (string)$roomStay->TimeSpan['Start'];
                $checkOut = (string)$roomStay->TimeSpan['End'];
                $otaRoomCode = (string)$roomStay->RoomTypes->RoomType['RoomTypeCode'];
                
                // Extract Pricing
                $totalPrice = (float)$roomStay->Total['AmountBeforeTax'];
                
                // Extract Booking.com Reservation ID
                $otaBookingId = (string)$reservation->ResGlobalInfo->HotelReservationIDs->HotelReservationID['ResID_Value'];

                $newBookings[] = [
                    'ota_source'     => 'Booking.com',
                    'ota_booking_id' => $otaBookingId,
                    'guest_name'     => trim("$firstName $lastName"),
                    'check_in'       => date('Y-m-d', strtotime($checkIn)),
                    'check_out'      => date('Y-m-d', strtotime($checkOut)),
                    'ota_room_code'  => $otaRoomCode,
                    'total_price'    => $totalPrice,
                    'status'         => 'confirmed' // Assuming new fetch means confirmed
                ];
            }
        } catch (Exception $e) {
            error_log("Booking.com XML Parse Error: " . $e->getMessage());
        }

        return $newBookings;
    }

    /**
     * @param string $payload
     * @param bool $returnResponse If true, returns the raw XML string instead of boolean success
     * @return bool|string
     */
    private function sendRequest(string $payload, bool $returnResponse = false)
    {
        $ch = curl_init(self::ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
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
            error_log("Booking.com API Error: HTTP $httpCode - $response");
            return $returnResponse ? '' : false;
        }

        return $returnResponse ? $response : true;
    }
}