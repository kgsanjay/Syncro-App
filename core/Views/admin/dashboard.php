<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">SaaS Command Center</h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">God-Eye View: Manage tenants, track GMV, and control broadcasts.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-[var(--text)] bg-[var(--white)] border border-[var(--border)] px-3 py-1.5 rounded shadow-sm">
            <svg class="w-4 h-4 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            Super Admin Session
        </div>
    </div>

    <?php if ($successMsg = \Syncro\Security\SessionManager::getFlash('success') ?? ($_GET['success'] ?? null)): ?>
        <div class="bg-[var(--light)] border-l-4 border-[var(--theme2)] p-4 mb-8 rounded shadow-sm flex items-center">
            <svg class="w-5 h-5 text-[var(--theme2)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="text-sm font-bold text-[var(--header)]"><?= htmlspecialchars(is_string($successMsg) ? $successMsg : 'Action completed successfully.') ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMsg = \Syncro\Security\SessionManager::getFlash('error') ?? ($_GET['error'] ?? null)): ?>
        <div class="bg-[var(--light)] border-l-4 border-[var(--theme)] p-4 mb-8 rounded shadow-sm flex items-center">
            <svg class="w-5 h-5 text-[var(--theme)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm font-bold text-[var(--header)]"><?= htmlspecialchars(is_string($errorMsg) ? $errorMsg : 'Error processing request.') ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-[var(--header)] rounded p-6 shadow-lg border border-[var(--theme2)] relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-[var(--theme)] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
            <h3 class="text-[var(--light)] opacity-70 text-xs font-bold uppercase tracking-widest mb-2 relative z-10">Estimated MRR</h3>
            <div class="flex items-baseline gap-2 relative z-10">
                <span class="text-4xl font-black text-[var(--white)] tracking-tighter">₹<?= number_format((float)($mrr ?? 0)) ?></span>
                <span class="text-[var(--theme)] text-sm font-bold">/mo</span>
            </div>
            <p class="text-[10px] text-[var(--light)] opacity-60 font-bold uppercase mt-2 tracking-widest relative z-10">Monthly Recurring Revenue</p>
        </div>

        <div class="bg-[var(--white)] rounded p-6 shadow-sm border border-[var(--border)] group hover:border-[var(--theme)] transition-colors relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-[var(--theme)] rounded-l"></div>
            <h3 class="text-[var(--text)] text-xs font-bold uppercase tracking-widest mb-2 pl-2">Platform GMV</h3>
            <div class="flex items-baseline gap-2 pl-2">
                <span class="text-4xl font-black text-[var(--header)] tracking-tighter">₹<?= number_format((float)($platformGmv ?? 0)) ?></span>
            </div>
            <p class="text-[10px] text-[var(--text)] font-bold uppercase mt-2 pl-2 tracking-widest">Total Booking Volume Processed</p>
        </div>

        <div class="bg-[var(--white)] rounded-2xl p-8 shadow-xl border border-[var(--border)] group hover:border-[var(--theme2)]/30 transition-all relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-2 bg-[var(--theme2)] rounded-l-2xl opacity-10 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex justify-between items-start mb-4 pl-3">
                <h3 class="text-[var(--text)] text-[10px] font-black uppercase tracking-[0.2em] opacity-40">Active Sovereignty</h3>
                <span class="bg-[var(--theme2)] text-[var(--white)] text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1 rounded-lg shadow-lg shadow-[var(--theme2)]/20">System Live</span>
            </div>
            <span class="text-5xl font-black text-[var(--header)] tracking-tighter pl-3 flex items-baseline gap-3">
                <?= $activeHotels ?? 0 ?> <span class="text-xl text-[var(--text)] opacity-20">/ <?= $totalHotels ?? 0 ?></span>
            </span>
            <p class="text-[10px] text-[var(--text)] font-black uppercase mt-4 pl-3 tracking-[0.15em] opacity-60">Hotels Currently Online</p>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden mb-10 group hover:shadow-indigo-500/5 transition-all">
        <div class="bg-[var(--light)] px-8 py-5 border-b border-[var(--border)] flex justify-between items-center bg-gradient-to-r from-[var(--light)] to-[var(--white)]">
            <h2 class="text-base font-black text-[var(--header)] uppercase tracking-tight flex items-center">
                <div class="w-10 h-10 rounded-xl bg-[var(--theme2)]/10 text-[var(--theme2)] flex items-center justify-center mr-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                </div>
                Platform Broadcast Protocol
            </h2>
        </div>
        <div class="p-8">
            <form action="/admin/broadcast" method="POST" class="flex flex-col md:flex-row gap-6">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="flex-1 relative">
                    <input type="text" name="message" required placeholder="Type a message to instantly broadcast globally..." class="w-full px-6 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)]">
                </div>
                
                <div class="w-full md:w-56">
                    <select name="type" class="w-full px-6 py-4 border-2 border-[var(--border)] rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] bg-[var(--light)] text-[var(--header)] focus:outline-none focus:border-[var(--theme2)] transition-all appearance-none cursor-pointer">
                        <option value="info">🟦 Info / General</option>
                        <option value="warning">🟧 Warning / Portal Maint.</option>
                        <option value="success">🟩 Success / Platform Update</option>
                    </select>
                </div>

                <button type="submit" class="bg-[var(--header)] text-[var(--white)] font-black px-10 py-4 rounded-2xl shadow-xl hover:bg-[var(--theme2)] transition-all text-[10px] uppercase tracking-[0.2em] transform hover:-translate-y-1 active:scale-95">
                    Execute Pulse
                </button>
            </form>

            <?php if (!empty($announcements)): ?>
            <div class="mt-8 pt-8 border-t-2 border-[var(--border)]/50">
                <h3 class="text-[10px] font-black text-[var(--text)] uppercase tracking-[0.3em] mb-4 opacity-40">Active Transmissions</h3>
                <div class="space-y-3">
                    <?php foreach ($announcements as $ann): ?>
                        <div class="flex justify-between items-center p-4 rounded-2xl bg-[var(--light)]/50 border border-[var(--border)] backdrop-blur-sm group/item hover:bg-[var(--white)] transition-all">
                            <span class="text-sm font-bold text-[var(--header)]">
                                <?php if($ann['type']==='warning') echo '🟧'; elseif($ann['type']==='success') echo '🟩'; else echo '🟦'; ?>
                                <?= htmlspecialchars($ann['message']) ?>
                            </span>
                            <form action="/admin/broadcast/delete" method="POST" class="m-0 p-0" onsubmit="return confirm('Terminate this broadcast?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                <button class="text-[var(--danger)] hover:bg-[var(--danger)] hover:text-[var(--white)] text-[9px] font-black uppercase tracking-[0.2em] bg-[var(--white)] px-4 py-2 rounded-xl shadow-sm border border-[var(--border)] transition-all">Terminate</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
        
        <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden h-fit group hover:shadow-indigo-500/5 transition-all">
            <div class="p-8 border-b border-[var(--border)] bg-[var(--light)]/50">
                <h2 class="text-base font-black text-[var(--header)] flex items-center uppercase tracking-tight">
                    <div class="w-8 h-8 rounded-lg bg-[var(--theme2)] text-[var(--white)] flex items-center justify-center mr-3 shadow-lg shadow-[var(--theme2)]/20">
                        <svg class="w-5 h-5 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    Onboard New Asset
                </h2>
                <p class="text-[11px] text-[var(--text)] mt-2 font-black uppercase tracking-widest opacity-40">Workspace Provisioning Engine</p>
            </div>
            
            <div class="p-8">
                <form action="/admin/hotels/create" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                    
                    <div>
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Property Identity</label>
                        <input type="text" name="property_name" required placeholder="e.g. The Grand Resort" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)]">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Protocol Slug (Namespace)</label>
                        <input type="text" name="slug" required placeholder="e.g. grand-resort" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-black outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--theme2)] font-mono focus:border-[var(--theme)]">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Authorized Proprietor</label>
                        <input type="text" name="admin_name" required class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-sm font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)]">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Admin Credential (Login ID)</label>
                        <input type="email" name="admin_email" required class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-sm font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)] font-mono">
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Entropy Password</label>
                        <input type="text" name="admin_password" required class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-sm font-black outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)] font-mono focus:border-[var(--theme2)]">
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] font-black py-5 rounded-2xl transition-all shadow-2xl hover:bg-[var(--theme2)] text-[10px] uppercase tracking-[0.3em] transform hover:-translate-y-1 active:scale-95">
                            Activate Workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2 bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden h-fit group hover:shadow-indigo-500/5 transition-all">
            <div class="p-8 border-b border-[var(--border)] bg-[var(--light)]/50 flex items-center justify-between">
                <h3 class="text-base font-black text-[var(--header)] uppercase tracking-tight">Recent Infrastructure Leases</h3>
                <a href="/admin/hotels" class="text-[9px] font-black text-[var(--theme2)] uppercase tracking-[0.2em] hover:text-[var(--header)] p-3 rounded-xl bg-[var(--white)] shadow-sm border border-[var(--border)] transition-all hover:-translate-y-1 active:scale-95 flex items-center">
                    Universal Registry
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-[var(--light)]/30 text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] border-b-2 border-[var(--border)]">
                        <tr>
                            <th class="px-8 py-5 text-left">Tenant Profile</th>
                            <th class="px-8 py-5 text-left">SLA & Fiscal State</th>
                            <th class="px-8 py-5 text-right">Operational HUD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <?php if (empty($recentHotels)): ?>
                            <tr><td colspan="3" class="px-8 py-16 text-center text-[10px] font-black uppercase tracking-[0.3em] opacity-40">No active leases found in sector.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentHotels as $h): ?>
                                <?php 
                                    $isSuspended = $h['status'] === 'suspended';
                                    $isExpired = false;
                                    $daysLeft = 0;
                                    if (!empty($h['next_billing_date'])) {
                                        $daysLeft = (strtotime($h['next_billing_date']) - time()) / (60 * 60 * 24);
                                        $isExpired = $daysLeft < 0;
                                    }
                                ?>
                            <tr class="<?= $isSuspended ? 'bg-[var(--danger)]/5 opacity-60' : 'hover:bg-[var(--light)]/50' ?> transition-all group/row">
                                
                                <td class="px-8 py-6 align-top">
                                    <div class="text-[15px] font-black text-[var(--header)] flex items-center mb-1 tracking-tight">
                                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($h['property_name'] ?? '') ?>
                                        <?php if($isSuspended): ?>
                                            <span class="ml-3 px-2 py-0.5 text-[8px] font-black bg-[var(--danger)] text-[var(--white)] rounded uppercase tracking-[0.2em] shadow-lg animate-pulse">Suspended Account</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="/book/<?= \Syncro\Security\SecurityManager::sanitizeOutput($h['slug'] ?? '') ?>" target="_blank" class="text-[11px] text-[var(--theme2)] hover:text-[var(--header)] font-black tracking-widest mb-3 block transition-all uppercase opacity-60 hover:opacity-100">
                                        /book/<?= \Syncro\Security\SecurityManager::sanitizeOutput($h['slug'] ?? '') ?> &rarr;
                                    </a>
                                    <div class="text-[11px] text-[var(--text)] flex items-center font-bold font-mono opacity-40">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($h['admin_email'] ?? '') ?>
                                    </div>
                                </td>
                                
                                <td class="px-8 py-6 align-top">
                                    <div class="text-[10px] font-black mb-1.5 uppercase tracking-[0.2em] inline-block px-2 py-0.5 rounded-md <?= $isExpired ? 'bg-[var(--danger)]/10 text-[var(--danger)]' : 'bg-[var(--success)]/10 text-[var(--success)]' ?>">
                                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($h['subscription_plan'] ?? 'Enterprise Unidentified') ?>
                                    </div>
                                    <div class="text-[13px] font-black text-[var(--header)] tracking-tight mt-2">
                                        Vigilance: <?= !empty($h['next_billing_date']) ? date('M j, Y', strtotime($h['next_billing_date'])) : 'Infinity' ?>
                                    </div>
                                    <?php if(!$isSuspended && !empty($h['next_billing_date'])): ?>
                                        <div class="text-[9px] font-black uppercase tracking-[0.2em] mt-2 <?= $isExpired ? 'text-[var(--danger)] animate-pulse' : 'text-[var(--text)] opacity-30' ?>">
                                            <?= $isExpired ? 'Termination Overdue' : round($daysLeft) . ' cycles remaining' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="px-8 py-6 text-right align-top">
                                    <div class="flex flex-col items-end gap-3 opacity-100 sm:opacity-0 sm:group-hover/row:opacity-100 transition-all duration-300 transform sm:group-hover/row:translate-x-0 sm:translate-x-4">
                                        
                                        <a href="/admin/hotels/edit?id=<?= $h['id'] ?>" class="bg-[var(--header)] text-[var(--white)] font-black px-6 py-2.5 rounded-xl text-[9px] transition-all shadow-xl hover:bg-[var(--theme2)] uppercase tracking-[0.2em] w-full text-center hover:-translate-y-0.5 active:scale-95">
                                            Administer Control
                                        </a>
                                        
                                        <form action="/admin/hotels/extend" method="POST" onsubmit="return confirm('Extend operational license for 1 month?');" class="w-full">
                                            <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                                            <input type="hidden" name="hotel_id" value="<?= (int)$h['id'] ?>">
                                            <button type="submit" class="bg-[var(--white)] text-[var(--theme2)] border-2 border-[var(--theme2)]/20 hover:border-[var(--theme2)] hover:bg-[var(--theme2)] hover:text-[var(--white)] font-black px-6 py-2 rounded-xl text-[9px] transition-all shadow-xl uppercase tracking-[0.2em] w-full hover:-translate-y-0.5 active:scale-95">
                                                Ledger Release (+1M)
                                            </button>
                                        </form>
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