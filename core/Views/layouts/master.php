<?php
declare(strict_types=1);
use Syncro\Security\SecurityManager;
use Syncro\Security\SessionManager;

// Fetch any flash messages
$successMsg = SessionManager::getFlash('success');
$errorMsg   = SessionManager::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Syncro - Advanced Property Management System and Channel Manager. Manage reservations, housekeeping, and multiple properties securely.">
    <meta name="keywords" content="PMS, Property Management System, Hotel Software, Channel Manager, Syncro, Hospitality">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Social -->
    <meta property="og:title" content="Syncro | Modern Property Management">
    <meta property="og:description" content="Streamline your hotel operations with Syncro's multi-tenant Property Management System.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://yourdomain.com">
    <meta property="og:image" content="https://yourdomain.com/assets/images/og-image.jpg">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Syncro | Modern Property Management">
    <meta name="twitter:description" content="Streamline your hotel operations with Syncro's multi-tenant PMS.">
    <?= csrf_meta() ?>">
    <title><?= SecurityManager::sanitizeOutput($pageTitle ?? 'Syncro | Adhyan Channel Manager') ?></title>
    
    <script src="<?= BASE_PATH ?>/assets/js/tailwindcss.js?v=1.0"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css?v=1.0">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        theme: 'var(--theme2)',
                        theme2: 'var(--theme2)',
                        header: 'var(--header)',
                        text: 'var(--text)',
                        light: 'var(--light)',
                        border: 'var(--border)',
                        white: 'var(--white)',
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
        body {
            background-color: var(--light);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            color: var(--header);
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-screen flex flex-col selection:bg-[var(--theme)] selection:text-white font-sans">
    <main class="flex-grow flex flex-col justify-center p-4">
        
        <div class="w-full max-w-md mx-auto">
            <?php if ($successMsg): ?>
                <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold">
                    <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="p-4 mb-6 rounded border-l-4 border-[var(--theme2)] bg-[var(--white)] text-[var(--header)] shadow-sm font-semibold">
                    ⚠️ <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>
        </div>

        <?php require $viewPath; ?>
    </main>
</body>
</html>