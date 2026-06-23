<?php declare(strict_types=1); ?>

<div class="mb-10 flex flex-col sm:flex-row sm:items-center justify-between space-y-6 sm:space-y-0 pb-6 border-b-2 border-[var(--border)]">
    <div class="flex items-center gap-6">
        <a href="<?= base_url('/admin/hotels') ?>" class="w-12 h-12 bg-[var(--white)] rounded-2xl border border-[var(--border)] flex items-center justify-center text-[var(--text)] hover:text-[var(--theme2)] hover:bg-[var(--light)] transition-all shadow-lg hover:-translate-x-1 active:scale-95 group">
            <svg class="w-6 h-6 transform group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-3xl font-black text-[var(--header)] tracking-tighter uppercase mb-1">Onboard New Asset</h1>
            <p class="text-[10px] text-[var(--text)] font-black uppercase tracking-[0.2em] opacity-40">Workspace Provisioning & DB Partitioning Protocol</p>
        </div>
    </div>
</div>

<?php if (!empty($error) || isset($_GET['error'])): ?>
    <div class="mb-8 bg-[var(--danger)]/5 border-l-4 border-[var(--danger)] p-5 rounded-xl shadow-lg flex items-center animate-pulse">
        <div class="w-8 h-8 rounded-lg bg-[var(--danger)]/10 text-[var(--danger)] flex items-center justify-center mr-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--header)]">
            <?= e($error ?? 'Protocol Aborted: Identifier Conflict Detected.') ?>
        </p>
    </div>
<?php endif; ?>

<div class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] p-10 lg:p-14 max-w-5xl relative overflow-hidden group transition-all hover:shadow-indigo-500/5">
    <div class="absolute top-0 right-0 -mr-24 -mt-24 w-80 h-80 bg-[var(--theme2)] rounded-full opacity-10 blur-3xl pointer-events-none group-hover:opacity-20 transition-opacity"></div>
    <div class="absolute bottom-0 left-0 -ml-24 -mb-24 w-64 h-64 bg-[var(--theme)] rounded-full opacity-5 blur-2xl pointer-events-none"></div>

    <form action="<?= base_url('/admin/hotels/create') ?>" method="POST" class="space-y-12 relative z-10 m-0">
        <?= csrf_field() ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
            <div class="space-y-8">
                <h3 class="text-[10px] font-black text-[var(--header)] uppercase tracking-[0.4em] opacity-30 border-b-2 border-[var(--border)] pb-3">Sector Identity</h3>
                
                <div class="space-y-6">
                    <div>
                        <label for="property_name" class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-3 opacity-60">Legal Entity Title</label>
                        <input type="text" id="property_name" name="property_name" required placeholder="e.g. Grand Adhyan Resort"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] focus:bg-[var(--white)] focus:outline-none focus:border-[var(--theme2)] transition-all font-bold shadow-inner">
                    </div>

                    <div>
                        <label for="slug" class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-3 opacity-60">Protocol Slug (Namespace)</label>
                        <input type="text" id="slug" name="slug" placeholder="e.g. grand-resort (Auto-Entropy Enabled)"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--theme2)] transition-all font-mono text-sm shadow-inner focus:outline-none focus:border-[var(--theme)] focus:bg-[var(--white)]">
                    </div>
                </div>
            </div>

            <div class="space-y-8">
                <h3 class="text-[10px] font-black text-[var(--header)] uppercase tracking-[0.4em] opacity-30 border-b-2 border-[var(--border)] pb-3">Authorized Proprietor</h3>
                
                <div class="space-y-6">
                    <div>
                        <label for="admin_name" class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-3 opacity-60">Master Command Name</label>
                        <input type="text" id="admin_name" name="admin_name" required placeholder="e.g. John Doe"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] focus:bg-[var(--white)] focus:outline-none focus:border-[var(--theme2)] transition-all font-bold shadow-inner">
                    </div>

                    <div>
                        <label for="admin_email" class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-3 opacity-60">Credential Identifier (Email)</label>
                        <input type="email" id="admin_email" name="admin_email" required placeholder="admin@hotel.com"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] focus:bg-[var(--white)] focus:outline-none focus:border-[var(--theme2)] transition-all font-mono text-sm shadow-inner">
                    </div>

                    <div>
                        <label for="admin_password" class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] mb-3 opacity-60">Initial Password String</label>
                        <input type="text" id="admin_password" name="admin_password" placeholder="Leave for Secure Auto-Entropy"
                            class="w-full px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--theme2)] transition-all font-mono text-sm shadow-inner focus:outline-none focus:border-[var(--theme)] focus:bg-[var(--white)]">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-[var(--header)] p-6 rounded-2xl border-2 border-[var(--theme2)]/30 flex items-start shadow-xl">
            <div class="w-10 h-10 rounded-xl bg-[var(--theme2)]/20 text-[var(--theme2)] flex items-center justify-center mr-5 mt-0.5 flex-shrink-0">
                <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-[12px] text-[var(--white)]/70 font-bold leading-relaxed uppercase tracking-widest">
                COMMIT PROTOCOL: Provisioning this tenant will execute a global workspace activation. Database isolation will be instantiated, 256-bit signature secrets generated, and a 14-cycle evaluation lease authorized. <span class="text-[var(--theme)] font-black">All credentials will be purged upon view termination.</span>
            </p>
        </div>

        <div class="pt-10 border-t-2 border-[var(--border)]/50 flex justify-end">
            <button type="submit" class="w-full sm:w-auto bg-[var(--header)] text-[var(--white)] font-black py-5 px-12 rounded-2xl shadow-2xl hover:bg-[var(--theme2)] transition-all text-[11px] uppercase tracking-[0.4em] flex items-center justify-center transform hover:-translate-y-1 active:scale-95 group/btn">
                <svg class="w-5 h-5 mr-4 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Execute Activation
            </button>
        </div>
    </form>
</div>