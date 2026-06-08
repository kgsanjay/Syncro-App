<?php declare(strict_types=1); 

// Dynamically Parse Pricing from DB Settings
$plan1 = (int)($settings['plan_1_month'] ?? 2000);
$plan3 = (int)($settings['plan_3_month'] ?? 4000);
$plan6 = (int)($settings['plan_6_month'] ?? 6000);
$plan12 = (int)($settings['plan_12_month'] ?? 10000);
?>
<nav class="bg-[var(--white)] border-b border-[var(--border)] py-4 px-6 md:px-12 flex justify-between items-center z-10 relative shadow-sm">
    <a href="/" class="text-2xl font-extrabold tracking-tight text-[var(--header)]">
        SYNCRO<span class="text-[var(--theme)]">.</span>
    </a>
    <a href="/login" class="text-sm font-bold text-[var(--text)] hover:text-[var(--theme2)] transition-colors">Already have an account? Log In</a>
</nav>

<div class="flex-1 flex flex-col md:flex-row max-w-7xl mx-auto w-full min-h-[calc(100vh-73px)]">
    
    <div class="w-full md:w-5/12 p-8 md:p-12 lg:p-16 flex flex-col justify-center border-r border-[var(--border)] bg-[var(--white)]">
        <span class="text-[var(--theme2)] font-extrabold tracking-widest uppercase text-xs mb-3 block">Step 1 of 2</span>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight text-[var(--header)]">Select your license.</h1>
        <p class="text-[var(--text)] mb-10 font-medium">Choose your intended package. <strong class="text-[var(--theme2)]">No payment required today.</strong> Start your 14-day free trial instantly.</p>

        <form action="/register/process-trial" method="POST" id="registrationForm" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">

            <div class="space-y-4">
                
                <label class="relative flex cursor-pointer rounded border bg-[var(--white)] p-5 shadow-sm focus-within:ring-2 focus-within:ring-[var(--theme2)] hover:border-[var(--theme2)] transition-all border-[var(--border)]">
                    <input type="radio" name="plan_months" value="1" class="peer sr-only">
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-5 w-5 rounded-full border border-[var(--border)] flex items-center justify-center peer-checked:border-[var(--theme2)] peer-checked:bg-[var(--theme2)] transition-all">
                                <div class="h-2.5 w-2.5 rounded-full bg-[var(--white)] opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <div class="ml-4 flex flex-col">
                                <span class="font-bold text-[var(--header)]">1 Month License</span>
                                <span class="text-xs text-[var(--text)] font-medium mt-0.5">Standard monthly rate.</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black text-[var(--header)]">₹<?= number_format($plan1) ?></span>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded border-2 border-transparent peer-checked:border-[var(--theme2)] pointer-events-none transition-all"></div>
                </label>

                <label class="relative flex cursor-pointer rounded border bg-[var(--white)] p-5 shadow-sm focus-within:ring-2 focus-within:ring-[var(--theme2)] hover:border-[var(--theme2)] transition-all border-[var(--border)]">
                    <input type="radio" name="plan_months" value="3" class="peer sr-only">
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-5 w-5 rounded-full border border-[var(--border)] flex items-center justify-center peer-checked:border-[var(--theme2)] peer-checked:bg-[var(--theme2)] transition-all">
                                <div class="h-2.5 w-2.5 rounded-full bg-[var(--white)] opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <div class="ml-4 flex flex-col">
                                <span class="font-bold text-[var(--header)]">3 Month License</span>
                                <span class="text-xs text-[var(--theme2)] font-bold mt-0.5">Save ₹<?= number_format(($plan1 * 3) - $plan3) ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black text-[var(--header)]">₹<?= number_format($plan3) ?></span>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded border-2 border-transparent peer-checked:border-[var(--theme2)] pointer-events-none transition-all"></div>
                </label>

                <label class="relative flex cursor-pointer rounded border bg-[var(--white)] p-5 shadow-sm focus-within:ring-2 focus-within:ring-[var(--theme2)] hover:border-[var(--theme2)] transition-all border-[var(--border)]">
                    <input type="radio" name="plan_months" value="6" class="peer sr-only">
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-5 w-5 rounded-full border border-[var(--border)] flex items-center justify-center peer-checked:border-[var(--theme2)] peer-checked:bg-[var(--theme2)] transition-all">
                                <div class="h-2.5 w-2.5 rounded-full bg-[var(--white)] opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <div class="ml-4 flex flex-col">
                                <span class="font-bold text-[var(--header)] flex items-center">6 Month License</span>
                                <span class="text-xs text-[var(--theme2)] font-bold mt-0.5">Save ₹<?= number_format(($plan1 * 6) - $plan6) ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black text-[var(--header)]">₹<?= number_format($plan6) ?></span>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded border-2 border-transparent peer-checked:border-[var(--theme2)] pointer-events-none transition-all"></div>
                </label>

                <label class="relative flex cursor-pointer rounded border p-5 shadow-sm focus-within:ring-2 focus-within:ring-[var(--theme2)] hover:border-[var(--theme2)] transition-all overflow-hidden border-[var(--border)] bg-[var(--light)]">
                    <div class="absolute top-0 right-0 bg-[var(--theme2)] text-[var(--white)] text-[10px] font-black uppercase tracking-widest py-1 px-3 rounded-bl">Best Value</div>
                    
                    <input type="radio" name="plan_months" value="12" class="peer sr-only" checked>
                    <div class="flex w-full items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-5 w-5 rounded-full border border-[var(--border)] flex items-center justify-center peer-checked:border-[var(--theme2)] peer-checked:bg-[var(--theme2)] transition-all bg-[var(--white)]">
                                <div class="h-2.5 w-2.5 rounded-full bg-[var(--white)] opacity-0 peer-checked:opacity-100"></div>
                            </div>
                            <div class="ml-4 flex flex-col">
                                <span class="font-bold text-[var(--header)]">1 Year License</span>
                                <span class="text-xs text-[var(--theme2)] font-bold mt-0.5">Massive Savings</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-black text-[var(--header)]">₹<?= number_format($plan12) ?></span>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded border-2 border-transparent peer-checked:border-[var(--theme2)] pointer-events-none transition-all"></div>
                </label>
            </div>
    </div>

    <div class="w-full md:w-7/12 p-8 md:p-12 lg:p-16 bg-[var(--light)] flex flex-col justify-center">
        <span class="text-[var(--text)] font-extrabold tracking-widest uppercase text-xs mb-3 block">Step 2 of 2</span>
        <h2 class="text-2xl md:text-3xl font-extrabold mb-8 tracking-tight text-[var(--header)]">Create your property.</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 bg-[var(--light)] border-l-4 border-[var(--theme)] p-4 rounded shadow-sm flex items-center">
                <svg class="w-5 h-5 text-[var(--theme)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm font-bold text-[var(--header)]">
                    <?php 
                        if ($_GET['error'] === 'exists') echo "An account with that email already exists.";
                        elseif ($_GET['error'] === 'password') echo "Password must be at least 8 characters.";
                        elseif ($_GET['error'] === 'missing') echo "Please fill in all required fields.";
                        else echo "System error. Please try again or contact support.";
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="bg-[var(--white)] rounded p-8 shadow-sm border border-[var(--border)]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Property Name</label>
                    <input type="text" name="property_name" id="property_name" required onkeyup="generateSlug()" placeholder="e.g. The Grand Resort" class="w-full px-4 py-3 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none transition-shadow bg-[var(--light)] focus:bg-[var(--white)] font-medium text-[var(--text)]">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Public Booking URL</label>
                    <div class="flex rounded shadow-sm border border-[var(--border)] overflow-hidden focus-within:ring-2 focus-within:ring-[var(--theme2)] focus-within:border-[var(--theme2)] transition-shadow">
                        <span class="inline-flex items-center px-4 border-r border-[var(--border)] bg-[var(--light)] text-[var(--text)] sm:text-sm font-mono">
                            syncro.adhyancreatives.in/book/
                        </span>
                        <input type="text" name="slug" id="slug" required placeholder="grand-resort" class="flex-1 block w-full min-w-0 px-4 py-3 sm:text-sm outline-none font-mono bg-[var(--white)] text-[var(--text)]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Owner Full Name</label>
                    <input type="text" name="admin_name" required placeholder="John Doe" class="w-full px-4 py-3 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none transition-shadow bg-[var(--light)] focus:bg-[var(--white)] font-medium text-[var(--text)]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Admin Email (Login ID)</label>
                    <input type="email" name="admin_email" required placeholder="owner@hotel.com" class="w-full px-4 py-3 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none transition-shadow bg-[var(--light)] focus:bg-[var(--white)] font-medium text-[var(--text)]">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-[var(--header)] uppercase tracking-wider mb-2">Secure Password</label>
                    <input type="password" name="admin_password" required minlength="8" placeholder="••••••••" class="w-full px-4 py-3 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none transition-shadow bg-[var(--light)] focus:bg-[var(--white)] font-medium text-[var(--text)]">
                </div>
            </div>

            <div class="pt-4 border-t border-[var(--border)]">
                <button type="submit" class="w-full flex justify-center items-center bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-4 px-8 rounded transition-all shadow-md text-lg">
                    Start 14-Day Free Trial
                    <svg class="ml-2 w-5 h-5 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
                <p class="text-center text-xs text-[var(--text)] mt-4 font-bold flex justify-center items-center">
                    <svg class="w-4 h-4 mr-1 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    No credit card required. Cancel anytime.
                </p>
            </div>
        </div>
        </form>
    </div>
</div>

<script>
    function generateSlug() {
        const name = document.getElementById('property_name').value;
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        document.getElementById('slug').value = slug;
    }
</script>