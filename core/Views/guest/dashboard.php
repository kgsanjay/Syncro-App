<div class="max-w-4xl mx-auto py-10 px-4">
    
    <div class="mb-4 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-[var(--theme)]">My Bookings</h1>
        <a href="<?= base_url('/guest/logout') ?>" class="text-red-500 hover:text-red-700 font-bold text-sm">Logout</a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="bg-white rounded-lg shadow-sm p-10 text-center border border-[var(--border)]">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            <h2 class="text-xl font-bold text-[var(--header)] mb-2">No Bookings Found</h2>
            <p class="text-[var(--text)]">You haven't made any bookings yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($bookings as $booking): ?>
                <div class="bg-white rounded-lg shadow-sm border border-[var(--border)] overflow-hidden flex flex-col transition-shadow hover:shadow-md">
                    <div class="bg-[var(--theme)] text-white p-4">
                        <h2 class="text-xl font-bold truncate"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['property_name'] ?? $booking['hotel_name']) ?></h2>
                    </div>
                    <div class="p-6 flex-grow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Room</p>
                                <p class="font-medium text-[var(--header)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['room_name']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block px-2 py-1 text-xs font-bold rounded <?php 
                                    if ($booking['status'] === 'confirmed') echo 'bg-green-100 text-green-800';
                                    elseif ($booking['status'] === 'checked_in') echo 'bg-blue-100 text-blue-800';
                                    elseif ($booking['status'] === 'checked_out') echo 'bg-gray-100 text-gray-800';
                                    elseif ($booking['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                    else echo 'bg-yellow-100 text-yellow-800';
                                ?> uppercase tracking-wide">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($booking['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                            <div>
                                <p class="text-gray-500 font-bold mb-1">Check-in</p>
                                <p class="font-medium"><?= date('M j, Y', strtotime($booking['check_in'])) ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold mb-1">Check-out</p>
                                <p class="font-medium"><?= date('M j, Y', strtotime($booking['check_out'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 border-t border-[var(--border)] mt-auto">
                        <a href="<?= base_url() ?>/guest/portal?booking_ref=<?= $booking['id'] ?>" class="block w-full text-center bg-[var(--theme2)] hover:bg-[var(--theme)] text-white font-bold py-2 px-4 rounded transition-colors">
                            Manage Booking
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
