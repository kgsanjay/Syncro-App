<?php declare(strict_types=1); ?>

<nav class="fixed w-full z-50 bg-[var(--white)]/95 backdrop-blur-md border-b border-[var(--border)] transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center">
                <span class="text-2xl font-extrabold tracking-tight text-[var(--header)]">
                    SYNCRO<span class="text-[var(--theme)]">.</span>
                </span>
            </div>
            <div class="hidden md:flex space-x-10">
                <a href="#features" class="text-[var(--text)] hover:text-[var(--theme2)] text-sm font-bold uppercase tracking-wider transition-colors">Platform Features</a>
                <a href="#pricing" class="text-[var(--text)] hover:text-[var(--theme2)] text-sm font-bold uppercase tracking-wider transition-colors">Plans</a>
            </div>
            <div class="flex items-center space-x-5">
                <a href="<?= base_url('/login') ?>" class="text-[var(--text)] hover:text-[var(--header)] text-sm font-bold transition-colors">Login</a>
                <a href="<?= base_url('/register') ?>" class="bg-[var(--theme2)] text-[var(--white)] font-bold px-7 py-2.5 rounded hover:bg-[var(--header)] shadow-md transition-all text-xs uppercase tracking-widest">
                    Start 14-Day Free Trial
                </a>
            </div>
        </div>
    </div>
</nav>

<section class="relative pt-40 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-[var(--light)]">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="inline-flex items-center space-x-2.5 bg-[var(--white)] border border-[var(--border)] text-[var(--theme2)] px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest mb-8 shadow-sm">
            <span class="relative flex h-2 w-2">
              <span class="absolute inline-flex h-full w-full rounded-full bg-[var(--theme)] opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--theme)]"></span>
            </span>
            <span>The Enterprise Operating System for Hotels</span>
        </div>
        
        <h1 class="text-5xl md:text-7xl lg:text-[5.5rem] font-extrabold tracking-tighter mb-8 text-[var(--header)] leading-[1.05]">
            Outsmart the OTAs.<br />
            <span class="text-[var(--theme2)]">Own your revenue.</span>
        </h1>
        
        <p class="text-lg md:text-xl text-[var(--text)] mb-10 max-w-3xl mx-auto leading-relaxed font-medium">
            Stop paying 15% commissions. Syncro equips your property with a zero-commission booking engine, a 10-channel OTA background manager, promo code generation, and a complete enterprise PMS.
        </p>
        
        <div class="flex flex-col sm:flex-row justify-center items-center space-y-4 sm:space-y-0 sm:space-x-6">
            <a href="<?= base_url('/register') ?>" class="w-full sm:w-auto bg-[var(--theme2)] text-[var(--white)] font-extrabold text-lg px-10 py-4 rounded shadow-lg hover:bg-[var(--header)] transition-all duration-300">
                Start 14-Day Free Trial
            </a>
            <a href="#pricing" class="w-full sm:w-auto bg-[var(--white)] border border-[var(--border)] text-[var(--header)] font-bold text-lg px-10 py-4 rounded hover:bg-[var(--light)] transition-all duration-300 shadow-sm">
                View Subscription Plans
            </a>
        </div>
    </div>

    <div class="mt-24 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-[var(--light)] to-transparent z-20"></div>
        <div class="rounded-t border border-[var(--border)] shadow-xl overflow-hidden bg-[var(--white)] relative">
            <div class="bg-[var(--light)] border-b border-[var(--border)] px-5 py-4 flex items-center space-x-2.5">
                <div class="w-3 h-3 rounded-full bg-[var(--border)]"></div>
                <div class="w-3 h-3 rounded-full bg-[var(--border)]"></div>
                <div class="w-3 h-3 rounded-full bg-[var(--border)]"></div>
            </div>
            <img src="<?= base_url('/assets/images/syncro-dashboard.png') ?>" alt="Syncro Dashboard Overview" class="w-full h-auto">
        </div>
    </div>
</section>

<section id="features" class="py-24 bg-[var(--white)] relative z-30 border-t border-[var(--border)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-20">
            <span class="text-[var(--theme2)] font-bold tracking-widest uppercase text-xs mb-3 block">Enterprise Platform Features</span>
            <h2 class="text-3xl md:text-5xl font-extrabold text-[var(--header)] mb-5 tracking-tight">Everything you need. Nothing you don't.</h2>
            <p class="text-lg text-[var(--text)] max-w-2xl mx-auto font-medium">A beautifully refined decentralized architecture designed to replace 5 different legacy hospitality software tools.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Direct Booking & PhonePe</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Generate premium, SEO-optimized landing pages dynamically. Process payments directly to your bank via our deeply integrated PhonePe Gateway.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Automated OTA Manager</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Background cron-engine automatically syncs your inventory and rates with Booking.com, MakeMyTrip, Agoda, Airbnb, and other major channels.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Dynamic Pricing Engine</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Maximize yield with AI-inspired logic. Rule-based automated pricing dynamically adjusts your rates based on real-time occupancy and demand.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Real-Time Notifications</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Stay instantly informed. Server-Sent Events (SSE) deliver live updates for new bookings, cancellations, and housekeeping alerts directly to your dashboard.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Progressive Web App (PWA)</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Install Syncro on any mobile device without the App Store. Experience native-like speed, offline capabilities, and instant access from your home screen.</p>
            </div>
            
            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Revenue Analytics</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Deep dive into your property's performance. Export financial data, track RevPAR, occupancy rates, and channel profitability in real-time.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Promo Code Engine</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Drive revenue and guest loyalty. Instantly generate percentage or flat-rate discount codes for special marketing campaigns.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Housekeeping & POS</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Track real-time room cleanliness statuses and easily attach physical Point-of-Sale charges (minibar, spa) to a guest's master folio.</p>
            </div>

            <div class="bg-[var(--light)] rounded-xl p-10 border border-[var(--border)] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col group">
                <div class="w-14 h-14 bg-[var(--white)] text-[var(--theme2)] rounded-lg flex items-center justify-center mb-6 border border-[var(--border)] shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-bold text-[var(--header)] mb-3 tracking-tight">Built-in Helpdesk</h3>
                <p class="text-[var(--text)] leading-relaxed font-medium">Never get stuck. Access direct threaded support ticketing with platform administrators right from your dashboard.</p>
            </div>

        </div>
    </div>
</section>

<section id="pricing" class="py-32 bg-[var(--header)] relative overflow-hidden">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-20">
            <span class="text-[var(--theme)] font-extrabold tracking-widest uppercase text-xs mb-4 block">SaaS Subscription Models</span>
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-extrabold mb-6 text-[var(--white)] tracking-tighter">Choose your prepaid plan.</h2>
            <p class="text-xl text-[var(--light)] opacity-80 font-medium max-w-2xl mx-auto">Start with a 14-day free trial. Then, lock in massive discounts by committing to a longer software license.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">
            
            <div class="bg-[var(--white)]/5 rounded p-8 border border-[var(--white)]/10 flex flex-col hover:border-[var(--theme2)] transition-all duration-300 shadow-lg">
                <h3 class="text-xl font-bold text-[var(--white)] mb-2 uppercase tracking-wide">1 Month License</h3>
                <div class="my-4 flex flex-col">
                    <span class="text-sm text-[var(--white)]/50 line-through font-semibold">₹2,500/mo</span>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-4xl font-black text-[var(--white)] tracking-tight">₹2,000</span>
                    </div>
                </div>
                <p class="text-sm text-[var(--light)]/70 mb-6 border-b border-[var(--white)]/10 pb-6">Standard monthly rate. Includes full enterprise access.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-[var(--light)] flex-1 font-medium">
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 14-Day Free Trial</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Zero-Commission Booking Engine</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 10+ Global OTA Sync Manager</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Promo Code Generator</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Unlimited Staff Accounts</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Helpdesk Support Ticket Access</li>
                </ul>
                <a href="<?= base_url('/register') ?>" class="block w-full text-center bg-[var(--white)]/10 hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-3.5 rounded transition-colors uppercase tracking-widest text-xs">Select 1 Month</a>
            </div>

            <div class="bg-[var(--white)]/5 rounded p-8 border border-[var(--white)]/10 flex flex-col hover:border-[var(--theme2)] transition-all duration-300 shadow-lg">
                <h3 class="text-xl font-bold text-[var(--white)] mb-2 uppercase tracking-wide">3 Month License</h3>
                <div class="my-4 flex flex-col">
                    <span class="text-sm text-[var(--white)]/50 line-through font-semibold">₹7,500/qtr</span>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-4xl font-black text-[var(--white)] tracking-tight">₹4,000</span>
                    </div>
                </div>
                <p class="text-sm text-[var(--theme)] mb-6 border-b border-[var(--white)]/10 pb-6 font-bold">Save ₹3,500 instantly.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-[var(--light)] flex-1 font-medium">
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 14-Day Free Trial</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Zero-Commission Booking Engine</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 10+ Global OTA Sync Manager</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Promo Code Generator</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Unlimited Staff Accounts</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Helpdesk Support Ticket Access</li>
                </ul>
                <a href="<?= base_url('/register') ?>" class="block w-full text-center bg-[var(--white)]/10 hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-3.5 rounded transition-colors uppercase tracking-widest text-xs">Select 3 Months</a>
            </div>

            <div class="bg-[var(--white)]/5 rounded p-8 border border-[var(--white)]/10 flex flex-col hover:border-[var(--theme2)] transition-all duration-300 shadow-lg">
                <h3 class="text-xl font-bold text-[var(--white)] mb-2 uppercase tracking-wide">6 Month License</h3>
                <div class="my-4 flex flex-col">
                    <span class="text-sm text-[var(--white)]/50 line-through font-semibold">₹15,000/6-mo</span>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-4xl font-black text-[var(--white)] tracking-tight">₹6,000</span>
                    </div>
                </div>
                <p class="text-sm text-[var(--theme)] mb-6 border-b border-[var(--white)]/10 pb-6 font-bold">Save ₹9,000 instantly.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-[var(--light)] flex-1 font-medium">
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 14-Day Free Trial</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Zero-Commission Booking Engine</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> 10+ Global OTA Sync Manager</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Promo Code Generator</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Unlimited Staff Accounts</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-bold">✓</span> Priority Helpdesk Routing</li>
                </ul>
                <a href="<?= base_url('/register') ?>" class="block w-full text-center bg-[var(--white)]/10 hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-3.5 rounded transition-colors uppercase tracking-widest text-xs">Select 6 Months</a>
            </div>

            <div class="bg-[var(--theme2)] rounded p-8 border-2 border-[var(--theme)] flex flex-col relative shadow-2xl scale-105 z-10">
                <div class="absolute top-0 right-0 bg-[var(--theme)] text-[var(--header)] text-[10px] font-black uppercase tracking-widest py-1.5 px-4 rounded-bl rounded-tr-sm">Best Value</div>
                <h3 class="text-xl font-bold text-[var(--white)] mb-2 uppercase tracking-wide">1 Year License</h3>
                <div class="my-4 flex flex-col">
                    <span class="text-sm text-[var(--white)]/50 line-through font-semibold">₹34,000/yr</span>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="text-5xl font-black text-[var(--white)] tracking-tighter">₹10,000</span>
                    </div>
                </div>
                <p class="text-sm text-[var(--theme)] mb-6 border-b border-[var(--white)]/20 pb-6 font-bold">Massive ₹24,000 total savings.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-[var(--white)] flex-1 font-medium">
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> 14-Day Free Trial</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> Zero-Commission Booking Engine</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> 10+ Global OTA Sync Manager</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> Promo Code Generator</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> Unlimited Staff Accounts</li>
                    <li class="flex items-start"><span class="text-[var(--theme)] mr-3 font-black">✓</span> Priority Level 1 Support</li>
                </ul>
                <a href="<?= base_url('/register') ?>" class="block w-full text-center bg-[var(--theme)] text-[var(--header)] font-extrabold py-4 rounded hover:bg-[var(--white)] shadow-md transition-all uppercase tracking-widest text-xs">Get 1 Year Plan</a>
            </div>

        </div>
    </div>
</section>

<footer class="bg-[var(--header)] py-16 border-t border-[var(--white)]/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
        <div class="mb-8 md:mb-0 text-center md:text-left">
            <span class="text-2xl font-extrabold tracking-tight text-[var(--white)] block mb-2">
                SYNCRO<span class="text-[var(--theme)]">.</span>
            </span>
            <p class="text-[var(--white)]/50 text-sm font-medium">The enterprise operating system for independent hotels.</p>
        </div>
        
        <div class="flex items-center space-x-8 text-sm">
            <a href="<?= base_url('/register') ?>" class="text-[var(--theme)] hover:text-[var(--white)] transition-colors font-bold tracking-wide">Start Free Trial</a>
            <a href="mailto:sales@adhyancreatives.in" class="text-[var(--white)]/70 hover:text-[var(--white)] transition-colors font-semibold tracking-wide">Contact Sales</a>
            <a href="<?= base_url('/login') ?>" class="text-[var(--white)]/70 hover:text-[var(--white)] transition-colors font-semibold tracking-wide">Client Login</a>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 pt-8 border-t border-[var(--white)]/10 text-center flex flex-col md:flex-row justify-between items-center gap-6 md:gap-4">
        <p class="text-[var(--white)]/50 text-xs uppercase tracking-widest font-bold">
            &copy; <?= date('Y') ?> Syncro Hospitality by Adhyan Creatives.
        </p>

        <div class="flex flex-wrap justify-center gap-6 text-[var(--white)]/50 text-xs uppercase tracking-widest font-bold">
            <a href="<?= base_url('/terms') ?>" class="hover:text-[var(--theme)] transition-colors">Terms of Use</a>
            <a href="<?= base_url('/privacy') ?>" class="hover:text-[var(--theme)] transition-colors">Privacy Policy</a>
            <a href="<?= base_url('/refund') ?>" class="hover:text-[var(--theme)] transition-colors">Shipping & Refunds</a>
        </div>

        <div class="mt-2 md:mt-0">
            <span class="text-[var(--white)]/30 text-xs font-mono font-bold">v1.1.0 Prod</span>
        </div>
    </div>
</footer>