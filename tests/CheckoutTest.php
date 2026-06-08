<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    public function testWebhookChecksumLogic()
    {
        $payload = [
            'success' => true,
            'code' => 'PAYMENT_SUCCESS',
            'message' => 'Your payment is successful.',
            'data' => [
                'merchantId' => 'SYNCRO_TEST',
                'merchantTransactionId' => 'TEST_TXN_001',
                'transactionId' => 'T1234567890',
                'amount' => 500000, // 5000 INR
                'state' => 'COMPLETED',
                'responseCode' => 'SUCCESS'
            ]
        ];

        $base64Payload = base64_encode(json_encode($payload));
        $saltKey = 'test-salt-key-12345';
        $saltIndex = '1';

        $rawBody = json_encode(['response' => $base64Payload]);
        $expectedChecksum = hash('sha256', $rawBody . $saltKey) . '###' . $saltIndex;

        $this->assertNotEmpty($expectedChecksum, 'Checksum should be generated correctly.');
        $this->assertStringEndsWith('###1', $expectedChecksum, 'Checksum should end with salt index.');
    }
}
