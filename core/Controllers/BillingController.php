<?php
declare(strict_types=1);

namespace Syncro\Controllers;

use Syncro\Services\BillingService;
use Syncro\Security\SessionManager;
use Syncro\Security\SecurityManager;
use Syncro\Services\CacheManager; // IMPORT CACHE

class BillingController extends BaseHotelController
{
    private BillingService $billingService;

    public function __construct()
    {
        parent::__construct();
        $this->billingService = new BillingService();
    }

    public function invoice(string $id): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $bookingId = (int)$id;
        if (!$bookingId) {
            SessionManager::setFlash('error', 'Invalid Folio ID.');
            $this->redirect('/user/bookings');
            return;
        }

        try {
            $folioData = $this->billingService->getFolioDetails($this->hotelId, $bookingId);
            
            $this->render('user/invoice', array_merge([
                'pageTitle' => 'Folio #' . str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT)
            ], $folioData), 'user_layout');

        } catch (\Exception $e) {
            SessionManager::setFlash('error', $e->getMessage());
            $this->redirect('/user/bookings');
        }
    }

    public function storePosCharge(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $bookingId = (int)($postData['booking_id'] ?? 0);
        
        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            SessionManager::setFlash('error', 'Security Violation: CSRF Token Mismatch.');
            $this->redirect('/user/invoice?id=' . $bookingId);
            return;
        }

        $description = strip_tags(trim($postData['description'] ?? ''));
        $amount = (float)($postData['amount'] ?? 0);

        if (!$bookingId || empty($description) || $amount <= 0) {
            SessionManager::setFlash('error', 'Invalid POS charge details provided.');
            $this->redirect('/user/invoice?id=' . $bookingId);
            return;
        }

        try {
            $this->billingService->addPosCharge($this->hotelId, $bookingId, $description, $amount);
            
            // 🔥 CLEAR DASHBOARD CACHE
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);
            
            // 🔥 CLEAR FINANCIAL REPORTS CACHE (Today's reports)
            $this->clearReportCache();

            SessionManager::setFlash('success', 'POS charge added successfully.');
            $this->redirect('/user/invoice?id=' . $bookingId);
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Failed to add charge: ' . $e->getMessage());
            $this->redirect('/user/invoice?id=' . $bookingId);
        }
    }

    public function storePayment(array $postData): void
    {
        $this->requireRole(['hotel_admin', 'receptionist']);
        
        $bookingId = (int)($postData['booking_id'] ?? 0);

        if (!SecurityManager::validateCsrfToken($postData['csrf_token'] ?? '')) {
            SessionManager::setFlash('error', 'Security Violation: CSRF Token Mismatch.');
            $this->redirect('/user/invoice?id=' . $bookingId);
            return;
        }

        $amount = (float)($postData['amount'] ?? 0);

        if (!$bookingId || $amount <= 0) {
            SessionManager::setFlash('error', 'Invalid payment amount.');
            $this->redirect('/user/invoice?id=' . $bookingId);
            return;
        }

        try {
            $this->billingService->addPayment($this->hotelId, $bookingId, $postData);
            
            // 🔥 CLEAR DASHBOARD CACHE
            CacheManager::clear('dashboard_metrics_hotel_' . $this->hotelId);
            
            // 🔥 CLEAR FINANCIAL REPORTS CACHE (Today's reports)
            $this->clearReportCache();

            SessionManager::setFlash('success', 'Payment recorded successfully.');
            $this->redirect('/user/invoice?id=' . $bookingId);
        } catch (\Exception $e) {
            SessionManager::setFlash('error', 'Payment failed: ' . $e->getMessage());
            $this->redirect('/user/invoice?id=' . $bookingId);
        }
    }

    /**
     * Helper to invalidate the specific financial report caches for current day/month
     */
    private function clearReportCache(): void
    {
        $today = date('Y-m-d');
        $month = date('n');
        $year = date('Y');
        
        // Matches the cache key format used in ReportController
        $reportCacheKey = "fin_reports_h{$this->hotelId}_{$today}_m{$month}_y{$year}";
        CacheManager::clear($reportCacheKey);
    }
}