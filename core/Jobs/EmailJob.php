<?php
declare(strict_types=1);

namespace Syncro\Jobs;

use Exception;

class EmailJob
{
    public function handle(array $data): void
    {
        $toEmail = $data['toEmail'] ?? '';
        $toName = $data['toName'] ?? '';
        $subject = $data['subject'] ?? '';
        $htmlContent = $data['htmlContent'] ?? '';
        $textContent = $data['textContent'] ?? '';
        $headers = $data['headers'] ?? '';

        $apiKey = $_ENV['BREVO_API_KEY'] ?? getenv('BREVO_API_KEY') ?: '';
        $senderEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'reservations@adhyancreatives.in';
        $senderName = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'Syncro Hospitality';

        if (empty($apiKey)) {
            $safeSenderEmail = str_replace(["\r", "\n"], '', $senderEmail);
            
            if (empty($headers)) {
                $headers = "From: {$senderName} <{$safeSenderEmail}>\r\n";
                $headers .= "Reply-To: {$safeSenderEmail}\r\n";
                
                if (!empty($htmlContent)) {
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                } else {
                    $headers .= "X-Mailer: PHP/" . phpversion();
                }
            }

            $body = !empty($htmlContent) ? $htmlContent : $textContent;
            $success = @mail($toEmail, $subject, $body, $headers);
            
            if (!$success) {
                throw new Exception("Failed to send email via basic mail() fallback.");
            }
            return;
        }

        // Send via Brevo API
        $payload = [
            'sender' => ['name' => $senderName, 'email' => $senderEmail],
            'to' => [['email' => $toEmail]],
            'subject' => $subject,
        ];

        if (!empty($toName)) {
            $payload['to'][0]['name'] = $toName;
        }

        if (!empty($htmlContent)) {
            $payload['htmlContent'] = $htmlContent;
        }
        
        if (!empty($textContent)) {
            $payload['textContent'] = $textContent;
        }

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new Exception("Brevo API Error ({$httpCode}): " . $response);
        }
    }
}
