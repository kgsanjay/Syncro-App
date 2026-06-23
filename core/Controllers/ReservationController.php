<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Services\BookingService;
use Syncro\Services\RoomService;
use Syncro\Security\SessionManager;
use Syncro\Services\CacheManager;
use Syncro\Services\EmailService; 

class ReservationController extends BaseHotelController
{
    private \Syncro\Models\Database $db;

    private BookingService $bookingService;
    private RoomService $roomService;

    public function __construct(\Syncro\Models\Database $db)
    {
        $this->db = $db;
        parent::__construct($db);
        $this->bookingService = new BookingService();
        $this->roomService = new RoomService();
    }

    public function index(): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $statusFilter = $_GET['status'] ?? 'all';
        
        $bookings = $this->bookingService->getBookings($this->hotelId, $statusFilter);
        $rooms = $this->roomService->getRoomTypes($this->hotelId);
        $physicalRooms = $this->roomService->getPhysicalRooms($this->hotelId);
        
        $this->render('user/bookings', [
            'pageTitle'     => 'Reservations',
            'bookings'      => $bookings,
            'rooms'         => $rooms,
            'physicalRooms' => $physicalRooms
        ], 'user_layout');
    }

    public function store(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        try {
            $bookingId = $this->bookingService->createDirectBooking($this->hotelId, $postData);
            
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);

            $pusher = new \Syncro\Services\PusherBroadcaster();
            $pusher->broadcast("hotel_channel_{$this->hotelId}", 'new_booking', [
                'bookingId' => $bookingId,
                'guestName' => $postData['guest_name'] ?? 'Guest',
                'checkIn' => $postData['check_in'] ?? '',
                'checkOut' => $postData['check_out'] ?? '',
                'totalPrice' => $postData['total_price'] ?? 0,
                'source' => 'Direct'
            ]);

            if (!empty($postData['guest_email'])) {
                $guestEmail = filter_var($postData['guest_email'], FILTER_SANITIZE_EMAIL);
                $guestName = htmlspecialchars($postData['guest_name'] ?? 'Valued Guest');
                $checkIn = date('F j, Y', strtotime($postData['check_in']));
                $checkOut = date('F j, Y', strtotime($postData['check_out']));
                $price = number_format((float)($postData['total_price'] ?? 0), 2);
                $hotelName = $_SESSION['hotel_name'] ?? 'Our Hotel';

                $subject = "Your Booking Confirmation - " . $hotelName;

                $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eef1f6; border-radius: 8px;'>
                    <div style='background-color: #003366; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0; font-size: 24px;'>Booking Confirmed!</h1>
                        <p style='margin: 5px 0 0 0; opacity: 0.9;'>{$hotelName}</p>
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
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #333333; font-weight: bold; text-align: right;'>{$checkIn}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777;'>Check-Out Date</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #333333; font-weight: bold; text-align: right;'>{$checkOut}</td>
                            </tr>
                            <tr>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #777777;'>Total Price</td>
                                <td style='padding: 12px; border-bottom: 1px solid #eef1f6; color: #008000; font-weight: bold; text-align: right;'>₹{$price}</td>
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
                
                // Seed automated communications for this booking
                $db = $this->db->getPDO();
                
                // 1. Pre-arrival (1 day before check-in at 10 AM)
                $preArrivalDate = date('Y-m-d 10:00:00', strtotime($postData['check_in'] . ' -1 day'));
                $preArrivalMsg = "Dear {$guestName},\n\nWe look forward to welcoming you to {$hotelName} tomorrow! Your confirmation number is #SYNC-{$bookingId}.\n\nSafe travels!";
                
                $stmt = $db->prepare("
                    INSERT INTO communication_queue (hotel_id, booking_id, guest_email, type, subject, message, scheduled_for) 
                    VALUES (:hid, :bid, :email, 'pre_arrival', :sub, :msg, :sched)
                ");
                $stmt->execute([
                    'hid' => $this->hotelId,
                    'bid' => $bookingId,
                    'email' => $guestEmail,
                    'sub' => "We are excited to welcome you tomorrow!",
                    'msg' => $preArrivalMsg,
                    'sched' => $preArrivalDate
                ]);

                // 2. Post-departure (1 day after check-out at 10 AM)
                $postDepartureDate = date('Y-m-d 10:00:00', strtotime($postData['check_out'] . ' +1 day'));
                $postDepartureMsg = "Dear {$guestName},\n\nThank you for staying at {$hotelName}. We hope you had a wonderful experience. We would love to hear your feedback!\n\nBest Regards,\nThe {$hotelName} Team";
                
                $stmt->execute([
                    'hid' => $this->hotelId,
                    'bid' => $bookingId,
                    'email' => $guestEmail,
                    'sub' => "Thank you for your stay!",
                    'msg' => $postDepartureMsg,
                    'sched' => $postDepartureDate
                ]);
            }

            SessionManager::setFlash('success', 'Booking confirmed successfully!');
            $this->redirect('/user/bookings');

        } catch (\Exception $e) {
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/bookings');
        }
    }

    // --- MOVED FROM HOTEL CONTROLLER AND ROUTED TO SERVICE ---
    public function assignRoom(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $bookingId = (int)($postData['booking_id'] ?? 0);
        $roomId = (int)($postData['physical_room_id'] ?? 0);

        if (!$bookingId || !$roomId) { 
            SessionManager::setFlash('error', 'Invalid form submission.');
            $this->redirect('/user/bookings'); 
            return; 
        }

        try {
            // Service handles all database logic securely
            $this->bookingService->assignPhysicalRoom($this->hotelId, $bookingId, $roomId);
            
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);
            SessionManager::setFlash('success', 'Room successfully assigned.');
            $this->redirect('/user/bookings');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/bookings');
        }
    }

    public function updateStatus(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $bookingId = (int)($postData['booking_id'] ?? 0);
        $status = $postData['status'] ?? 'confirmed';

        try {
            // Service handles inventory tape adjustments and DB transactions
            $this->bookingService->updateStatus($this->hotelId, $bookingId, $status);
            
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);
            SessionManager::setFlash('success', 'Booking status updated successfully.');
            $this->redirect('/user/bookings');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to update status.');
            $this->redirect('/user/bookings');
        }
    }

    public function updatePaymentStatus(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);

        $bookingId = (int)($postData['booking_id'] ?? 0);
        $paymentStatus = strip_tags(trim($postData['payment_status'] ?? 'pending'));

        if (!$bookingId) {
            $this->redirect('/user/bookings');
            return;
        }

        try {
            $this->bookingService->updatePaymentStatus($this->hotelId, $bookingId, $paymentStatus);
            
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);
            SessionManager::setFlash('success', 'Payment status manually updated.');
            $this->redirect('/user/bookings');
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to update payment status.');
            $this->redirect('/user/bookings');
        }
    }
}