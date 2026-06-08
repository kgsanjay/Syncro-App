<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Guest CRM') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --theme: #1a365d;
            --theme2: #2563eb;
            --light: #f8fafc;
            --white: #ffffff;
            --text: #475569;
            --header: #0f172a;
            --border: #e2e8f0;
            --success: #10b981;
        }
    </style>
</head>
<body class="bg-[var(--light)] text-[var(--text)] font-sans antialiased min-h-screen">

<div class="max-w-[1400px] mx-auto py-10 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Guest CRM</h1>
            <p class="text-sm text-[var(--text)] mt-1">Manage guest relationships, lifetime value, and marketing campaigns.</p>
        </div>
        <a href="/user/dashboard" class="bg-gray-200 text-[var(--header)] font-bold py-2 px-4 rounded shadow hover:bg-gray-300">
            Back to Dashboard
        </a>
    </div>

    <!-- Marketing Callout -->
    <div class="bg-white rounded shadow-sm border border-[var(--border)] p-6 mb-8 flex justify-between items-center bg-gradient-to-r from-[var(--theme)] to-[var(--theme2)] text-white">
        <div>
            <h2 class="text-xl font-bold mb-1">Email Campaign Drafts</h2>
            <p class="text-sm opacity-80">Select VIP guests below and send targeted promotional offers.</p>
        </div>
        <button onclick="alert('In a live environment, this would open the Drag-and-Drop Email Builder connected to SendGrid.')" class="bg-white text-[var(--theme)] font-bold py-2 px-6 rounded shadow hover:bg-gray-100">
            Draft New Campaign
        </button>
    </div>

    <div class="bg-white rounded shadow-sm border border-[var(--border)] overflow-hidden">
        <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)]">
            <h2 class="font-bold text-[var(--header)]">Guest Directory & LTV (Lifetime Value)</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-[var(--border)]">
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">Guest Name</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">Contact Info</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider text-center">Total Stays</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider text-right">Lifetime Value (LTV)</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider text-right">Last Visit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    <?php foreach ($guests as $index => $guest): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-bold text-[var(--header)] flex items-center">
                                <?= htmlspecialchars($guest['full_name']) ?>
                                <?php if($index < 10 && $guest['total_revenue'] > 0): ?>
                                    <span class="ml-2 bg-yellow-100 text-yellow-800 text-[10px] font-black uppercase px-2 py-0.5 rounded">VIP</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="text-sm text-[var(--header)]"><?= htmlspecialchars($guest['email']) ?></div>
                            <div class="text-xs text-[var(--text)]"><?= htmlspecialchars($guest['phone']) ?></div>
                        </td>
                        <td class="py-4 px-6 text-center font-bold">
                            <?= $guest['total_stays'] ?>
                        </td>
                        <td class="py-4 px-6 text-right font-bold text-[var(--theme2)]">
                            ₹<?= number_format((float)$guest['total_revenue'], 2) ?>
                        </td>
                        <td class="py-4 px-6 text-right text-sm">
                            <?= $guest['last_visit_date'] ? date('M j, Y', strtotime($guest['last_visit_date'])) : 'Never' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
