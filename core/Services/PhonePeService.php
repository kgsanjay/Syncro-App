<?php
declare(strict_types=1);

namespace Syncro\Services;

use Exception;

class PhonePeService
{
    private string $merchantId;
    private string $saltKey;
    private string $saltIndex;
    private string $env;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = $_ENV['PHONEPE_MERCHANT_ID'] ?? '';
        $this->saltKey = $_ENV['PHONEPE_SALT_KEY'] ?? '';
        $this->saltIndex = $_ENV['PHONEPE_SALT_INDEX'] ?? '1';
        $this->env = $_ENV['PHONEPE_ENV'] ?? 'UAT';

        if (empty($this->merchantId) || empty($this->saltKey)) {
            throw new Exception("PhonePe credentials are not configured in .env");
        }

        if ($this->env === 'PROD') {
            $this->baseUrl = "https://api.phonepe.com/apis/hermes";
        } else {
            $this->baseUrl = "https://api-preprod.phonepe.com/apis/pg-sandbox";
        }
    }

    /**
     * Create a standard checkout payment request
     * 
     * @param string|int $orderId Unique order ID (Booking ID)
     * @param float $amount Amount in INR
     * @param string $userId Guest/User ID
     * @param string $redirectUrl URL to redirect the user after payment
     * @param string $callbackUrl S2S Webhook URL
     * @param string $mobileNumber Optional mobile number
     * @return string Payment page URL
     * @throws Exception
     */
    public function createPaymentRequest(
        $orderId, 
        float $amount, 
        string $userId, 
        string $redirectUrl, 
        string $callbackUrl,
        string $mobileNumber = ''
    ): string {
        $transactionId = 'TXN_' . $orderId . '_' . time();

        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $transactionId,
            'merchantUserId' => "UID_" . $userId,
            'amount' => (int) round($amount * 100), // convert to paise
            'redirectUrl' => $redirectUrl,
            'redirectMode' => 'REDIRECT',
            'callbackUrl' => $callbackUrl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ]
        ];

        if (!empty($mobileNumber)) {
            $payload['mobileNumber'] = $mobileNumber;
        }

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);

        $endpoint = "/pg/v1/pay";
        $checksum = hash('sha256', $base64Payload . $endpoint . $this->saltKey) . '###' . $this->saltIndex;

        $response = $this->makeRequest($endpoint, $base64Payload, $checksum);

        if (isset($response['success']) && $response['success'] === true && isset($response['data']['instrumentResponse']['redirectInfo']['url'])) {
            return $response['data']['instrumentResponse']['redirectInfo']['url'];
        }

        $errorMsg = $response['message'] ?? 'Unknown error';
        throw new Exception("PhonePe Payment Request Failed: " . $errorMsg);
    }

    /**
     * Verify the webhook signature from S2S callback
     * 
     * @param string $base64Response
     * @param string $checksumHeader (X-VERIFY header)
     * @return array|false Decoded response array if valid, false otherwise
     */
    public function verifyWebhook(string $base64Response, string $checksumHeader)
    {
        $calculatedChecksum = hash('sha256', $base64Response . $this->saltKey) . '###' . $this->saltIndex;

        if (hash_equals($calculatedChecksum, $checksumHeader)) {
            $decodedJson = base64_decode($base64Response);
            return json_decode($decodedJson, true);
        }

        return false;
    }

    /**
     * Helper to make API requests via cURL
     */
    private function makeRequest(string $endpoint, string $base64Payload, string $checksum): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-VERIFY: ' . $checksum,
            'Accept: application/json'
        ];

        $requestBody = json_encode(['request' => $base64Payload]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Since we may test on local env, ignore SSL verification if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        if ($result === false) {
            throw new Exception("cURL Error: " . $curlError);
        }

        return json_decode($result, true) ?: [];
    }
}
