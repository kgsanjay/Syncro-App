<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-[var(--light)] border-l-4 border-[var(--theme2)] p-4 rounded shadow-sm flex items-center">
            <svg class="w-5 h-5 text-[var(--theme2)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="text-sm font-bold text-[var(--header)]">Rates successfully updated for the selected period.</p>
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
                <svg class="w-8 h-8 mr-3 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Dynamic Rate Manager
            </h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Bulk update pricing rules. These rates automatically sync to your direct booking engine and OTAs.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1">
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden sticky top-6">
                <div class="bg-[var(--header)] px-6 py-4 border-b border-[var(--theme)]">
                    <h3 class="font-bold text-[var(--white)] text-lg uppercase tracking-widest flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Bulk Override
                    </h3>
                </div>
                
                <form action="<?= base_url('/user/rates') ?>" method="POST" class="p-6 space-y-5">
                    <?= csrf_field() ?>">
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Room Category</label>
                        <select name="room_type_id" required class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-medium cursor-pointer">
                            <option value="">Select a room type...</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>"><?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?> (Base: ₹<?= number_format((float)$room['base_price']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Start Date</label>
                            <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">End Date</label>
                            <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">New Daily Rate (₹)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-[var(--text)] font-bold">₹</span>
                            </div>
                            <input type="number" name="new_price" required min="1" step="0.01" placeholder="e.g. 5000" class="w-full pl-8 pr-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--header)] font-bold text-lg">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-3.5 px-4 rounded shadow transition-all uppercase tracking-widest text-sm mt-2">
                        Apply New Rates
                    </button>
                    
                    <p class="text-xs text-[var(--text)] text-center font-medium mt-4">
                        Updating rates will immediately reflect on your direct booking engine.
                    </p>
                </form>
            </div>
            
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden mt-8">
                <div class="bg-[var(--header)] px-6 py-4 border-b border-[var(--theme)]">
                    <h3 class="font-bold text-[var(--white)] text-lg uppercase tracking-widest flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Pricing Rules
                    </h3>
                </div>
                <form action="<?= base_url('/user/rates/rule/create') ?>" method="POST" class="p-6 space-y-4 border-b border-[var(--border)]">
                    <?= csrf_field() ?>">
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Room Type</label>
                        <select name="room_type_id" class="w-full px-3 py-2 border border-[var(--border)] rounded bg-[var(--light)] outline-none text-sm cursor-pointer">
                            <option value="">All Room Types</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?= $room['id'] ?>"><?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Rule Type</label>
                            <select name="rule_type" required class="w-full px-3 py-2 border border-[var(--border)] rounded bg-[var(--light)] outline-none text-sm cursor-pointer">
                                <option value="occupancy_based">Occupancy Based</option>
                                <option value="time_based">Time Based (Days Advance)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Condition Value</label>
                            <input type="number" name="condition_value" required placeholder="e.g. 80" class="w-full px-3 py-2 border border-[var(--border)] rounded bg-[var(--light)] outline-none text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Adjustment</label>
                            <select name="adjustment_type" required class="w-full px-3 py-2 border border-[var(--border)] rounded bg-[var(--light)] outline-none text-sm cursor-pointer">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Value (+/-)</label>
                            <input type="number" name="adjustment_value" step="0.01" required placeholder="e.g. 10 or -500" class="w-full px-3 py-2 border border-[var(--border)] rounded bg-[var(--light)] outline-none text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[var(--success)] hover:opacity-90 text-[var(--white)] font-bold py-2.5 px-4 rounded shadow uppercase tracking-widest text-xs">
                        Add Rule
                    </button>
                </form>

                <div class="p-6 space-y-4">
                    <h4 class="font-bold text-[var(--header)] text-sm uppercase tracking-wider border-b border-[var(--border)] pb-2">Active Rules</h4>
                    <?php if(empty($pricingRules)): ?>
                        <p class="text-xs text-[var(--text)] italic">No pricing rules defined.</p>
                    <?php else: ?>
                        <?php foreach($pricingRules as $rule): ?>
                            <div class="bg-[var(--light)] p-3 rounded border border-[var(--border)] flex justify-between items-center">
                                <div>
                                    <div class="text-xs font-bold text-[var(--header)]">
                                        <?= $rule['room_type_name'] ?? 'All Rooms' ?>: 
                                        <?= $rule['rule_type'] === 'occupancy_based' ? '>=' . $rule['condition_value'] . '% Occupied' : '>=' . $rule['condition_value'] . ' Days in Advance' ?>
                                    </div>
                                    <div class="text-xs text-[var(--text)]">
                                        Adjustment: <?= $rule['adjustment_value'] > 0 ? '+' : '' ?><?= $rule['adjustment_value'] ?><?= $rule['adjustment_type'] === 'percentage' ? '%' : '₹' ?>
                                    </div>
                                </div>
                                <form method="POST" action="<?= base_url('/user/rates/rule/toggle') ?>">
                                    <?= csrf_field() ?>">
                                    <input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                                    <button type="submit" class="text-xs font-bold px-2 py-1 rounded <?= $rule['status'] === 'active' ? 'bg-[var(--danger)] text-white' : 'bg-[var(--success)] text-white' ?>">
                                        <?= $rule['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
                <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                    <h3 class="font-bold text-[var(--header)]">Rate Calendar View</h3>
                    
                    <div class="flex space-x-2">
                        <?php 
                            $prevMonth = $month - 1; $prevYear = $year;
                            if($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
                            $nextMonth = $month + 1; $nextYear = $year;
                            if($nextMonth > 12) { $nextMonth = 1; $nextYear++; }
                        ?>
                        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="px-3 py-1.5 border border-[var(--border)] rounded bg-[var(--white)] text-[var(--text)] hover:text-[var(--header)] hover:bg-[var(--light)] transition-colors text-sm font-bold">&larr; Prev</a>
                        <span class="px-4 py-1.5 bg-[var(--header)] text-[var(--white)] rounded font-bold text-sm tracking-wider uppercase border border-[var(--theme2)]">
                            <?= date('F Y', strtotime($startDate)) ?>
                        </span>
                        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="px-3 py-1.5 border border-[var(--border)] rounded bg-[var(--white)] text-[var(--text)] hover:text-[var(--header)] hover:bg-[var(--light)] transition-colors text-sm font-bold">Next &rarr;</a>
                    </div>
                </div>

                <div class="p-6">
                    <?php if(empty($rooms)): ?>
                        <div class="text-center py-10 text-[var(--text)] font-medium">No room types found. Create one first.</div>
                    <?php else: ?>
                        <div class="space-y-8">
                            <?php foreach($rooms as $room): ?>
                                <div>
                                    <h4 class="font-bold text-[var(--header)] mb-3 text-lg border-b border-[var(--border)] pb-2"><?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?> <span class="text-xs font-normal text-[var(--text)] uppercase tracking-widest ml-2">Base: ₹<?= number_format((float)$room['base_price']) ?></span></h4>
                                    
                                    <div class="grid grid-cols-7 gap-2">
                                        <?php 
                                            $daysInMonth = date('t', strtotime($startDate));
                                            $firstDayOfWeek = date('N', strtotime($startDate)); 
                                            
                                            $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                            foreach($daysOfWeek as $dow) {
                                                echo "<div class='text-center text-[10px] font-bold text-[var(--text)] uppercase tracking-widest mb-2'>{$dow}</div>";
                                            }

                                            for ($i = 1; $i < $firstDayOfWeek; $i++) {
                                                echo "<div class='h-16 rounded border border-[var(--light)] bg-[var(--light)]/30'></div>";
                                            }

                                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                                $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                $price = $rates[$room['id']][$currentDate] ?? $room['base_price'];
                                                $isToday = $currentDate === date('Y-m-d');
                                                $isModified = isset($rates[$room['id']][$currentDate]);
                                                
                                                $bgClass = $isToday ? 'bg-[var(--theme2)]/10 border-2 border-[var(--theme2)]' : 'bg-[var(--white)] border border-[var(--border)] hover:bg-[var(--light)] transition-all hover:scale-105 hover:z-20 hover:shadow-lg';
                                                $priceColor = $isModified ? 'text-[var(--theme2)] font-black' : 'text-[var(--text)] font-bold opacity-40';
                                                
                                                echo "
                                                <div class='h-16 rounded-xl {$bgClass} p-2.5 flex flex-col justify-between relative cursor-default group'>
                                                    <span class='text-[9px] font-black text-[var(--header)] absolute top-2 left-2.5 opacity-40 group-hover:opacity-100 transition-opacity'>{$day}</span>
                                                    <div class='mt-auto text-right text-[10px] {$priceColor} truncate tracking-tighter' title='₹" . number_format((float)$price) . "'>
                                                        ₹" . number_format((float)$price) . "
                                                    </div>
                                                </div>";
                                            }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>