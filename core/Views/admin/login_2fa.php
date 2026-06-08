<?php declare(strict_types=1); 
// Capture both Controller-passed errors and Session flash errors
$displayError = $error ?? \Syncro\Security\SessionManager::getFlash('error');
?>

<style>
    .otp-input {
        letter-spacing: 0.5em;
        text-indent: 0.25em; 
    }
    .otp-input::placeholder {
        color: rgba(var(--header-rgb), 0.1);
        letter-spacing: 0.5em;
    }
</style>

<div class="flex items-center justify-center w-full px-6 min-h-[calc(100vh-6rem)] relative overflow-hidden">
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-1/4 -right-1/4 w-[600px] h-[600px] bg-[var(--theme2)]/5 rounded-full blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-1/4 -left-1/4 w-[600px] h-[600px] bg-[var(--theme)]/10 rounded-full blur-[120px] opacity-40"></div>
    </div>

    <div class="max-w-md w-full bg-[var(--white)] rounded-[2.5rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.12)] border border-[var(--border)] overflow-hidden relative z-10 transition-all hover:shadow-indigo-500/10 group">
        
        <div class="bg-[var(--header)] p-12 text-center relative overflow-hidden group-hover:bg-[#0a0a0b] transition-colors">
            <div class="absolute top-0 right-0 -mr-12 -mt-12 w-32 h-32 bg-[var(--theme2)] rounded-full blur-3xl opacity-20 group-hover:opacity-40 transition-opacity"></div>
            <h1 class="text-4xl font-black tracking-tighter mb-4 relative z-10">
                <span class="text-[var(--white)]">SYNCRO</span><span class="text-[var(--theme)]">.</span>
            </h1>
            <p class="text-[9px] font-black uppercase tracking-[0.4em] text-[var(--white)]/40 relative z-10 flex items-center justify-center gap-3">
                <span class="w-8 h-px bg-[var(--white)]/20"></span>
                IDENTITY SECURITY
                <span class="w-8 h-px bg-[var(--white)]/20"></span>
            </p>
        </div>

        <div class="p-12 sm:p-14">
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-[var(--theme2)]/5 text-[var(--theme2)] mb-6 border-2 border-[var(--theme2)] shadow-2xl transform -rotate-6 group-hover:rotate-0 transition-transform duration-500">
                    <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h2 class="text-2xl font-black text-[var(--header)] tracking-tighter uppercase">Signature Required</h2>
                <p class="text-[10px] text-[var(--text)] mt-4 font-black uppercase tracking-[0.2em] leading-relaxed opacity-40">
                    A multi-stage enrollment sequence has been<br>dispatched to your registered protocol email.
                </p>
            </div>

            <?php if ($displayError): ?>
                <div class="mb-10 bg-[var(--danger)]/5 border-l-4 border-[var(--danger)] p-5 rounded-xl shadow-lg flex items-center animate-shake">
                    <div class="w-8 h-8 rounded-lg bg-[var(--danger)]/10 text-[var(--danger)] flex items-center justify-center mr-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--header)]"><?= htmlspecialchars($displayError) ?></p>
                </div>
            <?php endif; ?>

            <form action="/login/2fa" method="POST" class="space-y-8 m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                
                <div class="relative group/otp">
                    <label for="code" class="sr-only">Signature Fragment</label>
                    <input id="code" name="code" type="text" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="000 000" autofocus
                        class="otp-input appearance-none block w-full px-10 py-8 border-2 border-[var(--border)] rounded-3xl text-[var(--header)] bg-[var(--light)] focus:outline-none focus:border-[var(--theme2)] focus:bg-[var(--white)] text-center text-5xl font-black shadow-inner transition-all transform hover:scale-[1.02] tracking-[0.2em]">
                    <div class="absolute inset-0 rounded-3xl border-2 border-[var(--theme2)] opacity-0 group-focus-within/otp:opacity-100 transition-opacity pointer-events-none scale-105"></div>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center items-center py-5 px-6 rounded-2xl shadow-2xl text-[11px] font-black text-[var(--white)] bg-[var(--header)] hover:bg-[var(--theme2)] transition-all uppercase tracking-[0.4em] mt-10 active:scale-95 group/btn transform hover:-translate-y-1">
                    <svg class="w-5 h-5 mr-4 group-hover:rotate-12 transition-transform opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Finalize Authentication
                </button>
            </form>

            <div class="mt-12 text-center">
                <p class="text-[9px] text-[var(--text)] font-black uppercase tracking-[0.2em] opacity-40">
                    Transmission latency detected? <br>
                    <a href="/login" class="inline-block mt-4 text-[var(--theme2)] hover:text-[var(--header)] transition-colors underline decoration-2 underline-offset-8 decoration-[var(--theme2)]/30 hover:decoration-[var(--theme2)]">Request New Dispatches</a>.
                </p>
            </div>
            
            <div class="mt-12 pt-8 border-t-2 border-[var(--border)]/50 text-center">
                <p class="text-[9px] font-black text-[var(--text)] uppercase tracking-[0.4em] opacity-20">&copy; <?= date('Y') ?> Syncro PMS Infrastructure.</p>
            </div>
        </div>
    </div>
</div>