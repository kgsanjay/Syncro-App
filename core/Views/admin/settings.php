<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">
    <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b-2 border-[var(--border)]">
        <div>
            <h1 class="text-4xl font-black text-[var(--header)] tracking-tighter uppercase mb-2">Platform Governance</h1>
            <p class="text-[10px] text-[var(--text)] font-black uppercase tracking-[0.3em] opacity-40">Global SaaS Parameters & Security Protocols</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-[var(--success)]/10 text-[var(--success)] border border-[var(--success)]/20 px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest animate-pulse">System Online</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        <div class="lg:col-span-2 space-y-10">
            <form action="<?= base_url('/admin/settings/update') ?>" method="POST" class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] overflow-hidden transition-all hover:shadow-indigo-500/5 group m-0">
                <?= csrf_field() ?>">
                
                <div class="bg-[var(--header)] px-8 py-6 flex justify-between items-center group-hover:bg-[var(--theme2)] transition-colors">
                    <h2 class="text-[var(--white)] font-black tracking-[0.3em] uppercase text-[10px]">Infrastructure Billing & Liquid Logic</h2>
                    <svg class="w-5 h-5 text-[var(--theme)] animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                </div>
                
                <div class="p-10 space-y-10">
                    <div class="p-6 bg-[var(--light)] rounded-2xl border-2 border-[var(--border)] shadow-inner transition-all hover:border-[var(--theme2)]/30">
                        <label class="flex items-center cursor-pointer group/check">
                            <div class="relative">
                                <input type="checkbox" name="payment_gateway_enabled" value="1" class="peer hidden" <?= ($settings['payment_gateway_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                <div class="w-14 h-8 bg-[var(--border)] rounded-full peer-checked:bg-[var(--theme2)] transition-all shadow-inner"></div>
                                <div class="absolute left-1 top-1 w-6 h-6 bg-[var(--white)] rounded-full transition-all peer-checked:translate-x-6 shadow-md"></div>
                            </div>
                            <span class="ml-5 text-[12px] font-black text-[var(--header)] uppercase tracking-[0.2em] group-hover/check:text-[var(--theme2)] transition-colors">Activate PhonePe Node</span>
                        </label>
                        <p class="text-[10px] text-[var(--text)] mt-4 ml-1 shadow-none opacity-40 font-bold leading-relaxed uppercase tracking-widest">When deactivated, tenant collectives inherit 'Pending' status and require manual clearance protocols.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div class="space-y-3">
                            <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">Merchant Identifier (Primary)</label>
                            <input type="text" name="phonepe_merchant_id" value="<?= e($settings['phonepe_merchant_id'] ?? '') ?>" class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-[14px] font-black shadow-inner transition-all font-mono">
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">HMAC Encryption Salt</label>
                            <input type="password" name="phonepe_salt_key" value="<?= e($settings['phonepe_salt_key'] ?? '') ?>" class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-[14px] font-black shadow-inner transition-all font-mono">
                        </div>
                    </div>

                    <div class="pt-6">
                        <h3 class="text-[10px] font-black text-[var(--header)] uppercase tracking-[0.4em] mb-8 border-b-2 border-[var(--border)] pb-4 opacity-30">Tier Calibration (₹ INR)</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <?php 
                                $plans = [
                                    ['key' => 'plan_1_month', 'label' => '1 Cycle'],
                                    ['key' => 'plan_3_month', 'label' => '3 Cycles'],
                                    ['key' => 'plan_6_month', 'label' => '6 Cycles'],
                                    ['key' => 'plan_12_month', 'label' => 'Solar Year'],
                                ];
                                foreach($plans as $p):
                            ?>
                            <div class="group/tier">
                                <label class="block text-[8px] font-black text-[var(--text)] uppercase tracking-widest mb-3 opacity-40 group-hover/tier:opacity-100 transition-opacity"><?= e((string) $p['label']) ?></label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[var(--text)] font-black text-xs opacity-30">₹</span>
                                    <input type="number" name="<?= e((string) $p['key']) ?>" value="<?= (int)($settings[$p['key']] ?? 0) ?>" class="w-full pl-8 pr-4 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] font-black text-[var(--theme2)] focus:bg-[var(--white)] focus:border-[var(--theme)] outline-none transition-all text-sm shadow-inner group-hover/tier:scale-105">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] font-black uppercase tracking-[0.4em] text-[11px] py-6 rounded-2xl shadow-2xl hover:bg-[var(--theme2)] transition-all transform hover:-translate-y-1 active:scale-95 group/btn">
                            <div class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-4 group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Commit Configuration
                            </div>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="lg:col-span-1">
            <form action="<?= base_url('/admin/settings/password') ?>" method="POST" class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] overflow-hidden transition-all hover:shadow-indigo-500/5 group h-fit m-0">
                <?= csrf_field() ?>">
                
                <div class="bg-[var(--theme)] px-8 py-6 flex justify-between items-center group-hover:bg-[var(--theme2)] transition-colors">
                    <h2 class="text-[var(--header)] font-black tracking-[0.3em] uppercase text-[10px] group-hover:text-[var(--white)] transition-colors">Security Core</h2>
                    <svg class="w-5 h-5 text-[var(--header)] group-hover:text-[var(--theme)] transition-colors animate-spin-slow" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 11.37c1.328 2.84 4.07 4.63 7.834 4.63 3.763 0 6.505-1.79 7.833-4.63a.5.5 0 00-.917-.4c-1.127 2.41-3.522 3.86-6.916 3.86-3.394 0-5.79-1.45-6.916-3.86a.5.5 0 00-.917.4zM9 5a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                </div>
                
                <div class="p-8 space-y-8">
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">Current Sovereignty Key</label>
                        <input type="password" name="current_password" required class="w-full px-6 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-sm font-black shadow-inner">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">New Authorization String</label>
                        <input type="password" name="new_password" required minlength="8" class="w-full px-6 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-sm font-black shadow-inner">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">Confirm Mutation</label>
                        <input type="password" name="confirm_password" required minlength="8" class="w-full px-6 py-4 rounded-xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-sm font-black shadow-inner">
                    </div>
                    
                    <button type="submit" class="w-full mt-4 bg-[var(--light)] border-2 border-[var(--border)] text-[var(--header)] font-black uppercase tracking-[0.3em] text-[10px] py-4 rounded-xl hover:bg-[var(--header)] hover:text-[var(--white)] transition-all shadow-lg transform hover:-translate-y-1 active:scale-95">
                        Cycle Access Keys
                    </button>
                </div>
            </form>

            <div class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] overflow-hidden transition-all hover:shadow-indigo-500/5 group h-fit m-0 mt-10">
                <div class="bg-[var(--header)] px-8 py-6 flex justify-between items-center group-hover:bg-[var(--theme2)] transition-colors">
                    <h2 class="text-[var(--white)] font-black tracking-[0.3em] uppercase text-[10px] transition-colors">Multi-Factor Auth</h2>
                    <svg class="w-5 h-5 text-[var(--white)] opacity-50 group-hover:text-[var(--theme)] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                
                <div class="p-8">
                    <?php if(!empty($currentUser['two_factor_enabled'])): ?>
                        <div class="flex items-center gap-4 mb-6 p-4 bg-[var(--success)]/10 border border-[var(--success)]/30 rounded-xl">
                            <svg class="w-6 h-6 text-[var(--success)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[var(--success)]">Security Active</p>
                                <p class="text-[9px] font-black uppercase tracking-widest opacity-60 mt-1 text-[var(--header)]">Google Authenticator Enabled</p>
                            </div>
                        </div>
                        <form action="<?= base_url('/admin/settings/2fa/disable') ?>" method="POST">
                            <?= csrf_field() ?>">
                            <button type="submit" class="w-full bg-red-500/10 border-2 border-red-500 text-red-600 font-black uppercase tracking-[0.3em] text-[10px] py-4 rounded-xl hover:bg-red-600 hover:text-white transition-all shadow-lg transform hover:-translate-y-1 active:scale-95" onclick="return confirm('Are you sure you want to disable 2FA? This will reduce your account security.')">
                                Disable 2FA Security
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="flex items-center gap-4 mb-6 p-4 bg-orange-500/10 border border-orange-500/30 rounded-xl">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-orange-600">Action Required</p>
                                <p class="text-[9px] font-black uppercase tracking-widest opacity-60 mt-1 text-[var(--header)]">Account Vulnerable (No 2FA)</p>
                            </div>
                        </div>
                        <a href="<?= base_url('/admin/settings/2fa/setup') ?>" class="block text-center w-full bg-[var(--theme)] text-[var(--header)] font-black uppercase tracking-[0.3em] text-[10px] py-4 rounded-xl hover:bg-[var(--theme2)] hover:text-[var(--white)] transition-all shadow-lg transform hover:-translate-y-1 active:scale-95">
                            Enable Google Authenticator
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>