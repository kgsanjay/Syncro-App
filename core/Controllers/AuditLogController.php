<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Models\Database;
use PDO;
use Throwable;

class AuditLogController extends BaseHotelController
{
    public function index(): void
    {
        // Only Hotel Admins should have access to security logs
        $this->requireRole(['hotel_admin']);

        try {
            $db = Database::getConnection();
            
            // FIX: We order by a.id DESC instead of created_at. 
            // This guarantees chronological order without risking a "Column not found" crash.
            $stmt = $db->prepare("
                SELECT a.*, u.name as user_name, u.role, u.email 
                FROM audit_logs a
                JOIN users u ON a.user_id = u.id
                WHERE u.hotel_id = :hid
                ORDER BY a.id DESC
                LIMIT 500
            ");
            $stmt->execute(['hid' => $this->hotelId]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('user/audit_logs', [
                'pageTitle' => 'Staff Audit Trail',
                'logs'      => $logs
            ], 'user_layout');

        } catch (Throwable $e) {
            // Self-Diagnosing Error: If the table is missing or malformed, it will print it directly to the screen!
            die("
                <div style='padding: 50px; font-family: sans-serif; color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; max-width: 800px; margin: 50px auto;'>
                    <h2 style='margin-top:0;'>Audit Trail Database Error</h2>
                    <p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <p><em>File: " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</em></p>
                    <p style='margin-top: 20px; font-size: 14px;'>Please check phpMyAdmin to ensure the `audit_logs` table exists and the columns match.</p>
                </div>
            ");
        }
    }
}