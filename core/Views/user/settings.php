<?php declare(strict_types=1); 

// Dynamically Parse Pricing from DB Settings
$plan1 = (int)($settings['plan_1_month'] ?? 2000);
$plan3 = (int)($settings['plan_3_month'] ?? 4000);
$plan6 = (int)($settings['plan_6_month'] ?? 6000);
$plan12 = (int)($settings['plan_12_month'] ?? 10000);
$gatewayEnabled = ($settings['payment_gateway_enabled'] ?? '0') === '1';
?>

<div class="max-w-[1000px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4">
        <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Account Settings</h1>
        <p class="mt-1 text-sm text-[var(--text)] font-medium">Manage your public profile, payment gateways, OTA integrations, and security.</p>
    </div>

    <div class="space-y-8">
        
        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="bg-[var(--header)] px-6 py-4 border-b border-[var(--theme)] flex justify-between items-center">
                <h3 class="font-bold text-[var(--white)] text-lg uppercase tracking-widest flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    Software License
                </h3>
            </div>
            
            <div class="p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 border-b border-[var(--border)] pb-8">
                    <div>
                        <p class="text-[10px] font-bold text-[var(--text)] uppercase tracking-widest mb-1">Current Plan</p>
                        <p class="text-xl font-bold text-[var(--theme2)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['subscription_plan'] ?? 'Unknown Plan') ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-[var(--text)] uppercase tracking-widest mb-1">License Valid Until</p>
                        <p class="text-xl font-bold text-[var(--header)]">
                            <?php 
                                $billingDate = $hotel['next_billing_date'] ?? null;
                                if ($billingDate) {
                                    $isExpired = strtotime($billingDate) < time();
                                    echo date('F j, Y', strtotime($billingDate));
                                    if ($isExpired) echo ' <span class="text-[10px] bg-[var(--danger)] text-[var(--white)] px-2.5 py-1 rounded-lg ml-2 uppercase tracking-[0.2em] font-black align-middle shadow-lg animate-pulse">Expired</span>';
                                } else {
                                    echo "Not Set";
                                }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="mb-8">
                    <p class="text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Direct Booking Engine URL</p>
                    <div class="flex items-center">
                        <input type="text" readonly value="<?= 'https://' . $_SERVER['HTTP_HOST'] . '/book/' . \Syncro\Security\SecurityManager::sanitizeOutput($hotel['slug'] ?? '') ?>" class="w-full px-4 py-3 border border-[var(--border)] rounded-l bg-[var(--light)] text-sm font-mono text-[var(--text)] outline-none">
                        <a href="<?= base_url() ?>/book/<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['slug'] ?? '') ?>" target="_blank" class="bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] px-6 py-3 rounded-r text-sm font-bold uppercase tracking-widest transition-colors flex items-center">
                            Visit
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                    </div>
                    <p class="text-xs text-[var(--text)] font-medium mt-2">Link this URL to your website's "Book Now" buttons, Instagram, and WhatsApp.</p>
                </div>

                <div class="bg-[var(--light)] p-6 rounded border border-[var(--border)]">
                    <h4 class="font-bold text-[var(--header)] mb-4 text-sm uppercase tracking-widest">Renew / Upgrade License</h4>
                    <form action="<?= $gatewayEnabled ? '/user/settings/renew/init' : '/user/settings/renew/offline' ?>" method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                        <?= csrf_field() ?>">
                        
                        <div class="flex-1 w-full">
                            <label class="block text-[10px] font-bold text-[var(--text)] uppercase tracking-widest mb-2">Select Extension Period</label>
                            <select name="plan_months" required class="w-full px-4 py-3 border-2 border-[var(--border)] rounded-xl bg-[var(--white)] text-sm focus:ring-0 focus:border-[var(--theme2)] outline-none text-[var(--header)] font-black cursor-pointer transition-all">
                                <option value="1">1 Month Extension (₹<?= number_format($plan1) ?>)</option>
                                <option value="3">3 Months Extension (₹<?= number_format($plan3) ?>)</option>
                                <option value="6">6 Months Extension (₹<?= number_format($plan6) ?>)</option>
                                <option value="12" selected>1 Year Extension (₹<?= number_format($plan12) ?>) - Best Value</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full sm:w-auto bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-black py-3 px-10 rounded-xl shadow-xl transition-all text-[10px] uppercase tracking-[0.2em] hover:-translate-y-1 active:scale-95 h-[48px]">
                            <?= $gatewayEnabled ? 'Proceed to Payment' : 'Request Extension' ?>
                        </button>
                    </form>
                    <?php if (!$gatewayEnabled): ?>
                        <p class="text-[10px] text-[var(--theme2)] font-black mt-4 uppercase tracking-widest opacity-60">Payments are currently processed offline. Submit a request to contact our team.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="font-bold text-[var(--header)] text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Public Booking Page Profile
                </h3>
            </div>
            
            <form action="<?= base_url('/user/settings/profile') ?>" method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-6">
                <?= csrf_field() ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Property Name</label>
                        <input type="text" name="property_name" required value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name'] ?? '') ?>" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--header)] font-bold transition-shadow focus:bg-[var(--white)]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">General Amenities (Comma Separated)</label>
                        <input type="text" name="amenities" placeholder="e.g., Free Parking, Pool, Spa, Free WiFi" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['amenities'] ?? '') ?>" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] transition-shadow focus:bg-[var(--white)]">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Hero Welcome Description</label>
                    <textarea name="description" rows="3" placeholder="Escape the ordinary. Discover world-class hospitality..." class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] transition-shadow focus:bg-[var(--white)]"><?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['description'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Upload Hero Background Image (JPG/PNG)</label>
                    <?php if(!empty($hotel['hero_image'])): ?>
                        <div class="mb-3">
                            <img src="<?= htmlspecialchars($hotel['hero_image']) ?>" class="h-24 w-auto rounded border border-[var(--border)] shadow-sm object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="hero_image" accept="image/jpeg, image/png, image/webp" class="w-full px-3 py-2 border border-[var(--border)] rounded text-sm outline-none bg-[var(--white)] text-[var(--text)] transition-colors file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-bold file:bg-[var(--light)] file:text-[var(--theme2)] hover:file:bg-[var(--border)] cursor-pointer">
                    <p class="text-[10px] text-[var(--text)] font-medium mt-1">Leave empty to keep your current image. Ideal aspect ratio: 16:9 (e.g. 1920x1080px).</p>
                </div>

                <div class="flex justify-end pt-4 border-t border-[var(--border)]">
                    <button type="submit" class="bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-3 px-8 rounded shadow transition-colors text-xs uppercase tracking-widest">
                        Save Profile Details
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="font-bold text-[var(--header)] text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Direct Payment Gateway Setup
                </h3>
            </div>
            
            <form action="<?= base_url('/user/settings/payment') ?>" method="POST" class="p-6 md:p-8 space-y-6">
                <?= csrf_field() ?>">
                
                <p class="text-sm text-[var(--text)] font-medium mb-6">Enter your PhonePe Merchant API Keys to receive guest payments directly to your bank account with zero commissions. Leave blank to process payments manually at the front desk.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">PhonePe Merchant ID</label>
                        <input type="text" name="phonepe_merchant_id" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['phonepe_merchant_id'] ?? '') ?>" placeholder="e.g., MUID..." class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">PhonePe Salt Key</label>
                        <input type="password" name="phonepe_salt_key" placeholder="••••••••••••••••••••••••" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        <p class="text-[10px] text-[var(--text)] font-medium mt-1">Stored securely using 256-bit AES encryption.</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Environment</label>
                        <select name="phonepe_env" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] transition-shadow focus:bg-[var(--white)]">
                            <option value="uat" <?= ($hotel['phonepe_env'] ?? 'uat') === 'uat' ? 'selected' : '' ?>>UAT (Testing)</option>
                            <option value="prod" <?= ($hotel['phonepe_env'] ?? '') === 'prod' ? 'selected' : '' ?>>Production (Live)</option>
                        </select>
                        <p class="text-[10px] text-[var(--text)] font-medium mt-1">Use UAT for testing fake transactions before going live.</p>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-[var(--border)]">
                    <button type="submit" class="bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-3 px-8 rounded shadow transition-colors text-xs uppercase tracking-widest">
                        Save Payment Credentials
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="font-bold text-[var(--header)] text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    OTA Channel API Setup
                </h3>
            </div>
            
            <form action="<?= base_url('/user/settings/ota') ?>" method="POST" class="p-6 md:p-8 space-y-8">
                <?= csrf_field() ?>">
                
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-lg mb-8">
                    <h4 class="text-sm font-bold text-indigo-900 uppercase tracking-widest mb-2">Syncro API Access Token</h4>
                    <p class="text-xs text-indigo-700 mb-3">Provide this token to external channel managers to allow them to push bookings to Syncro.</p>
                    <div class="flex">
                        <input type="text" readonly value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['api_token'] ?? 'No token generated yet') ?>" class="w-full px-4 py-2 border border-indigo-200 rounded-l bg-white text-sm font-mono text-indigo-900 select-all">
                        <a href="<?= base_url('/user/settings/generate-token') ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-r text-xs uppercase tracking-widest flex items-center transition-colors">
                            Generate New
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">MakeMyTrip</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key (Bearer Token)</label>
                            <input type="text" name="mmt_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['mmt_api_key'] ?? '') ?>" placeholder="Enter MMT API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Booking.com</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">XML Username</label>
                                <input type="text" name="booking_com_username" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['booking_com_username'] ?? '') ?>" placeholder="Enter Username" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">XML Password</label>
                                <input type="password" name="booking_com_password" placeholder="••••••••••••••••" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Agoda</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">YCS API Key</label>
                            <input type="text" name="agoda_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['agoda_api_key'] ?? '') ?>" placeholder="Enter Agoda API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Expedia</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">EQC Username</label>
                                <input type="text" name="expedia_username" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['expedia_username'] ?? '') ?>" placeholder="Enter EQC Username" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">EQC Password</label>
                                <input type="password" name="expedia_password" placeholder="••••••••••••••••" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Cleartrip</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key</label>
                            <input type="text" name="cleartrip_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['cleartrip_api_key'] ?? '') ?>" placeholder="Enter Cleartrip API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Yatra</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key</label>
                            <input type="text" name="yatra_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['yatra_api_key'] ?? '') ?>" placeholder="Enter Yatra API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">EaseMyTrip</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key</label>
                            <input type="text" name="easemytrip_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['easemytrip_api_key'] ?? '') ?>" placeholder="Enter EaseMyTrip API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Paytm Hotels</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key</label>
                            <input type="text" name="paytm_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['paytm_api_key'] ?? '') ?>" placeholder="Enter Paytm API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-6">
                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">Airbnb</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Host API Key</label>
                            <input type="text" name="airbnb_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['airbnb_api_key'] ?? '') ?>" placeholder="Enter Airbnb API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-bold text-[var(--header)] uppercase tracking-widest mb-4 border-b border-[var(--border)] pb-2">TripAdvisor</h4>
                        <div>
                            <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">API Key</label>
                            <input type="text" name="tripadvisor_api_key" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['tripadvisor_api_key'] ?? '') ?>" placeholder="Enter TripAdvisor API Key" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-[var(--border)] mt-8">
                    <button type="submit" class="bg-[var(--header)] hover:bg-[var(--theme2)] text-[var(--white)] font-bold py-3 px-8 rounded shadow transition-colors text-xs uppercase tracking-widest">
                        Save OTA Credentials
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)]">
                <h3 class="font-bold text-[var(--header)] text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--text)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Password Configuration
                </h3>
            </div>
            
            <form action="<?= base_url('/user/settings') ?>" method="POST" class="p-6 md:p-8 space-y-6">
                <?= csrf_field() ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Current Password</label>
                        <input type="password" name="current_password" required class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">New Password</label>
                        <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="8" class="w-full px-4 py-3 border border-[var(--border)] rounded bg-[var(--light)] text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none text-[var(--text)] font-mono transition-shadow focus:bg-[var(--white)]">
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-[var(--border)]">
                    <button type="submit" class="bg-[var(--white)] border border-[var(--border)] hover:bg-[var(--light)] text-[var(--header)] font-bold py-3 px-8 rounded shadow-sm transition-colors text-xs uppercase tracking-widest">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden mt-8">
            <div class="bg-[var(--light)] px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
                <h3 class="font-bold text-[var(--header)] text-lg flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[var(--theme2)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Two-Factor Authentication (2FA)
                </h3>
            </div>
            
            <div class="p-6 md:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div>
                    <?php $is2faEnabled = !empty($currentUser['two_factor_enabled']); ?>
                    
                    <?php if ($is2faEnabled): ?>
                        <p class="font-black text-[var(--success)] flex items-center text-[10px] uppercase tracking-[0.2em]">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Account Status: Enforced
                        </p>
                        <p class="text-[10px] text-[var(--text)] mt-2 font-black uppercase opacity-40">Your account is heavily protected against unauthorized access.</p>
                    <?php else: ?>
                        <p class="font-black text-[var(--danger)] flex items-center text-[10px] uppercase tracking-[0.2em]">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Account Status: Vulnerable
                        </p>
                        <p class="text-[10px] text-[var(--text)] mt-2 font-black uppercase opacity-40">We highly recommend enabling 2FA to protect your financial data.</p>
                    <?php endif; ?>
                </div>

                <div class="w-full md:w-auto">
                    <?php if ($is2faEnabled): ?>
                        <form action="<?= base_url('/user/settings/2fa/disable') ?>" method="POST" class="m-0" onsubmit="return confirm('Are you sure you want to disable 2FA? This will make your account vulnerable.');">
                            <?= csrf_field() ?>">
                            <button type="submit" class="w-full md:w-auto bg-[var(--danger)]/5 text-[var(--danger)] border-2 border-[var(--danger)]/30 px-8 py-4 rounded-xl font-black hover:bg-[var(--danger)] hover:text-[var(--white)] transition-all text-[10px] uppercase tracking-[0.2em] shadow-lg">
                                Disable Security Layer
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?= base_url('/user/settings/2fa/setup') ?>" class="block text-center w-full md:w-auto bg-[var(--theme2)] text-[var(--white)] px-8 py-4 rounded-xl font-black hover:bg-[var(--header)] transition-all text-[10px] uppercase tracking-[0.2em] shadow-2xl hover:-translate-y-1 active:scale-95">
                            Enable Two-Factor (2FA)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>