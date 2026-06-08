<?php declare(strict_types=1); ?>

<style>
    /* Print optimizations for a perfect A4 fit */
    @media print {
        body * { visibility: hidden; }
        #printable-invoice, #printable-invoice * { visibility: visible; }
        #printable-invoice {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
            color: #000 !important; /* Force black text for crisp printing */
        }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @page { margin: 0.5cm; }
        body { background-color: white !important; }
        .print\:hidden { display: none !important; }
    }
</style>

<div class="max-w-[900px] mx-auto pb-12">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
        <a href="/user/bookings" class="text-[var(--text)] hover:text-[var(--theme2)] text-sm font-bold flex items-center transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Reservations
        </a>
        <div class="flex gap-3">
            <button onclick="window.print()" class="bg-[var(--header)] text-[var(--white)] px-5 py-2 rounded text-sm font-bold hover:bg-[var(--theme2)] transition-colors flex items-center shadow-sm uppercase tracking-widest">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print / Save PDF
            </button>
        </div>
    </div>

    <div id="printable-invoice" class="bg-[var(--white)] rounded-xl shadow-xl border border-[var(--border)] p-10 sm:p-12 relative overflow-hidden">
        
        <div class="flex justify-between items-start border-b-4 border-[var(--header)] pb-8 mb-10">
            <div>
                <h1 class="text-3xl font-black text-[var(--header)] uppercase tracking-tight mb-1">
                    <?= htmlspecialchars($booking['property_name']) ?>
                </h1>
                <p class="text-[10px] text-[var(--text)] uppercase tracking-[0.2em] font-black opacity-40">Official Guest Folio & Tax Invoice</p>
            </div>
            <div class="text-right">
                <h2 class="text-[10px] text-[var(--text)] uppercase tracking-[0.2em] font-black opacity-40 mb-2">Invoice / Folio No.</h2>
                <p class="text-2xl font-black text-[var(--header)] tracking-tight">#<?= str_pad((string)$booking['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <div class="mt-3 px-3 py-1 bg-[var(--light)] rounded-lg text-[10px] font-black text-[var(--header)] uppercase tracking-widest inline-block border border-[var(--border)]">
                    Date: <?= date('M j, Y') ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-12 mb-12 text-sm">
            <div>
                <h3 class="text-[10px] text-[var(--header)] uppercase tracking-[0.2em] font-black border-b border-[var(--border)] pb-2 mb-4 opacity-40">Billed To</h3>
                <p class="font-black text-[var(--header)] text-xl tracking-tight"><?= htmlspecialchars($booking['guest_name']) ?></p>
                <?php if(!empty($booking['guest_email'])): ?>
                    <p class="text-[var(--text)] font-semibold mt-1 tracking-tight"><?= htmlspecialchars($booking['guest_email']) ?></p>
                <?php endif; ?>
                <?php if(!empty($booking['guest_phone'])): ?>
                    <p class="text-[var(--text)] font-semibold mt-1 tracking-tight"><?= htmlspecialchars($booking['guest_phone']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="bg-[var(--light)]/50 p-6 rounded-2xl border border-[var(--border)]">
                <h3 class="text-[10px] text-[var(--header)] uppercase tracking-[0.2em] font-black border-b border-[var(--border)] pb-2 mb-4 opacity-40">Stay Details</h3>
                <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                    <div class="text-[var(--text)] text-[10px] font-black uppercase tracking-widest self-center opacity-60">Check-in</div>
                    <div class="font-black text-[var(--header)] text-right"><?= date('M j, Y', strtotime($booking['check_in'])) ?></div>
                    
                    <div class="text-[var(--text)] text-[10px] font-black uppercase tracking-widest self-center opacity-60">Check-out</div>
                    <div class="font-black text-[var(--header)] text-right"><?= date('M j, Y', strtotime($booking['check_out'])) ?></div>
                    
                    <div class="text-[var(--text)] text-[10px] font-black uppercase tracking-widest self-center opacity-60">Room Type</div>
                    <div class="font-black text-[var(--header)] text-right truncate pl-4"><?= htmlspecialchars($booking['room_name']) ?></div>
                    
                    <div class="text-[var(--text)] text-[10px] font-black uppercase tracking-widest self-center opacity-60">Duration</div>
                    <div class="font-black text-[var(--header)] text-right"><?= $nights ?> Night(s)</div>
                </div>
            </div>
        </div>

        <div class="mb-12">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-y-2 border-[var(--border)] bg-[var(--light)]/50 text-[10px] uppercase tracking-[0.2em] text-[var(--header)] font-black">
                        <th class="py-5 px-4 w-1/6">Date</th>
                        <th class="py-5 px-4 w-1/2">Description</th>
                        <th class="py-5 px-4 text-right">Tax Info</th>
                        <th class="py-5 px-4 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--border)] text-[var(--header)] font-bold">
                    
                    <?php foreach ($nightlyBreakdown as $night): ?>
                    <tr class="hover:bg-[var(--light)] transition-colors">
                        <td class="py-4 px-4 whitespace-nowrap text-[var(--text)] text-[11px] font-black"><?= date('M j, Y', strtotime($night['date'])) ?></td>
                        <td class="py-4 px-4">Room Charge</td>
                        <td class="py-4 px-4 text-right text-[10px] text-[var(--text)] font-black uppercase tracking-widest opacity-40"><?= htmlspecialchars($night['rule']) ?></td>
                        <td class="py-4 px-4 text-right font-black tracking-tight">₹<?= number_format($night['rate'] + $night['tax'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (!empty($posCharges)): ?>
                        <?php foreach ($posCharges as $pos): ?>
                        <tr class="hover:bg-[var(--light)] transition-colors">
                            <td class="py-4 px-4 whitespace-nowrap text-[var(--text)] text-[11px] font-black"><?= date('M j, Y', strtotime($pos['created_at'] ?? 'now')) ?></td>
                            <td class="py-4 px-4">
                                <?= htmlspecialchars($pos['description']) ?>
                                <span class="ml-2 px-2 py-0.5 bg-[var(--header)] text-[var(--theme)] text-[9px] font-black uppercase tracking-[0.2em] rounded-full">Incidental</span>
                            </td>
                            <td class="py-4 px-4 text-right text-[10px] text-[var(--text)] font-black uppercase tracking-widest opacity-40">Standard (18%)</td>
                            <?php $posTotalLine = (float)$pos['amount'] + ((float)$pos['amount'] * 0.18); ?>
                            <td class="py-4 px-4 text-right font-black tracking-tight">₹<?= number_format($posTotalLine, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-start border-t-2 border-[var(--border)] pt-8">
            
            <div class="pt-2">
                <?php if ($balanceDue <= 0): ?>
                    <div class="inline-block border-2 border-[var(--success)] text-[var(--success)] text-xl font-black uppercase tracking-[0.3em] px-6 py-3 rounded-xl opacity-90 shadow-sm transform -rotate-1">
                        PAID IN FULL
                    </div>
                <?php else: ?>
                    <div class="inline-block border-2 border-[var(--danger)] text-[var(--danger)] text-xl font-black uppercase tracking-[0.3em] px-6 py-3 rounded-xl opacity-90 shadow-sm transform -rotate-1 animate-pulse">
                        BALANCE DUE
                    </div>
                <?php endif; ?>
            </div>

            <div class="w-72 text-sm bg-[var(--light)]/30 p-6 rounded-2xl border border-[var(--border)]">
                <div class="flex justify-between py-1.5 text-[var(--text)] font-bold text-[11px] uppercase tracking-widest">
                    <span>Subtotal</span>
                    <span>₹<?= number_format($roomTotal + $posTotal, 2) ?></span>
                </div>
                <div class="flex justify-between py-1.5 text-[var(--text)] font-bold text-[11px] uppercase tracking-widest border-b border-[var(--border)] pb-3 mb-3">
                    <span>Total Taxes</span>
                    <span>₹<?= number_format($taxTotal, 2) ?></span>
                </div>
                <div class="flex justify-between py-1 text-[var(--header)] font-black text-lg tracking-tight">
                    <span>Grand Total</span>
                    <span>₹<?= number_format($grandTotal, 2) ?></span>
                </div>
                <div class="flex justify-between py-1.5 font-bold text-[var(--text)] text-[11px] uppercase tracking-widest border-b border-[var(--border)] pb-3 mb-3">
                    <span>Received</span>
                    <span class="text-[var(--success)]">- ₹<?= number_format($totalPaid, 2) ?></span>
                </div>
                
                <div class="flex justify-between py-3 font-black text-2xl tracking-tighter <?= $balanceDue > 0 ? 'text-[var(--danger)]' : 'text-[var(--header)]' ?>">
                    <span class="uppercase tracking-[0.1em] text-[10px] self-center opacity-40">Amount Due</span>
                    <span>₹<?= number_format($balanceDue, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-[var(--border)] text-center">
            <p class="text-[11px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-30">Thank you for choosing <?= htmlspecialchars($booking['property_name']) ?>.</p>
            <p class="text-[9px] mt-2 font-bold text-[var(--text)] opacity-40 uppercase tracking-widest">Digital Certificate: <?= hash('sha256', $booking['id'] . $grandTotal . date('Ymd')) ?></p>
        </div>

    </div>
    <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8 print:hidden">
        
        <div class="bg-[var(--white)] rounded-2xl shadow-sm border border-[var(--border)] overflow-hidden transition-all hover:shadow-lg">
            <div class="p-5 bg-[var(--light)] border-b border-[var(--border)]">
                <h3 class="font-black text-[var(--header)] flex items-center text-[10px] uppercase tracking-[0.2em]">
                    <svg class="w-4 h-4 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Post Incidental Charge
                </h3>
            </div>
            <form action="/user/invoice/charge" method="POST" class="p-6 m-0 space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                
                <div class="grid grid-cols-3 gap-6">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-[var(--text)] uppercase tracking-widest mb-2 opacity-50">Description</label>
                        <input type="text" name="description" placeholder="Room Service, Minibar..." required class="w-full p-3 border-2 border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--theme2)] text-sm font-bold bg-[var(--light)] focus:bg-[var(--white)] transition-all">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-[10px] font-black text-[var(--text)] uppercase tracking-widest mb-2 opacity-50">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" placeholder="0.00" required class="w-full p-3 border-2 border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--theme2)] text-sm font-black bg-[var(--light)] focus:bg-[var(--white)] transition-all">
                    </div>
                </div>
                <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] px-6 py-3.5 rounded-xl font-black hover:bg-[var(--theme2)] transition-all text-[10px] uppercase tracking-[0.2em] shadow-lg hover:-translate-y-1 active:scale-95">
                    Post to Folio
                </button>
            </form>
        </div>

        <?php if ($balanceDue > 0): ?>
        <div class="bg-[var(--white)] rounded-2xl shadow-sm border-2 border-[var(--theme2)]/30 overflow-hidden transition-all hover:shadow-xl">
            <div class="p-5 bg-[var(--theme2)]/5 border-b border-[var(--theme2)]/30">
                <h3 class="font-black text-[var(--theme2)] flex items-center text-[10px] uppercase tracking-[0.2em]">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Record Payment
                </h3>
            </div>
            <form action="/user/payment/store" method="POST" class="p-6 m-0 space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-[var(--theme2)] uppercase tracking-widest mb-2">Method</label>
                        <select name="payment_method" required class="w-full p-3 border-2 border-[var(--theme2)]/30 rounded-xl focus:outline-none focus:border-[var(--theme2)] bg-[var(--white)] text-sm font-black text-[var(--header)] transition-all cursor-pointer">
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="UPI">UPI / QR Code</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-[var(--theme2)] uppercase tracking-widest mb-2">Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" value="<?= $balanceDue ?>" max="<?= $balanceDue ?>" required class="w-full p-3 border-2 border-[var(--theme2)]/30 rounded-xl focus:outline-none focus:border-[var(--theme2)] bg-[var(--white)] text-sm font-black text-[var(--header)] transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-[var(--theme2)] uppercase tracking-widest mb-2 opacity-60">Ref ID (Optional)</label>
                    <input type="text" name="transaction_id" placeholder="UPI Ref or Auth Code" class="w-full p-3 border-2 border-[var(--theme2)]/10 rounded-xl focus:outline-none focus:border-[var(--theme2)] bg-[var(--light)] text-sm font-bold transition-all">
                </div>
                
                <button type="submit" class="w-full bg-[var(--theme2)] text-[var(--white)] px-6 py-4 rounded-xl font-black hover:bg-[var(--header)] transition-all shadow-xl text-[10px] uppercase tracking-[0.2em] hover:-translate-y-1 active:scale-95">
                    Settle Balance
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-[var(--success)]/5 rounded-2xl shadow-sm border-2 border-[var(--success)]/30 p-10 flex flex-col items-center justify-center text-center backdrop-blur-sm">
            <div class="w-16 h-16 bg-[var(--success)]/20 text-[var(--success)] rounded-full flex items-center justify-center mb-5 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-[11px] font-black text-[var(--success)] uppercase tracking-[0.3em]">Folio Settled</h3>
            <p class="text-[10px] text-[var(--success)] mt-2 font-black uppercase opacity-60">No outstanding balance. Guest is clear for check-out.</p>
        </div>
        <?php endif; ?>

    </div>
</div>