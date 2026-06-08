<?php
declare(strict_types=1);

require_once __DIR__ . '/core/init.php';

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
if ($basePath !== '') {
    ob_start(function($buffer) use ($basePath) {
        // Rewrite href="/...", action="/...", src="/..." to include the subdirectory
        $buffer = preg_replace('/(href|action|src)=["\']\/(?!\/)(.*?)["\']/i', '$1="' . $basePath . '/$2"', $buffer);
        // Fix JavaScript redirects: window.location.href = '/...'
        $buffer = preg_replace('/window\.location\.href\s*=\s*["\']\/(?!\/)(.*?)["\']/i', 'window.location.href = "' . $basePath . '/$1"', $buffer);
        return $buffer;
    });
}

// 1. Register global error and exception handling (Catch all fatal errors silently)
\Syncro\Security\ExceptionHandler::register();

// 2. Hide standard PHP errors from the screen; securely logged to error.log
ini_set('display_errors', '0'); 

// Security Headers (now fully managed by .htaccess for performance and consistency) 

use Syncro\Controllers\AuthController;
use Syncro\Controllers\CheckoutController;
use Syncro\Controllers\AdminController;
use Syncro\Controllers\HotelController;
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

// Simple, strictly-typed Secure Router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Strip base directory if running in a subfolder (like XAMPP's htdocs/syncro)
if (strpos($requestUri, '/syncro') === 0) {
    $requestUri = substr($requestUri, strlen('/syncro'));
    if ($requestUri === '') $requestUri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // =========================================================================
    // DYNAMIC ROUTES (Public Hosted Booking Pages)
    // =========================================================================
    if (preg_match('/^\/book\/([a-zA-Z0-9\-]+)$/', $requestUri, $matches)) {
        $slug = $matches[1];
        
        if ($method === 'POST') {
            (new PublicController())->processBooking($slug, $_POST);
        } else {
            (new PublicController())->viewHotelPage($slug);
        }
        exit(); 
    }

    // =========================================================================
    // STANDARD ROUTES
    // =========================================================================
    switch ($requestUri) {
        
        // --- PUBLIC MARKETING HOMEPAGE ---
        case '/':
            (new PublicController())->home();
            break;
            
        case '/guest/portal':
            (new GuestPortalController())->portal();
            break;

        case '/payment/checkout':
            (new PaymentController())->checkout();
            break;
            
        case '/webhook/stripe':
            if ($method === 'POST') (new PaymentController())->webhook();
            else http_response_code(405);
            break;

        case '/guest/portal/update':
            if ($method === 'POST') (new GuestPortalController())->updateCheckin($_POST, $_FILES);
            else http_response_code(405);
            break;
            
        // --- LEGAL POLICIES ---
        case '/terms':
            (new PublicController())->terms();
            break;
        case '/privacy':
            (new PublicController())->privacy();
            break;
        case '/refund':
            (new PublicController())->refund();
            break;
        case '/shipping':
            (new PublicController())->shipping();
            break;

        // --- AUTHENTICATION & ONBOARDING ---
        case '/login':
            $auth = new AuthController();
            if ($method === 'POST') {
                $auth->processLogin($_POST, $_SERVER['REMOTE_ADDR']);
            } else {
                $auth->showLoginForm();
            }
            break;
            
        case '/login/2fa':
            if ($method === 'POST') {
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                (new AuthController())->verify2fa($_POST, $ipAddress);
            } else {
                (new AuthController())->show2faForm();
            }
            break;
            
        case '/register':
            (new AuthController())->showRegister();
            break;

        case '/register/process-trial':
            if ($method === 'POST') (new AuthController())->processTrialRegistration($_POST);
            else http_response_code(405);
            break;

        case '/register/success':
            if ($method === 'GET') (new AuthController())->showRegisterSuccess();
            else http_response_code(405);
            break;

        case '/staff/accept':
            if ($method === 'GET') (new AuthController())->showAcceptInvite($_GET['token'] ?? '');
            elseif ($method === 'POST') (new AuthController())->processAcceptInvite($_POST);
            else http_response_code(405);
            break;

        case '/checkout/init':
            if ($method === 'POST') (new CheckoutController())->init($_POST);
            else http_response_code(405); 
            break;

        case '/checkout/verify':
            if ($method === 'POST') (new CheckoutController())->verify($_POST);
            else http_response_code(405);
            break;
            
        case '/logout':
            if ($method === 'POST') {
                SessionManager::destroy();
                header("Location: " . ($basePath !== '' ? rtrim($basePath, '/') : '') . "/login");
                exit();
            } else {
                http_response_code(405);
                echo "Method Not Allowed. Logout requires POST.";
                exit();
            }

        // --- SUPER ADMIN PORTAL ---
        case '/admin/dashboard':
            (new AdminController())->dashboard();
            break;
            
        case '/admin/hotels':
            (new AdminController())->hotels();
            break;
            
        case '/admin/hotels/create': 
            if ($method === 'POST') (new AdminController())->storeHotel($_POST);
            else (new AdminController())->createHotel();
            break;
            
        case '/admin/hotels/edit':
            if ($method === 'POST') (new AdminController())->updateHotel($_POST);
            else (new AdminController())->editHotel($_GET['id'] ?? null);
            break;
            
        case '/admin/hotels/extend':
            if ($method === 'POST') (new AdminController())->extendSubscription($_POST);
            else http_response_code(405);
            break;

        case '/admin/hotels/toggle-status':
            if ($method === 'POST') (new AdminController())->toggleStatus($_POST);
            else http_response_code(405);
            break;

        case '/admin/hotels/update-details':
            if ($method === 'POST') (new AdminController())->updateHotelDetails($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/hotels/update-billing':
            if ($method === 'POST') (new AdminController())->updateHotelBilling($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/hotels/reset-password':
            if ($method === 'POST') (new AdminController())->forcePasswordReset($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/hotels/impersonate':
            if ($method === 'POST') (new AdminController())->impersonateHotel($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/hotels/delete':
            if ($method === 'POST') (new AdminController())->deleteHotel($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/broadcast':
            if ($method === 'POST') (new AdminController())->createBroadcast($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/broadcast/delete':
            if ($method === 'POST') (new AdminController())->deleteBroadcast($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/support':
            if ($method === 'GET') (new AdminController())->supportInbox();
            else http_response_code(405);
            break;
            
        case '/admin/support/view':
            if ($method === 'GET') (new AdminController())->supportView($_GET);
            else http_response_code(405);
            break;
            
        case '/admin/support/reply':
            if ($method === 'POST') (new AdminController())->supportReply($_POST);
            else http_response_code(405);
            break;

        case '/admin/support/status':
            if ($method === 'POST') (new AdminController())->supportChangeStatus($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/settings':
            if ($method === 'GET') (new AdminController())->settings();
            else http_response_code(405);
            break;
            
        case '/admin/settings/update':
            if ($method === 'POST') (new AdminController())->updateSettings($_POST);
            else http_response_code(405);
            break;
            
        case '/admin/settings/password':
            if ($method === 'POST') (new AdminController())->updatePassword($_POST);
            else http_response_code(405);
            break;

        case '/admin/settings/2fa/setup':
            (new AdminController())->setupGoogle2fa();
            break;

        case '/admin/settings/2fa/verify':
            if ($method === 'POST') (new AdminController())->verifyAndEnableGoogle2fa($_POST);
            else http_response_code(405);
            break;

        case '/admin/settings/2fa/disable':
            if ($method === 'POST') (new AdminController())->disableGoogle2fa($_POST);
            else http_response_code(405);
            break;

        // --- HOTEL USER PORTAL ---
        case '/user/dashboard':
            (new HotelController())->dashboard();
            break;
            
        case '/user/audit-logs':
            if ($method === 'GET') (new HotelController())->auditLogs();
            else http_response_code(405);
            break;

        // --- HOUSEKEEPING PORTAL ---
        case '/housekeeping/dashboard':
            (new HousekeepingController())->dashboard();
            break;
            
        case '/housekeeping/update':
            if ($method === 'POST') (new HousekeepingController())->updateStatus();
            else http_response_code(405);
            break;

        // --- CRM PORTAL ---
        case '/crm/directory':
            (new CRMController())->directory();
            break;

        // --- EXPORTS ---
        case '/export/bookings':
            if ($method === 'GET') (new ExportController())->exportBookings();
            else http_response_code(405);
            break;

        case '/notifications/stream':
            if ($method === 'GET') (new NotificationController())->stream();
            else http_response_code(405);
            break;

        case '/notifications/read':
            if ($method === 'POST') (new NotificationController())->markRead();
            else http_response_code(405);
            
         // --- API ROUTES ---
        case '/api/v1/availability':
            if ($method === 'GET') (new \Syncro\Controllers\ApiController())->getAvailability();
            else http_response_code(405);
            break;

        case '/api/v1/bookings':
            if ($method === 'POST') (new \Syncro\Controllers\ApiController())->createBooking();
            else http_response_code(405);
            break;
            
        case '/export/expenses':
            if ($method === 'GET') (new ExportController())->exportExpenses();
            else http_response_code(405);
            break;

        case '/api/hotel/analytics':
            if ($method === 'GET') (new HotelController())->getAnalyticsData();
            else http_response_code(405);
            break;

        case '/user/reports':
            (new ReportController())->index();
            break;

        case '/user/guests':
            if ($method === 'POST') (new GuestController())->store($_POST);
            else (new GuestController())->index();
            break;

        case '/user/guest-profile':
            (new GuestController())->profile();
            break;

        case '/user/invoice':
            if ($method === 'GET') (new \Syncro\Controllers\InvoiceController())->show();
            else http_response_code(405);
            break;
            
        // --- SETTINGS & BILLING ---
        case '/user/staff':
            if ($method === 'GET') (new StaffController())->index();
            else http_response_code(405);
            break;
            
        case '/user/staff/invite':
            if ($method === 'POST') (new StaffController())->invite($_POST);
            else http_response_code(405);
            break;
            
        case '/user/staff/invite/revoke':
            if ($method === 'POST') (new StaffController())->revokeInvite($_POST);
            else http_response_code(405);
            break;
            
        case '/user/staff/revoke':
            if ($method === 'POST') (new StaffController())->revoke($_POST);
            else http_response_code(405);
            break;

        case '/user/settings':
            if ($method === 'POST') (new HotelController())->updatePassword($_POST);
            else (new HotelController())->settings();
            break;
            
        case '/user/settings/payment':
            if ($method === 'POST') (new HotelController())->updatePaymentSettings($_POST);
            break;
            
        case '/user/settings/generate-token':
            (new HotelController())->generateApiToken();
            break;
            
        case '/user/settings/profile':
            if ($method === 'POST') (new HotelController())->updateProfile($_POST);
            break;
        

            
        case '/user/settings/2fa/setup':
            (new HotelController())->setup2fa();
            break;
            
        case '/user/settings/2fa/verify':
            if ($method === 'POST') (new HotelController())->verifyAndEnable2fa($_POST);
            break;
            
        case '/user/settings/2fa/disable':
            if ($method === 'POST') (new HotelController())->disable2fa($_POST);
            break;
            
        case '/user/settings/renew/init':
            if ($method === 'POST') (new HotelController())->renewInit($_POST);
            else http_response_code(405); 
            break;

        case '/user/settings/renew/verify':
            if ($method === 'POST') (new HotelController())->renewVerify($_POST);
            else http_response_code(405);
            break;
            
        case '/user/settings/renew/offline':
            if ($method === 'POST') (new HotelController())->renewOffline($_POST);
            else http_response_code(405);
            break;

        // --- STAFF MANAGEMENT ---
        case '/user/staff':
            if ($method === 'POST') {
                if (isset($_POST['action']) && $_POST['action'] === 'delete') {
                    (new HotelController())->deleteStaff($_POST);
                } else {
                    (new HotelController())->createStaff($_POST);
                }
            } else {
                (new HotelController())->staffManager();
            }
            break;
        
        case '/user/audit-logs':
            (new AuditLogController())->index();
            break;
            
        // --- RESERVATIONS & FRONT DESK ---
        case '/user/bookings':
            if ($method === 'POST') (new ReservationController())->store($_POST);
            else (new ReservationController())->index();
            break;
            
        case '/user/bookings/assign':
            if ($method === 'POST') (new ReservationController())->assignRoom($_POST); 
            break;
            
        case '/user/bookings/status':
            if ($method === 'POST') (new ReservationController())->updateStatus($_POST); 
            break;
            
        case '/user/bookings/payment':
            if ($method === 'POST') (new ReservationController())->updatePaymentStatus($_POST); 
            break;
            
        case '/user/calendar':
            (new HotelController())->calendar();
            break;

        // --- INVENTORY & RATES ---
        case '/user/inventory':
            (new HotelController())->inventory();
            break;

        case '/user/expenses':
            if ($method === 'POST') (new \Syncro\Controllers\ExpenseController())->store($_POST);
            else (new \Syncro\Controllers\ExpenseController())->index();
            break;
            
        case '/user/expenses/delete':
            if ($method === 'POST') (new \Syncro\Controllers\ExpenseController())->delete($_POST);
            break;

        case '/user/rates':
            if ($method === 'POST') (new HotelController())->updateRates($_POST);
            else (new HotelController())->rateManager();
            break;

        case '/user/rates/rule/create':
            if ($method === 'POST') (new HotelController())->createPricingRule($_POST);
            else http_response_code(405);
            break;

        case '/user/rates/rule/toggle':
            if ($method === 'POST') (new HotelController())->togglePricingRule($_POST);
            else http_response_code(405);
            break;

        case '/user/pos':
            if ($method === 'GET') (new HotelController())->posDashboard();
            else http_response_code(405);
            break;

        case '/user/pos/add':
            if ($method === 'POST') (new HotelController())->addAncillarySale($_POST);
            else http_response_code(405);
            break;
            
        case '/user/rooms':
            if ($method === 'POST') (new RoomController())->storeRoomType($_POST);
            else (new RoomController())->index();
            break;
            
        case '/user/rooms/delete':
            if ($method === 'POST') (new RoomController())->deleteRoomType($_POST);
            break;
            
        case '/user/rooms/update':
            if ($method === 'POST') (new RoomController())->updateRoomType($_POST);
            break;
            
        case '/user/rooms/physical':
            if ($method === 'POST') (new RoomController())->storePhysicalRoom($_POST);
            break;
            
        case '/user/rooms/physical/delete':
            if ($method === 'POST') (new RoomController())->deletePhysicalRoom($_POST);
            break;
            
        case '/user/rooms/physical/update':
            if ($method === 'POST') (new RoomController())->updatePhysicalRoom($_POST);
            break;

        // --- CHANNEL MANAGER ---
        case '/user/channel-manager':
            if ($method === 'POST') (new HotelController())->storeMapping($_POST);
            else (new HotelController())->channelManager();
            break;
            
        case '/user/channel-manager/delete':
            if ($method === 'POST') (new HotelController())->deleteMapping($_POST);
            break;
            
        // --- HOUSEKEEPING & POS ---
        case '/user/housekeeping':
            (new HotelController())->housekeeping();
            break;

        case '/user/housekeeping/assign':
            if ($method === 'POST') (new HotelController())->assignHousekeeper($_POST);
            else http_response_code(405);
            break;
            
        case '/user/housekeeping/ticket':
            if ($method === 'POST') (new HotelController())->createMaintenanceTicket($_POST);
            else http_response_code(405);
            break;
            
        case '/user/housekeeping/ticket/resolve':
            if ($method === 'POST') (new HotelController())->resolveMaintenanceTicket($_POST);
            else http_response_code(405);
            break;

        case '/user/invoice':
            (new BillingController())->invoice($_GET['id'] ?? '');
            break;
            
        case '/user/invoice/charge':
            if ($method === 'POST') (new BillingController())->storePosCharge($_POST);
            break;

        case '/user/payment/store':
            if ($method === 'POST') (new BillingController())->storePayment($_POST);
            break;
            
        case '/user/support':
            if ($method === 'GET') (new \Syncro\Controllers\SupportController())->index();
            elseif ($method === 'POST') (new \Syncro\Controllers\SupportController())->create($_POST);
            else http_response_code(405);
            break;
            
        case '/user/support/view':
            if ($method === 'GET') (new \Syncro\Controllers\SupportController())->view($_GET);
            elseif ($method === 'POST') (new \Syncro\Controllers\SupportController())->reply($_POST);
            else http_response_code(405);
            break;
            
        // --- PROMO CODES ---
        case '/user/promo-codes':
            if ($method === 'GET') (new HotelController())->promoCodes();
            else http_response_code(405);
            break;
        
        case '/user/promo-codes/create':
            if ($method === 'POST') (new HotelController())->storePromoCode($_POST);
            else http_response_code(405);
            break;
        
        // Handle the delete route with dynamic ID (e.g. /user/promo-codes/delete/5)
        case (preg_match('#^/user/promo-codes/delete/(\d+)$#', $requestUri, $matches) ? true : false):
            if ($method === 'POST') {
                $_POST['id'] = $matches[1]; // Inject the ID from the URL into POST data
                (new HotelController())->deletePromoCode($_POST);
            } else {
                http_response_code(405);
            }
            break;
            
        case '/user/stop-impersonating':
            if ($method === 'POST') (new HotelController())->stopImpersonating();
            else http_response_code(405);
            break;
            
        // --- WEBHOOKS & API ---
        case '/api/inventory':
            if ($method === 'GET') (new \Syncro\Controllers\Api\ChannelManagerController())->getInventory();
            else http_response_code(405);
            break;
            
        case '/api/reservations':
            if ($method === 'POST') (new \Syncro\Controllers\Api\ChannelManagerController())->postReservation();
            else http_response_code(405);
            break;

        case '/api/webhook/phonepe':
            if ($method === 'POST') (new CheckoutController())->webhook();
            else http_response_code(405);
            break;
            
        // --- INTERNAL AJAX APIs ---
        case '/ajax/inventory/update':
            if ($method === 'POST') (new AjaxInventoryController())->update();
            else http_response_code(405);
            break;
            
        case '/ajax/inventory/sync':
            if ($method === 'POST') (new AjaxInventoryController())->syncToChannels();
            else http_response_code(405);
            break;
            
        case '/ajax/housekeeping/update':
            if ($method === 'POST') (new AjaxInventoryController())->updateHousekeeping();
            else http_response_code(405);
            break;
            
        case '/ajax/promo/validate':
            if ($method === 'POST') (new PublicController())->validatePromo($_POST);
            else http_response_code(405);
            break;
            
        // --- 404 FALLBACK ---
        default:
            http_response_code(404);
            echo '<div style="font-family: sans-serif; text-align: center; padding: 50px; color: #002244;"><h1>404</h1><p>Resource Not Found</p></div>';
            break;
    }
} catch (\Throwable $e) {
    // Pass the caught exception to our centralized, secure Error Handler
    \Syncro\Security\ExceptionHandler::handleException($e);
}