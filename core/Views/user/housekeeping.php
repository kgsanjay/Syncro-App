<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4">
        <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Housekeeping</h1>
        <p class="mt-1 text-sm text-[var(--text)] font-medium">Real-time status of all physical doors. Updates save instantly.</p>
    </div>

    <?php if(!empty($tickets)): ?>
        <div class="mb-8 bg-[var(--danger)]/10 border-l-4 border-[var(--danger)] p-4 rounded-r shadow-sm">
            <h2 class="text-lg font-bold text-[var(--danger)] mb-2">Active Maintenance Tickets</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach($tickets as $ticket): ?>
                    <div class="bg-[var(--white)] p-4 rounded shadow-sm border border-[var(--danger)]/20">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold text-[var(--header)]">Room <?= \Syncro\Security\SecurityManager::sanitizeOutput($ticket['room_number']) ?></span>
                            <span class="text-xs bg-[var(--danger)] text-[var(--white)] px-2 py-0.5 rounded uppercase font-bold"><?= \Syncro\Security\SecurityManager::sanitizeOutput($ticket['status']) ?></span>
                        </div>
                        <p class="text-sm text-[var(--text)] mb-3"><?= nl2br(\Syncro\Security\SecurityManager::sanitizeOutput($ticket['issue_description'])) ?></p>
                        <div class="flex justify-between items-center text-xs text-[var(--text)]/80">
                            <span>Reported by: <?= \Syncro\Security\SecurityManager::sanitizeOutput($ticket['reporter_name'] ?? 'System') ?></span>
                            <form method="POST" action="/user/housekeeping/ticket/resolve" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                <button type="submit" class="text-[var(--success)] font-bold hover:underline">Mark Resolved</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if(empty($rooms)): ?>
        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-12 text-center">
            <p class="text-[var(--text)] font-medium">No physical rooms found. Please configure rooms first.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach($rooms as $room): ?>
                <?php 
                    // Strictly mapped to mandated semantic tokens for "Ivy League" clarity
                    $borderColor = 'border-[var(--border)]';
                    $badgeBg = 'bg-[var(--light)] text-[var(--text)]';
                    $isCleaning = false;
                    
                    if ($room['housekeeping_status'] === 'clean') {
                        $borderColor = 'border-[var(--success)]/40 ring-1 ring-[var(--success)]/20';
                        $badgeBg = 'bg-[var(--success)] text-[var(--white)]';
                    } elseif ($room['housekeeping_status'] === 'dirty') {
                        $borderColor = 'border-[var(--danger)]/40 ring-1 ring-[var(--danger)]/20';
                        $badgeBg = 'bg-[var(--danger)] text-[var(--white)]';
                    } elseif ($room['housekeeping_status'] === 'cleaning') {
                        $borderColor = 'border-[var(--theme)]/60 ring-1 ring-[var(--theme)]/40';
                        $badgeBg = 'bg-[var(--theme)] text-[var(--header)]';
                        $isCleaning = true;
                    }
                ?>
                <div class="bg-[var(--white)] rounded-xl shadow-sm border-2 <?= $borderColor ?> overflow-hidden transition-all duration-500 flex flex-col relative group hover:shadow-md lg:hover:-translate-y-1" id="room-card-<?= $room['id'] ?>">
                    
                    <div class="<?= $badgeBg ?> px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-center border-b border-[var(--border)] transition-all duration-300 <?= $isCleaning ? 'animate-pulse' : '' ?>" id="room-badge-<?= $room['id'] ?>">
                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($room['housekeeping_status']) ?>
                    </div>
                    
                    <div class="p-6 text-center flex-1 flex flex-col justify-center">
                        <span class="text-3xl font-black text-[var(--header)] tracking-tighter group-hover:scale-110 transition-transform duration-500">
                            <?= \Syncro\Security\SecurityManager::sanitizeOutput($room['room_number']) ?>
                        </span>
                        <span class="text-[10px] text-[var(--text)] font-bold mt-2 uppercase opacity-60 tracking-wider truncate w-full px-1">
                            <?= \Syncro\Security\SecurityManager::sanitizeOutput($room['type_name']) ?>
                        </span>
                        
                        <?php if($_SESSION['role'] === 'hotel_admin'): ?>
                            <form method="POST" action="/user/housekeeping/assign" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <select name="staff_id" onchange="this.form.submit()" class="w-full bg-[var(--light)] border border-[var(--border)] text-[var(--text)] text-xs rounded px-1 py-1 outline-none">
                                    <option value="">Unassigned</option>
                                    <?php foreach($staffMembers as $staff): ?>
                                        <option value="<?= $staff['id'] ?>" <?= $room['assigned_housekeeper_id'] == $staff['id'] ? 'selected' : '' ?>><?= \Syncro\Security\SecurityManager::sanitizeOutput($staff['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: ?>
                            <div class="mt-4 text-[10px] text-[var(--theme2)] font-bold truncate">
                                <?= $room['housekeeper_name'] ? 'Assigned: ' . \Syncro\Security\SecurityManager::sanitizeOutput($room['housekeeper_name']) : 'Unassigned' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-2 bg-[var(--light)] border-t border-[var(--border)]">
                        <select onchange="updateStatus(<?= $room['id'] ?>, this.value)" class="w-full bg-[var(--white)] border border-[var(--border)] text-[var(--header)] text-xs font-bold rounded px-2 py-1.5 outline-none cursor-pointer focus:ring-2 focus:ring-[var(--theme2)] text-center appearance-none mb-2">
                            <option value="clean" <?= $room['housekeeping_status'] === 'clean' ? 'selected' : '' ?>>Clean</option>
                            <option value="dirty" <?= $room['housekeeping_status'] === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                            <option value="cleaning" <?= $room['housekeeping_status'] === 'cleaning' ? 'selected' : '' ?>>Cleaning</option>
                            <option value="maintenance" <?= $room['housekeeping_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                        <button onclick="reportIssue(<?= $room['id'] ?>, '<?= \Syncro\Security\SecurityManager::sanitizeOutput($room['room_number']) ?>')" class="w-full text-xs text-[var(--danger)] hover:underline font-bold py-1">Report Issue</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    async function updateStatus(roomId, newStatus) {
        // Securely fetch CSRF token from the layout head
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const formData = new FormData();
        formData.append('room_id', roomId);
        formData.append('status', newStatus);
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('/ajax/housekeeping/update', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if(data.success) {
                // Instantly update UI without reloading the page
                const card = document.getElementById(`room-card-${roomId}`);
                const badge = document.getElementById(`room-badge-${roomId}`);
                
                // Reset classes
                card.className = "bg-[var(--white)] rounded-lg shadow-sm border-2 overflow-hidden transition-all duration-300 flex flex-col relative";
                badge.className = "px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-center border-b border-[var(--border)] transition-colors";
                
                badge.innerText = newStatus;
                
                // Apply strict architectural classes based on new state
                if(newStatus === 'clean') {
                    card.classList.add('border-[var(--success)]/40', 'ring-1', 'ring-[var(--success)]/20');
                    badge.classList.add('bg-[var(--success)]', 'text-[var(--white)]');
                } else if (newStatus === 'dirty') {
                    card.classList.add('border-[var(--danger)]/40', 'ring-1', 'ring-[var(--danger)]/20');
                    badge.classList.add('bg-[var(--danger)]', 'text-[var(--white)]');
                } else if (newStatus === 'cleaning') {
                    card.classList.add('border-[var(--theme)]/60', 'ring-1', 'ring-[var(--theme)]/40');
                    badge.classList.add('bg-[var(--theme)]', 'text-[var(--header)]', 'animate-pulse');
                } else {
                    card.classList.add('border-[var(--border)]');
                    badge.classList.add('bg-[var(--light)]', 'text-[var(--text)]');
                }
            } else {
                alert("Failed to update status. Please check your connection.");
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            alert("Network error updating status.");
        }
    }

    function reportIssue(roomId, roomNumber) {
        const desc = prompt(`Report a maintenance issue for Room ${roomNumber}:`);
        if (desc && desc.trim().length > 0) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user/housekeeping/ticket';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const roomInput = document.createElement('input');
            roomInput.type = 'hidden';
            roomInput.name = 'room_id';
            roomInput.value = roomId;
            
            const descInput = document.createElement('input');
            descInput.type = 'hidden';
            descInput.name = 'description';
            descInput.value = desc;
            
            form.appendChild(csrfInput);
            form.appendChild(roomInput);
            form.appendChild(descInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>