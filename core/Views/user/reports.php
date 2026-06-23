<?php declare(strict_types=1); ?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-[var(--header)]">Financial & Performance Reports</h2>
        <p class="text-[var(--text)] text-sm mt-1">Track your property's revenue, occupancy, and daily shift reconciliation.</p>
    </div>
    
    <div class="flex flex-col sm:flex-row items-center gap-3">
        <form method="GET" action="<?= base_url('/user/reports') ?>" class="flex items-center gap-3 bg-[var(--white)] p-2 rounded-lg border border-[var(--border)] shadow-sm">
            <input type="date" name="date" value="<?= htmlspecialchars($targetDate) ?>" class="p-2 border border-[var(--border)] rounded focus:outline-none focus:border-[var(--theme)] text-sm text-[var(--text)] bg-[var(--light)]">
            
            <select name="month" class="p-2 border border-[var(--border)] rounded focus:outline-none focus:border-[var(--theme)] text-sm text-[var(--text)] bg-[var(--light)]">
                <?php for($m=1; $m<=12; ++$m): ?>
                    <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
            
            <select name="year" class="p-2 border border-[var(--border)] rounded focus:outline-none focus:border-[var(--theme)] text-sm text-[var(--text)] bg-[var(--light)]">
                <?php $currentYear = (int)date('Y'); for($y = $currentYear - 1; $y <= $currentYear + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="bg-[var(--theme2)] text-[var(--white)] px-4 py-2 rounded text-sm font-semibold hover:opacity-90 transition-opacity">
                Update
            </button>
        </form>

        <a href="<?= base_url() ?>/user/reports?date=<?= htmlspecialchars($targetDate) ?>&month=<?= $month ?>&year=<?= $year ?>&export=csv" 
           class="inline-flex items-center justify-center px-6 py-3.5 bg-[var(--header)] text-[var(--theme)] text-xs font-black rounded-xl hover:bg-[var(--theme2)] hover:text-[var(--white)] transition-all shadow-xl whitespace-nowrap w-full sm:w-auto uppercase tracking-widest border border-[var(--theme)]/20 hover:-translate-y-1 active:scale-95">
            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Fiscal Data
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="col-span-1 lg:col-span-2 space-y-6">
        <h3 class="font-bold text-lg text-[var(--header)] border-b border-[var(--border)] pb-2">
            Monthly Performance (<?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?>)
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-[var(--white)] p-6 rounded-xl shadow-sm border border-[var(--border)] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-[var(--light)] rounded-bl-full z-0 opacity-50"></div>
                <div class="relative z-10">
                    <p class="text-[var(--text)] text-sm font-bold uppercase tracking-wider mb-1">Total Revenue</p>
                    <p class="text-3xl font-bold text-[var(--header)]">₹<?= number_format((float)$performance['total_revenue'], 2) ?></p>
                </div>
            </div>
            
            <div class="bg-[var(--white)] p-6 rounded-xl shadow-sm border border-[var(--border)] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-red-50 rounded-bl-full z-0 opacity-50"></div>
                <div class="relative z-10">
                    <p class="text-[var(--text)] text-sm font-bold uppercase tracking-wider mb-1">Total Expenses</p>
                    <p class="text-3xl font-bold text-red-600">₹<?= number_format((float)($totalExpenses ?? 0), 2) ?></p>
                </div>
            </div>
            
            <div class="bg-[var(--white)] p-6 rounded-xl shadow-sm border border-[var(--border)] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-green-50 rounded-bl-full z-0 opacity-50"></div>
                <div class="relative z-10">
                    <p class="text-[var(--text)] text-sm font-bold uppercase tracking-wider mb-1">Net Profit</p>
                    <p class="text-3xl font-bold text-green-600">₹<?= number_format(((float)$performance['total_revenue'] - (float)($totalExpenses ?? 0)), 2) ?></p>
                </div>
            </div>

            <div class="bg-[var(--theme2)] p-6 rounded-xl shadow-sm border border-[var(--border)] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-[var(--theme)] rounded-bl-full z-0 opacity-20"></div>
                <div class="relative z-10">
                    <p class="text-white/80 text-sm font-bold uppercase tracking-wider mb-1">Occupancy</p>
                    <p class="text-3xl font-bold text-[var(--white)]">
                        <?= number_format((float)$performance['occupancy_rate'], 1) ?>%
                    </p>
                    <p class="text-xs text-[var(--theme)] mt-1"><?= $performance['occupied_nights'] ?> / <?= $performance['available_nights'] ?> Nights Booked</p>
                </div>
            </div>

            <div class="bg-[var(--white)] p-6 rounded-xl shadow-sm border border-[var(--border)] flex flex-col justify-center">
                <div class="flex justify-between items-end mb-3 pb-3 border-b border-[var(--border)]">
                    <div>
                        <p class="text-[var(--text)] text-xs font-bold uppercase tracking-wider">ADR</p>
                        <p class="text-xl font-bold text-[var(--theme2)]">₹<?= number_format((float)$performance['adr'], 2) ?></p>
                    </div>
                </div>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-[var(--text)] text-xs font-bold uppercase tracking-wider">RevPAR</p>
                        <p class="text-xl font-bold text-[var(--theme2)]">₹<?= number_format((float)$performance['revpar'], 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-span-1">
        <h3 class="font-bold text-lg text-[var(--header)] border-b border-[var(--border)] pb-2 mb-6">
            Daily Reconciliation (<?= date('M j, Y', strtotime($targetDate)) ?>)
        </h3>
        
        <div class="bg-[var(--white)] rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="p-4 bg-[var(--theme2)] text-[var(--white)] flex justify-between items-center">
                <span class="font-semibold tracking-wide">Total Shift Collection</span>
                <span class="text-xl font-bold text-[var(--theme)]">₹<?= number_format($shiftTotal, 2) ?></span>
            </div>
            
            <div class="p-0">
                <?php if (empty($reconciliation)): ?>
                    <p class="p-8 text-center text-[var(--text)] text-sm">No transactions recorded on this date.</p>
                <?php else: ?>
                    <ul class="divide-y divide-[var(--border)]">
                        <?php foreach ($reconciliation as $rec): ?>
                            <li class="p-4 flex justify-between items-center hover:bg-[var(--light)]/50 transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded bg-[var(--light)] flex items-center justify-center text-[var(--theme2)] font-bold border border-[var(--border)]">
                                        <?= substr(htmlspecialchars($rec['payment_method']), 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-[var(--header)]"><?= htmlspecialchars($rec['payment_method']) ?></p>
                                        <p class="text-xs text-[var(--text)]"><?= (int)$rec['transaction_count'] ?> Transaction(s)</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-[var(--theme2)]">₹<?= number_format((float)$rec['total_collected'], 2) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="p-4 border-t border-[var(--border)] bg-[var(--light)]">
                <button onclick="window.print()" class="w-full text-center text-sm font-bold text-[var(--text)] hover:text-[var(--theme2)] transition-colors">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Shift Report
                </button>
            </div>
        </div>
    </div>
</div>