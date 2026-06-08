<?php declare(strict_types=1); ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <div class="mb-8">
        <h1 class="text-3xl font-black text-[var(--header)] tracking-tight">Promo Codes</h1>
        <p class="text-[var(--text)] mt-2">Create and manage direct booking discount codes.</p>
    </div>

    <?php if ($success === 'created'): ?>
        <div class="mb-8 bg-[var(--success)]/10 border-l-4 border-[var(--success)] text-[var(--success)] px-6 py-4 rounded-r-xl font-black text-xs uppercase tracking-widest shadow-sm">
            Promo code generated successfully.
        </div>
    <?php elseif ($success === 'deleted'): ?>
        <div class="mb-8 bg-[var(--theme2)]/10 border-l-4 border-[var(--theme2)] text-[var(--theme2)] px-6 py-4 rounded-r-xl font-black text-xs uppercase tracking-widest shadow-sm">
            Promo code removed securely.
        </div>
    <?php elseif ($error): ?>
        <div class="mb-8 bg-[var(--danger)]/10 border-l-4 border-[var(--danger)] text-[var(--danger)] px-6 py-4 rounded-r-xl font-black text-xs uppercase tracking-widest shadow-sm animate-shake">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
        
        <div class="lg:col-span-1">
            <div class="bg-[var(--white)] rounded-2xl shadow-sm border border-[var(--border)] overflow-hidden transition-all hover:shadow-xl group">
                <div class="bg-[var(--header)] px-8 py-5 border-b border-[var(--theme)]/30">
                    <h2 class="text-[var(--white)] font-black tracking-[0.2em] uppercase text-[10px] flex items-center">
                        <svg class="w-4 h-4 mr-3 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        New Privilege Code
                    </h2>
                </div>
                <div class="p-8">
                    <form action="/user/promo-codes/create" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                        
                        <div>
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-40">Code Identifier</label>
                            <input type="text" name="code" required placeholder="e.g. ELITE20" class="w-full px-5 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black uppercase tracking-[0.3em] focus:outline-none focus:border-[var(--theme2)] focus:ring-4 focus:ring-[var(--theme2)]/10 transition-all placeholder:tracking-normal placeholder:opacity-20 placeholder:font-medium">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-40">Benefit Type</label>
                                <select name="discount_type" class="w-full px-5 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black focus:outline-none focus:border-[var(--theme2)] focus:ring-4 focus:ring-[var(--theme2)]/10 transition-all cursor-pointer appearance-none">
                                    <option value="percentage">% Percentage</option>
                                    <option value="fixed">₹ Flat Amount</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-40">Net Value</label>
                                <input type="number" step="0.01" name="discount_value" required placeholder="20" class="w-full px-5 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black focus:outline-none focus:border-[var(--theme2)] focus:ring-4 focus:ring-[var(--theme2)]/10 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-40">Temporal Boundary</label>
                            <input type="date" name="valid_until" class="w-full px-5 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black focus:outline-none focus:border-[var(--theme2)] focus:ring-4 focus:ring-[var(--theme2)]/10 transition-all cursor-pointer">
                        </div>

                        <button type="submit" class="w-full bg-[var(--theme2)] text-[var(--white)] font-black uppercase tracking-[0.25em] text-[10px] py-5 rounded-xl hover:bg-[var(--header)] transition-all mt-4 shadow-lg hover:-translate-y-1 active:scale-95">
                            Authorize Code
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-[var(--white)] rounded-2xl shadow-sm border border-[var(--border)] overflow-hidden h-fit">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-[var(--light)] border-b border-[var(--border)]">
                                <th class="py-5 px-8 text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40">Privilege Identifiers</th>
                                <th class="py-5 px-8 text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40">Net Yield</th>
                                <th class="py-5 px-8 text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40">Boundary</th>
                                <th class="py-5 px-8 text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            <?php if (empty($promoCodes)): ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-[var(--text)] font-black uppercase text-[10px] tracking-widest opacity-30 italic">No privilege codes authorized.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($promoCodes as $promo): ?>
                                    <tr class="hover:bg-[var(--light)] transition-all group">
                                        <td class="py-6 px-8">
                                            <span class="bg-[var(--header)] text-[var(--theme)] font-black px-4 py-2 rounded-lg text-[11px] tracking-[0.3em] shadow-md border border-[var(--theme)]/20">
                                                <?= htmlspecialchars($promo['code']) ?>
                                            </span>
                                        </td>
                                        <td class="py-6 px-8 font-black text-[var(--header)] text-sm tracking-tighter">
                                            <?= $promo['discount_type'] === 'percentage' ? (float)$promo['discount_value'] . '%' : '₹' . number_format((float)$promo['discount_value'], 2) ?>
                                        </td>
                                        <td class="py-6 px-8 text-[var(--text)] text-[11px] font-black uppercase tracking-wider">
                                            <?php if ($promo['valid_until']): ?>
                                                <?= date('M j, Y', strtotime($promo['valid_until'])) ?>
                                                <?php if (strtotime($promo['valid_until']) < time()): ?>
                                                    <span class="ml-3 text-[9px] text-[var(--danger)] font-black uppercase px-2 py-0.5 border border-[var(--danger)]/30 bg-[var(--danger)]/10 rounded-full tracking-widest">(Expired)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="opacity-30">Perpetual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-6 px-8 text-right">
                                            <form action="/user/promo-codes/delete/<?= $promo['id'] ?>" method="POST" onsubmit="return confirm('Immediately revoke all system access for this privilege code?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                                                <button type="submit" class="text-[10px] font-black text-[var(--danger)] hover:text-[var(--header)] uppercase tracking-[0.2em] transition-all hover:underline decoration-2 underline-offset-4">
                                                    Revoke
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
</div>