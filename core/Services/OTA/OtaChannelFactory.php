<?php
declare(strict_types=1);

namespace Syncro\Services\OTA;

use Exception;

class OtaChannelFactory
{
    /**
     * Instantiates the correct OTA Channel class based on the channel name.
     * * @param string $channelName e.g., 'Booking.com', 'MakeMyTrip', 'Agoda'
     * @param array $hotelCredentials The row from your 'hotels' table containing API keys
     * @return OtaChannelInterface
     * @throws Exception
     */
    public static function create(string $channelName, array $hotelCredentials): OtaChannelInterface
    {
        switch ($channelName) {
            case 'Booking.com':
                if (empty($hotelCredentials['booking_com_username']) || empty($hotelCredentials['booking_com_password'])) {
                    throw new Exception("Booking.com credentials not configured.");
                }
                return new BookingComChannel(
                    $hotelCredentials['booking_com_username'], 
                    $hotelCredentials['booking_com_password']
                );

            case 'MakeMyTrip':
                if (empty($hotelCredentials['mmt_api_key'])) {
                    throw new Exception("MakeMyTrip API key not configured.");
                }
                return new MakeMyTripChannel($hotelCredentials['mmt_api_key']);

            case 'Agoda':
                if (empty($hotelCredentials['agoda_api_key'])) {
                    throw new Exception("Agoda credentials not configured.");
                }
                return new AgodaChannel($hotelCredentials['agoda_api_key']);

            case 'Cleartrip':
                if (empty($hotelCredentials['cleartrip_api_key'])) {
                    throw new Exception("Cleartrip credentials not configured.");
                }
                return new CleartripChannel($hotelCredentials['cleartrip_api_key']);

            case 'Yatra':
                if (empty($hotelCredentials['yatra_api_key'])) {
                    throw new Exception("Yatra credentials not configured.");
                }
                return new YatraChannel($hotelCredentials['yatra_api_key']);

            case 'Expedia':
                if (empty($hotelCredentials['expedia_username']) || empty($hotelCredentials['expedia_password'])) {
                    throw new Exception("Expedia credentials not configured.");
                }
                return new ExpediaChannel(
                    $hotelCredentials['expedia_username'], 
                    $hotelCredentials['expedia_password']
                );

            case 'EaseMyTrip':
                if (empty($hotelCredentials['easemytrip_api_key'])) {
                    throw new Exception("EaseMyTrip credentials not configured.");
                }
                return new EaseMyTripChannel($hotelCredentials['easemytrip_api_key']);

            case 'Paytm':
                if (empty($hotelCredentials['paytm_api_key'])) {
                    throw new Exception("Paytm credentials not configured.");
                }
                return new PaytmChannel($hotelCredentials['paytm_api_key']);

            case 'Airbnb':
                if (empty($hotelCredentials['airbnb_api_key'])) {
                    throw new Exception("Airbnb credentials not configured.");
                }
                return new AirbnbChannel($hotelCredentials['airbnb_api_key']);

            case 'TripAdvisor':
                if (empty($hotelCredentials['tripadvisor_api_key'])) {
                    throw new Exception("TripAdvisor credentials not configured.");
                }
                return new TripAdvisorChannel($hotelCredentials['tripadvisor_api_key']);

            default:
                throw new Exception("Unsupported OTA Channel: {$channelName}");
        }
    }
}