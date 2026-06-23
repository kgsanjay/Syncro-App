<?php
declare(strict_types=1);

namespace Syncro\Services;

use Pusher\Pusher;
use Exception;

class RealtimeBroadcaster
{
    private static ?Pusher $pusher = null;

    /**
     * Get the Pusher instance
     */
    private static function getPusher(): Pusher
    {
        if (self::$pusher === null) {
            $options = [
                'cluster' => $_ENV['PUSHER_APP_CLUSTER'] ?? 'mt1',
                'useTLS' => true
            ];

            self::$pusher = new Pusher(
                $_ENV['PUSHER_APP_KEY'] ?? '',
                $_ENV['PUSHER_APP_SECRET'] ?? '',
                $_ENV['PUSHER_APP_ID'] ?? '',
                $options
            );
        }

        return self::$pusher;
    }

    /**
     * Broadcast an event to a channel
     *
     * @param string $channel The channel name (e.g., 'private-hotel-1')
     * @param string $event The event name (e.g., 'NewBookingEvent')
     * @param array $payload The data payload to send
     * @return bool True on success, false on failure
     */
    public static function broadcast(string $channel, string $event, array $payload): bool
    {
        try {
            self::getPusher()->trigger($channel, $event, $payload);
            return true;
        } catch (Exception $e) {
            error_log("Broadcasting Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Authorize a private channel connection
     */
    public static function authorizeChannel(string $channelName, string $socketId): string
    {
        try {
            return self::getPusher()->socket_auth($channelName, $socketId);
        } catch (Exception $e) {
            error_log("Broadcasting Auth Error: " . $e->getMessage());
            return json_encode(['error' => 'Authentication failed']);
        }
    }
}
