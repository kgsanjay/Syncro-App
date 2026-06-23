<?php
declare(strict_types=1);
use Syncro\Security\SecurityManager;
use Syncro\Security\SessionManager;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Fetch any flash messages
$successMsg = SessionManager::getFlash('success');
$errorMsg   = SessionManager::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?= csrf_meta() ?>">
    
    <title><?= SecurityManager::sanitizeOutput($pageTitle ?? 'Syncro Admin') ?></title>
    <script src="<?= base_url('/assets/js/tailwindcss.js?v=1.0') ?>"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('/assets/css/app.css?v=1.0') ?>">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        theme: 'var(--theme2)', theme2: 'var(--theme2)', header: 'var(--header)',
                        text: 'var(--text)', light: 'var(--light)', border: 'var(--border)', white: 'var(--white)',
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
<body class="flex h-screen overflow-hidden bg-[var(--light)] selection:bg-[var(--theme)] selection:text-white font-sans">
    
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden md:hidden transition-opacity" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 glass-dark text-[var(--white)] flex flex-col shadow-2xl z-30 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out flex-shrink-0">
        <div class="h-16 flex items-center justify-between px-6 border-b border-[var(--white)]/10">
            <span class="text-2xl font-display font-bold text-[var(--theme)] tracking-tight drop-shadow-md">Syncro</span>
            <button onclick="toggleSidebar()" class="md:hidden text-[var(--white)]/70 hover:text-[var(--white)] focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="<?= base_url('/admin/dashboard') ?>" class="flex items-center px-4 py-3 rounded-lg font-medium border-l-4 transition-all duration-200 <?= $uri === '/admin/dashboard' ? 'bg-[var(--white)]/10 text-[var(--white)] border-[var(--theme)]' : 'text-[var(--white)]/70 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/30' ?>">
                Dashboard
            </a>
            <a href="<?= base_url('/admin/hotels') ?>" class="flex items-center px-4 py-3 rounded-lg font-medium border-l-4 transition-all duration-200 <?= str_starts_with($uri, '/admin/hotels') ? 'bg-[var(--white)]/10 text-[var(--white)] border-[var(--theme)]' : 'text-[var(--white)]/70 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/30' ?>">
                Client Hotels
            </a>
            <a href="<?= base_url('/admin/support') ?>" class="flex items-center px-4 py-3 rounded-lg font-medium border-l-4 transition-all duration-200 <?= str_starts_with($uri, '/admin/support') ? 'bg-[var(--white)]/10 text-[var(--white)] border-[var(--theme)]' : 'text-[var(--white)]/70 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/30' ?>">
                Support Inbox
            </a>
            <a href="<?= base_url('/admin/settings') ?>" class="flex items-center px-4 py-3 rounded-lg font-medium border-l-4 transition-all duration-200 <?= str_starts_with($uri, '/admin/settings') ? 'bg-[var(--white)]/10 text-[var(--white)] border-[var(--theme)]' : 'text-[var(--white)]/70 hover:bg-[var(--white)]/5 hover:text-[var(--white)] border-transparent hover:border-[var(--white)]/30' ?>">
                Platform Settings
            </a>
        </nav>
        
        <div class="p-4 border-t border-[var(--white)]/10">
            <form action="<?= base_url('/logout') ?>" method="POST" class="m-0 p-0">
                <?= csrf_field() ?>">
                <button type="submit" class="w-full flex items-center px-4 py-3 text-[var(--theme)] hover:text-[var(--white)] hover:bg-[var(--white)]/5 rounded-lg font-medium transition-colors text-left">
                    Secure Logout
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-16 glass border-b border-[var(--border)] shadow-sm flex items-center justify-between px-4 sm:px-8 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="mr-4 md:hidden text-[var(--theme)] hover:opacity-80 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-lg sm:text-xl font-display font-bold text-[var(--header)] truncate max-w-[180px] sm:max-w-none"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-3">
                <span class="hidden sm:inline text-xs font-bold text-[var(--text)] uppercase tracking-wider">Role:</span>
                <span class="text-xs sm:text-sm font-semibold text-[var(--theme2)] bg-[var(--light)] py-1 px-2 sm:px-3 rounded-full border border-[var(--border)]">Super Admin</span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[var(--light)] p-4 sm:p-8 relative">
            
            <?php if ($successMsg): ?>
                <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold flex justify-between items-center" id="flash-success">
                    <span><?= htmlspecialchars($successMsg) ?></span>
                    <button onclick="document.getElementById('flash-success').style.display='none'" class="text-[var(--text)] hover:text-[var(--theme2)] focus:outline-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme2)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold flex justify-between items-center" id="flash-error">
                    <span>⚠️ <?= htmlspecialchars($errorMsg) ?></span>
                    <button onclick="document.getElementById('flash-error').style.display='none'" class="text-[var(--text)] hover:text-[var(--theme2)] focus:outline-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            <?php endif; ?>

            <?php require $viewPath; ?>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('mobileOverlay').classList.toggle('hidden');
        }
    </script>
</body>
</html>