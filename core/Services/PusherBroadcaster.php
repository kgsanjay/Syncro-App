<?php
declare(strict_types=1);

namespace Syncro\Services;

use Pusher\Pusher;

class PusherBroadcaster
{
    private Pusher $pusher;

    public function __construct()
    {
        $appId = $_ENV['PUSHER_APP_ID'] ?? '';
        $key = $_ENV['PUSHER_KEY'] ?? '';
        $secret = $_ENV['PUSHER_SECRET'] ?? '';
        $cluster = $_ENV['PUSHER_CLUSTER'] ?? '';

        $options = [
            'cluster' => $cluster,
            'useTLS' => true
        ];

        $this->pusher = new Pusher(
            $key,
            $secret,
            $appId,
            $options
        );
    }

    public function broadcast(string $channel, string $event, array $payload): void
    {
        try {
            $this->pusher->trigger($channel, $event, $payload);
        } catch (\Exception $e) {
            error_log("Pusher broadcast failed: " . $e->getMessage());
        }
    }
}
