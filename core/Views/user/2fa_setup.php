<?php declare(strict_types=1); ?>

<style>
    input[name="code"]::placeholder {
        color: var(--text);
        letter-spacing: 0.5em;
    }
</style>

<div class="mb-6 flex items-center justify-between">
    <a href="<?= base_url('/user/settings') ?>" class="text-[var(--text)] hover:text-[var(--theme2)] font-medium flex items-center transition-colors">
        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Back to Settings
    </a>
</div>

<div class="max-w-xl mx-auto bg-[var(--white)] rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
    <div class="bg-[var(--header)] p-6 text-center text-[var(--white)]">
        <h2 class="text-2xl font-bold tracking-tight">Protect Your Account</h2>
        <p class="text-[var(--theme)] font-medium mt-1">Email-Based Two-Factor Authentication</p>
    </div>

    <div class="p-8 text-center">
        <div class="w-16 h-16 bg-blue-50 text-[var(--theme2)] rounded-full flex items-center justify-center mx-auto mb-4 border border-blue-100">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        
        <h3 class="text-lg font-bold text-[var(--header)] mb-2">Check Your Inbox</h3>
        <p class="text-sm text-[var(--text)] leading-relaxed mb-8">
            We have sent a 6-digit verification code to <strong><?= htmlspecialchars($email ?? 'your email address') ?></strong>. <br>Please enter it below to confirm your setup.
        </p>

        <form action="<?= base_url('/user/settings/2fa/verify') ?>" method="POST" class="space-y-6">
            <?= csrf_field() ?>">
            
            <div class="max-w-xs mx-auto">
                <input type="text" name="code" required autocomplete="off" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="• • • • • •"
                    class="w-full text-center text-2xl tracking-[0.5em] px-4 py-4 rounded border border-[var(--border)] bg-[var(--light)] text-[var(--header)] focus:outline-none focus:ring-2 focus:ring-[var(--theme2)] transition-shadow font-mono font-bold shadow-inner">
            </div>

            <button type="submit" class="w-full max-w-xs mx-auto bg-[var(--theme2)] text-[var(--white)] px-8 py-3 rounded font-bold hover:opacity-90 transition-opacity shadow-sm uppercase tracking-widest text-sm">
                Verify & Enable
            </button>
        </form>
    </div>
</div>