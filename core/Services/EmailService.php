<?php
declare(strict_types=1);

namespace Syncro\Services;

class EmailService
{
    /**
     * Sends a generic transactional email (Used for 2FA OTPs, Password Resets, etc.)
     */
    public static function sendTransactionalEmail(string $toEmail, string $subject, string $htmlContent): bool
    {
        $queue = new DatabaseQueue();
        return $queue->push(\Syncro\Jobs\EmailJob::class, [
            'toEmail' => $toEmail,
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ]);
    }

    /**
     * Sends the beautifully formatted Booking Confirmation Email
     */
    public static function sendBookingConfirmation(string $toEmail, string $guestName, string $hotelName, string $checkIn, string $checkOut, int $bookingId): bool
    {
        // FIX: Neutralize HTML/XSS injection vectors from user input
        $safeGuestName = htmlspecialchars($guestName, ENT_QUOTES, 'UTF-8');
        $safeHotelName = htmlspecialchars($hotelName, ENT_QUOTES, 'UTF-8');

        $htmlContent = "
            <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 40px 30px; border: 1px solid #e2e8f0; border-radius: 4px; background-color: #ffffff;'>
                
                <div style='text-align: center; border-bottom: 2px solid #003366; padding-bottom: 20px; margin-bottom: 25px;'>
                    <h1 style='color: #003366; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;'>Booking Confirmed.</h1>
                    <p style='color: #475569; font-size: 12px; margin-top: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;'>{$safeHotelName}</p>
                </div>
                
                <p style='color: #1e293b; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>Dear <strong>{$safeGuestName}</strong>,</p>
                <p style='color: #1e293b; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>Your reservation has been successfully locked in. Below are the official details of your upcoming stay:</p>
                
                <div style='background-color: #f8fafc; padding: 25px; border: 1px solid #e2e8f0; border-left: 4px solid #003366; border-radius: 4px; margin: 25px 0;'>
                    <table width='100%' style='font-size: 15px; color: #0f172a; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #e2e8f0;'><strong>Folio Number:</strong></td>
                            <td style='padding: 10px 0; text-align: right; font-family: monospace; font-weight: bold; border-bottom: 1px solid #e2e8f0;'>#" . str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #e2e8f0;'><strong>Check-in Date:</strong></td>
                            <td style='padding: 10px 0; text-align: right; font-weight: bold; border-bottom: 1px solid #e2e8f0;'>" . date('F j, Y', strtotime($checkIn)) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'><strong>Check-out Date:</strong></td>
                            <td style='padding: 10px 0; text-align: right; font-weight: bold;'>" . date('F j, Y', strtotime($checkOut)) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='color: #1e293b; font-size: 15px; line-height: 1.6; margin-top: 30px;'>We look forward to hosting you. If you have any special requests or require modifications, please contact the front desk directly.</p>
                
                <div style='text-align: center; margin-top: 50px; padding-top: 25px; border-top: 1px solid #e2e8f0;'>
                    <p style='color: #64748b; font-size: 10px; margin: 0; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;'>Securely Processed By</p>
                    <p style='color: #003366; font-weight: 900; font-size: 18px; margin: 5px 0 0 0; letter-spacing: -0.5px;'>SYNCRO<span style='color:#3b82f6;'>.</span></p>
                </div>
            </div>
        ";

        // Plaintext fallback for basic mail
        $textContent = "Dear {$guestName},\n\n";
        $textContent .= "Your stay at {$hotelName} is confirmed.\n\n";
        $textContent .= "Folio ID: #" . str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT) . "\n";
        $textContent .= "Check-in: {$checkIn}\n";
        $textContent .= "Check-out: {$checkOut}\n\n";
        $textContent .= "Thank you,\nSyncro Hospitality";

        $queue = new DatabaseQueue();
        return $queue->push(\Syncro\Jobs\EmailJob::class, [
            'toEmail' => $toEmail,
            'toName' => $guestName,
            'subject' => "Your Reservation at {$safeHotelName} is Confirmed",
            'htmlContent' => $htmlContent,
            'textContent' => $textContent
        ]);
    }
}