<?php declare(strict_types=1); ?>

<div class="max-w-2xl mx-auto pb-10">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-[var(--header)] tracking-tighter uppercase mb-2">Secure Authentication</h1>
        <p class="text-[10px] text-[var(--text)] font-black uppercase tracking-[0.3em] opacity-40">Configure Google Authenticator</p>
    </div>

    <div class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] overflow-hidden">
        <div class="bg-[var(--header)] px-8 py-6 flex justify-between items-center">
            <h2 class="text-[var(--white)] font-black tracking-[0.3em] uppercase text-[10px]">Setup Instructions</h2>
            <svg class="w-5 h-5 text-[var(--theme)] animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>

        <div class="p-8">
            <ol class="space-y-6 text-sm text-[var(--text)] mb-8">
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--light)] text-[var(--header)] font-black flex items-center justify-center mr-4 text-xs">1</span>
                    <p>Download <strong>Google Authenticator</strong> or Authy on your mobile device.</p>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--light)] text-[var(--header)] font-black flex items-center justify-center mr-4 text-xs">2</span>
                    <div>
                        <p class="mb-3">Scan the QR code below with your app:</p>
                        <div class="bg-[var(--light)] p-4 rounded-xl inline-block border-2 border-[var(--border)]">
                            <img src="<?= e($qrImage) ?>" alt="QR Code" class="w-48 h-48">
                        </div>
                    </div>
                </li>
                <li class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[var(--light)] text-[var(--header)] font-black flex items-center justify-center mr-4 text-xs">3</span>
                    <div>
                        <p class="mb-1">Or enter this manual secret key:</p>
                        <code class="px-3 py-1 bg-[var(--light)] text-[var(--header)] font-mono font-black tracking-widest rounded border border-[var(--border)]">
                            <?= e($secret) ?>
                        </code>
                    </div>
                </li>
            </ol>

            <form action="<?= base_url('/admin/settings/2fa/verify') ?>" method="POST" class="mt-8 border-t-2 border-[var(--border)] pt-8">
                <?= csrf_field() ?>">
                
                <div class="space-y-3 mb-6">
                    <label class="block text-[9px] font-black text-[var(--header)] uppercase tracking-[0.3em] opacity-40">Enter 6-Digit Code</label>
                    <input type="text" name="code" required maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" class="w-full text-center px-6 py-4 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] focus:bg-[var(--white)] focus:border-[var(--theme2)] outline-none text-2xl font-black shadow-inner tracking-[0.5em] font-mono transition-all">
                </div>

                <button type="submit" class="w-full bg-[var(--theme)] text-[var(--header)] font-black uppercase tracking-[0.3em] text-[11px] py-5 rounded-xl shadow-lg hover:bg-[var(--theme2)] hover:text-[var(--white)] transition-all transform hover:-translate-y-1 active:scale-95">
                    Verify & Enable 2FA
                </button>
            </form>
        </div>
    </div>
</div>
