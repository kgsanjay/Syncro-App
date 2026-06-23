<?php declare(strict_types=1); ?>
<div class="min-h-[calc(100vh-73px)] bg-[var(--light)] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-[var(--white)] rounded-xl shadow-xl border border-[var(--border)] overflow-hidden text-center p-10">
        
        <div class="w-20 h-20 bg-[var(--theme2)] text-[var(--white)] rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        
        <h1 class="text-3xl font-extrabold text-[var(--header)] mb-4 tracking-tight">Request Received</h1>
        <p class="text-[var(--text)] font-medium mb-8">Thank you for choosing Syncro PMS. Your property registration is currently pending review.</p>
        
        <div class="bg-[var(--light)] border border-[var(--border)] p-5 rounded-lg mb-8 text-left">
            <h3 class="font-bold text-[var(--header)] text-sm uppercase tracking-wider mb-2">Next Steps:</h3>
            <p class="text-sm text-[var(--text)] leading-relaxed">Our enterprise onboarding team will contact you shortly at your registered email address with instructions for offline payment and account activation.</p>
        </div>
        
        <a href="<?= base_url('/') ?>" class="inline-block w-full bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-4 rounded transition-colors uppercase tracking-widest text-xs shadow-sm">
            Return to Homepage
        </a>
    </div>
</div>