<?php declare(strict_types=1); ?>

<div class="min-h-screen bg-[var(--bg)] flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-[var(--theme)] tracking-tighter">SYNCRO<span class="text-[var(--theme2)]">.</span></h1>
            <p class="text-[var(--text-muted)] mt-2 font-medium">Join Your Team</p>
        </div>

        <div class="bg-[var(--surface)] p-8 rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-[var(--border)]">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-[var(--text)] mb-2">Accept Invitation</h2>
                <p class="text-[var(--text-muted)]">Set up your account for <strong><?= htmlspecialchars($email) ?></strong></p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl mb-6 text-sm font-medium border border-red-100 flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form action="/staff/accept" method="POST" class="space-y-5">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-[var(--text)] mb-2">Full Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-[var(--bg)] border border-[var(--border)] rounded-xl text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] focus:border-transparent transition-shadow outline-none" placeholder="John Doe">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-[var(--text)] mb-2">Create Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full px-4 py-3 bg-[var(--bg)] border border-[var(--border)] rounded-xl text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] focus:border-transparent transition-shadow outline-none" placeholder="Min 8 characters">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-[var(--theme)] to-[var(--theme2)] text-white py-3.5 rounded-xl font-bold text-lg hover:shadow-lg hover:shadow-[var(--theme2)]/30 transition-all active:scale-[0.98]">
                    Join Team
                </button>
            </form>
        </div>
    </div>
</div>
