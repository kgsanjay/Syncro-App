<?php
declare(strict_types=1);

namespace Syncro\Security;

class CsrfManager
{
    /**
     * Generates and stores a cryptographically secure CSRF token in the session.
     * Returns the token.
     */
    public static function generateToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            try {
                $randomBytes = bin2hex(random_bytes(32));
                // We store the raw random bytes in the session.
                $_SESSION['_csrf'] = $randomBytes;
            } catch (\Exception $e) {
                error_log("CRITICAL SECURITY ERROR: System cannot generate cryptographically secure randomness.");
                throw new \RuntimeException("A critical system security error occurred. Operations halted.");
            }
        }
        
        $secret = $_ENV['CSRF_SECRET'] ?? $_ENV['APP_KEY'] ?? 'fallback_secret_if_env_missing';
        // The token sent to the client is a hash of the session's random bytes and the server secret
        return hash_hmac('sha256', $_SESSION['_csrf'], $secret);
    }

    /**
     * Validates the provided CSRF token against the session.
     */
    public static function validateToken(?string $token): bool
    {
        if (empty($_SESSION['_csrf']) || empty($token)) {
            return false;
        }

        $secret = $_ENV['CSRF_SECRET'] ?? $_ENV['APP_KEY'] ?? 'fallback_secret_if_env_missing';
        $expectedToken = hash_hmac('sha256', $_SESSION['_csrf'], $secret);

        return hash_equals($expectedToken, $token);
    }
}
