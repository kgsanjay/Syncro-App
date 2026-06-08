<?php
declare(strict_types=1);
use Syncro\Security\SecurityManager;
use Syncro\Security\SessionManager;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$role = $_SESSION['role'] ?? 'guest';

// Fetch any flash messages
$successMsg = SessionManager::getFlash('success');
$errorMsg   = SessionManager::getFlash('error');

// Ensure CSRF token is available
$csrfToken = $_SESSION['csrf_token'] ?? '';

// Check if account is expired
$isAccountExpired = !empty($_SESSION['account_expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#003366">
    <link rel="apple-touch-icon" href="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23003366'/><text x='50' y='50' fill='white' font-size='40' text-anchor='middle' alignment-baseline='middle'>S</text></svg>">
    
    <title><?= SecurityManager::sanitizeOutput($pageTitle ?? 'Property Dashboard | Syncro') ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css?v=1.0">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        theme: '#4f46e5', theme2: '#312e81', header: '#0f172a',
                        text: '#475569', light: '#f8fafc', border: '#e2e8f0', white: '#ffffff',
                    },
                    fontFamily: { 
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Outfit', 'Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .glass { @apply bg-white/80 backdrop-blur-md border border-white/20; }
            .glass-dark { @apply bg-[var(--theme2)]/95 backdrop-blur-lg border border-white/10; }
            .hover-lift { @apply transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg; }
            .animate-fade-in-up { animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        }
    </style>
    <style>
        body { background-color: var(--light); color: var(--text); -webkit-font-smoothing: antialiased; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: var(--text); border-radius: 4px; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex flex-col h-screen overflow-hidden bg-[var(--light)] selection:bg-[var(--theme)] selection:text-white font-sans">
    
    <?php if (!empty($_SESSION['impersonator_id'])): ?>
        <div class="bg-[var(--theme)] text-[var(--header)] px-6 py-2 flex flex-col sm:flex-row justify-between items-center text-xs font-bold uppercase tracking-widest z-50 relative shadow-md shrink-0">
            <div class="flex items-center mb-2 sm:mb-0">
                <svg class="w-4 h-4 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                WARNING: You are currently impersonating <?= htmlspecialchars($_SESSION['property_name'] ?? 'a client') ?>.
            </div>
            <form action="/user/stop-impersonating" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="bg-[var(--header)] text-[var(--white)] px-4 py-1.5 rounded shadow hover:bg-[var(--theme2)] transition-colors w-full sm:w-auto">
                    Return to Admin Panel
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div class="flex flex-1 overflow-hidden">
        <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden transition-opacity" onclick="toggleSidebar()"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 glass-dark text-[var(--white)] flex flex-col shadow-2xl z-30 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out flex-shrink-0">
            <div class="h-16 flex items-center justify-between px-6 border-b border-[var(--white)]/10 shrink-0">
                <span class="text-2xl font-display font-bold text-[var(--theme)] tracking-tight drop-shadow-md">Syncro</span>
                <button onclick="toggleSidebar()" class="md:hidden text-[var(--white)]/70 hover:text-[var(--white)] focus:outline-none" aria-label="Close sidebar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                
                <?php if ($role === 'hotel_admin'): ?>
                <a href="/user/dashboard" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/dashboard' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard Overview
                </a>
                <?php endif; ?>

                <?php if (in_array($role, ['hotel_admin', 'receptionist'])): ?>
                <a href="/user/bookings" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/bookings' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Reservations
                </a>
                <a href="/user/calendar" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/calendar' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Visual Calendar
                </a>
                <a href="/user/guests" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= str_starts_with($uri, '/user/guests') || str_starts_with($uri, '/user/guest-profile') ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Guest Directory
                </a>
                <?php endif; ?>

                <div class="pt-4 pb-2 px-4 text-[10px] font-black text-[var(--white)]/30 uppercase tracking-[0.2em]">Management</div>

                <a href="/user/housekeeping" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/housekeeping' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Housekeeping
                </a>
                
                <?php if ($role === 'hotel_admin'): ?>
                <a href="/user/inventory" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/inventory' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Tape Chart
                </a>
                <a href="/user/rates" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= strpos($uri, '/user/rates') === 0 ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Rate Manager
                </a>
                <a href="/user/channel-manager" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= str_starts_with($uri, '/user/channel-manager') ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    OTA Mappings
                </a>
                <a href="/user/reports" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= str_starts_with($uri, '/user/reports') ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    P&L Reports
                </a>
                <a href="/user/expenses" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= str_starts_with($uri, '/user/expenses') ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Expenses
                </a>
                
                <div class="pt-4 pb-2 px-4 text-[10px] font-black text-[var(--white)]/30 uppercase tracking-[0.2em]">Config</div>

                <a href="/user/audit-logs" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/audit-logs' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Audit Trail Log
                </a>
                <a href="/user/staff" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/staff' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Staff Members
                </a>
                <a href="/user/settings" class="flex items-center px-4 py-3 rounded-lg font-bold border-l-4 transition-all duration-300 <?= $uri === '/user/settings' ? 'bg-[var(--theme2)] text-[var(--white)] border-[var(--theme)] shadow-lg translate-x-1' : 'text-[var(--white)]/60 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/20' ?>">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings & Security
                </a>
                <?php endif; ?>
            </nav>
            
            <div class="p-4 border-t border-[var(--white)]/10 shrink-0">
                <form action="/logout" method="POST" class="m-0 p-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" class="w-full flex items-center px-4 py-3 text-[var(--theme)] hover:text-[var(--white)] hover:bg-[var(--white)]/5 rounded-lg font-medium transition-colors text-left">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Secure Logout
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="glass border-b border-[var(--border)] h-16 flex items-center justify-between px-6 shadow-sm z-10 shrink-0">
                <div class="flex items-center">
                    <button onclick="toggleSidebar()" class="mr-4 md:hidden text-[var(--text)] hover:text-[var(--theme)] focus:outline-none transition-colors" aria-label="Open sidebar">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    
                    <?php if (isset($_SESSION['user_properties']) && count($_SESSION['user_properties']) > 1): ?>
                        <form action="/user/switch-property" method="POST" class="m-0 flex items-center">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <div class="relative group">
                                <select name="target_hotel_id" onchange="this.form.submit()" class="appearance-none font-display font-extrabold text-[var(--header)] bg-transparent border-0 py-2 pr-8 focus:outline-none focus:ring-0 cursor-pointer text-xl transition-all group-hover:text-[var(--theme)]">
                                    <?php foreach ($_SESSION['user_properties'] as $prop): ?>
                                        <option value="<?= $prop['id'] ?>" <?= $prop['id'] == $_SESSION['active_hotel_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prop['property_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-[var(--theme)] opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <h2 class="text-xl font-display font-extrabold text-[var(--header)] tracking-tight">
                            <?= htmlspecialchars($_SESSION['property_name'] ?? 'PMS Dashboard') ?>
                        </h2>
                    <?php endif; ?>
                </div>

                <div class="flex items-center space-x-5">
                    <!-- Notification Bell -->
                    <div class="relative cursor-pointer hover-lift" id="notificationBell" onclick="toggleNotificationPanel()">
                        <div class="p-2 bg-white rounded-full shadow-sm border border-gray-100 text-[var(--text)] hover:text-[var(--theme)] transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <span id="notificationBadge" class="hidden absolute top-0 right-0 -mt-1 -mr-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full ring-2 ring-white">0</span>
                        
                        <!-- Notification Dropdown Panel -->
                        <div id="notificationPanel" class="hidden absolute right-0 mt-3 w-80 glass border border-gray-200 shadow-2xl rounded-xl overflow-hidden z-50 flex flex-col max-h-96 animate-fade-in-up">
                            <div class="bg-white/90 border-b border-gray-200 px-4 py-3 font-bold text-gray-800 text-sm flex justify-between items-center backdrop-blur-md">
                                Notifications
                                <button type="button" class="text-xs text-[var(--theme)] font-medium hover:underline" onclick="clearAllNotifications(event)">Dismiss All</button>
                            </div>
                            <div id="notificationList" class="overflow-y-auto flex-1 p-0 bg-white/50 backdrop-blur-sm">
                                <div class="px-4 py-8 text-center text-sm text-gray-500 italic">You're all caught up!</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center text-sm font-medium text-[var(--text)]">
                        <span class="mr-2 hidden sm:inline-block">Hello, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
                        <div class="w-8 h-8 rounded-full bg-[var(--theme2)] text-[var(--white)] flex items-center justify-center font-bold shadow-sm cursor-help" title="Role: <?= htmlspecialchars(ucfirst($role)) ?>">
                            <?= substr($_SESSION['name'] ?? 'U', 0, 1) ?>
                        </div>
                    </div>
                </div>
            </header>

            <?php if ($isAccountExpired): ?>
                <div class="bg-[var(--theme)] text-[var(--header)] px-4 py-3 text-center text-[13px] font-bold flex flex-col md:flex-row justify-center items-center shadow-md tracking-wide z-20 shrink-0 gap-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <span>ACCOUNT EXPIRED: Full system access is restricted. Please renew your plan to restore access.</span>
                    </div>
                    <div class="flex items-center">
                        <a href="/user/settings" class="bg-[var(--header)] text-[var(--theme)] hover:bg-[var(--theme2)] hover:text-[var(--white)] px-4 py-1.5 rounded shadow text-xs uppercase tracking-widest transition-colors">
                            Renew Plan
                        </a>
                    </div>
                </div>
            <?php elseif (isset($_SESSION['account_expiring_soon'])): ?>
                <div class="bg-[var(--theme2)] text-[var(--white)] px-4 py-3.5 text-center text-[13px] font-bold flex justify-center items-center shadow-md border-b border-[var(--border)] tracking-wide z-20 shrink-0">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    ACTION REQUIRED: Your Syncro license will expire in <?= (int)$_SESSION['account_expiring_soon'] ?> day(s). Please renew your plan soon to prevent service interruption.
                </div>
            <?php endif; ?>
            <?php 
                try {
                    $announcements = \Syncro\Models\Database::table('announcements')->where('is_active', 1)->orderBy('created_at', 'DESC')->get();
                    if (!empty($announcements) && !$isAccountExpired): 
            ?>
                <div class="w-full shrink-0 z-20 flex flex-col relative shadow-md border-b border-[var(--border)]">
                    <?php foreach($announcements as $ann): ?>
                        <?php 
                            // Strictly mapped to mandated color variables
                            $bgClass = 'bg-[var(--theme2)] text-[var(--white)]'; // Default Info
                            $icon = '📢';
                            if($ann['type'] === 'warning') { $bgClass = 'bg-[var(--theme)] text-[var(--header)]'; $icon = '⚠️'; }
                            if($ann['type'] === 'success') { $bgClass = 'bg-[var(--header)] text-[var(--theme)]'; $icon = '✅'; }
                        ?>
                        <div class="<?= $bgClass ?> px-4 py-2.5 text-center text-[13px] font-bold flex justify-center items-center shadow-inner tracking-wide">
                            <span class="mr-2 animate-bounce"><?= $icon ?></span> 
                            <?= htmlspecialchars($ann['message']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php 
                    endif;
                } catch (\Exception $e) {
                    // Fail silently if table doesn't exist yet or db error, to not break the layout
                }
            ?>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[var(--light)] p-4 sm:p-8 relative">
                
                <?php if ($successMsg): ?>
                    <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold flex justify-between items-center animate-fade-in-down" id="flash-success">
                        <span><?= htmlspecialchars($successMsg) ?></span>
                        <button onclick="document.getElementById('flash-success').style.display='none'" class="text-[var(--text)] hover:text-[var(--theme2)] focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme2)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold flex justify-between items-center animate-fade-in-down" id="flash-error">
                        <span>⚠️ <?= htmlspecialchars($errorMsg) ?></span>
                        <button onclick="document.getElementById('flash-error').style.display='none'" class="text-[var(--text)] hover:text-[var(--theme2)] focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                <?php endif; ?>

                <?php require $viewPath; ?>
            </main>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        
        // Expose a helper function to get the CSRF token for AJAX requests
        window.getCsrfToken = function() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        };

        // --- SSE Notifications Logic ---
        let notificationCount = 0;
        let lastEventId = 0;

        function toggleNotificationPanel() {
            const panel = document.getElementById('notificationPanel');
            panel.classList.toggle('hidden');
        }

        function clearAllNotifications(e) {
            e.stopPropagation();
            const list = document.getElementById('notificationList');
            list.innerHTML = '<div class="px-4 py-6 text-center text-sm text-gray-500 italic">No new notifications</div>';
            notificationCount = 0;
            updateBadge();
            // In a real app we would mark all as read on server here
        }

        function updateBadge() {
            const badge = document.getElementById('notificationBadge');
            if (notificationCount > 0) {
                badge.innerText = notificationCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function showToast(title, message) {
            // Create a toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-white border border-gray-200 shadow-xl rounded-lg p-4 w-80 z-50 animate-fade-in-down flex flex-col gap-1';
            toast.innerHTML = `
                <div class="font-bold text-gray-800 text-sm flex justify-between">
                    ${title}
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
                <div class="text-sm text-gray-600">${message}</div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    toast.remove();
                }
            }, 5000);
        }

        function initSSE() {
            const eventSource = new EventSource(`/notifications/stream?last_id=${lastEventId}`);
            
            eventSource.addEventListener('notification', (e) => {
                const data = JSON.parse(e.data);
                lastEventId = Math.max(lastEventId, parseInt(data.id));
                
                // Add to list
                const list = document.getElementById('notificationList');
                if (notificationCount === 0) {
                    list.innerHTML = '';
                }
                
                const item = document.createElement('div');
                item.className = 'px-4 py-3 border-b border-gray-100 hover:bg-indigo-50 cursor-pointer transition-colors';
                item.innerHTML = `
                    <div class="font-bold text-sm text-gray-800 mb-0.5">${data.title}</div>
                    <div class="text-xs text-gray-600 line-clamp-2">${data.message}</div>
                    <div class="text-[10px] text-gray-400 mt-1">${new Date(data.time).toLocaleTimeString()}</div>
                `;
                item.onclick = function() {
                    // Mark as read API call
                    fetch('/notifications/read', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: data.id })
                    });
                    this.remove();
                    notificationCount--;
                    updateBadge();
                    if (notificationCount === 0) {
                        list.innerHTML = '<div class="px-4 py-6 text-center text-sm text-gray-500 italic">No new notifications</div>';
                    }
                };
                
                list.prepend(item);
                notificationCount++;
                updateBadge();
                showToast(data.title, data.message);
            });

            eventSource.addEventListener('close', (e) => {
                eventSource.close();
                // Reconnect after 3 seconds
                setTimeout(initSSE, 3000);
            });

            eventSource.onerror = (err) => {
                eventSource.close();
                // Reconnect after 5 seconds on error
                setTimeout(initSSE, 5000);
            };
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            initSSE();
            
            // Register Service Worker for PWA
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/service-worker.js')
                    .then(reg => console.log('Service Worker registered with scope:', reg.scope))
                    .catch(err => console.error('Service Worker registration failed:', err));
            }
        });
    </script>
</body>
</html>