<?php
declare(strict_types=1);

namespace Syncro\Middleware;

use Syncro\Security\RateLimiter;

class ThrottleRequestsMiddleware implements MiddlewareInterface
{
    /**
     * Handle the incoming request.
     * Throttles requests based on IP address or Authorization token.
     */
    public function handle(): void
    {
        // Identify the client. Use API token if provided, otherwise fallback to IP address
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';
        
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $identifier = hash('sha256', $headers['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $identifier = hash('sha256', $_SERVER['HTTP_AUTHORIZATION']);
        }

        // Limit: 60 requests per 1 minute
        $allowed = RateLimiter::check($identifier, 60, 1);

        if (!$allowed) {
            http_response_code(429);
            header('Retry-After: 60');
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.'
            ]);
            exit;
        }
    }
}

// Fallback for Nginx/CLI environments where apache_request_headers is unavailable
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        $arh = [];
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return $arh;
    }
}
