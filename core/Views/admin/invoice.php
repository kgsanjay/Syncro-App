<?php
$pageTitle = $pageTitle ?? 'Invoice';
$booking = $booking ?? [];
$payments = $payments ?? [];

$statusBadgeColor = 'bg-gray-100 text-gray-800';
if ($booking['payment_status'] === 'paid') $statusBadgeColor = 'bg-green-100 text-green-800';
if ($booking['payment_status'] === 'pending') $statusBadgeColor = 'bg-yellow-100 text-yellow-800';

$totalPaid = array_reduce($payments, fn($carry, $item) => $carry + $item['amount'], 0);
$balanceDue = max(0, $booking['total_price'] - $totalPaid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Folio #<?= e((string)$booking['id']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans p-8">

<div class="max-w-4xl mx-auto bg-white p-10 border border-gray-200 shadow-sm relative">
    
    <!-- Action Bar (Hidden when printing) -->
    <div class="no-print absolute top-4 right-4 flex space-x-4">
        <a href="<?= base_url('/user/dashboard') ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition text-sm font-semibold">← Back</a>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-semibold">🖨 Print / Save PDF</button>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-start border-b-2 border-gray-100 pb-8 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 tracking-tight"><?= e($booking['hotel_name']) ?></h1>
            <p class="text-gray-500 mt-1"><?= nl2br(e($booking['hotel_address'])) ?></p>
            <p class="text-gray-500"><?= e($booking['hotel_phone']) ?> | <?= e($booking['hotel_email']) ?></p>
        </div>
        <div class="text-right mt-12">
            <h2 class="text-4xl font-black text-gray-200 uppercase tracking-widest">INVOICE</h2>
            <p class="text-gray-600 mt-2 font-mono">Folio #<?= str_pad((string)$booking['id'], 5, '0', STR_PAD_LEFT) ?></p>
            <p class="text-gray-600 font-mono">Date: <?= date('d M Y') ?></p>
        </div>
    </div>

    <!-- Guest Details -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Billed To</h3>
            <p class="text-lg font-semibold text-gray-800"><?= e($booking['guest_name']) ?></p>
            <p class="text-gray-600"><?= e($booking['guest_email']) ?></p>
            <p class="text-gray-600"><?= e($booking['guest_phone']) ?></p>
        </div>
        <div>
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Stay Details</h3>
            <table class="w-full text-sm">
                <tr><td class="py-1 text-gray-600">Check-in:</td><td class="font-semibold text-right"><?= date('d M Y', strtotime($booking['check_in_date'])) ?></td></tr>
                <tr><td class="py-1 text-gray-600">Check-out:</td><td class="font-semibold text-right"><?= date('d M Y', strtotime($booking['check_out_date'])) ?></td></tr>
                <tr><td class="py-1 text-gray-600">Room Type:</td><td class="font-semibold text-right"><?= e($booking['room_type_name']) ?></td></tr>
                <tr><td class="py-1 text-gray-600">Status:</td><td class="text-right"><span class="px-2 py-0.5 text-xs font-bold uppercase tracking-wider rounded <?= e((string) $statusBadgeColor) ?>"><?= e($booking['payment_status']) ?></span></td></tr>
            </table>
        </div>
    </div>

    <!-- Charges Table -->
    <div class="mb-8">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Room Charges</h3>
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500">
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider">Description</th>
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4">Accommodation (<?= (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400 ?> nights)</td>
                    <td class="py-3 px-4 text-right font-mono text-gray-800 font-semibold">₹<?= number_format($booking['total_price'], 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Payments Table -->
    <?php if (!empty($payments)): ?>
    <div class="mb-8">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Payments Received</h3>
        <table class="w-full text-sm text-left">
            <thead>
                <tr class="bg-gray-50 text-gray-500">
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider">Date</th>
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider">Method</th>
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider">Txn ID</th>
                    <th class="py-2 px-4 font-semibold uppercase tracking-wider text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments as $p): ?>
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-4 text-gray-600"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                    <td class="py-3 px-4 text-gray-600"><?= e($p['payment_method']) ?></td>
                    <td class="py-3 px-4 text-gray-600 font-mono text-xs"><?= e($p['transaction_id']) ?></td>
                    <td class="py-3 px-4 text-right font-mono text-gray-800 font-semibold">₹<?= number_format($p['amount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Summary -->
    <div class="flex justify-end border-t-2 border-gray-100 pt-6">
        <div class="w-64">
            <div class="flex justify-between py-2 text-sm text-gray-600">
                <span>Subtotal</span>
                <span class="font-mono">₹<?= number_format($booking['total_price'], 2) ?></span>
            </div>
            <div class="flex justify-between py-2 text-sm text-gray-600 border-b border-gray-200">
                <span>Total Paid</span>
                <span class="font-mono">₹<?= number_format($totalPaid, 2) ?></span>
            </div>
            <div class="flex justify-between py-3 text-lg font-bold text-gray-800">
                <span>Balance Due</span>
                <span class="font-mono <?= $balanceDue > 0 ? 'text-red-600' : 'text-green-600' ?>">₹<?= number_format($balanceDue, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-16 pt-8 border-t border-gray-100 text-center text-xs text-gray-400">
        <p>Thank you for choosing <?= e($booking['hotel_name']) ?>.</p>
        <p class="mt-1">Powered by Syncro PMS</p>
    </div>

</div>
</body>
</html>
