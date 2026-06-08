<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Exception;

class PaymentController extends BaseController
{
    /**
     * Start a PhonePe Checkout Session from the Guest Portal
     */
    public function checkout(): void
    {
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $token = $_GET['token'] ?? '';

        if (!$bookingId || empty($token)) {
            die('Invalid payment link.');
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT b.id, b.guest_id, b.total_price, b.payment_status, b.token, 
                       h.phonepe_merchant_id, h.phonepe_salt_key, h.phonepe_env, h.slug 
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                WHERE b.id = ? AND b.token = ?
            ");
            $stmt->execute([$bookingId, $token]);
            $booking = $stmt->fetch();

            if (!$booking) {
                die('Booking not found or invalid token.');
            }

            if ($booking['payment_status'] === 'paid') {
                $this->redirect('/guest/portal?token=' . $token . '&msg=already_paid');
                return;
            }

            // Fetch guest phone
            $guestStmt = $db->prepare("SELECT phone FROM guests WHERE id = ?");
            $guestStmt->execute([$booking['guest_id']]);
            $guestPhone = $guestStmt->fetchColumn() ?: '';

            $merchantId = $booking['phonepe_merchant_id'] ?? '';
            $saltKey = $booking['phonepe_salt_key'] ?? '';
            $env = $booking['phonepe_env'] ?? 'uat';

            if (empty($merchantId) || empty($saltKey)) {
                die('The hotel has not configured their payment gateway.');
            }

            $baseUrl = $env === 'prod' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
            $amountInPaise = (int)round((float)$booking['total_price'] * 100); 
            $merchantTransactionId = 'PORTAL_' . $booking['id'] . '_' . time();
            $saltIndex = "1"; 
            $endpoint = "/pg/v1/pay";

            // Note: We use CheckoutController's webhook because it already securely handles PhonePe callbacks!
            $successUrl = "https://{$_SERVER['HTTP_HOST']}/checkout/verify";
            $webhookUrl = "https://{$_SERVER['HTTP_HOST']}/api/webhook/phonepe";

            $payload = [
                'merchantId' => $merchantId,
                'merchantTransactionId' => $merchantTransactionId,
                'merchantUserId' => 'MUID_' . ($booking['guest_id'] ?? 'GUEST'),
                'amount' => $amountInPaise,
                'redirectUrl' => $successUrl,
                'redirectMode' => 'POST',
                'callbackUrl' => $webhookUrl,
                'mobileNumber' => $guestPhone,
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];

            $base64Payload = base64_encode(json_encode($payload));
            $checksum = hash('sha256', $base64Payload . $endpoint . $saltKey) . '###' . $saltIndex;

            $ch = curl_init($baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $base64Payload]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-VERIFY: ' . $checksum
            ]);
            
            // Bypass SSL check for local dev
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                die("Failed to communicate with payment gateway.");
            }

            $orderData = json_decode($response, true);

            if (isset($orderData['success']) && $orderData['success'] === true) {
                // Save the generated transaction ID to verify later
                $db->prepare("UPDATE bookings SET transaction_id = ? WHERE id = ?")
                   ->execute([$merchantTransactionId, $bookingId]);

                $paymentUrl = $orderData['data']['instrumentResponse']['redirectInfo']['url'];
                header("Location: " . $paymentUrl);
                exit;
            } else {
                die("PhonePe Error: " . ($orderData['message'] ?? 'Unknown initialization error'));
            }

        } catch (Exception $e) {
            die('Checkout initialization failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    // We no longer need the webhook here, since we route it to CheckoutController->webhook()
    // which already has the secure PhonePe callback logic handling multiple transaction types.
}
