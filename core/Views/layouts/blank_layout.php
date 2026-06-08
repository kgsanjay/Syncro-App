<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    
    <title><?= \Syncro\Security\SecurityManager::sanitizeOutput($pageTitle ?? 'Syncro') ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css?v=1.0">
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
        :root { --theme: #FFC107; --theme2: #003366; --header: #002244; --text: #555555; --light: #f8f9fa; --border: #eef1f6; --white: #ffffff; }
        html { scroll-behavior: smooth; }
        body { background-color: var(--light); color: var(--text); -webkit-font-smoothing: antialiased; }
    </style>
</head>
<body class="bg-[var(--light)] antialiased selection:bg-[var(--theme)] selection:text-[var(--header)]">
    
    <?php require $viewPath; ?>

</body>
</html>