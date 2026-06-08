<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use Syncro\Security\SessionManager;
use Exception;

class ExportController extends BaseController
{
    public function exportBookings(): void
    {
        SessionManager::requireLogin();
        // Only allow admins and managers to export
        if ($_SESSION['role'] !== 'hotel_admin' && $_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Forbidden: Export access required.");
        }

        $hotelId = $_SESSION['hotel_id'];
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT b.booking_reference, g.full_name as guest_name, g.email, 
                   b.check_in_date, b.check_out_date, b.status, b.total_price,
                   b.payment_status, b.booking_source
            FROM bookings b
            JOIN guests g ON b.guest_id = g.id
            WHERE b.hotel_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$hotelId]);
        $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bookings_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, ['Booking Ref', 'Guest Name', 'Email', 'Check In', 'Check Out', 'Status', 'Total Price', 'Payment Status', 'Source']);
        
        // Write rows
        foreach ($bookings as $row) {
            fputcsv($output, [
                $row['booking_reference'],
                $row['guest_name'],
                $row['email'],
                $row['check_in_date'],
                $row['check_out_date'],
                $row['status'],
                $row['total_price'],
                $row['payment_status'],
                $row['booking_source']
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportExpenses(): void
    {
        SessionManager::requireLogin();
        if ($_SESSION['role'] !== 'hotel_admin' && $_SESSION['role'] !== 'manager') {
            http_response_code(403);
            die("Forbidden: Export access required.");
        }

        $hotelId = $_SESSION['hotel_id'];
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT expense_date, category, amount, description, payment_method, reference_number
            FROM expenses
            WHERE hotel_id = ?
            ORDER BY expense_date DESC
        ");
        $stmt->execute([$hotelId]);
        $expenses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="expenses_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Category', 'Amount', 'Description', 'Payment Method', 'Ref Num']);
        
        foreach ($expenses as $row) {
            fputcsv($output, [
                $row['expense_date'],
                $row['category'],
                $row['amount'],
                $row['description'],
                $row['payment_method'],
                $row['reference_number']
            ]);
        }
        fclose($output);
        exit;
    }
}
