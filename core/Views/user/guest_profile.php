<?php declare(strict_types=1); ?>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= base_url('/user/guests') ?>" class="text-[var(--text)] hover:text-[var(--theme2)] font-bold text-sm flex items-center transition-colors">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Directory
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="md:col-span-1 bg-[var(--header)] text-white rounded-xl shadow-md p-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-[var(--theme)] rounded-full blur-3xl opacity-20"></div>
        
        <div class="flex items-center space-x-4 mb-6 relative z-10">
            <div class="w-16 h-16 rounded-full bg-[var(--theme)] text-[var(--header)] flex items-center justify-center font-black text-2xl">
                <?= substr(htmlspecialchars($guestInfo['guest_name'] ?? 'G'), 0, 1) ?>
            </div>
            <div>
                <h2 class="text-xl font-bold tracking-tight"><?= htmlspecialchars($guestInfo['guest_name'] ?? 'Unknown Guest') ?></h2>
            </div>
        </div>

        <div class="space-y-3 relative z-10 text-sm opacity-90">
            <p class="flex items-center"><span class="w-5 opacity-60">✉</span> <?= htmlspecialchars($guestInfo['guest_email'] ?? 'No Email') ?></p>
            <p class="flex items-center"><span class="w-5 opacity-60">📞</span> <?= htmlspecialchars($guestInfo['guest_phone'] ?? 'No Phone') ?></p>
        </div>
    </div>

    <div class="md:col-span-2 grid grid-cols-2 gap-6">
        <div class="bg-[var(--white)] p-8 rounded-2xl border border-[var(--border)] shadow-xl relative overflow-hidden group hover:scale-[1.02] transition-all">
            <div class="absolute top-0 right-0 w-24 h-24 bg-[var(--theme2)]/5 -mr-12 -mt-12 rounded-full z-0 group-hover:bg-[var(--theme2)]/10 transition-colors"></div>
            <div class="relative z-10">
                <span class="text-[10px] font-black text-[var(--text)] uppercase tracking-[0.2em] opacity-50 block mb-2">Lifetime Occupancy</span>
                <p class="text-4xl font-black text-[var(--header)] tracking-tighter"><?= $metrics['total_stays'] ?? 0 ?></p>
                <p class="text-[10px] text-[var(--theme2)] font-black mt-2 uppercase tracking-widest">Confirmed Stays</p>
            </div>
        </div>
        <div class="bg-[var(--white)] p-8 rounded-2xl border border-[var(--border)] shadow-xl relative overflow-hidden group hover:scale-[1.02] transition-all">
            <div class="absolute top-0 right-0 w-24 h-24 bg-[var(--success)]/5 -mr-12 -mt-12 rounded-full z-0 group-hover:bg-[var(--success)]/10 transition-colors"></div>
            <div class="relative z-10">
                <span class="text-[10px] font-black text-[var(--text)] uppercase tracking-[0.2em] opacity-50 block mb-2">Gross Contribution</span>
                <p class="text-4xl font-black text-[var(--success)] tracking-tighter">₹<?= number_format((float)$metrics['total_spend'], 2) ?></p>
                <p class="text-[10px] text-[var(--header)] font-black mt-2 uppercase tracking-widest">Total Folio Spend</p>
            </div>
        </div>
    </div>
</div>

<h3 class="font-black text-[var(--header)] mb-6 text-xl uppercase tracking-tight flex items-center">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    Immutable Stay History
</h3>
<div class="bg-[var(--white)] rounded-2xl shadow-xl border border-[var(--border)] overflow-hidden">
    <table class="w-full text-left text-sm border-collapse">
        <thead class="bg-[var(--light)] text-[10px] uppercase font-black text-[var(--header)] border-b-2 border-[var(--border)]">
            <tr>
                <th class="p-5 tracking-[0.2em]">Folio Path</th>
                <th class="p-5 tracking-[0.2em]">Temporal Period</th>
                <th class="p-5 tracking-[0.2em]">Asset Unit</th>
                <th class="p-5 tracking-[0.2em]">Ledger Status</th>
                <th class="p-5 text-right tracking-[0.2em]">Intelligence</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[var(--border)]">
            <?php foreach ($history as $stay): ?>
            <tr class="hover:bg-[var(--light)] transition-colors">
                <td class="p-5 font-black text-[var(--header)] text-xs tracking-widest">#<?= str_pad((string)$stay['id'], 5, '0', STR_PAD_LEFT) ?></td>
                <td class="p-5 font-bold text-[var(--text)]"><?= date('M j, Y', strtotime($stay['check_in'])) ?> &mdash; <?= date('M j, Y', strtotime($stay['check_out'])) ?></td>
                <td class="p-5 font-black text-[var(--theme2)] uppercase text-[11px]"><?= htmlspecialchars($stay['room_name'] ?? 'N/A') ?></td>
                <td class="p-5">
                    <?php if($stay['status'] === 'cancelled'): ?>
                        <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-[var(--danger)]/10 text-[var(--danger)] border border-[var(--danger)]/20 shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--danger)] mr-2 inline-block"></span> Cancelled
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-[var(--success)]/10 text-[var(--success)] border border-[var(--success)]/20 shadow-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--success)] mr-2 inline-block animate-pulse shadow-[0_0_8px_var(--success)]"></span> Completed
                        </span>
                    <?php endif; ?>
                </td>
                <td class="p-5 text-right">
                    <a href="<?= base_url() ?>/user/invoice?id=<?= $stay['id'] ?>" class="inline-flex items-center px-4 py-2 bg-[var(--header)] text-[var(--theme)] text-[10px] font-black uppercase tracking-widest rounded-lg hover:bg-[var(--theme2)] hover:text-[var(--white)] transition-all shadow-lg active:scale-95">
                        Archive &rarr;
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>