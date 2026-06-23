<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Exception;

class CheckoutController extends BaseController
{
    private \Syncro\Models\Database $db;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
    }

    /**
     * Step 1: Initialize the PhonePe Order and Redirect
     */
    public function init(array $postData): void
    {
        $bookingId = (int)($postData['booking_id'] ?? 0);

        try {
            $db = $this->db->getPDO();
            
            $stmt = $db->prepare("
                SELECT b.*, h.phonepe_merchant_id, h.phonepe_salt_key, h.phonepe_env, h.slug 
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                WHERE b.id = :bid AND b.status = 'confirmed' AND b.payment_status = 'pending'
            ");
            $stmt->execute(['bid' => $bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Invalid booking or payment already processed.");
            }

            // Strictly enforce the new PhonePe database columns
            $merchantId = $booking['phonepe_merchant_id'] ?? '';
            $saltKey = $booking['phonepe_salt_key'] ?? '';
            $env = $booking['phonepe_env'] ?? 'uat';

            if (empty($merchantId) || empty($saltKey)) {
                throw new Exception("The hotel has not configured their payment gateway.");
            }

            // Determine Base URL
            $baseUrl = $env === 'prod' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

            // Build the PhonePe Payload
            $amountInPaise = (int)round((float)$booking['total_price'] * 100); 
            $merchantTransactionId = 'MT_' . $booking['id'] . '_' . time();
            $saltIndex = "1"; 
            $endpoint = "/pg/v1/pay";

            $payload = [
                'merchantId' => $merchantId,
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => 'MUID_' . ($booking['guest_id'] ?? 'GUEST'),
                'amount' => $amountInPaise,
                'redirectUrl' => "https://{$_SERVER['HTTP_HOST']}/checkout/verify",
                'redirectMode' => 'POST',
                'callbackUrl' => "https://{$_SERVER['HTTP_HOST']}/api/webhook/phonepe",
                'mobileNumber' => $booking['guest_phone'] ?? '',
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];

            $base64Payload = base64_encode(json_encode($payload));
            $checksum = hash('sha256', $base64Payload . $endpoint . $saltKey) . '###' . $saltIndex;

            // Call PhonePe API
            $ch = curl_init($baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $base64Payload]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("PhonePe HTTP Error: " . $httpCode . " Response: " . $response);
                throw new Exception("Failed to communicate with payment gateway.");
            }

            $orderData = json_decode($response, true);

            if (isset($orderData['success']) && $orderData['success'] === true) {
                // Save the generated transaction ID to verify later
                $this->db->getTable('bookings')
                    ->where('id', $bookingId)
                    ->update(['transaction_id' => $merchantTransactionId]);

                // Redirect user to PhonePe Hosted Checkout Page
                $paymentUrl = $orderData['data']['instrumentResponse']['redirectInfo']['url'];
                header("Location: " . $paymentUrl);
                exit;
            } else {
                throw new Exception("PhonePe Error: " . ($orderData['message'] ?? 'Unknown initialization error'));
            }

        } catch (Exception $e) {
            error_log("IBE Checkout Error: " . $e->getMessage());
            $slug = $booking['slug'] ?? '';
            $this->redirect("/book/{$slug}?error=" . urlencode($e->getMessage()));
        }
    }

    /**
     * Step 2: Verify the Payment after the user returns from PhonePe
     */
    public function verify(array $postData): void
    {
        $merchantTransactionId = $postData['transactionId'] ?? '';
        $code = $postData['code'] ?? '';

        if (empty($merchantTransactionId)) {
            $this->redirect("/?error=" . urlencode('Invalid Request. Missing transaction ID.'));
            return;
        }

        try {
            $db = $this->db->getPDO();
            $stmt = $db->prepare("
                SELECT b.id, b.total_price, b.hotel_id, b.guest_email, b.guest_name, b.check_in, b.check_out, h.phonepe_merchant_id, h.phonepe_salt_key, h.phonepe_env, h.slug, h.property_name as hotel_name
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                WHERE b.transaction_id = :tid
            ");
            $stmt->execute(['tid' => $merchantTransactionId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $this->redirect("/?error=" . urlencode('Booking session not found.'));
                return;
            }

            if ($code !== 'PAYMENT_SUCCESS') {
                $this->redirect("/book/{$booking['slug']}?error=" . urlencode('Payment failed or was cancelled.'));
                return;
            }

            // Strictly enforce the new PhonePe database columns
            $merchantId = $booking['phonepe_merchant_id'] ?? '';
            $saltKey = $booking['phonepe_salt_key'] ?? '';
            $env = $booking['phonepe_env'] ?? 'uat';

            // Server-to-Server Status Verification to prevent URL spoofing
            $saltIndex = "1";
            $endpoint = "/pg/v1/status/" . $merchantId . "/" . $merchantTransactionId;
            $checksum = hash('sha256', $endpoint . $saltKey) . '###' . $saltIndex;

            $baseUrl = $env === 'prod' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

            $ch = curl_init($baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum,
                'X-MERCHANT-ID: ' . $merchantId
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $statusData = json_decode($response, true);

            if (isset($statusData['success']) && $statusData['success'] === true && $statusData['data']['state'] === 'COMPLETED') {
                
                // Secure Transaction & Update Ledger
                $db->beginTransaction();

                $this->db->getTable('bookings')
                    ->where('id', $booking['id'])
                    ->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $statusData['data']['transactionId'] // Use actual Bank Reference ID
                    ]);

                $this->db->getTable('payments')->insert([
                    'hotel_id'       => $booking['hotel_id'],
                    'booking_id'     => $booking['id'],
                    'amount'         => (float)$booking['total_price'],
                    'payment_method' => 'PhonePe IBE',
                    'transaction_id' => $statusData['data']['transactionId'],
                    'notes'          => 'Public Website Booking Deposit'
                ]);

                $db->commit();

                // Send Email Confirmation
                \Syncro\Services\EmailService::sendBookingConfirmation(
                    $booking['guest_email'],
                    $booking['guest_name'],
                    $booking['hotel_name'],
                    $booking['check_in'],
                    $booking['check_out'],
                    (int)$booking['id']
                );

                // Show Success Screen
                $this->render('public/checkout_success', [
                    'pageTitle' => 'Booking Confirmed!',
                    'slug'      => $booking['slug'],
                    'bookingId' => $booking['id']
                ], 'blank_layout');

            } else {
                throw new Exception("Payment verification failed at the bank level.");
            }

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("PhonePe Verification Failed: " . $e->getMessage());
            $slug = $booking['slug'] ?? '';
            $this->redirect("/book/{$slug}?error=" . urlencode("Payment verification failed. Please contact the hotel."));
        }
    }

    /**
     * Step 3: Handle PhonePe Server-to-Server Webhook
     */
    public function webhook(): void
    {
        $payload = file_get_contents('php://input');
        $verifyHeader = $_SERVER['HTTP_X_VERIFY'] ?? '';

        if (empty($payload) || empty($verifyHeader)) {
            http_response_code(400);
            echo "Missing payload or verification header";
            return;
        }

        $data = json_decode($payload, true);
        if (!isset($data['response'])) {
            http_response_code(400);
            echo "Invalid payload structure";
            return;
        }

        try {
            $db = $this->db->getPDO();

            // 1. Verify X-VERIFY checksum header using PHONEPE_SALT_KEY from environment
            $saltKey = $_ENV['PHONEPE_SALT_KEY'] ?? getenv('PHONEPE_SALT_KEY');
            if (empty($saltKey)) {
                $stmtSettings = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'phonepe_salt_key'");
                $saltKey = $stmtSettings->fetchColumn() ?: '';
            }

            // Extract salt index from header (e.g., checksum###1)
            $parts = explode('###', $verifyHeader);
            $saltIndex = $parts[1] ?? '1';
            
            $expectedChecksum = hash('sha256', $data['response'] . $saltKey) . '###' . $saltIndex;

            if (!hash_equals($expectedChecksum, $verifyHeader)) {
                http_response_code(401);
                echo "Unauthorized Checksum Mismatch";
                return;
            }

            // 2. Extract transaction_id from the verified payload
            $decodedResponse = json_decode(base64_decode($data['response']), true);
            $merchantTransactionId = $decodedResponse['data']['merchantTransactionId'] ?? '';
            $transactionId = $decodedResponse['data']['transactionId'] ?? ''; // Bank transaction ID
            $code = $decodedResponse['code'] ?? '';
            
            // Fallback to merchantTransactionId if bank transactionId is missing in failure cases
            $idempotencyTxnId = !empty($transactionId) ? $transactionId : $merchantTransactionId;

            if (empty($merchantTransactionId)) {
                http_response_code(400);
                echo "Missing transaction ID";
                return;
            }

            // 3. Idempotency Check: Query the payments table for this exact transaction_id
            $stmtCheck = $db->prepare("SELECT id FROM payments WHERE transaction_id = :tid");
            // If the payments table has a 'status' column we can append `AND status = 'SUCCESS'`,
            // but checking for the record's existence prevents double-insertion.
            $stmtCheck->execute(['tid' => $idempotencyTxnId]);
            if ($stmtCheck->fetch()) {
                // If a record with this exact transaction_id already exists, immediately return a 200 OK
                http_response_code(200);
                echo "OK - Idempotent Request, Already Processed";
                return;
            }

            // Look up booking by merchantTransactionId
            $stmt = $db->prepare("
                SELECT b.id, b.total_price, b.payment_status, b.hotel_id, b.guest_email, b.guest_name, b.check_in, b.check_out, h.property_name as hotel_name
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                WHERE b.transaction_id = :tid
            ");
            $stmt->execute(['tid' => $merchantTransactionId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                // SaaS Renewal Webhook fallback or not found
                if (strpos($merchantTransactionId, 'RENEW_') === 0) {
                    http_response_code(200);
                    echo "OK";
                    return;
                }

                http_response_code(404);
                echo "Transaction not found";
                return;
            }

            if ($booking['payment_status'] === 'paid') {
                http_response_code(200);
                echo "OK - Booking Already Paid";
                return;
            }

            if ($code === 'PAYMENT_SUCCESS' && isset($decodedResponse['data']['state']) && $decodedResponse['data']['state'] === 'COMPLETED') {
                
                // 4. Wrap the entire payment success logic inside a single Database Transaction
                $db->beginTransaction();

                try {
                    $this->db->getTable('bookings')
                        ->where('id', $booking['id'])
                        ->update([
                            'payment_status' => 'paid',
                            'transaction_id' => $transactionId
                        ]);

                    // Update invoice if it exists, or change reservation status (mapped as bookings here)
                    $this->db->getTable('payments')->insert([
                        'hotel_id'       => $booking['hotel_id'],
                        'booking_id'     => $booking['id'],
                        'amount'         => (float)$booking['total_price'],
                        'payment_method' => 'PhonePe IBE (Webhook)',
                        'transaction_id' => $transactionId,
                        'notes'          => 'Public Website Booking Deposit',
                        'status'         => 'SUCCESS' // ensuring SUCCESS status is stored if the column exists
                    ]);

                    $db->commit();
                } catch (Exception $txException) {
                    $db->rollBack();
                    throw $txException;
                }

                // Send Email Confirmation outside the transaction
                \Syncro\Services\EmailService::sendBookingConfirmation(
                    $booking['guest_email'],
                    $booking['guest_name'],
                    $booking['hotel_name'],
                    $booking['check_in'],
                    $booking['check_out'],
                    (int)$booking['id']
                );
            }

            http_response_code(200);
            echo "OK";

        } catch (Exception $e) {
            error_log("Webhook Error: " . $e->getMessage());
            http_response_code(500);
            echo "Internal Server Error";
        }
    }
}