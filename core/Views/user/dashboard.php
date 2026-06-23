<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <?php if (isset($_GET['welcome_saas'])): ?>
        <div class="bg-[var(--theme2)] rounded p-6 sm:p-8 mb-8 text-[var(--white)] shadow-md flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden border border-[var(--header)]">
            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-[var(--white)] rounded-full opacity-10 blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
                <h2 class="text-2xl font-bold mb-2 tracking-tight flex items-center text-[var(--white)]">
                    Welcome to Syncro! <span class="ml-2 text-3xl">🎉</span>
                </h2>
                <p class="text-[var(--light)] text-sm font-medium opacity-80">Your payment was successful and your Enterprise License is now active. Let's get your property online.</p>
            </div>
            <a href="<?= base_url('/user/rooms') ?>" class="relative z-10 bg-[var(--theme)] text-[var(--header)] font-extrabold px-6 py-3 rounded shadow hover:bg-[var(--white)] hover:shadow-lg transition-all text-sm whitespace-nowrap uppercase tracking-wider">
                Set up your first room
            </a>
        </div>
    <?php endif; ?>

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Property Overview</h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Real-time metrics and front-desk operations for <span class="text-[var(--header)] font-bold"><?= date('F j, Y') ?></span>.</p>
        </div>
        
        <div class="flex items-center gap-2 text-xs font-bold text-[var(--header)] bg-[var(--light)] border border-[var(--border)] px-3 py-1.5 rounded shadow-sm uppercase tracking-widest" title="Data is cached for 15 minutes to ensure lightning-fast load times.">
            <svg class="w-4 h-4 <?= isset($cachedTime) ? 'text-green-600' : 'text-[var(--theme)]' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <?= isset($cachedTime) ? 'Cached: ' . htmlspecialchars((string)$cachedTime) : 'Live Data' ?>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-[var(--header)] rounded shadow border border-[var(--theme2)] p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-[var(--theme)] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
            <div class="flex justify-between items-start relative z-10 mb-2">
                <h3 class="text-[11px] font-bold text-[var(--light)] opacity-70 uppercase tracking-widest">30-Day Revenue</h3>
                <div class="bg-[var(--white)]/10 p-1.5 rounded text-[var(--theme)] border border-[var(--white)]/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <p class="text-3xl font-black text-[var(--white)] tracking-tighter relative z-10">₹<?= number_format((float)($totalRevenue ?? 0)) ?></p>
            <p class="text-[11px] text-[var(--theme)] font-bold mt-2 relative z-10 uppercase tracking-wider">
                Rooms (₹<?= number_format((float)($roomRevenue ?? 0)) ?>) + POS (₹<?= number_format((float)($ancillaryRevenue ?? 0)) ?>)
            </p>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-6 relative overflow-hidden group hover:border-[var(--theme2)] transition-colors">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--theme2)] rounded-l"></div>
            <h3 class="text-[11px] font-bold text-[var(--text)] uppercase tracking-widest mb-2 pl-2">ADR (Average Daily Rate)</h3>
            <p class="text-3xl font-black text-[var(--header)] tracking-tighter pl-2" id="kpi-adr">₹0</p>
            <p class="text-[11px] text-[var(--theme2)] font-bold mt-2 pl-2 bg-[var(--light)] inline-block px-2 py-0.5 rounded">Average price per room</p>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-6 relative overflow-hidden group hover:border-[var(--theme)] transition-colors">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--theme)] rounded-l"></div>
            <h3 class="text-[11px] font-bold text-[var(--text)] uppercase tracking-widest mb-2 pl-2">RevPAR</h3>
            <p class="text-3xl font-black text-[var(--header)] tracking-tighter pl-2" id="kpi-revpar">₹0</p>
            <p class="text-[11px] text-[var(--theme)] font-bold mt-2 pl-2 bg-[var(--light)] inline-block px-2 py-0.5 rounded">Revenue Per Available Room</p>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-6 relative overflow-hidden group hover:border-[var(--text)] transition-colors">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--text)] rounded-l"></div>
            <h3 class="text-[11px] font-bold text-[var(--text)] uppercase tracking-widest mb-2 pl-2">In-House</h3>
            <p class="text-3xl font-black text-[var(--header)] tracking-tighter pl-2"><?= $inHouse ?? 0 ?> <span class="text-sm text-[var(--text)] font-medium" id="kpi-occ">/ <?= $occupancyRate ?? 0 ?>%</span></p>
            <p class="text-[11px] text-[var(--text)] font-bold mt-2 pl-2 bg-[var(--light)] inline-block px-2 py-0.5 rounded">Occupied rooms (Occupancy)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                    <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                        <h3 class="font-bold text-[var(--header)] flex items-center">
                            <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                            7-Day Revenue Trend
                        </h3>
                    </div>
                    <div class="p-6">
                        <canvas id="revenueChart" height="120"></canvas>
                    </div>
                </div>

                <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                    <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                        <h3 class="font-bold text-[var(--header)] flex items-center">
                            <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                            Revenue by Source
                        </h3>
                    </div>
                    <div class="p-6 flex justify-center">
                        <canvas id="sourceChart" height="120" class="max-h-[200px]"></canvas>
                    </div>
                </div>
            </div>
                </div>
            </div>
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                    <h3 class="font-bold text-[var(--header)] flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Front Desk: Today's Arrivals
                    </h3>
                    <div class="flex gap-2">
                        <a href="<?= base_url('/export/bookings') ?>" class="text-[11px] text-[var(--text)] font-bold uppercase tracking-widest hover:text-[var(--header)] transition-colors bg-[var(--white)] px-3 py-1.5 rounded border border-[var(--border)] inline-flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export CSV
                        </a>
                        <a href="<?= base_url('/user/bookings?status=confirmed') ?>" class="text-[11px] text-[var(--theme2)] font-bold uppercase tracking-widest hover:text-[var(--header)] transition-colors bg-[var(--white)] px-3 py-1.5 rounded border border-[var(--border)]">View Master Ledger &rarr;</a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--border)]">
                        <thead class="bg-[var(--white)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Guest Name</th>
                                <th class="px-6 py-4 text-left text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Room Type</th>
                                <th class="px-6 py-4 text-right text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Payment Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]">
                            <?php if(empty($arrivalList)): ?>
                                <tr><td colspan="3" class="px-6 py-12 text-center text-sm font-medium text-[var(--text)]">No arrivals scheduled for today.</td></tr>
                            <?php else: ?>
                                <?php foreach($arrivalList as $arrival): ?>
                                <tr class="hover:bg-[var(--light)] transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?= base_url() ?>/user/invoice?id=<?= $arrival['id'] ?>" class="text-sm font-bold text-[var(--header)] group-hover:text-[var(--theme2)] transition-colors flex items-center">
                                            <?= htmlspecialchars((string)$arrival['guest_name']) ?>
                                            <svg class="w-3 h-3 ml-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text)] font-medium">
                                        <?= htmlspecialchars((string)$arrival['room_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <?php if($arrival['payment_status'] === 'paid'): ?>
                                            <span class="px-2.5 py-1 text-[10px] font-bold rounded uppercase tracking-widest bg-[var(--success)]/10 text-[var(--success)] border border-[var(--success)]/20">Paid In Full</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 text-[10px] font-bold rounded uppercase tracking-widest bg-[var(--danger)]/10 text-[var(--danger)] border border-[var(--danger)]/20">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                    <h3 class="font-bold text-[var(--header)] flex items-center">
                        <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Front Desk: Today's Departures
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[var(--border)]">
                        <thead class="bg-[var(--white)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Guest Name</th>
                                <th class="px-6 py-4 text-left text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Room</th>
                                <th class="px-6 py-4 text-right text-[11px] font-bold text-[var(--text)] uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]">
                            <?php if(empty($departureList)): ?>
                                <tr><td colspan="3" class="px-6 py-12 text-center text-sm font-medium text-[var(--text)]">No departures scheduled for today.</td></tr>
                            <?php else: ?>
                                <?php foreach($departureList as $departure): ?>
                                <tr class="hover:bg-[var(--light)] transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?= base_url() ?>/user/invoice?id=<?= $departure['id'] ?>" class="text-sm font-bold text-[var(--header)] group-hover:text-[var(--theme2)] transition-colors flex items-center">
                                            <?= htmlspecialchars((string)$departure['guest_name']) ?>
                                            <svg class="w-3 h-3 ml-2 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text)] font-medium">
                                        <?= htmlspecialchars((string)($departure['physical_room_name'] ?? 'Unassigned')) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <?php if($departure['payment_status'] === 'paid'): ?>
                                            <span class="text-xs font-bold text-[var(--success)]">₹0.00</span>
                                        <?php else: ?>
                                            <span class="text-xs font-bold text-[var(--danger)]">Collect Payment</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-4">
            <a href="<?= base_url('/user/calendar') ?>" class="block bg-[var(--theme2)] rounded shadow-sm border border-[var(--header)] p-6 text-[var(--white)] hover:shadow-md transition-all relative overflow-hidden group">
                <div class="absolute right-0 top-0 -mr-4 -mt-4 w-24 h-24 bg-[var(--white)] opacity-10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                <h3 class="text-lg font-bold mb-1 flex items-center relative z-10">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Visual Calendar
                </h3>
                <p class="text-[13px] text-[var(--light)] opacity-80 mb-4 font-medium relative z-10">Drag and drop reservations, assign physical rooms, and track occupancy.</p>
                <div class="text-[11px] font-bold uppercase tracking-widest flex items-center text-[var(--header)] bg-[var(--theme)] w-fit px-3 py-1.5 rounded border border-[var(--theme)] relative z-10">
                    Open Tape Chart <span class="ml-2">&rarr;</span>
                </div>
            </a>

            <div class="grid grid-cols-1 gap-4">
                <a href="<?= base_url('/user/rates') ?>" class="flex items-center bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-5 hover:border-[var(--theme2)] hover:shadow-md transition-all group">
                    <div class="w-10 h-10 rounded bg-[var(--light)] flex items-center justify-center text-[var(--theme2)] mr-4 group-hover:bg-[var(--theme2)] group-hover:text-[var(--white)] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-[var(--header)] font-bold text-sm mb-0.5">Rate Manager</h3>
                        <p class="text-[12px] text-[var(--text)] font-medium">Adjust pricing for upcoming dates.</p>
                    </div>
                </a>

                <a href="<?= base_url('/housekeeping/dashboard') ?>" class="flex items-center bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-5 hover:border-[var(--theme2)] hover:shadow-md transition-all group lg:hover:-translate-y-1 duration-300">
                    <div class="w-10 h-10 rounded bg-[var(--light)] flex items-center justify-center text-[var(--text)] mr-4 group-hover:bg-[var(--header)] group-hover:text-[var(--white)] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-[var(--header)] font-bold text-sm mb-0.5">Housekeeping</h3>
                        <p class="text-[12px] text-[var(--text)] font-medium">Track dirty/clean room statuses.</p>
                    </div>
                </a>

                <a href="<?= base_url('/crm/directory') ?>" class="flex items-center bg-[var(--white)] rounded shadow-sm border border-[var(--border)] p-5 hover:border-[var(--theme2)] hover:shadow-md transition-all group lg:hover:-translate-y-1 duration-300">
                    <div class="w-10 h-10 rounded bg-[var(--light)] flex items-center justify-center text-[var(--text)] mr-4 group-hover:bg-[var(--header)] group-hover:text-[var(--white)] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-[var(--header)] font-bold text-sm mb-0.5">Guest CRM</h3>
                        <p class="text-[12px] text-[var(--text)] font-medium">LTV directory & marketing.</p>
                    </div>
                </a>
            </div>

            <!-- OTA Sync Pulse HUD -->
            <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
                <div class="bg-[var(--light)] px-5 py-4 border-b border-[var(--border)] flex justify-between items-center">
                    <h3 class="text-xs font-black text-[var(--header)] uppercase tracking-widest flex items-center">
                        <span class="relative flex h-2 w-2 mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--success)] opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--success)]"></span>
                        </span>
                        Channel Sync Pulse
                    </h3>
                    <span class="text-[10px] font-bold text-[var(--text)] opacity-60 uppercase tracking-tighter">Live Monitor</span>
                </div>
                <div class="p-4 space-y-4">
                    <?php if (empty($otaMappings)): ?>
                        <div class="text-center py-4">
                            <p class="text-[11px] text-[var(--text)] font-medium italic">No active channel mappings found.</p>
                            <a href="<?= base_url('/user/channel-manager') ?>" class="mt-2 inline-block text-[10px] font-bold text-[var(--theme2)] uppercase border-b border-dotted border-[var(--theme2)]">Configure OTAs</a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($otaMappings, 0, 4) as $mapping): ?>
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded bg-[var(--light)] border border-[var(--border)] flex items-center justify-center mr-3 group-hover:bg-[var(--white)] transition-colors">
                                        <span class="text-[10px] font-black text-[var(--header)]"><?= substr($mapping['channel_name'], 0, 1) ?></span>
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-bold text-[var(--header)] leading-none mb-1"><?= htmlspecialchars($mapping['channel_name']) ?></p>
                                        <p class="text-[9px] text-[var(--text)] font-medium opacity-70 uppercase tracking-tighter">
                                            <?= $mapping['last_sync_time'] ? date('H:i', strtotime($mapping['last_sync_time'])) . ' synced' : 'Waiting...' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-widest <?= $mapping['sync_status'] === 'active' ? 'bg-[var(--success)]/10 text-[var(--success)]' : 'bg-[var(--danger)]/10 text-[var(--danger)]' ?>">
                                    <?= $mapping['sync_status'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($otaMappings) > 4): ?>
                            <a href="<?= base_url('/user/channel-manager') ?>" class="block text-center pt-2 text-[10px] font-bold text-[var(--theme2)] uppercase tracking-widest opacity-70 hover:opacity-100 transition-opacity">
                                + <?= count($otaMappings) - 4 ?> More Channels
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartDates ?? []) ?>,
                    datasets: [{
                        label: 'Revenue (₹)',
                        data: <?= json_encode($chartRevenue ?? []) ?>,
                        borderColor: 'var(--header)',
                        backgroundColor: 'rgba(0, 51, 102, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Fetch Advanced Analytics
        fetch('/api/hotel/analytics')
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    const kpis = res.data.kpis;
                    document.getElementById('kpi-adr').innerText = '₹' + kpis.adr;
                    document.getElementById('kpi-revpar').innerText = '₹' + kpis.revpar;
                    document.getElementById('kpi-occ').innerText = '/ ' + kpis.occupancy_rate + '%';

                    const sources = res.data.revenue_sources;
                    if(sources.length > 0) {
                        const sCtx = document.getElementById('sourceChart');
                        new Chart(sCtx.getContext('2d'), {
                            type: 'doughnut',
                            data: {
                                labels: sources.map(s => s.source.toUpperCase()),
                                datasets: [{
                                    data: sources.map(s => s.revenue),
                                    backgroundColor: ['var(--header)', 'var(--success)', 'var(--theme)', 'var(--theme2)', 'var(--theme2)'],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
                                }
                            }
                        });
                    }
                }
            });
    });
</script>