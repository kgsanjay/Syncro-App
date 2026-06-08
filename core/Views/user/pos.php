<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-[var(--light)] border-l-4 border-[var(--theme2)] p-4 rounded shadow-sm flex items-center">
            <svg class="w-5 h-5 text-[var(--theme2)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="text-sm font-bold text-[var(--header)]">POS charge successfully added.</p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 bg-[var(--danger)]/5 border-l-4 border-[var(--danger)] p-5 rounded-r-xl shadow-sm flex items-center animate-shake">
            <svg class="w-6 h-6 text-[var(--danger)] mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm font-black text-[var(--danger)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($_GET['error']) ?></p>
        </div>
    <?php endif; ?>

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight flex items-center">
                <svg class="w-8 h-8 mr-3 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Point of Sale (POS)
            </h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Add ancillary sales (F&B, spa, parking) directly to guest folios.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden sticky top-6">
                <div class="bg-[var(--header)] px-6 py-4 border-b border-[var(--theme)]">
                    <h3 class="font-bold text-[var(--white)] text-lg uppercase tracking-widest">
                        New Charge
                    </h3>
                </div>
                
                <form action="/user/pos/add" method="POST" class="p-6 space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Guest / Room</label>
                        <select name="booking_id" required class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-medium cursor-pointer">
                            <option value="">Select active booking...</option>
                            <?php foreach($activeBookings as $booking): ?>
                                <option value="<?= $booking['id'] ?>">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['guest_name']) ?> - 
                                    Room <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['room_number'] ?? 'Unassigned') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Item Name / Service</label>
                        <input type="text" name="item_name" required placeholder="e.g. Breakfast, Mini-bar" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-medium">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Quantity</label>
                            <input type="number" name="quantity" required min="1" value="1" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Price (₹)</label>
                            <input type="number" name="price" required min="0.01" step="0.01" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-3.5 px-4 rounded shadow transition-all uppercase tracking-widest text-sm mt-2">
                        Post to Folio
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
                <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                    <h3 class="font-bold text-[var(--header)]">Recent Sales</h3>
                </div>

                <div class="p-0">
                    <?php if(empty($recentSales)): ?>
                        <div class="text-center py-10 text-[var(--text)] font-medium">No recent ancillary sales.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[var(--border)]">
                                <thead class="bg-[var(--light)]">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-[var(--text)] uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-[var(--text)] uppercase tracking-wider">Guest / Room</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-[var(--text)] uppercase tracking-wider">Item</th>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-[var(--text)] uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-[var(--text)] uppercase tracking-wider">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-[var(--white)] divide-y divide-[var(--border)]">
                                    <?php foreach($recentSales as $sale): ?>
                                        <tr class="hover:bg-[var(--light)] transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text)]">
                                                <?= date('M j, Y h:i A', strtotime($sale['sale_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-[var(--header)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($sale['guest_name']) ?></div>
                                                <div class="text-xs text-[var(--text)]">Room <?= \Syncro\Security\SecurityManager::sanitizeOutput($sale['room_number'] ?? 'Unassigned') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[var(--header)]">
                                                <?= \Syncro\Security\SecurityManager::sanitizeOutput($sale['item_name']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-[var(--text)]">
                                                <?= $sale['quantity'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-[var(--theme2)] text-right">
                                                ₹<?= number_format((float)$sale['total_amount'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
