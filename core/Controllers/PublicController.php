<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Services\BookingService;
use Syncro\Services\EmailService;
use Exception;

class PublicController extends BaseController
{
    public function home(): void
    {
        $this->render('public/home', [
            'pageTitle' => 'Syncro | The Operating System for Independent Hotels'
        ], 'blank_layout');
    }

    public function viewHotelPage(string $slug): void
    {
        $db = Database::getConnection();

        // 1. FETCH HOTEL PROFILE
        $stmt = $db->prepare("
            SELECT *
            FROM hotels WHERE slug = :slug AND status = 'active' LIMIT 1
        ");
        $stmt->execute(['slug' => $slug]);
        $hotel = $stmt->fetch();

        if (!$hotel) {
            http_response_code(404);
            echo "<div style='font-family:sans-serif; text-align:center; padding:50px; color:#555;'><h2>Hotel Not Found</h2><p>This property may be offline or the link is invalid.</p></div>";
            exit;
        }

        // 2. FETCH ROOM TYPES
        $stmt = $db->prepare("
            SELECT id, name, base_price, description, image_url, amenities 
            FROM room_types WHERE hotel_id = :hid ORDER BY base_price ASC
        ");
        $stmt->execute(['hid' => $hotel['id']]);
        $rooms = $stmt->fetchAll();

        // Pass any error messages from a failed booking attempt back to the view
        $error = $_GET['error'] ?? null;

        $this->render('public/booking_page', [
            'pageTitle' => 'Book Direct | ' . $hotel['property_name'],
            'hotel'     => $hotel,
            'rooms'     => $rooms,
            'slug'      => $slug,
            'success'   => $_GET['success'] ?? false,
            'error'     => $error
        ], 'blank_layout');
    }

    public function validatePromo(array $postData): void
    {
        header('Content-Type: application/json');
        $code = strtoupper(trim($postData['code'] ?? ''));
        $hotelId = (int)($postData['hotel_id'] ?? 0);

        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Promo code is required.']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT discount_type, discount_value 
            FROM promo_codes 
            WHERE code = :code 
            AND hotel_id = :hid 
            AND is_active = 1 
            AND (valid_until IS NULL OR valid_until >= CURDATE())
        ");
        $stmt->execute(['code' => $code, 'hid' => $hotelId]);
        $promo = $stmt->fetch();

        if ($promo) {
            echo json_encode([
                'success' => true, 
                'type' => $promo['discount_type'], 
                'value' => (float)$promo['discount_value']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code.']);
        }
    }

    public function processBooking(string $slug, array $postData): void
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM hotels WHERE slug = :slug AND status = 'active' LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $hotel = $stmt->fetch();

        if (!$hotel) {
            $this->redirect("/book/{$slug}?error=" . urlencode("Invalid property."));
            return;
        }

        $hotelId = (int)$hotel['id'];
        $roomId = (int)($postData['room_type_id'] ?? 0);
        $guestName = strip_tags(trim($postData['guest_name'] ?? ''));
        $guestEmail = trim(filter_var($postData['guest_email'] ?? '', FILTER_SANITIZE_EMAIL)); 
        $promoCode = strtoupper(strip_tags(trim($postData['promo_code'] ?? '')));

        $merchantId = $hotel['phonepe_merchant_id'] ?? $hotel['phonepay_merchant_id'] ?? '';
        $saltKey = $hotel['phonepe_salt_key'] ?? $hotel['phonepay_salt_key'] ?? '';

        try {
            $stmtPriceCheck = $db->prepare("SELECT base_price FROM room_types WHERE id = :rid AND hotel_id = :hid");
            $stmtPriceCheck->execute(['rid' => $roomId, 'hid' => $hotelId]);
            $roomCheck = $stmtPriceCheck->fetch();

            if (!$roomCheck) {
                throw new Exception("Invalid room selection.");
            }

            // Calculate total price based on dates
            $checkIn = new \DateTime($postData['check_in']);
            $checkOut = new \DateTime($postData['check_out']);
            $nights = max(1, $checkIn->diff($checkOut)->days);
            $calculatedTotal = (float)$roomCheck['base_price'] * $nights;

            // Apply Promo Code Server-Side
            if (!empty($promoCode)) {
                $stmtPromo = $db->prepare("SELECT discount_type, discount_value FROM promo_codes WHERE code = :code AND hotel_id = :hid AND is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())");
                $stmtPromo->execute(['code' => $promoCode, 'hid' => $hotelId]);
                $promo = $stmtPromo->fetch();
                
                if ($promo) {
                    if ($promo['discount_type'] === 'percentage') {
                        $calculatedTotal -= $calculatedTotal * ((float)$promo['discount_value'] / 100);
                    } elseif ($promo['discount_type'] === 'fixed') {
                        $calculatedTotal -= (float)$promo['discount_value'];
                    }
                    $calculatedTotal = max(0, $calculatedTotal);
                } else {
                    throw new Exception("Invalid or expired promo code applied.");
                }
            }

            // 1. Delegate to the Centralized Booking Service FIRST!
            $bookingService = new BookingService();
            
            // Format data for the service
            $serviceData = [
                'guest_name'   => $guestName,
                'guest_email'  => $guestEmail,
                'room_type_id' => $roomId,
                'check_in'     => $postData['check_in'],
                'check_out'    => $postData['check_out'],
                'source'       => 'Hosted Booking Page',
                'total_price'  => $calculatedTotal
            ];

            // This securely tracks inventory, locks dates, and creates the booking ID
            $bookingId = $bookingService->createDirectBooking($hotelId, $serviceData);

            // 2. Hand off to PhonePe redirect IF a payment is required and keys exist
            if ($calculatedTotal > 0 && !empty($merchantId) && !empty($saltKey)) {
                (new \Syncro\Controllers\CheckoutController())->init(['booking_id' => $bookingId]);
                return; // Stop execution here so the redirect processes correctly
            }

            // 3. Fallback: Free booking or No Gateway Configured
            if (!empty($guestEmail)) {
                $subject = "Your Booking Confirmation - " . $hotel['property_name'];
                $checkInFormatted = date('F j, Y', strtotime($postData['check_in']));
                $checkOutFormatted = date('F j, Y', strtotime($postData['check_out']));
                $priceFormatted = number_format($calculatedTotal, 2);

                $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eef1f6; border-radius: 8px;'>
                    <div style='background-color: #003366; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0; font-size: 24px;'>Booking Confirmed!</h1>
                        <p style='margin: 5px 0 0 0; opacity: 0.9;'>{$hotel['property_name']}</p>
                    </div>
                    
                    <div style='padding: 30px 20px; background-color: #ffffff;'>
                        <p style='color: #333333; font-size: 16px;'>Dear <strong>{$guestName}</strong>,</p>
                        <p style='color: #555555; line-height: 1.6;'>Thank you for choosing to stay with us. We are thrilled to confirm your reservation. Please find your stay details below:</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px;'>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777; width: 40%;'>Confirmation Number</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #333333; font-weight: bold; text-align: right;'>#SYNC-{$bookingId}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777;'>Check-In Date</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #333333; font-weight: bold; text-align: right;'>{$checkInFormatted}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777;'>Check-Out Date</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #333333; font-weight: bold; text-align: right;'>{$checkOutFormatted}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777;'>Total Price</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #008000; font-weight: bold; text-align: right;'>₹{$priceFormatted}</td>
                            </tr>
                        </table>

                        <p style='color: #555555; line-height: 1.6;'>If you have any questions or need to make changes to your reservation, please don't hesitate to contact our front desk.</p>
                        <p style='color: #333333; font-weight: bold; margin-top: 30px;'>We look forward to welcoming you!</p>
                    </div>
                    
                    <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888888; border-radius: 0 0 8px 8px;'>
                        This is an automated message generated by the Syncro Property Management System.
                    </div>
                </div>";

                EmailService::sendTransactionalEmail($guestEmail, $subject, $htmlBody);
            }

            $this->redirect("/book/{$slug}?success=true");

        } catch (Exception $e) {
            $this->redirect("/book/{$slug}?error=" . urlencode($e->getMessage()));
        }
    }

    // =========================================================================
    // LEGAL POLICY PAGES
    // =========================================================================

    public function terms(): void
    {
        $this->render('public/terms', [
            'pageTitle' => 'Terms & Conditions | Syncro'
        ], 'blank_layout');
    }

    public function privacy(): void
    {
        $this->render('public/privacy', [
            'pageTitle' => 'Privacy Policy | Syncro'
        ], 'blank_layout');
    }

    public function shipping(): void
    {
        $this->render('public/shipping', [
            'pageTitle' => 'Shipping & Delivery Policy | Syncro'
        ], 'blank_layout');
    }
    
    public function refund(): void
    {
        $this->render('public/refund', [
            'pageTitle' => 'Refund & Cancellation Policy | Syncro'
        ], 'blank_layout'); // Fixed layout mismatch
    }
}