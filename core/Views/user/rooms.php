<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Property Rooms & Inventory</h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Configure sales categories and manage physical door numbers.</p>
        </div>
    </div>

    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-black text-[var(--header)] uppercase tracking-widest text-xs">1. Room Categories (Sales Profiles)</h2>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-6 h-fit">
                <h3 class="text-[11px] font-bold text-[var(--header)] border-b border-[var(--border)] pb-3 mb-4 uppercase tracking-widest flex items-center">
                    <svg class="w-4 h-4 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Create Room Type
                </h3>
                <form action="<?= base_url('/user/rooms') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?= csrf_field() ?>">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Display Name</label>
                        <input type="text" name="name" required placeholder="e.g., Ocean View Suite" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-medium transition-colors">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Local Code</label>
                            <input type="text" name="local_room_code" required placeholder="OVS" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-mono transition-colors uppercase">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Base Price (₹)</label>
                            <input type="number" name="base_price" required min="1" placeholder="4500" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-black transition-colors">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Description (Booking Engine)</label>
                        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--text)] transition-colors"></textarea>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Amenities</label>
                        <input type="text" name="amenities" placeholder="e.g., Free WiFi, AC, Minibar, Balcony" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-medium transition-colors">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Upload Room Image (JPG/PNG)</label>
                        <input type="file" name="room_image" accept="image/jpeg, image/png, image/webp" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm outline-none bg-[var(--white)] text-[var(--text)] transition-colors file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[var(--light)] file:text-[var(--theme2)] hover:file:bg-[var(--border)]">
                    </div>

                    <button type="submit" class="w-full bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2.5 rounded transition-colors text-xs uppercase tracking-widest shadow-sm mt-2">
                        Save Category
                    </button>
                </form>
            </div>

            <div class="xl:col-span-2 bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--border)]">
                        <thead class="bg-[var(--light)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Room Type</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Base Rate</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Physical Qty</th>
                                <th class="px-6 py-4 text-right text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]">
                            <?php if(empty($rooms)): ?>
                                <tr><td colspan="4" class="px-6 py-10 text-center text-[var(--text)] text-sm font-medium">No room types configured yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($rooms as $type): ?>
                                    <?php 
                                        // Count physical rooms for this type
                                        $qty = 0;
                                        foreach($physicalRooms as $pr) {
                                            if ($pr['room_type_id'] === $type['id']) $qty++;
                                        }
                                    ?>
                                <tr class="hover:bg-[var(--light)] transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-[var(--header)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($type['name']) ?></div>
                                        <div class="text-[10px] font-mono font-bold text-[var(--text)] mt-0.5 uppercase tracking-wider">Code: <?= \Syncro\Security\SecurityManager::sanitizeOutput($type['local_room_code']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-black text-[var(--theme2)]">₹<?= number_format((float)$type['base_price']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-[10px] font-black rounded border bg-[var(--white)] text-[var(--header)] <?= $qty === 0 ? 'border-[var(--theme)]' : 'border-[var(--border)]' ?>">
                                            <?= $qty ?> Units
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form action="<?= base_url('/user/rooms/delete') ?>" method="POST" onsubmit="return confirm('Delete this room type? This will fail if bookings exist.');" class="inline-block">
                                            <?= csrf_field() ?>">
                                            <input type="hidden" name="room_type_id" value="<?= (int)$type['id'] ?>">
                                            <button type="submit" class="text-[10px] uppercase tracking-widest text-[var(--theme)] bg-[var(--white)] border border-[var(--border)] hover:bg-[var(--header)] hover:border-[var(--header)] px-3 py-1.5 rounded transition-colors font-bold shadow-sm">
                                                Delete
                                            </button>
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
    </div>

    <?php if(!empty($rooms)): ?>
    <div>
        <div class="flex items-center justify-between mb-4 mt-12">
            <h2 class="text-lg font-black text-[var(--header)] uppercase tracking-widest text-xs">2. Physical Inventory (Front Desk / Housekeeping)</h2>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-6 h-fit">
                <h3 class="text-[11px] font-bold text-[var(--header)] border-b border-[var(--border)] pb-3 mb-4 uppercase tracking-widest flex items-center">
                    <svg class="w-4 h-4 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                    Add Door Number
                </h3>
                <form action="<?= base_url('/user/rooms/physical') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <?= csrf_field() ?>">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Assign to Room Type</label>
                        <select name="room_type_id" required class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-bold transition-colors">
                            <option value="">-- Select Category --</option>
                            <?php foreach($rooms as $type): ?>
                                <option value="<?= (int)$type['id'] ?>"><?= \Syncro\Security\SecurityManager::sanitizeOutput($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Door / Room Number</label>
                        <input type="text" name="room_number" required placeholder="e.g., 101 or 102A" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-mono font-bold transition-colors">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-wider mb-1">Specific Room Image (Optional)</label>
                        <input type="file" name="physical_image" accept="image/jpeg, image/png, image/webp" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm outline-none bg-[var(--white)] text-[var(--text)] transition-colors file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[var(--light)] file:text-[var(--theme2)] hover:file:bg-[var(--border)]">
                    </div>

                    <button type="submit" class="w-full bg-[var(--white)] border border-[var(--border)] hover:bg-[var(--light)] hover:text-[var(--theme2)] text-[var(--header)] font-bold py-2.5 rounded transition-colors text-xs uppercase tracking-widest shadow-sm mt-2">
                        Add to Inventory
                    </button>
                </form>
            </div>

            <div class="xl:col-span-2 bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--border)]">
                        <thead class="bg-[var(--light)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Door Number</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Assigned Category</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Housekeeping</th>
                                <th class="px-6 py-4 text-right text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]">
                            <?php if(empty($physicalRooms)): ?>
                                <tr><td colspan="4" class="px-6 py-10 text-center text-[var(--text)] text-sm font-medium">No physical rooms added yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($physicalRooms as $pr): ?>
                                <tr class="hover:bg-[var(--light)] transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono font-black text-[var(--header)] flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-[var(--theme2)] opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                            <?= \Syncro\Security\SecurityManager::sanitizeOutput($pr['room_number']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-[var(--text)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($pr['type_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if($pr['housekeeping_status'] === 'clean'): ?>
                                            <span class="px-2.5 py-1 text-[10px] font-black rounded-lg border border-[var(--success)] bg-[var(--success)]/10 text-[var(--success)] uppercase tracking-widest shadow-sm">Clean</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 text-[10px] font-black rounded-lg border border-[var(--danger)] bg-[var(--danger)]/10 text-[var(--danger)] uppercase tracking-widest shadow-sm animate-pulse">Dirty</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form action="<?= base_url('/user/rooms/physical/delete') ?>" method="POST" onsubmit="return confirm('Remove this physical room?');" class="inline-block">
                                            <?= csrf_field() ?>">
                                            <input type="hidden" name="room_id" value="<?= (int)$pr['id'] ?>">
                                            <button type="submit" class="text-[10px] uppercase tracking-widest text-[var(--text)] hover:text-[var(--theme)] transition-colors font-bold flex items-center ml-auto">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                Drop
                                            </button>
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
    </div>
    <?php endif; ?>

</div>