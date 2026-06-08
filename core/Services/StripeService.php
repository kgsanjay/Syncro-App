<?php
declare(strict_types=1);

namespace Syncro\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Exception;

class StripeService
{
    public function __construct()
    {
        $secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? 'sk_test_mock_key';
        Stripe::setApiKey($secretKey);
    }

    /**
     * Create a Stripe Checkout Session for a guest's booking payment
     */
    public function createCheckoutSession(int $bookingId, float $amount, string $currency = 'inr', string $guestEmail = '', string $successUrl = '', string $cancelUrl = ''): Session
    {
        // Stripe expects amounts in cents/paise
        $amountInCents = (int)round($amount * 100);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => $guestEmail ?: null,
                'client_reference_id' => (string)$bookingId,
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Syncro Hotel Booking #' . $bookingId,
                            'description' => 'Room Reservation',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl ?: "http://localhost/syncro/guest/portal",
                'cancel_url'  => $cancelUrl ?: "http://localhost/syncro/guest/portal",
                'metadata' => [
                    'booking_id' => $bookingId
                ]
            ]);

            return $session;
        } catch (Exception $e) {
            error_log("Stripe Checkout Error: " . $e->getMessage());
            throw new Exception("Unable to initiate payment: " . $e->getMessage());
        }
    }
}
