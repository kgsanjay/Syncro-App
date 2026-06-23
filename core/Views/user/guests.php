<?php declare(strict_types=1); ?>
<div class="bg-[var(--white)] rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
    <div class="p-6 border-b border-[var(--border)] flex justify-between items-center">
        <h3 class="text-xl font-bold text-[var(--header)]">Guest Directory</h3>
        <button type="button" onclick="document.getElementById('addGuestModal').showModal()" class="bg-[var(--theme2)] hover:opacity-90 text-[var(--white)] px-4 py-2 rounded font-medium transition-opacity cursor-pointer">
            + Manual Entry
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[var(--light)] text-[var(--text)] text-sm uppercase tracking-wider">
                    <th class="p-4 font-semibold border-b border-[var(--border)]">Guest Name</th>
                    <th class="p-4 font-semibold border-b border-[var(--border)]">Contact</th>
                    <th class="p-4 font-semibold border-b border-[var(--border)]">Total Stays</th>
                    <th class="p-4 font-semibold border-b border-[var(--border)]">Last Visit</th>
                    <th class="p-4 font-semibold border-b border-[var(--border)] text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                <?php if (empty($guests)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-[var(--text)]">No guest records found. Click "+ Manual Entry" to add one.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($guests as $g): ?>
                        <tr class="hover:bg-[var(--light)] transition-colors">
                            <td class="p-4 font-bold text-[var(--header)]">
                                <?= htmlspecialchars($g['full_name'] ?? 'Unknown') ?>
                            </td>
                            <td class="p-4 text-sm text-[var(--text)]">
                                <?= htmlspecialchars($g['email'] ?? 'N/A') ?><br>
                                <?= htmlspecialchars($g['phone'] ?? 'N/A') ?>
                            </td>
                            <td class="p-4 font-medium text-[var(--theme2)]">
                                <?= (int)($g['total_stays'] ?? 0) ?> Stays
                            </td>
                            <td class="p-4 text-sm text-[var(--text)]">
                                <?= !empty($g['last_visit_date']) ? date('M j, Y', strtotime($g['last_visit_date'])) : 'Never' ?>
                            </td>
                            <td class="p-4 text-right">
                                <a href="<?= base_url() ?>/user/guest-profile?id=<?= $g['id'] ?? 0 ?>" class="text-[var(--theme2)] hover:text-[var(--theme)] font-semibold text-sm transition-colors">
                                    View Profile &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<dialog id="addGuestModal" class="rounded-2xl shadow-2xl w-full max-w-md p-0 border border-[var(--border)] backdrop:bg-[var(--header)]/60 backdrop:backdrop-blur-md bg-[var(--white)] m-auto">
    <div class="p-8">
        <div class="flex justify-between items-center mb-6 border-b border-[var(--border)] pb-4">
            <h2 class="text-xl font-black text-[var(--header)] uppercase tracking-tight">Manual Guest Entry</h2>
            <button type="button" onclick="document.getElementById('addGuestModal').close()" class="text-[var(--text)] hover:text-[var(--danger)] text-2xl font-black leading-none outline-none cursor-pointer transition-colors">&times;</button>
        </div>
        
        <form action="<?= base_url('/user/guests') ?>" method="POST" class="m-0 space-y-5">
            <?= csrf_field() ?>">
            
            <div class="space-y-5 text-left">
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Full Identity Name</label>
                    <input type="text" name="guest_name" required placeholder="E.g. John Doe" class="w-full p-3.5 border-2 border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--theme2)] text-sm font-bold bg-[var(--light)] focus:bg-[var(--white)] transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Secure Email Address</label>
                    <input type="email" name="guest_email" placeholder="john@example.com" class="w-full p-3.5 border-2 border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--theme2)] text-sm font-bold bg-[var(--light)] focus:bg-[var(--white)] transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Primary Contact Phone</label>
                    <input type="text" name="guest_phone" placeholder="+91 9876543210" class="w-full p-3.5 border-2 border-[var(--border)] rounded-xl focus:outline-none focus:border-[var(--theme2)] text-sm font-bold bg-[var(--light)] focus:bg-[var(--white)] transition-all">
                </div>
            </div>
            
            <div class="pt-4">
                <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] px-6 py-4 rounded-xl font-black text-xs uppercase tracking-[0.2em] hover:bg-[var(--theme2)] transition-all shadow-xl hover:-translate-y-1 active:scale-95 cursor-pointer">
                    Commit to Directory
                </button>
            </div>
        </form>
    </div>
</dialog>