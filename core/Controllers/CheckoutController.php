<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Exception;

class CheckoutController extends BaseController
{
    /**
     * Step 1: Initialize the PhonePe Order and Redirect
     */
    public function init(array $postData): void
    {
        $bookingId = (int)($postData['booking_id'] ?? 0);

        try {
            $db = Database::getConnection();
            
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
                Database::table('bookings')
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
            $db = Database::getConnection();
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

                Database::table('bookings')
                    ->where('id', $booking['id'])
                    ->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $statusData['data']['transactionId'] // Use actual Bank Reference ID
                    ]);

                Database::table('payments')->insert([
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

        $decodedResponse = json_decode(base64_decode($data['response']), true);
        $merchantTransactionId = $decodedResponse['data']['merchantTransactionId'] ?? '';
        $code = $decodedResponse['code'] ?? '';

        if (empty($merchantTransactionId)) {
            http_response_code(400);
            echo "Missing transaction ID";
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.id, b.total_price, b.payment_status, b.hotel_id, b.guest_email, b.guest_name, b.check_in, b.check_out, h.phonepe_salt_key, h.property_name as hotel_name
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                WHERE b.transaction_id = :tid
            ");
            $stmt->execute(['tid' => $merchantTransactionId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                // SaaS Renewal Webhook fallback
                if (strpos($merchantTransactionId, 'RENEW_') === 0) {
                    $saltKey = getenv('PHONEPE_SALT_KEY');
                    if (!$saltKey) {
                        $stmtSettings = $db->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'phonepe_salt_key'");
                        $saltKey = $stmtSettings->fetchColumn();
                    }
                    
                    $expectedChecksum = hash('sha256', $data['response'] . $saltKey) . '###1';

                    if (!hash_equals($expectedChecksum, $verifyHeader)) {
                        http_response_code(401);
                        echo "Unauthorized Checksum Mismatch";
                        return;
                    }
                    
                    // SaaS renewal is handled in HotelController usually, just ack
                    http_response_code(200);
                    echo "OK";
                    return;
                }

                http_response_code(404);
                echo "Transaction not found";
                return;
            }

            // Guest Booking Webhook
            $saltKey = $booking['phonepe_salt_key'] ?? '';
            $expectedChecksum = hash('sha256', $data['response'] . $saltKey) . '###1';

            if (!hash_equals($expectedChecksum, $verifyHeader)) {
                http_response_code(401);
                echo "Unauthorized Checksum Mismatch";
                return;
            }

            if ($booking['payment_status'] === 'paid') {
                http_response_code(200);
                echo "OK - Already Processed";
                return;
            }

            if ($code === 'PAYMENT_SUCCESS' && isset($decodedResponse['data']['state']) && $decodedResponse['data']['state'] === 'COMPLETED') {
                $db->beginTransaction();

                Database::table('bookings')
                    ->where('id', $booking['id'])
                    ->update([
                        'payment_status' => 'paid',
                        'transaction_id' => $decodedResponse['data']['transactionId']
                    ]);

                Database::table('payments')->insert([
                    'hotel_id'       => $booking['hotel_id'],
                    'booking_id'     => $booking['id'],
                    'amount'         => (float)$booking['total_price'],
                    'payment_method' => 'PhonePe IBE (Webhook)',
                    'transaction_id' => $decodedResponse['data']['transactionId'],
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
            }

            http_response_code(200);
            echo "OK";

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Webhook Error: " . $e->getMessage());
            http_response_code(500);
            echo "Internal Server Error";
        }
    }
}