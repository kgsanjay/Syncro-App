<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error | Syncro PMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --theme2: #003366;
            --light: #f8f9fa;
            --text: #555555;
        }
    </style>
</head>
<body class="bg-[var(--light)] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden text-center p-8">
        <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-900 mb-2">System Error</h1>
        <p class="text-[var(--text)] text-sm mb-8 leading-relaxed">
            Our system encountered an unexpected issue while processing your request. The engineering team has been notified and is looking into it.
        </p>

        <a href="javascript:history.back()" class="inline-flex justify-center w-full px-4 py-3 text-sm font-bold text-white bg-[var(--theme2)] border border-transparent rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all shadow-sm">
            Go Back
        </a>
    </div>
</body>
</html>