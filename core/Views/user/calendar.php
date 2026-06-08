<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-6 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight flex items-center">
                <svg class="w-8 h-8 mr-3 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Front Desk Timeline
            </h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Visual calendar for the next 14 days. Hover over a booking for details.</p>
        </div>
        <div class="flex gap-3">
            <span class="bg-[var(--white)] border border-[var(--border)] text-[var(--header)] font-bold py-2 px-4 rounded shadow-sm text-sm flex items-center">
                <?= $dates[0]->format('M j, Y') ?> &mdash; <?= $dates[13]->format('M j, Y') ?>
            </span>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
        
        <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full border-collapse table-fixed">
                <thead class="bg-[var(--light)] border-b border-[var(--border)]">
                    <tr>
                        <th class="w-48 px-4 py-3 text-left text-[11px] font-bold text-[var(--header)] uppercase tracking-widest border-r border-[var(--border)] bg-[var(--white)] sticky left-0 z-20">
                            Physical Rooms
                        </th>
                        <?php foreach($dates as $date): ?>
                            <th class="px-2 py-3 text-center border-r border-[var(--border)] <?= $date->format('Y-m-d') === date('Y-m-d') ? 'bg-[var(--theme2)] text-[var(--white)]' : 'text-[var(--text)]' ?>">
                                <div class="text-[10px] font-bold uppercase tracking-widest"><?= $date->format('D') ?></div>
                                <div class="text-sm font-black"><?= $date->format('j') ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="bg-[var(--white)]">
                    <?php if(empty($groupedRooms)): ?>
                        <tr>
                            <td colspan="15" class="px-6 py-12 text-center text-sm font-medium text-[var(--text)]">
                                No physical rooms found. Please create physical rooms (e.g., 101, 102) in the Room Types menu.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($groupedRooms as $typeName => $physicalRooms): ?>
                            <tr class="bg-[var(--light)] border-y border-[var(--border)]">
                                <td colspan="15" class="px-4 py-2 text-xs font-bold text-[var(--header)] uppercase tracking-wider sticky left-0 z-10 bg-[var(--light)]">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($typeName) ?>
                                </td>
                            </tr>
                            
                            <?php foreach($physicalRooms as $room): ?>
                                <tr class="border-b border-[var(--light)] group hover:bg-[var(--light)]/50 transition-colors">
                                    <td class="w-48 px-4 py-3 text-sm font-bold text-[var(--header)] border-r border-[var(--border)] sticky left-0 bg-[var(--white)] group-hover:bg-[var(--light)]/50 transition-colors z-10 flex items-center justify-between">
                                        <span>Rm <?= \Syncro\Security\SecurityManager::sanitizeOutput($room['room_number']) ?></span>
                                        <?php if($room['housekeeping_status'] === 'dirty'): ?>
                                            <span class="w-2 h-2 rounded-full bg-[var(--header)]" title="Dirty"></span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 rounded-full bg-[var(--theme2)]" title="Clean"></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <?php 
                                        // A simple loop to render the 14 days and check for overlapping bookings.
                                        // In a robust JS calendar this would be absolute positioned, but table cells work well for strict server-side rendering.
                                        for ($i = 0; $i < 14; $i++): 
                                            $currentDateStr = $dates[$i]->format('Y-m-d');
                                            $cellBooking = null;
                                            $isStart = false;
                                            
                                            // Find if a booking falls on this date for this specific physical room
                                            foreach($bookings as $b) {
                                                if ($b['assigned_room_id'] == $room['physical_id']) {
                                                    $bStart = $b['check_in'];
                                                    $bEnd = $b['check_out'];
                                                    
                                                    // Standard hotel logic: guest occupies the room on the night of check-in up to the night BEFORE check-out
                                                    if ($currentDateStr >= $bStart && $currentDateStr < $bEnd) {
                                                        $cellBooking = $b;
                                                        if ($currentDateStr === $bStart || $i === 0) {
                                                            $isStart = true;
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                    ?>
                                        <td class="relative border-r border-[var(--light)] p-0 m-0 h-12 align-middle">
                                            <?php if($cellBooking): ?>
                                                <?php 
                                                    // Determine visual style based on status
                                                    $bgColor = 'bg-[var(--theme2)] border-[var(--header)]/10 text-[var(--white)]';
                                                    if ($cellBooking['status'] === 'checked_in') $bgColor = 'bg-[var(--theme)] border-[var(--theme)]/20 text-[var(--header)]';
                                                    if ($cellBooking['status'] === 'checked_out') $bgColor = 'bg-[var(--light)] border-[var(--border)] text-[var(--text)] opacity-60';
                                                    if ($cellBooking['status'] === 'cancelled') $bgColor = 'bg-[var(--danger)]/10 border-[var(--danger)]/20 text-[var(--danger)]';
                                                ?>
                                                <div class="absolute inset-y-1 inset-x-0 <?= $bgColor ?> <?= $isStart ? 'ml-1 rounded-lg' : '' ?> <?= ($currentDateStr == date('Y-m-d', strtotime($cellBooking['check_out'] . ' -1 day')) || $i == 13) ? 'mr-1 rounded-lg' : '' ?> flex items-center overflow-hidden px-2 shadow-sm border cursor-pointer transition-all hover:scale-[1.02] hover:z-30 hover:shadow-md" title="<?= \Syncro\Security\SecurityManager::sanitizeOutput($cellBooking['guest_name']) ?> (<?= strtoupper($cellBooking['status']) ?>)">
                                                    <?php if($isStart): ?>
                                                        <span class="text-[9px] font-black truncate uppercase tracking-tighter">
                                                            <?= \Syncro\Security\SecurityManager::sanitizeOutput($cellBooking['guest_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-full h-full hover:bg-[var(--theme)]/5 transition-colors cursor-crosshair"></div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="bg-[var(--light)] px-6 py-4 border-t border-[var(--border)] flex gap-6 text-xs font-bold text-[var(--text)] uppercase tracking-widest">
            <div class="flex items-center"><span class="w-3 h-3 rounded bg-[var(--theme2)] mr-2"></span> Confirmed</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded bg-[var(--theme)] mr-2"></span> Checked In</div>
            <div class="flex items-center"><span class="w-3 h-3 rounded bg-[var(--border)] mr-2"></span> Checked Out</div>
        </div>
    </div>
</div>