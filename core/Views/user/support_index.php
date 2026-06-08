<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Enterprise Helpdesk</h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Need assistance? Open a ticket and our support team will help you.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
            <div class="p-6 border-b border-[var(--border)] bg-[var(--light)]">
                <h2 class="text-base font-extrabold text-[var(--header)] flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Open New Ticket
                </h2>
            </div>
            
            <div class="p-6">
                <form action="/user/support" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    
                    <div>
                        <label class="block text-[11px] font-bold text-[var(--header)] uppercase tracking-wider mb-2">Subject</label>
                        <input type="text" name="subject" required placeholder="e.g., How do I map my MakeMyTrip rooms?" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors text-[var(--text)]">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[var(--header)] uppercase tracking-wider mb-2">Priority Level</label>
                        <select name="priority" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors text-[var(--text)] font-bold">
                            <option value="low">Low (General Question)</option>
                            <option value="normal" selected>Normal (Issue / Bug)</option>
                            <option value="high">High (Impacting Operations)</option>
                            <option value="urgent">Urgent (System Down / Channel Error)</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-bold text-[var(--header)] uppercase tracking-wider mb-2">Message Details</label>
                        <textarea name="message" required rows="5" placeholder="Please describe your issue in detail..." class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-colors text-[var(--text)] resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-[var(--header)] uppercase tracking-wider mb-2">Attach Image (Optional)</label>
                        <input type="file" name="attachment" accept="image/jpeg, image/png, image/webp" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm outline-none bg-[var(--white)] text-[var(--text)] file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[var(--light)] file:text-[var(--theme2)] hover:file:bg-[var(--border)] cursor-pointer">
                        <p class="text-[10px] text-[var(--text)] font-medium mt-1">JPG, PNG, or WEBP. Max 5MB.</p>
                    </div>

                    <button type="submit" class="w-full bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-3.5 rounded transition-all shadow-md hover:shadow-lg text-sm mt-4 uppercase tracking-wider">
                        Submit Support Ticket
                    </button>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2 bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden h-fit">
            <div class="p-6 border-b border-[var(--border)] bg-[var(--light)] flex items-center justify-between">
                <h3 class="text-base font-extrabold text-[var(--header)]">Your Support Tickets</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border)]">
                    <thead class="bg-[var(--white)]">
                        <tr>
                            <th class="px-6 py-4 text-left text-[11px] font-extrabold text-[var(--text)] uppercase tracking-wider">Ticket Info</th>
                            <th class="px-6 py-4 text-left text-[11px] font-extrabold text-[var(--text)] uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-[11px] font-extrabold text-[var(--text)] uppercase tracking-wider">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="bg-[var(--white)] divide-y divide-[var(--light)]">
                        <?php if (empty($tickets)): ?>
                            <tr><td colspan="3" class="px-6 py-12 text-center text-sm font-medium text-[var(--text)]">You have no open support tickets.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                            <tr class="hover:bg-[var(--light)] transition-colors group cursor-pointer" onclick="window.location.href='/user/support/view?id=<?= $t['id'] ?>'">
                                <td class="px-6 py-5">
                                    <div class="text-[14px] font-bold text-[var(--theme2)] group-hover:text-[var(--header)] transition-colors mb-1">
                                        <?= htmlspecialchars($t['subject']) ?>
                                    </div>
                                    <div class="text-[11px] text-[var(--text)] font-black uppercase tracking-[0.2em] opacity-50 flex items-center gap-3">
                                        Ticket #<?= str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT) ?>
                                        <?php if($t['priority'] === 'urgent'): ?>
                                            <span class="px-2 py-0.5 rounded bg-[var(--danger)]/10 text-[var(--danger)] border border-[var(--danger)]/20 animate-pulse">Urgent Priority</span>
                                        <?php elseif($t['priority'] === 'high'): ?>
                                            <span class="px-2 py-0.5 rounded bg-[var(--theme)] text-[var(--header)] border border-[var(--theme2)]/20">High Priority</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded bg-[var(--light)] text-[var(--text)] border border-[var(--border)]"><?= ucfirst($t['priority']) ?> Priority</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <?php 
                                        $statusClass = [
                                            'open' => 'bg-indigo-500/10 text-indigo-500 border-indigo-500/30 shadow-[0_0_8px_rgba(99,102,241,0.2)]',
                                            'in_progress' => 'bg-[var(--theme)]/10 text-[var(--header)] border-[var(--theme)] shadow-[0_0_8px_rgba(255,193,7,0.2)]',
                                            'waiting_on_customer' => 'bg-orange-500/10 text-orange-600 border-orange-500/30',
                                            'resolved' => 'bg-[var(--success)]/10 text-[var(--success)] border-[var(--success)]/30',
                                            'closed' => 'bg-[var(--text)]/10 text-[var(--text)] border-[var(--border)] opacity-60',
                                        ];
                                        $css = $statusClass[$t['status']] ?? $statusClass['open'];
                                    ?>
                                    <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg border transition-all <?= $css ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current mr-2 inline-block <?= in_array($t['status'], ['open', 'in_progress']) ? 'animate-pulse' : '' ?>"></span>
                                        <?= str_replace('_', ' ', $t['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="text-[12px] font-black text-[var(--header)] tracking-tight">
                                        <?= date('M j, Y', strtotime($t['updated_at'])) ?>
                                    </div>
                                    <div class="text-[10px] text-[var(--text)] font-black uppercase tracking-widest opacity-40 mt-1">
                                        <?= date('h:i A', strtotime($t['updated_at'])) ?>
                                    </div>
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