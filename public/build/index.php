<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/init.php';

// Dynamically fix absolute URLs if running in a subdirectory (like XAMPP's /syncro)
$basePath = '';
$requestUriRaw = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Determine if we are running in a subdirectory (e.g. /syncro)
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptDir = dirname($scriptName);
if ($scriptDir !== '/' && $scriptDir !== '\\') {
    $basePath = $scriptDir;
}
define('BASE_PATH', $basePath);

// 1. Register global error and exception handling (Catch all fatal errors silently)
\Syncro\Security\ExceptionHandler::register();

// 2. Hide standard PHP errors from the screen; securely logged to error.log
ini_set('display_errors', '0'); 

use Syncro\Controllers\AuthController;
use Syncro\Controllers\CheckoutController;
use Syncro\Controllers\AdminController;
use Syncro\Controllers\HotelController;
use Syncro\Controllers\HotelStaffController;
use Syncro\Controllers\HotelInventoryController;
use Syncro\Controllers\HotelRateController;
use Syncro\Controllers\HotelHousekeepingController;
use Syncro\Controllers\HotelPromoController;
use Syncro\Controllers\RoomController;
use Syncro\Controllers\ReservationController;
use Syncro\Controllers\BillingController;
use Syncro\Controllers\GuestController;
use Syncro\Controllers\GuestPortalController;
use Syncro\Controllers\StaffController;
use Syncro\Controllers\ReportController;
use Syncro\Controllers\AjaxInventoryController;
use Syncro\Controllers\PublicController;
use Syncro\Security\SessionManager;
use Syncro\Controllers\AuditLogController;
use Syncro\Controllers\PaymentController;
use Syncro\Controllers\HousekeepingController;
use Syncro\Controllers\CRMController;
use Syncro\Controllers\ExportController;
use Syncro\Controllers\NotificationController;
use Syncro\Controllers\InvoiceController;
use Syncro\Security\Router;

// Strip base directory if running in a subfolder (like XAMPP's htdocs/syncro)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, '/syncro') === 0) {
    $requestUri = substr($requestUri, strlen('/syncro'));
    if ($requestUri === '') $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new \Syncro\Models\Database();
    $router = new Router();

    // =========================================================================
    // DYNAMIC ROUTES (Public Hosted Booking Pages)
    // =========================================================================
    $router->post('/book/{slug}', \Syncro\Controllers\PublicController::class, 'processBooking', [\Syncro\Middleware\ThrottleRequestsMiddleware::class]);
    $router->get('/book/{slug}', \Syncro\Controllers\PublicController::class, 'viewHotelPage', [\Syncro\Middleware\ThrottleRequestsMiddleware::class]);

    // =========================================================================
    // STANDARD ROUTES
    // =========================================================================
    $router->post('/broadcasting/auth', \Syncro\Controllers\AuthController::class, 'authenticatePusher');
    
    // --- PUBLIC MARKETING HOMEPAGE ---
    $router->get('/', \Syncro\Controllers\PublicController::class, 'home');

    // --- GUEST PORTAL & LOGIN ---
    $router->post('/guest/login', \Syncro\Controllers\GuestAuthController::class, 'requestOtp');
    $router->get('/guest/login', \Syncro\Controllers\GuestAuthController::class, 'showLoginForm');

    $router->get('/guest/login/google', \Syncro\Controllers\GuestAuthController::class, 'googleLogin');

    $router->get('/guest/login/google/callback', \Syncro\Controllers\GuestAuthController::class, 'googleCallback');
        
    $router->post('/guest/verify', \Syncro\Controllers\GuestAuthController::class, 'verifyOtp');
        
    $router->get('/guest/logout', \Syncro\Controllers\GuestAuthController::class, 'logout');
    $router->post('/guest/logout', \Syncro\Controllers\GuestAuthController::class, 'logout');

    $router->get('/guest/dashboard', \Syncro\Controllers\GuestPortalController::class, 'dashboard');
        
    $router->get('/guest/portal', \Syncro\Controllers\GuestPortalController::class, 'portal');

    $router->post('/guest/portal/update', \Syncro\Controllers\GuestPortalController::class, 'updateCheckin');

    $router->get('/payment/checkout', \Syncro\Controllers\PaymentController::class, 'checkout');
    $router->post('/payment/checkout', \Syncro\Controllers\PaymentController::class, 'checkout');
        
    // --- LEGAL POLICIES ---
    $router->get('/terms', \Syncro\Controllers\PublicController::class, 'terms');
    $router->get('/privacy', \Syncro\Controllers\PublicController::class, 'privacy');
    $router->get('/refund', \Syncro\Controllers\PublicController::class, 'refund');
    $router->get('/shipping', \Syncro\Controllers\PublicController::class, 'shipping');

    // --- AUTHENTICATION & ONBOARDING ---
    $router->post('/login', \Syncro\Controllers\AuthController::class, 'processLogin');
    $router->get('/login', \Syncro\Controllers\AuthController::class, 'showLoginForm');
        
    $router->post('/login/2fa', \Syncro\Controllers\AuthController::class, 'verify2fa');
    $router->get('/login/2fa', \Syncro\Controllers\AuthController::class, 'show2faForm');
        
    $router->get('/register', \Syncro\Controllers\AuthController::class, 'showRegister');

    $router->post('/register/process-trial', \Syncro\Controllers\AuthController::class, 'processTrialRegistration');

    $router->get('/register/success', \Syncro\Controllers\AuthController::class, 'showRegisterSuccess');

    $router->get('/staff/accept', \Syncro\Controllers\AuthController::class, 'showAcceptInvite');
    $router->post('/staff/accept', \Syncro\Controllers\AuthController::class, 'processAcceptInvite');

    $router->post('/checkout/init', \Syncro\Controllers\CheckoutController::class, 'init');

    $router->post('/checkout/verify', \Syncro\Controllers\CheckoutController::class, 'verify');
        
    $router->post('/logout', \Syncro\Controllers\AuthController::class, 'logout');

    // --- SUPER ADMIN PORTAL ---
    $router->get('/admin/dashboard', \Syncro\Controllers\AdminController::class, 'dashboard');
        
    $router->get('/admin/hotels', \Syncro\Controllers\AdminController::class, 'hotels');
        
    $router->post('/admin/hotels/create', \Syncro\Controllers\AdminController::class, 'storeHotel');
    $router->get('/admin/hotels/create', \Syncro\Controllers\AdminController::class, 'createHotel');
        
    $router->post('/admin/hotels/edit', \Syncro\Controllers\AdminController::class, 'updateHotel');
    $router->get('/admin/hotels/edit', \Syncro\Controllers\AdminController::class, 'editHotel');
        
    $router->post('/admin/hotels/extend', \Syncro\Controllers\AdminController::class, 'extendSubscription');

    $router->post('/admin/hotels/toggle-status', \Syncro\Controllers\AdminController::class, 'toggleStatus');

    $router->post('/admin/hotels/update-details', \Syncro\Controllers\AdminController::class, 'updateHotelDetails');
        
    $router->post('/admin/hotels/update-billing', \Syncro\Controllers\AdminController::class, 'updateHotelBilling');
        
    $router->post('/admin/hotels/reset-password', \Syncro\Controllers\AdminController::class, 'forcePasswordReset');
        
    $router->post('/admin/hotels/impersonate', \Syncro\Controllers\AdminController::class, 'impersonateHotel');
        
    $router->post('/admin/hotels/delete', \Syncro\Controllers\AdminController::class, 'deleteHotel');
        
    $router->post('/admin/broadcast', \Syncro\Controllers\AdminController::class, 'createBroadcast');
        
    $router->post('/admin/broadcast/delete', \Syncro\Controllers\AdminController::class, 'deleteBroadcast');
        
    $router->get('/admin/support', \Syncro\Controllers\AdminController::class, 'supportInbox');
        
    $router->get('/admin/support/view', \Syncro\Controllers\AdminController::class, 'supportView');
        
    $router->post('/admin/support/reply', \Syncro\Controllers\AdminController::class, 'supportReply');

    $router->post('/admin/support/status', \Syncro\Controllers\AdminController::class, 'supportChangeStatus');
        
    $router->get('/admin/settings', \Syncro\Controllers\AdminController::class, 'settings');
        
    $router->post('/admin/settings/update', \Syncro\Controllers\AdminController::class, 'updateSettings');
        
    $router->post('/admin/settings/password', \Syncro\Controllers\AdminController::class, 'updatePassword');

    $router->get('/admin/settings/2fa/setup', \Syncro\Controllers\AdminController::class, 'setupGoogle2fa');

    $router->post('/admin/settings/2fa/verify', \Syncro\Controllers\AdminController::class, 'verifyAndEnableGoogle2fa');

    $router->post('/admin/settings/2fa/disable', \Syncro\Controllers\AdminController::class, 'disableGoogle2fa');

    // --- HOTEL USER PORTAL ---
    $router->get('/user/dashboard', \Syncro\Controllers\HotelController::class, 'dashboard');

    // --- HOUSEKEEPING PORTAL ---
    $router->get('/housekeeping/dashboard', \Syncro\Controllers\HousekeepingController::class, 'dashboard');
        
    $router->post('/housekeeping/update', \Syncro\Controllers\HousekeepingController::class, 'updateStatus');

    // --- CRM PORTAL ---
    $router->get('/crm/directory', \Syncro\Controllers\CRMController::class, 'directory');

    // --- EXPORTS ---
    $router->get('/export/bookings', \Syncro\Controllers\ExportController::class, 'exportBookings');

    $router->get('/notifications/stream', \Syncro\Controllers\NotificationController::class, 'stream');

    $router->post('/notifications/read', \Syncro\Controllers\NotificationController::class, 'markRead');
        
    // --- API ROUTES ---
    $router->get('/api/v1/availability', \Syncro\Controllers\ApiController::class, 'getAvailability', [
        \Syncro\Middleware\ApiAuthMiddleware::class,
        \Syncro\Middleware\ThrottleRequestsMiddleware::class
    ]);

    $router->post('/api/v1/bookings', \Syncro\Controllers\ApiController::class, 'createBooking', [
        \Syncro\Middleware\ApiAuthMiddleware::class,
        \Syncro\Middleware\ThrottleRequestsMiddleware::class
    ]);
        
    $router->get('/export/expenses', \Syncro\Controllers\ExportController::class, 'exportExpenses');

    $router->get('/api/hotel/analytics', \Syncro\Controllers\HotelController::class, 'getAnalyticsData', [\Syncro\Middleware\ApiAuthMiddleware::class]);

    $router->get('/user/reports', \Syncro\Controllers\ReportController::class, 'index');

    $router->post('/user/guests', \Syncro\Controllers\GuestController::class, 'store');
    $router->get('/user/guests', \Syncro\Controllers\GuestController::class, 'index');

    $router->get('/user/guest-profile', \Syncro\Controllers\GuestController::class, 'profile');

    $router->get('/user/invoice', \Syncro\Controllers\InvoiceController::class, 'show');
        
    // --- SETTINGS & BILLING ---
    $router->get('/user/staff', \Syncro\Controllers\StaffController::class, 'index');
        
    $router->post('/user/staff/invite', \Syncro\Controllers\StaffController::class, 'invite');
        
    $router->post('/user/staff/invite/revoke', \Syncro\Controllers\StaffController::class, 'revokeInvite');
        
    $router->post('/user/staff/revoke', \Syncro\Controllers\StaffController::class, 'revoke');

    $router->post('/user/settings', \Syncro\Controllers\HotelController::class, 'updatePassword');
    $router->get('/user/settings', \Syncro\Controllers\HotelController::class, 'settings');
        
    $router->post('/user/settings/payment', \Syncro\Controllers\HotelController::class, 'updatePaymentSettings');
        
    $router->get('/user/settings/generate-token', \Syncro\Controllers\HotelController::class, 'generateApiToken');
    $router->post('/user/settings/generate-token', \Syncro\Controllers\HotelController::class, 'generateApiToken');
        
    $router->post('/user/settings/profile', \Syncro\Controllers\HotelController::class, 'updateProfile');

    $router->get('/user/settings/2fa/setup', \Syncro\Controllers\HotelController::class, 'setup2fa');
        
    $router->post('/user/settings/2fa/verify', \Syncro\Controllers\HotelController::class, 'verifyAndEnable2fa');
        
    $router->post('/user/settings/2fa/disable', \Syncro\Controllers\HotelController::class, 'disable2fa');
        
    $router->post('/user/settings/renew/init', \Syncro\Controllers\HotelController::class, 'renewInit');

    $router->post('/user/settings/renew/verify', \Syncro\Controllers\HotelController::class, 'renewVerify');
        
    $router->post('/user/settings/renew/offline', \Syncro\Controllers\HotelController::class, 'renewOffline');

    $router->get('/user/audit-logs', \Syncro\Controllers\AuditLogController::class, 'index');

    // --- RESERVATIONS & FRONT DESK ---
    $router->post('/user/bookings', \Syncro\Controllers\ReservationController::class, 'store');
    $router->get('/user/bookings', \Syncro\Controllers\ReservationController::class, 'index');
        
    $router->post('/user/bookings/assign', \Syncro\Controllers\ReservationController::class, 'assignRoom');
        
    $router->post('/user/bookings/status', \Syncro\Controllers\ReservationController::class, 'updateStatus');
        
    $router->post('/user/bookings/payment', \Syncro\Controllers\ReservationController::class, 'updatePaymentStatus');
        
    $router->get('/user/calendar', \Syncro\Controllers\HotelInventoryController::class, 'calendar');

    // --- INVENTORY & RATES ---
    $router->get('/user/inventory', \Syncro\Controllers\HotelInventoryController::class, 'inventory');

    $router->post('/user/expenses', \Syncro\Controllers\ExpenseController::class, 'store');
    $router->get('/user/expenses', \Syncro\Controllers\ExpenseController::class, 'index');
        
    $router->post('/user/expenses/delete', \Syncro\Controllers\ExpenseController::class, 'delete');

    $router->post('/user/rates', \Syncro\Controllers\HotelRateController::class, 'updateRates');
    $router->get('/user/rates', \Syncro\Controllers\HotelRateController::class, 'rateManager');

    $router->post('/user/rates/rule/create', \Syncro\Controllers\HotelRateController::class, 'createPricingRule');

    $router->post('/user/rates/rule/toggle', \Syncro\Controllers\HotelRateController::class, 'togglePricingRule');

    $router->get('/user/pos', \Syncro\Controllers\HotelController::class, 'posDashboard');

    $router->post('/user/pos/add', \Syncro\Controllers\HotelController::class, 'addAncillarySale');
        
    $router->post('/user/rooms', \Syncro\Controllers\RoomController::class, 'storeRoomType');
    $router->get('/user/rooms', \Syncro\Controllers\RoomController::class, 'index');
        
    $router->post('/user/rooms/delete', \Syncro\Controllers\RoomController::class, 'deleteRoomType');
        
    $router->post('/user/rooms/update', \Syncro\Controllers\RoomController::class, 'updateRoomType');
        
    $router->post('/user/rooms/physical', \Syncro\Controllers\RoomController::class, 'storePhysicalRoom');
        
    $router->post('/user/rooms/physical/delete', \Syncro\Controllers\RoomController::class, 'deletePhysicalRoom');
        
    $router->post('/user/rooms/physical/update', \Syncro\Controllers\RoomController::class, 'updatePhysicalRoom');

    // --- CHANNEL MANAGER ---
    $router->post('/user/channel-manager', \Syncro\Controllers\HotelInventoryController::class, 'storeMapping');
    $router->get('/user/channel-manager', \Syncro\Controllers\HotelInventoryController::class, 'channelManager');
        
    $router->post('/user/channel-manager/delete', \Syncro\Controllers\HotelInventoryController::class, 'deleteMapping');
        
    // --- HOUSEKEEPING & POS ---
    $router->get('/user/housekeeping', \Syncro\Controllers\HotelHousekeepingController::class, 'housekeeping');

    $router->post('/user/housekeeping/assign', \Syncro\Controllers\HotelHousekeepingController::class, 'assignHousekeeper');
        
    $router->post('/user/housekeeping/ticket', \Syncro\Controllers\HotelHousekeepingController::class, 'createMaintenanceTicket');
        
    $router->post('/user/housekeeping/ticket/resolve', \Syncro\Controllers\HotelHousekeepingController::class, 'resolveMaintenanceTicket');

    $router->post('/user/invoice/charge', \Syncro\Controllers\BillingController::class, 'storePosCharge');

    $router->post('/user/payment/store', \Syncro\Controllers\BillingController::class, 'storePayment');
        
    $router->get('/user/support', \Syncro\Controllers\SupportController::class, 'index');
    $router->post('/user/support', \Syncro\Controllers\SupportController::class, 'create');
        
    $router->get('/user/support/view', \Syncro\Controllers\SupportController::class, 'view');
    $router->post('/user/support/view', \Syncro\Controllers\SupportController::class, 'reply');
        
    // --- PROMO CODES ---
    $router->get('/user/promo-codes', \Syncro\Controllers\HotelPromoController::class, 'promoCodes');
    
    $router->post('/user/promo-codes/create', \Syncro\Controllers\HotelPromoController::class, 'storePromoCode');
    
    $router->post('/user/promo-codes/delete/{id}', \Syncro\Controllers\HotelPromoController::class, 'deletePromoCode');
        
    $router->post('/user/stop-impersonating', \Syncro\Controllers\HotelController::class, 'stopImpersonating');
        
    // --- WEBHOOKS & API ---
    $router->get('/api/inventory', \Syncro\Controllers\Api\ChannelManagerController::class, 'getInventory', [\Syncro\Middleware\ApiAuthMiddleware::class]);
        
    $router->post('/api/reservations', \Syncro\Controllers\Api\ChannelManagerController::class, 'postReservation', [\Syncro\Middleware\ApiAuthMiddleware::class]);

    $router->post('/api/webhook/phonepe', \Syncro\Controllers\CheckoutController::class, 'webhook', [\Syncro\Middleware\ApiAuthMiddleware::class]);
        
    // --- INTERNAL AJAX APIs ---
    $router->post('/ajax/inventory/update', \Syncro\Controllers\AjaxInventoryController::class, 'update');
        
    $router->post('/ajax/inventory/sync', \Syncro\Controllers\AjaxInventoryController::class, 'syncToChannels');
        
    $router->post('/ajax/housekeeping/update', \Syncro\Controllers\AjaxInventoryController::class, 'updateHousekeeping');
        
    $router->post('/ajax/promo/validate', \Syncro\Controllers\PublicController::class, 'validatePromo');

    $router->dispatch($method, $requestUri);

} catch (\Throwable $e) {
    // Pass the caught exception to our centralized, secure Error Handler
    \Syncro\Security\ExceptionHandler::handleException($e);
}