<?php
declare(strict_types=1);

namespace Syncro\Services;

class AlertService
{
    /**
     * Dispatch a critical failure alert
     *
     * @param \Throwable $exception
     */
    public static function dispatch(\Throwable $exception): void
    {
        $payload = self::formatPayload($exception);

        $slackWebhook = $_ENV['SLACK_WEBHOOK_URL'] ?? getenv('SLACK_WEBHOOK_URL');
        if (!empty($slackWebhook)) {
            self::dispatchToSlack($slackWebhook, $payload);
            return;
        }

        $alertEmail = $_ENV['ALERT_EMAIL_ADDRESS'] ?? getenv('ALERT_EMAIL_ADDRESS');
        if (!empty($alertEmail)) {
            self::dispatchToEmail($alertEmail, $payload);
        }
    }

    /**
     * Formats the exception into a structured payload
     */
    private static function formatPayload(\Throwable $exception): array
    {
        $uri = $_SERVER['REQUEST_URI'] ?? 'CLI/Unknown';
        $userId = $_SESSION['user_id'] ?? 'Anonymous';

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'uri' => $uri,
            'user_id' => $userId,
            'trace' => substr($exception->getTraceAsString(), 0, 1000) . '...' // Trim long traces
        ];
    }

    /**
     * Dispatch alert to a Slack Webhook asynchronously using cURL
     */
    private static function dispatchToSlack(string $webhookUrl, array $payload): void
    {
        $slackMessage = [
            'text' => "🚨 *CRITICAL FAILURE DETECTED* 🚨\n" .
                      "*Exception:* `{$payload['exception_class']}`\n" .
                      "*Message:* {$payload['message']}\n" .
                      "*Location:* `{$payload['file']}:{$payload['line']}`\n" .
                      "*URI:* {$payload['uri']}\n" .
                      "*User ID:* {$payload['user_id']}\n\n" .
                      "*Trace:*\n```\n{$payload['trace']}\n```"
        ];

        $jsonPayload = json_encode($slackMessage);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonPayload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Don't block for long
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        
        // Execute and immediately close
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Dispatch alert via EmailService
     */
    private static function dispatchToEmail(string $email, array $payload): void
    {
        $subject = "CRITICAL ALERT: " . $payload['exception_class'];
        
        $htmlContent = "
            <h2>🚨 CRITICAL FAILURE DETECTED 🚨</h2>
            <p><strong>Exception:</strong> {$payload['exception_class']}</p>
            <p><strong>Message:</strong> {$payload['message']}</p>
            <p><strong>Location:</strong> {$payload['file']}:{$payload['line']}</p>
            <p><strong>URI:</strong> {$payload['uri']}</p>
            <p><strong>User ID:</strong> {$payload['user_id']}</p>
            <hr>
            <h3>Trace</h3>
            <pre>{$payload['trace']}</pre>
        ";

        // Using our existing EmailService
        \Syncro\Services\EmailService::sendTransactionalEmail($email, $subject, $htmlContent);
    }
}
