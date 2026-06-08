<?php
declare(strict_types=1);

namespace Syncro\Security;

class ExceptionHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        
        // Ensure fatal errors are also caught
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    public static function handleException(\Throwable $exception): void
    {
        // 1. Log the error silently
        $logMessage = sprintf(
            "[%s] Exception: '%s' in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Log to your custom error.log file
        error_log($logMessage, 3, __DIR__ . '/../../error.log');

        // 2. Clear any output buffers to prevent partial page rendering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 3. Show the friendly 500 error page
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo "<pre>";
        echo "Exception: " . $exception->getMessage() . "\n";
        echo "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
        echo "Trace: \n" . $exception->getTraceAsString();
        echo "</pre>";
        exit;
    }

    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }
}