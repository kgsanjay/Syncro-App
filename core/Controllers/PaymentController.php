<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Services\StripeService;
use Exception;

class PaymentController extends BaseController
{
    /**
     * Start a Stripe Checkout Session
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
                SELECT id, guest_id, total_price, payment_status, token 
                FROM bookings 
                WHERE id = ? AND token = ?
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

            // Fetch guest email
            $guestStmt = $db->prepare("SELECT email FROM guests WHERE id = ?");
            $guestStmt->execute([$booking['guest_id']]);
            $guestEmail = $guestStmt->fetchColumn() ?: '';

            $stripe = new StripeService();
            $successUrl = "http://" . $_SERVER['HTTP_HOST'] . "/guest/portal?token={$token}&payment=success";
            $cancelUrl = "http://" . $_SERVER['HTTP_HOST'] . "/guest/portal?token={$token}&payment=cancelled";

            $session = $stripe->createCheckoutSession(
                $booking['id'], 
                (float)$booking['total_price'], 
                'inr', 
                $guestEmail, 
                $successUrl, 
                $cancelUrl
            );

            // Redirect to Stripe Hosted Checkout
            header("Location: " . $session->url);
            exit;

        } catch (Exception $e) {
            die('Checkout initialization failed: ' . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Stripe Webhook Endpoint to securely mark payments as paid
     */
    public function webhook(): void
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        $event = null;

        try {
            if ($endpoint_secret) {
                $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            } else {
                // For local dev without webhook secrets (not recommended in prod)
                $event = json_decode($payload);
            }
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $bookingId = (int)($session->client_reference_id ?? 0);

            if ($bookingId) {
                $db = Database::getConnection();
                $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid' WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                error_log("Stripe Webhook: Booking {$bookingId} marked as PAID.");
            }
        }

        http_response_code(200);
    }
}
