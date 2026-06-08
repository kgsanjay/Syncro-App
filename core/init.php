<?php
declare(strict_types=1);

// Security: Prevent direct browser execution of this core file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    die('Direct access forbidden.');
}

// 1. Timezone Configuration for accurate audit logging
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// ==============================================================================
// CRITICAL FIX 1: Manually load the .env file so the Database can connect
// ==============================================================================
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0) continue; 
        
        // Parse the key=value pairs
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value, "\"'");
            putenv(trim($name) . '=' . trim($value, "\"'")); // Also set for getenv() usage
        }
    }
}

// 2. Strict Error Reporting (Zero Exposure Policy)
// In production, E_ALL is logged, but NEVER displayed to the browser (prevents path disclosure)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ==============================================================================
// CRITICAL FIX 2: Route errors to a writable directory inside your app
// (Hostinger shared hosting blocks writing to /var/log/)
// ==============================================================================
$secureLogPath = __DIR__ . '/../error.log';
ini_set('error_log', $secureLogPath);

// 3. Industry Standard Autoloader
// Replaces the custom string-matching autoloader with Composer's optimized autoloader.
$composerAutoloadPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($composerAutoloadPath)) {
    require_once $composerAutoloadPath;
} else {
    // Failsafe strictly for debugging; should never trigger in production
    error_log("CRITICAL: Composer autoloader missing. Run 'composer install'.");
    die("System misconfiguration. Please contact the administrator.");
}