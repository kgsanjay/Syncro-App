<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-6 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight flex items-center">
                <svg class="w-8 h-8 mr-3 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Reservation Ledger
            </h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Manage all guest folios, walk-ins, and online bookings.</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= base_url('/export/bookings') ?>" class="bg-[var(--white)] text-[var(--header)] border border-[var(--border)] font-bold py-2.5 px-5 rounded shadow-sm hover:bg-gray-50 hover:shadow transition-all text-sm flex items-center">
                <svg class="w-4 h-4 mr-2 text-[var(--text)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
            </a>
            <button onclick="document.getElementById('walkInModal').classList.remove('hidden')" class="bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2.5 px-5 rounded shadow-sm transition-all text-sm uppercase tracking-wider flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Walk-In
            </button>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
        
        <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-[var(--text)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="tableSearch" placeholder="Search by Guest Name or Folio ID..." class="block w-full pl-10 pr-3 py-2 border border-[var(--border)] rounded leading-5 bg-[var(--white)] text-[var(--text)] focus:outline-none focus:ring-2 focus:ring-[var(--theme2)] focus:border-[var(--theme2)] sm:text-sm transition-colors shadow-sm font-medium">
            </div>
            <div class="text-[11px] font-bold uppercase tracking-widest text-[var(--text)]" id="recordCount">
                <?= count($bookings ?? []) ?> Total Records
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)]">
                <thead class="bg-[var(--white)]">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Folio</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Guest Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Dates of Stay</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Room & Source</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Payment</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Status / Action</th>
                    </tr>
                </thead>
                <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]" id="bookingTableBody">
                    <?php if(empty($bookings)): ?>
                        <tr id="emptyStateRow">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-[var(--border)] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <h3 class="text-sm font-bold text-[var(--header)]">No reservations found</h3>
                                <p class="mt-1 text-sm text-[var(--text)]">Get started by creating a new walk-in booking.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($bookings as $booking): ?>
                        <tr class="hover:bg-[var(--light)] transition-colors group search-row">
                            
                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <span class="text-xs font-mono font-bold text-[var(--text)] bg-[var(--light)] border border-[var(--border)] px-2 py-1 rounded">
                                    #<?= str_pad((string)$booking['id'], 5, '0', STR_PAD_LEFT) ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <div class="text-sm font-bold text-[var(--header)] mb-0.5 guest-name">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['guest_name']) ?>
                                </div>
                                <div class="text-[11px] font-black text-[var(--text)] uppercase tracking-[0.1em] flex items-center">
                                    <?php 
                                        $statusColor = 'bg-[var(--warning)]';
                                        if ($booking['status'] === 'confirmed' || $booking['status'] === 'checked_in') $statusColor = 'bg-[var(--success)]';
                                        if ($booking['status'] === 'cancelled') $statusColor = 'bg-[var(--danger)]';
                                    ?>
                                    <span class="w-2 h-2 rounded-full <?= $statusColor ?> mr-2 shadow-sm"></span>
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['status']) ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <div class="text-sm text-[var(--header)] font-bold mb-0.5">
                                    <?= date('M j', strtotime($booking['check_in'])) ?> &rarr; <?= date('M j', strtotime($booking['check_out'])) ?>
                                </div>
                                <div class="text-[11px] font-bold text-[var(--text)] opacity-60">
                                    <?= (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400 ?> Nights
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <div class="text-sm font-bold text-[var(--header)] mb-1">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['room_name'] ?? 'Unassigned Category') ?>
                                </div>
                                
                                <?php if(!empty($booking['assigned_room_id'])): ?>
                                    <button type="button" onclick="openAssignModal(<?= (int)$booking['id'] ?>, <?= (int)($booking['room_type_id'] ?? 0) ?>)" class="px-2.5 py-1 bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] text-[10px] font-black rounded-lg uppercase tracking-widest transition-all hover:scale-105 active:scale-95 flex items-center mt-1 w-fit shadow-sm">
                                        Room <?= \Syncro\Security\SecurityManager::sanitizeOutput((string)($booking['physical_room_number'] ?? '')) ?>
                                        <svg class="w-3.5 h-3.5 ml-1.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="openAssignModal(<?= (int)$booking['id'] ?>, <?= (int)($booking['room_type_id'] ?? 0) ?>)" class="text-[10px] font-black uppercase tracking-widest text-[var(--header)] bg-[var(--theme)] px-2.5 py-1 rounded-lg hover:bg-[var(--theme)]/80 transition-all hover:scale-105 active:scale-95 mt-1 inline-block shadow-sm">
                                        Assign Room
                                    </button>
                                <?php endif; ?>

                                <div class="text-[10px] mt-2 font-black uppercase tracking-[0.2em] <?= strtolower($booking['source'] ?? '') === 'direct' ? 'text-[var(--success)]' : 'text-[var(--theme2)]' ?>">
                                    Via <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['source'] ?? 'Unknown') ?>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap align-top">
                                <form action="<?= base_url('/user/bookings/payment') ?>" method="POST" class="mb-2">
                                    <?= csrf_field() ?>">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    
                                    <select name="payment_status" onchange="this.form.submit()" class="text-[10px] font-black uppercase tracking-[0.2em] border-2 border-[var(--border)] rounded-lg px-2.5 py-1.5 outline-none cursor-pointer focus:ring-4 focus:ring-[var(--theme2)]/10 focus:border-[var(--theme2)] transition-all <?= $booking['payment_status'] === 'paid' ? 'bg-[var(--success)]/10 text-[var(--success)] border-[var(--success)]/30' : 'bg-[var(--danger)]/10 text-[var(--danger)] border-[var(--danger)]/30' ?>">
                                        <option value="pending" <?= $booking['payment_status'] !== 'paid' ? 'selected' : '' ?>>
                                            Pending
                                        </option>
                                        <option value="paid" <?= $booking['payment_status'] === 'paid' ? 'selected' : '' ?>>
                                            Paid
                                        </option>
                                    </select>
                                </form>
                                
                                <a href="<?= base_url() ?>/user/invoice?id=<?= $booking['id'] ?>" class="text-[10px] font-bold text-[var(--theme2)] hover:text-[var(--header)] inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    View Folio
                                </a>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right align-top">
                                <form action="<?= base_url('/user/bookings/status') ?>" method="POST" class="flex justify-end">
                                    <?= csrf_field() ?>">
                                    <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">
                                    
                                    <select name="status" onchange="this.form.submit()" class="text-xs font-bold border border-[var(--border)] rounded px-2 py-1.5 outline-none cursor-pointer focus:ring-2 focus:ring-[var(--theme2)] text-[var(--header)] bg-[var(--light)]">
                                        <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="checked_in" <?= $booking['status'] === 'checked_in' ? 'selected' : '' ?>>Check-In</option>
                                        <option value="checked_out" <?= $booking['status'] === 'checked_out' ? 'selected' : '' ?>>Check-Out</option>
                                        <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>

                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="walkInModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-[var(--header)] bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="document.getElementById('walkInModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-[var(--white)] rounded text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-[var(--border)]">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="text-lg leading-6 font-bold text-[var(--header)] flex items-center" id="modal-title">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Create Walk-in Booking
                </h3>
                <button type="button" onclick="document.getElementById('walkInModal').classList.add('hidden')" class="text-[var(--text)] hover:text-[var(--header)]">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="<?= base_url('/user/bookings') ?>" method="POST">
                <div class="px-6 py-6 space-y-4">
                    <?= csrf_field() ?>">
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Guest Full Name</label>
                        <input type="text" name="guest_name" required class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-medium text-[var(--text)]">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Email (Optional)</label>
                            <input type="email" name="guest_email" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-medium text-[var(--text)]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Phone (Optional)</label>
                            <input type="text" name="guest_phone" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-medium text-[var(--text)]">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Check-in</label>
                            <input type="date" name="check_in" required value="<?= date('Y-m-d') ?>" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-mono text-[var(--text)]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Check-out</label>
                            <input type="date" name="check_out" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-mono text-[var(--text)]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Room Category</label>
                        <select name="room_type_id" required class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-medium cursor-pointer text-[var(--text)]">
                            <?php foreach($rooms ?? [] as $rt): ?>
                                <option value="<?= $rt['id'] ?>"><?= \Syncro\Security\SecurityManager::sanitizeOutput($rt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="bg-[var(--light)] px-6 py-4 border-t border-[var(--border)] flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('walkInModal').classList.add('hidden')" class="bg-[var(--white)] border border-[var(--border)] text-[var(--text)] font-bold py-2 px-4 rounded shadow-sm hover:text-[var(--header)] text-sm">Cancel</button>
                    <button type="submit" class="bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2 px-6 rounded shadow transition-colors text-sm uppercase tracking-wider">Confirm Walk-in</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="assignModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-[var(--header)] bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="document.getElementById('assignModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-[var(--white)] rounded text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-[var(--border)]">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="text-lg leading-6 font-bold text-[var(--header)]">Assign Physical Room</h3>
                <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="text-[var(--text)] hover:text-[var(--header)]">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="<?= base_url('/user/bookings/assign') ?>" method="POST">
                <div class="px-6 py-6 space-y-4">
                    <?= csrf_field() ?>">
                    <input type="hidden" name="booking_id" id="assign_booking_id" value="">
                    
                    <div>
                        <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-1">Select Available Room</label>
                        <select name="physical_room_id" id="assign_room_select" required class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors font-medium cursor-pointer text-[var(--text)]">
                            <option value="">-- Choose a Room --</option>
                            <?php foreach($physicalRooms ?? [] as $pr): ?>
                                <option value="<?= $pr['id'] ?>" data-type="<?= $pr['room_type_id'] ?>" class="hidden">Room <?= \Syncro\Security\SecurityManager::sanitizeOutput($pr['room_number']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-[var(--text)] mt-2">Only rooms matching this booking's category are shown.</p>
                    </div>
                </div>
                
                <div class="bg-[var(--light)] px-6 py-4 border-t border-[var(--border)] flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" class="bg-[var(--white)] border border-[var(--border)] text-[var(--text)] font-bold py-2 px-4 rounded shadow-sm hover:text-[var(--header)] text-sm">Cancel</button>
                    <button type="submit" class="bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2 px-6 rounded shadow transition-colors text-sm uppercase tracking-wider">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(bookingId, roomTypeId) {
    document.getElementById('assign_booking_id').value = bookingId;
    const select = document.getElementById('assign_room_select');
    select.value = ''; 
    
    let matchCount = 0;

    Array.from(select.options).forEach(opt => {
        if(opt.value === "") return;
        
        // Disable options that don't match so they aren't accidentally submitted
        if(opt.getAttribute('data-type') == roomTypeId || roomTypeId == 0) {
            opt.classList.remove('hidden');
            opt.style.display = 'block'; 
            opt.disabled = false;
            matchCount++;
        } else {
            opt.classList.add('hidden');
            opt.style.display = 'none';
            opt.disabled = true;
        }
    });
    
    if (matchCount === 0) {
        alert("Warning: You don't have any physical rooms (e.g., 101, 102) created for this category yet! Please go to 'Property Rooms' to add physical rooms.");
    }
    
    document.getElementById('assignModal').classList.remove('hidden');
}

// Live Search Filter Logic
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    if(searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody#bookingTableBody tr.search-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const counterElement = document.getElementById('recordCount');
            if (counterElement) {
                counterElement.textContent = visibleCount + ' Total Records';
            }
        });
    }
});
</script>