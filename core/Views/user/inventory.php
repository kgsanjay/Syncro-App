<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-6 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight flex items-center">
                <svg class="w-8 h-8 mr-3 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Inventory Tape Chart
            </h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Manage available rooms and stop-sells. Changes save automatically.</p>
        </div>
        <div class="flex gap-3">
            <button id="syncNowBtn" class="bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2.5 px-6 rounded shadow transition-all text-sm uppercase tracking-wider flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Push to Channels
            </button>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
        
        <div class="overflow-x-auto">
            <table class="min-w-[1000px] w-full border-collapse">
                <thead class="bg-[var(--header)] text-[var(--white)]">
                    <tr>
                        <th class="w-48 px-4 py-3 text-left text-[11px] font-bold uppercase tracking-widest border-r border-[var(--white)]/20 sticky left-0 z-20 bg-[var(--header)]">
                            Room Category
                        </th>
                        <?php foreach($dates as $dateStr): ?>
                            <?php $dateObj = new \DateTime($dateStr); ?>
                            <th class="px-2 py-3 text-center border-r border-[var(--white)]/20 min-w-[70px]">
                                <div class="text-[10px] font-bold uppercase tracking-widest text-[var(--theme)]"><?= $dateObj->format('D') ?></div>
                                <div class="text-sm font-black"><?= $dateObj->format('j M') ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-[var(--white)] divide-y divide-[var(--border)]">
                    <?php if(empty($roomTypes)): ?>
                        <tr><td colspan="15" class="px-6 py-12 text-center text-sm font-medium text-[var(--text)]">No room types configured.</td></tr>
                    <?php else: ?>
                        <?php foreach($roomTypes as $room): ?>
                            
                            <tr class="hover:bg-[var(--light)] transition-colors">
                                <td class="w-48 px-4 py-3 text-sm font-bold text-[var(--header)] border-r border-[var(--border)] sticky left-0 bg-[var(--white)] z-10 border-b-0 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?>
                                    <div class="text-[10px] text-[var(--theme2)] font-bold uppercase mt-1 tracking-widest">Available</div>
                                </td>
                                
                                <?php foreach($dates as $dateStr): ?>
                                    <?php 
                                        $invData = $inventoryData[$room['id']][$dateStr] ?? null;
                                        $avail = $invData ? (int)$invData['available_rooms'] : 0;
                                        // Stop sell formatting
                                        $isStopSell = $invData && (int)$invData['stop_sell'] === 1;
                                    ?>
                                    <td class="border-r border-[var(--light)] p-0 relative <?= $isStopSell ? 'bg-[var(--danger)]/5' : '' ?> transition-colors group">
                                        <input type="number" 
                                               min="0"
                                               data-room="<?= $room['id'] ?>" 
                                               data-date="<?= $dateStr ?>" 
                                               data-field="inv" 
                                               value="<?= $avail ?>" 
                                               class="w-full h-12 text-center text-sm font-black text-[var(--header)] bg-transparent focus:ring-4 focus:ring-[var(--theme2)]/10 outline-none border-none hover:bg-[var(--white)] focus:bg-[var(--white)] transition-all <?= $avail <= 0 ? 'text-[var(--danger)] opacity-40' : 'text-[var(--success)]' ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                            <tr class="bg-[var(--light)]/50 border-b-[3px] border-[var(--border)]">
                                <td class="w-48 px-4 py-2 text-[10px] font-black text-[var(--text)] uppercase tracking-[0.2em] border-r border-[var(--border)] sticky left-0 bg-[var(--light)] z-10 text-right opacity-60">
                                    Stop Sell
                                </td>
                                
                                <?php foreach($dates as $dateStr): ?>
                                    <?php 
                                        $invData = $inventoryData[$room['id']][$dateStr] ?? null;
                                        $isStopSell = $invData && (int)$invData['stop_sell'] === 1;
                                    ?>
                                    <td class="border-r border-[var(--light)] text-center p-2 relative <?= $isStopSell ? 'bg-[var(--danger)]/10' : '' ?> transition-colors">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   data-room="<?= $room['id'] ?>" 
                                                   data-date="<?= $dateStr ?>" 
                                                   data-field="stop" 
                                                   <?= $isStopSell ? 'checked' : '' ?>
                                                   class="form-checkbox h-5 w-5 <?= $isStopSell ? 'text-[var(--danger)]' : 'text-[var(--theme2)]' ?> border-2 border-[var(--border)] rounded-lg focus:ring-[var(--theme2)]/20 transition-all cursor-pointer">
                                        </label>
                                    </td>
                                <?php endforeach; ?>
                            </tr>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>