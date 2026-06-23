<?php
declare(strict_types=1);

namespace Syncro\Security;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            
            // Dynamically detect HTTPS to prevent lockout on local/dev environments
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) || 
                        getenv('APP_ENV') === 'production';

            session_set_cookie_params([
                'lifetime' => 86400, 
                'path'     => '/',
                'domain'   => getenv('APP_DOMAIN') ?: '',
                'secure'   => $isSecure,  
                'httponly' => true, 
                'samesite' => 'Strict' 
            ]);

            session_start();
        }
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000, 
                $params["path"], 
                $params["domain"], 
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Set a flash message in the session.
     * @param string $type e.g., 'success', 'error', 'warning'
     * @param string $message The message body
     */
    public static function setFlash(string $type, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            self::start();
        }
        $_SESSION['flash_messages'][$type] = $message;
    }

    /**
     * Retrieve and immediately delete a flash message.
     */
    public static function getFlash(string $type): ?string
    {
        if (isset($_SESSION['flash_messages'][$type])) {
            $message = $_SESSION['flash_messages'][$type];
            unset($_SESSION['flash_messages'][$type]);
            return $message;
        }
        return null;
    }
}