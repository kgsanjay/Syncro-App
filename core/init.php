<?php
declare(strict_types=1);

// Security: Prevent direct browser execution of this core file
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    die('Direct access forbidden.');
}

// 1. Industry Standard Autoloader
// Replaces the custom string-matching autoloader with Composer's optimized autoloader.
$composerAutoloadPath = __DIR__ . '/../vendor/autoload.php';

if (file_exists($composerAutoloadPath)) {
    require_once $composerAutoloadPath;
} else {
    // Failsafe strictly for debugging; should never trigger in production
    error_log("CRITICAL: Composer autoloader missing. Run 'composer install'.");
    die("System misconfiguration. Please contact the administrator.");
}

// ==============================================================================
// CRITICAL FIX 1: Load Environment Variables
// ==============================================================================
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

// 2. Timezone Configuration for accurate audit logging
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// ==============================================================================
// DYNAMIC BASE URL (Handles both /syncro/ local and / production roots)
// ==============================================================================
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $baseDir === '/' || $baseDir === '\\' ? '' : $baseDir);

// 3. Strict Error Reporting (Zero Exposure Policy)
// In production, E_ALL is logged, but NEVER displayed to the browser (prevents path disclosure)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// ==============================================================================
// CRITICAL FIX 2: Route errors to a writable directory inside your app
// (Hostinger shared hosting blocks writing to /var/log/)
// ==============================================================================
$secureLogPath = __DIR__ . '/../error.log';
ini_set('error_log', $secureLogPath);

// ==============================================================================
// 4. Load Global Helpers
// ==============================================================================
require_once __DIR__ . '/helpers.php';