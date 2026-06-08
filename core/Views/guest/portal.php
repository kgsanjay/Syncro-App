<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \Syncro\Security\SecurityManager::sanitizeOutput($pageTitle ?? 'Guest Portal') ?></title>
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
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .print-border { border: 1px solid #000; padding: 20px; }
        }
    </style>
</head>
<body class="bg-[var(--light)] text-[var(--text)] font-sans antialiased min-h-screen">

<div class="max-w-3xl mx-auto py-10 px-4">
    
    <div class="bg-[var(--theme)] text-[var(--white)] rounded-t-lg p-6 flex items-center justify-between no-print">
        <h1 class="text-2xl font-bold"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['hotel_name']) ?> - Guest Portal</h1>
    </div>

    <div class="bg-[var(--white)] border border-[var(--border)] rounded-b-lg shadow-sm p-8 print-border">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded no-print">
                <p class="text-sm font-bold text-green-800">Check-in details updated successfully!</p>
            </div>
        <?php endif; ?>

        <!-- Invoice Header (Visible on Print) -->
        <div class="hidden print:block mb-8 text-center border-b border-[var(--border)] pb-4">
            <h1 class="text-3xl font-black text-[var(--header)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['hotel_name']) ?></h1>
            <h2 class="text-xl font-bold mt-2">Guest Invoice</h2>
            <p class="mt-1 text-sm text-[var(--text)]">Booking Ref: #<?= $booking['id'] ?></p>
        </div>

        <!-- Booking Details -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Guest Details</h3>
                <p class="font-medium text-[var(--header)] text-lg"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['guest_name']) ?></p>
                <p class="text-sm"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['guest_email'] ?? 'N/A') ?></p>
                <p class="text-sm"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['guest_phone'] ?? 'N/A') ?></p>
            </div>
            <div class="text-right">
                <h3 class="text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Stay Details</h3>
                <p class="font-medium text-[var(--header)] text-lg"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['room_name']) ?></p>
                <p class="text-sm">Check-in: <?= date('M j, Y', strtotime($booking['check_in'])) ?></p>
                <p class="text-sm">Check-out: <?= date('M j, Y', strtotime($booking['check_out'])) ?></p>
            </div>
        </div>

        <!-- Self Check-in Form (No Print) -->
        <div class="mb-10 no-print border-t border-[var(--border)] pt-8">
            <h2 class="text-xl font-bold text-[var(--header)] mb-4">Pre-Arrival Check-in</h2>
            <form action="/guest/portal/update" method="POST" enctype="multipart/form-data" class="space-y-4 bg-gray-50 p-6 rounded border border-gray-200">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-[var(--header)] mb-1">Expected Arrival Time</label>
                        <input type="time" name="arrival_time" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['arrival_time'] ?? '') ?>" class="w-full px-3 py-2 border border-[var(--border)] rounded bg-white outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-[var(--header)] mb-1">Upload ID Document (PDF/Image)</label>
                        <?php if ($booking['guest_id_document_url']): ?>
                            <p class="text-xs text-[var(--success)] font-bold mb-1">✓ ID Uploaded</p>
                        <?php endif; ?>
                        <input type="file" name="id_document" class="w-full text-sm">
                    </div>
                </div>
                
                <button type="submit" class="bg-[var(--theme2)] text-white font-bold py-2 px-6 rounded shadow hover:bg-[var(--theme)] transition-colors">
                    Save Check-in Details
                </button>
            </form>
        </div>

        <!-- Invoice / Folio -->
        <div class="border-t border-[var(--border)] pt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-[var(--header)]">Guest Folio & Invoice</h2>
                <button onclick="window.print()" class="no-print bg-[var(--theme)] text-white font-bold py-1.5 px-4 rounded text-sm hover:opacity-90">
                    Download PDF / Print
                </button>
            </div>
            
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-[var(--header)]">
                        <th class="py-2 text-sm font-bold text-[var(--header)]">Description</th>
                        <th class="py-2 text-sm font-bold text-[var(--header)] text-center">Qty</th>
                        <th class="py-2 text-sm font-bold text-[var(--header)] text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    <!-- Room Charge -->
                    <tr>
                        <td class="py-3">
                            <div class="font-bold text-[var(--header)]">Room Charges</div>
                            <div class="text-xs text-[var(--text)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['room_name']) ?></div>
                        </td>
                        <td class="py-3 text-center">1</td>
                        <td class="py-3 text-right font-bold text-[var(--header)]">₹<?= number_format((float)$booking['total_price'], 2) ?></td>
                    </tr>
                    
                    <!-- Ancillary Sales -->
                    <?php 
                    $ancillaryTotal = 0;
                    foreach($ancillarySales as $sale): 
                        $ancillaryTotal += $sale['total_amount'];
                    ?>
                        <tr>
                            <td class="py-3">
                                <div class="font-bold text-[var(--header)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($sale['item_name']) ?></div>
                                <div class="text-xs text-[var(--text)]"><?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?></div>
                            </td>
                            <td class="py-3 text-center"><?= $sale['quantity'] ?></td>
                            <td class="py-3 text-right font-medium text-[var(--header)]">₹<?= number_format((float)$sale['total_amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-[var(--header)]">
                        <td colspan="2" class="py-4 text-right font-bold text-[var(--header)] uppercase tracking-wider">Total Amount Due</td>
                        <td class="py-4 text-right font-black text-[var(--theme2)] text-xl">₹<?= number_format((float)($booking['total_price'] + $ancillaryTotal), 2) ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="mt-8 flex justify-end no-print">
                <?php if (($booking['payment_status'] ?? 'pending') === 'paid'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-3 rounded text-center font-bold">
                        ✓ Payment Completed
                    </div>
                <?php else: ?>
                    <a href="/payment/checkout?booking_id=<?= $booking['id'] ?>&token=<?= htmlspecialchars($_GET['token'] ?? '') ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-8 rounded shadow-lg transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Pay Securely with PhonePe
                    </a>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
