<?php declare(strict_types=1); ?>

<div class="min-h-screen bg-[var(--light)] flex flex-col items-center justify-center p-4">
    <div class="bg-[var(--white)] p-8 md:p-10 rounded-2xl shadow-xl border border-[var(--border)] text-center max-w-md w-full transition-transform hover:-translate-y-1">
        
        <div class="w-20 h-20 bg-[var(--header)] text-[var(--theme)] rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border-4 border-[var(--theme)]">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h2 class="text-2xl font-black text-[var(--header)] mb-3 tracking-tight uppercase">Payment Successful!</h2>
        
        <p class="text-[var(--text)] mb-8 font-medium text-sm leading-relaxed">
            Your booking (Folio <strong class="text-[var(--header)] font-black">#<?= str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT) ?></strong>) is confirmed. An official receipt has been sent to your email.
        </p>
        
        <a href="/book/<?= htmlspecialchars($slug) ?>" class="block w-full bg-[var(--theme2)] text-[var(--white)] font-extrabold py-4 px-6 rounded-lg uppercase tracking-widest text-xs hover:bg-[var(--header)] transition-colors shadow-md">
            Return to Property Page
        </a>
        
    </div>
</div>