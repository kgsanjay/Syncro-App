<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Services\ReportService;
use Syncro\Services\CacheManager;
use Syncro\Models\Database;
use Throwable;

class ReportController extends BaseHotelController
{
    private ReportService $reportService;

    public function __construct()
    {
        parent::__construct();
        $this->reportService = new ReportService();
    }

    public function index(): void
    {
        // Strictly enforced RBAC: Only admins see financial deep-dives
        $this->requireRole(['hotel_admin']);

        $targetDate = $_GET['date'] ?? date('Y-m-d');
        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        
        // Check if the user clicked the "Export to CSV" button
        $isExport = isset($_GET['export']) && $_GET['export'] === 'csv';

        // 1. Define a unique cache key based on Hotel ID, Date, and Month/Year
        $cacheKey = "fin_reports_h{$this->hotelId}_{$targetDate}_m{$month}_y{$year}";
        
        // 2. Attempt to pull from Cache
        $reportData = CacheManager::get($cacheKey);

        if (!$reportData) {
            try {
                // 3. CACHE MISS: Execute high-intensity calculations
                // Daily Reconciliation (Shift Collections)
                $reconciliation = $this->reportService->getDailyReconciliation($this->hotelId, $targetDate);
                
                // Monthly Performance (Revenue Trends & Occupancy)
                $performance = $this->reportService->getMonthlyPerformance($this->hotelId, $month, $year);

                // Calculate the grand total of the selected day's collections
                $shiftTotal = array_reduce($reconciliation, fn($carry, $item) => $carry + (float)$item['total_collected'], 0.0);

                // Calculate Expenses for the month
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT SUM(amount) FROM expenses WHERE hotel_id = :hotel_id AND YEAR(date) = :y AND MONTH(date) = :m");
                $stmt->execute(['hotel_id' => $this->hotelId, 'y' => $year, 'm' => $month]);
                $totalExpenses = (float)$stmt->fetchColumn();

                $reportData = [
                    'reconciliation' => $reconciliation,
                    'performance'    => $performance,
                    'shiftTotal'     => $shiftTotal,
                    'totalExpenses'  => $totalExpenses,
                    'cachedAt'       => date('g:i A')
                ];

                // 4. Save to cache for 15 minutes
                CacheManager::set($cacheKey, $reportData, 900);

            } catch (Throwable $e) {
                // FIXED: Hand the error over to your new Global Exception Handler!
                // It will log it securely and show the 500 error page.
                throw $e;
            }
        }

        // --- 5. EXPORT TO CSV LOGIC ---
        if ($isExport) {
            $filename = "Financial_Report_" . str_replace('-', '', $targetDate) . ".csv";
            
            // Tell the browser to download a CSV file
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            // Open PHP output stream
            $output = fopen('php://output', 'w');
            
            // Add BOM (Byte Order Mark) so Microsoft Excel reads UTF-8 characters correctly
            fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            // Write Daily Reconciliation Section
            fputcsv($output, ['Daily Shift Reconciliation - ' . date('F j, Y', strtotime($targetDate))]);
            fputcsv($output, ['Payment Method', 'Total Collected']);
            
            if (!empty($reportData['reconciliation'])) {
                foreach ($reportData['reconciliation'] as $row) {
                    fputcsv($output, [ucfirst($row['payment_method']), number_format((float)$row['total_collected'], 2)]);
                }
            } else {
                fputcsv($output, ['No collections', '0.00']);
            }
            fputcsv($output, ['Shift Total', number_format((float)$reportData['shiftTotal'], 2)]);
            
            fputcsv($output, []); // Blank Row Separator
            fputcsv($output, []); // Blank Row Separator

            // Write Monthly Performance Section
            $monthName = date('F', mktime(0, 0, 0, $month, 10));
            fputcsv($output, ["Monthly Performance - {$monthName} {$year}"]);
            fputcsv($output, ['Metric', 'Value']);
            
            if (!empty($reportData['performance'])) {
                $perf = $reportData['performance'];
                $expenses = $reportData['totalExpenses'];
                $netProfit = $perf['total_revenue'] - $expenses;
                
                fputcsv($output, ['Total Revenue', number_format((float)$perf['total_revenue'], 2)]);
                fputcsv($output, ['Total Expenses', number_format($expenses, 2)]);
                fputcsv($output, ['Net Profit (P&L)', number_format($netProfit, 2)]);
                fputcsv($output, ['Occupied Nights', $perf['occupied_nights']]);
                fputcsv($output, ['Available Nights', $perf['available_nights']]);
                fputcsv($output, ['Occupancy Rate (%)', number_format((float)$perf['occupancy_rate'], 1)]);
                fputcsv($output, ['ADR', number_format((float)$perf['adr'], 2)]);
                fputcsv($output, ['RevPAR', number_format((float)$perf['revpar'], 2)]);
            } else {
                fputcsv($output, ['No performance data', '']);
            }

            fclose($output);
            exit(); // Stop PHP from rendering the HTML view below!
        }

        // --- 6. STANDARD HTML RENDER ---
        $this->render('user/reports', array_merge([
            'pageTitle'  => 'Financial Reports',
            'targetDate' => $targetDate,
            'month'      => $month,
            'year'       => $year
        ], $reportData), 'user_layout');
    }
}