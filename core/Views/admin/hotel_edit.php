<?php declare(strict_types=1); ?>

<div class="mb-10 flex flex-col sm:flex-row sm:items-center justify-between space-y-6 sm:space-y-0 pb-6 border-b-2 border-[var(--border)]">
    <div class="flex items-center gap-6">
        <a href="/admin/hotels" class="w-12 h-12 bg-[var(--white)] rounded-2xl border border-[var(--border)] flex items-center justify-center text-[var(--text)] hover:text-[var(--theme2)] hover:bg-[var(--light)] transition-all shadow-lg hover:-translate-x-1 active:scale-95 group">
            <svg class="w-6 h-6 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-3xl font-black text-[var(--header)] flex items-center gap-4 tracking-tighter">
                <?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name']) ?>
                <?php if ($hotel['status'] === 'active'): ?>
                    <span class="bg-[var(--success)]/10 text-[var(--success)] border border-[var(--success)]/20 text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1 rounded-xl shadow-sm animate-pulse">Running</span>
                <?php else: ?>
                    <span class="bg-[var(--header)] text-[var(--theme)] border-2 border-[var(--theme2)]/30 text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1 rounded-xl shadow-xl">Suspended</span>
                <?php endif; ?>
            </h1>
            <p class="text-[10px] text-[var(--text)] mt-2 font-black uppercase tracking-[0.2em] opacity-40">Operational Realm: #<?= (int)$hotel['id'] ?> &bull; Activation Cycle: <?= date('M d, Y', strtotime($hotel['created_at'])) ?></p>
        </div>
    </div>
    
    <form action="/admin/hotels/impersonate" method="POST" class="m-0">
        <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
        <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
        <button type="submit" class="flex items-center bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] font-black py-4 px-8 rounded-2xl shadow-2xl transition-all text-[10px] uppercase tracking-[0.3em] w-full sm:w-auto justify-center group hover:-translate-y-1 active:scale-95">
            <svg class="w-5 h-5 mr-3 text-[var(--theme)] animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            Assume Authority
        </button>
    </form>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="mb-8 bg-[var(--success)]/5 border-l-4 border-[var(--success)] p-5 rounded-xl shadow-lg flex items-center animate-bounce">
        <div class="w-8 h-8 rounded-lg bg-[var(--success)]/10 text-[var(--success)] flex items-center justify-center mr-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--header)]">Infrastructure Update Committed Successfully</p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-10">
    
    <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] p-8 h-fit group transition-all hover:shadow-indigo-500/5">
        <h2 class="text-[10px] font-black text-[var(--header)] border-b border-[var(--border)] pb-4 mb-6 uppercase tracking-[0.3em] opacity-40">Identity Framework</h2>
        
        <form action="/admin/hotels/update-details" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
            <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
            
            <div>
                <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Property Label</label>
                <input type="text" name="property_name" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name']) ?>" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Namespace Slug</label>
                <input type="text" name="slug" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['slug']) ?>" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-black outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--theme2)] transition-all font-mono">
            </div>
            <div>
                <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Primary Command Email</label>
                <input type="email" name="admin_email" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['email']) ?>" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] transition-all font-mono">
            </div>
            
            <div class="pt-4">
                <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] font-black py-5 rounded-2xl shadow-xl hover:bg-[var(--theme2)] transition-all text-[10px] uppercase tracking-[0.3em] transform hover:-translate-y-1">
                    Commit Identity
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-10 h-fit">
        
        <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] p-8 transition-all hover:shadow-indigo-500/5">
            <h2 class="text-[10px] font-black text-[var(--header)] border-b border-[var(--border)] pb-4 mb-6 uppercase tracking-[0.3em] opacity-40">Service Level Agreement</h2>
            <form action="/admin/hotels/update-billing" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
                <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
                
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Tier Specification</label>
                    <select name="subscription_plan" id="plan_select" onchange="autoCalculateDate()" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-black focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] transition-all appearance-none cursor-pointer">
                        <option value="<?= htmlspecialchars($hotel['subscription_plan'] ?? '') ?>" selected>
                            ACTIVE: <?= htmlspecialchars($hotel['subscription_plan'] ?? 'No SLA') ?>
                        </option>
                        <option value="1 Month License">1 Month License</option>
                        <option value="3 Month License">3 Month License</option>
                        <option value="6 Month License">6 Month License</option>
                        <option value="1 Year License">1 Year License</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Termination Horizon</label>
                    <input type="date" name="next_billing_date" id="billing_date" value="<?= $hotel['next_billing_date'] ? date('Y-m-d', strtotime($hotel['next_billing_date'])) : '' ?>" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-black focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] text-[var(--theme2)] transition-all">
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-[var(--white)] border-2 border-[var(--theme2)]/30 text-[var(--theme2)] font-black py-4 rounded-2xl shadow-lg hover:bg-[var(--theme2)] hover:text-[var(--white)] transition-all text-[10px] uppercase tracking-[0.3em] transform hover:-translate-y-1">
                        Solidify SLA Parameters
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] p-8 transition-all hover:shadow-indigo-500/5">
            <h2 class="text-[10px] font-black text-[var(--header)] border-b border-[var(--border)] pb-4 mb-6 uppercase tracking-[0.3em] opacity-40">Infrastructure Sovereignty</h2>
            <form action="/admin/hotels/toggle-status" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
                <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
                
                <select name="status" class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl bg-[var(--light)] focus:bg-[var(--white)] text-[14px] font-black focus:ring-0 focus:border-[var(--theme2)] outline-none text-[var(--header)] mb-6 cursor-pointer transition-all appearance-none">
                    <option value="active" <?= $hotel['status'] === 'active' ? 'selected' : '' ?>>Operational (Full Access)</option>
                    <option value="suspended" <?= $hotel['status'] === 'suspended' ? 'selected' : '' ?>>Blacklisted (Access Terminated)</option>
                </select>
                
                <button type="submit" class="w-full bg-[var(--white)] border-2 border-[var(--border)] text-[var(--text)] hover:text-[var(--header)] font-black py-4 rounded-2xl shadow-lg hover:bg-[var(--light)] transition-all text-[10px] uppercase tracking-[0.3em] transform hover:-translate-y-1">
                    Enforce Protocol
                </button>
            </form>
        </div>

    </div>

    <div class="space-y-10 h-fit">
        
        <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] p-8 transition-all hover:shadow-indigo-500/5">
            <h2 class="text-[10px] font-black text-[var(--header)] border-b border-[var(--border)] pb-4 mb-6 uppercase tracking-[0.3em] opacity-40">Security Override</h2>
            <form action="/admin/hotels/reset-password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
                <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
                
                <input type="text" name="new_password" placeholder="Generate entropy..." required class="w-full px-5 py-4 border-2 border-[var(--border)] rounded-2xl text-[14px] font-black outline-none mb-6 bg-[var(--light)] focus:bg-[var(--white)] text-[var(--header)] font-mono transition-all focus:border-[var(--theme2)] shadow-inner">
                
                <button type="submit" class="w-full bg-[var(--theme2)] text-[var(--white)] font-black py-5 rounded-2xl shadow-xl hover:bg-[var(--header)] transition-all text-[10px] uppercase tracking-[0.3em] transform hover:-translate-y-1">
                    Execute Reset Action
                </button>
            </form>
        </div>
        
        <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] p-8 transition-all hover:shadow-indigo-500/5">
            <h2 class="text-[10px] font-black text-[var(--header)] border-b border-[var(--border)] pb-4 mb-6 uppercase tracking-[0.3em] opacity-40">Telemetry Webhooks</h2>
            <div class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">Infrastructure Signature</label>
                    <div class="bg-[var(--light)] p-4 rounded-xl border-2 border-[var(--border)] text-[11px] font-black font-mono text-[var(--text)] break-all select-all shadow-inner">
                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['api_key']) ?>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-2 opacity-50">HMAC Validation Secret</label>
                    <div class="bg-[var(--light)] p-4 rounded-xl border-2 border-[var(--border)] text-[11px] font-black font-mono text-[var(--theme2)] break-all select-all shadow-inner">
                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['api_secret']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-[var(--header)] rounded-2xl border-4 border-[var(--theme2)] p-8 relative overflow-hidden group shadow-2xl">
            <div class="absolute top-0 right-0 -mr-12 -mt-12 w-32 h-32 bg-[var(--theme2)] rounded-full blur-3xl opacity-20 pointer-events-none group-hover:opacity-40 transition-opacity"></div>
            
            <h2 class="text-xl font-black text-[var(--white)] border-b border-[var(--white)]/10 pb-4 mb-6 uppercase tracking-[0.4em] relative z-10">Termination</h2>
            <p class="text-[11px] text-[var(--white)]/60 mb-8 font-bold relative z-10 leading-relaxed uppercase tracking-widest">Permanent erasure of tenant collective. This utility will deconstruct all assets, reservations, and protocols. Action is immutable.</p>
            
            <form action="/admin/hotels/delete" method="POST" onsubmit="return confirm('Sovereign Clearance: Are you absolutely certain you wish to terminate this tenant collective?');" class="relative z-10 m-0">
                <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken) ?>">
                <input type="hidden" name="hotel_id" value="<?= (int)$hotel['id'] ?>">
                
                <button type="submit" class="w-full bg-[var(--danger)] text-[var(--white)] font-black py-5 rounded-2xl shadow-2xl transition-all text-[10px] uppercase tracking-[0.4em] border border-[var(--danger)] hover:bg-[var(--white)] hover:text-[var(--danger)] hover:-translate-y-1 active:scale-95">
                    Execute Purge
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function autoCalculateDate() {
        const planSelect = document.getElementById('plan_select').value;
        const dateInput = document.getElementById('billing_date');
        
        // If they pick the "CURRENT" placeholder, don't change the date
        if (!planSelect || planSelect.startsWith('CURRENT:')) {
            return;
        }
        
        const futureDate = new Date();
        
        // Add time based on the selected plan string
        if (planSelect.includes('1 Month')) {
            futureDate.setMonth(futureDate.getMonth() + 1);
        } else if (planSelect.includes('3 Month')) {
            futureDate.setMonth(futureDate.getMonth() + 3);
        } else if (planSelect.includes('6 Month')) {
            futureDate.setMonth(futureDate.getMonth() + 6);
        } else if (planSelect.includes('1 Year')) {
            futureDate.setFullYear(futureDate.getFullYear() + 1);
        }

        // Format as YYYY-MM-DD for the input type="date"
        const yyyy = futureDate.getFullYear();
        const mm = String(futureDate.getMonth() + 1).padStart(2, '0');
        const dd = String(futureDate.getDate()).padStart(2, '0');
        
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }
</script>