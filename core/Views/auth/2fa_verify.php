<?php declare(strict_types=1); ?>

<style>
    input[name="code"]::placeholder {
        color: var(--text);
        letter-spacing: 0.5em;
    }
</style>

<div class="text-center mb-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-[var(--theme2)] mb-4 border border-blue-100 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
    </div>
    <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Two-Step Verification</h1>
    <p class="text-sm text-[var(--text)] mt-2 font-medium">
        We've sent a 6-digit verification code to your email. <br>Please enter it below to securely log in.
    </p>
</div>

<?php if ($errorMsg = \Syncro\Security\SessionManager::getFlash('error')): ?>
    <div class="mb-6 p-4 rounded bg-red-50 border-l-4 border-red-500 text-red-700 text-sm font-bold">
        <?= htmlspecialchars($errorMsg) ?>
    </div>
<?php endif; ?>

<form action="<?= base_url('/login/2fa') ?>" method="POST" class="space-y-6">
    <?= csrf_field() ?>">
    
    <div>
        <label for="code" class="sr-only">6-Digit Code</label>
        <input id="code" name="code" type="text" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="• • • • • •"
            class="appearance-none block w-full px-4 py-4 border border-[var(--border)] rounded-lg text-[var(--header)] bg-[var(--light)] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--theme2)] focus:border-transparent text-center text-3xl tracking-[0.5em] font-mono font-bold shadow-inner transition-shadow">
    </div>

    <div>
        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-lg text-white bg-[var(--theme2)] hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--theme2)] transition-all shadow-md uppercase tracking-widest">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-white/50 group-hover:text-white/80 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg>
            </span>
            Verify & Secure Login
        </button>
    </div>
</form>

<div class="mt-6 text-center">
    <p class="text-xs text-[var(--text)]">
        Didn't receive the code? <a href="<?= base_url('/login') ?>" class="font-bold text-[var(--theme2)] hover:text-[var(--theme)] transition-colors">Go back and try again</a>.
    </p>
</div>