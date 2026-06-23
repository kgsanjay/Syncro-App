<?php declare(strict_types=1); 

// Fallbacks if hotel hasn't uploaded data yet
$heroImage = !empty($hotel['hero_image']) ? $hotel['hero_image'] : "https://images.unsplash.com/photo-1542314831-c6a420325142?auto=format&fit=crop&q=80&w=2000";
$hotelDesc = !empty($hotel['description']) ? $hotel['description'] : "Escape the ordinary. Discover world-class hospitality, breathtaking views, and unparalleled comfort at " . $hotel['property_name'] . ".";

?>

<style>
    :root {
        --theme: var(--theme);
        --theme2: var(--header);
        --header: var(--header);
        --text: var(--text);
        --light: var(--light);
        --border: var(--light);
        --white: var(--white);
    }
    html { scroll-behavior: smooth; }
</style>

<nav class="absolute top-0 w-full z-50 p-6 flex justify-between items-center bg-gradient-to-b from-black/80 to-transparent backdrop-blur-[2px]">
    <h1 class="text-[var(--white)] text-2xl font-black uppercase tracking-[0.3em] m-0 drop-shadow-lg"><?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name']) ?></h1>
    <a href="#booking-section" class="border-2 border-[var(--theme)]/60 text-[var(--white)] py-2.5 px-8 rounded-full font-black uppercase text-[10px] no-underline transition-all duration-500 bg-[var(--theme)]/10 hover:bg-[var(--theme)] hover:text-[var(--header)] hover:scale-105 active:scale-95 shadow-xl">Reserve Now</a>
</nav>

<header class="relative h-[90vh] min-h-[700px] flex flex-col items-center justify-center bg-cover bg-center bg-fixed text-center" style="background-image: url('<?= htmlspecialchars($heroImage) ?>');">
    <div class="absolute inset-0 bg-gradient-to-t from-[var(--header)] via-black/40 to-black/60"></div>
    <div class="relative z-10 px-5 text-[var(--white)] max-w-5xl mx-auto mt-20">
        <div class="inline-block mb-6 px-4 py-1.5 border border-[var(--theme)]/30 rounded-full bg-black/20 backdrop-blur-sm">
            <span class="text-[var(--theme)] font-black tracking-[5px] uppercase text-[10px]">A Signature Collection Experience</span>
        </div>
        <h2 class="text-6xl md:text-8xl font-black leading-tight mb-8 text-[var(--white)] tracking-tighter drop-shadow-2xl">Elevate Your Stay.</h2>
        <p class="text-xl md:text-2xl font-medium leading-relaxed text-[var(--light)]/90 mb-12 max-w-3xl mx-auto drop-shadow-md">
            <?= nl2br(\Syncro\Security\SecurityManager::sanitizeOutput($hotelDesc)) ?>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#booking-section" class="inline-block bg-[var(--theme)] text-[var(--header)] font-black py-5 px-12 rounded-lg text-[11px] uppercase tracking-[0.2em] transition-all duration-500 shadow-2xl hover:bg-[var(--white)] hover:-translate-y-2 active:scale-95">Check Availability</a>
            <a href="#rooms-section" class="inline-block bg-white/10 backdrop-blur-md text-[var(--white)] border border-white/20 font-black py-5 px-12 rounded-lg text-[11px] uppercase tracking-[0.2em] transition-all duration-500 hover:bg-white/20 hover:-translate-y-1">View Suites</a>
        </div>
    </div>
</header>

<?php if(!empty($hotel['amenities'])): ?>
<div class="bg-[var(--header)] py-6 relative z-20 border-b border-[var(--theme)]/30 shadow-2xl">
    <div class="max-w-7xl mx-auto flex flex-wrap justify-center gap-x-12 gap-y-4 px-6">
        <?php 
            $hotelAmenities = explode(',', $hotel['amenities']);
            foreach($hotelAmenities as $amenity): 
                $amenity = trim($amenity);
                if(empty($amenity)) continue;
        ?>
            <span class="text-[var(--white)] text-[10px] font-black uppercase tracking-[0.15em] flex items-center opacity-80 hover:opacity-100 transition-opacity">
                <svg class="text-[var(--theme)] w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                <?= \Syncro\Security\SecurityManager::sanitizeOutput($amenity) ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<section class="py-32 px-5 bg-[var(--light)]" id="rooms-section">
    <div class="text-center mb-20 max-w-2xl mx-auto">
        <span class="text-[var(--theme)] font-black uppercase tracking-[0.4em] text-[10px]">Exceptional Comfort</span>
        <h3 class="text-4xl md:text-5xl font-black text-[var(--header)] mt-4 tracking-tighter">The Signature Suites</h3>
        <div class="h-1.5 w-20 bg-[var(--theme)] mx-auto mt-6 rounded-full"></div>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">
        <?php $fallbackImages = [
            "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&q=80&w=800",
            "https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&q=80&w=800",
            "https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&q=80&w=800"
        ]; ?>
        
        <?php $imgIndex = 0; foreach ($rooms as $room): ?>
            <?php 
                $roomImg = !empty($room['image_url']) ? $room['image_url'] : $fallbackImages[$imgIndex % 3];
                $roomDesc = !empty($room['description']) ? $room['description'] : "A meticulously designed space offering the ultimate relaxation and comfort.";
                $imgIndex++;
            ?>
            <div class="bg-[var(--white)] rounded-3xl overflow-hidden border border-[var(--border)] transition-all duration-500 flex flex-col hover:-translate-y-4 hover:shadow-[0_40px_80px_-15px_rgba(0,0,0,0.1)] group">
                <div class="relative h-80 overflow-hidden">
                    <img src="<?= htmlspecialchars($roomImg) ?>" alt="<?= htmlspecialchars($room['name']) ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="absolute top-6 right-6 bg-[var(--header)] py-2.5 px-5 rounded-xl font-black text-[var(--theme)] text-sm shadow-xl border border-[var(--theme)]/30">
                        ₹<?= number_format((float)($room['base_price'] ?? 0)) ?> <span class="text-[9px] text-[var(--white)]/50 ml-1">/ NIGHT</span>
                    </div>
                </div>
                <div class="p-10 flex-grow flex flex-col">
                    <h4 class="text-3xl font-black text-[var(--header)] mb-4 tracking-tight"><?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?></h4>
                    <p class="text-[var(--text)] text-sm leading-relaxed mb-8 opacity-80 font-medium"><?= \Syncro\Security\SecurityManager::sanitizeOutput($roomDesc) ?></p>
                    
                    <div class="mb-8 flex flex-wrap gap-2">
                        <?php if(!empty($room['amenities'])): ?>
                            <?php foreach(explode(',', $room['amenities']) as $amenity): ?>
                                <span class="bg-[var(--light)] text-[var(--header)] text-[9px] font-black py-1.5 px-3 rounded-lg uppercase inline-block border border-[var(--border)] tracking-wider"><?= trim($amenity) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <button onclick="selectRoom(<?= (int)$room['id'] ?>)" class="w-full bg-[var(--theme2)] text-[var(--white)] font-black py-4.5 rounded-2xl cursor-pointer uppercase text-[10px] tracking-[0.2em] transition-all duration-500 mt-auto hover:bg-[var(--header)] hover:shadow-xl active:scale-95">Reserve Suite</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="booking-section" class="py-40 px-5 bg-[var(--light)] relative overflow-hidden">
    <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-[var(--theme)] opacity-5 blur-[120px] rounded-full -translate-y-1/2 translate-x-1/2"></div>
    <div class="absolute bottom-0 left-0 w-[600px] h-[600px] bg-[var(--theme2)] opacity-5 blur-[120px] rounded-full translate-y-1/2 -translate-x-1/2"></div>

    <div class="max-w-7xl mx-auto shadow-[0_50px_100px_-20px_rgba(0,0,0,0.15)] rounded-[40px] overflow-hidden flex flex-col lg:flex-row border border-[var(--border)] bg-[var(--white)] relative z-10">
        
        <?php if ($success): ?>
            <div class="w-full p-28 text-center bg-[var(--white)] flex flex-col items-center justify-center animate-fade-in">
                <div class="w-24 h-24 bg-[var(--header)] text-[var(--success)] rounded-full flex items-center justify-center mb-8 shadow-inner border-4 border-[var(--success)]/20">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-5xl font-black text-[var(--header)] mb-6 tracking-tighter">Reservation Confirmed.</h3>
                <p class="text-[var(--text)] mb-12 text-xl font-medium max-w-2xl">A digital confirmation has been sent to your inbox. We look forward to welcoming you to <strong class="text-[var(--header)]"><?= htmlspecialchars($hotel['property_name']) ?></strong>.</p>
                <a href="<?= base_url('/book/<?= htmlspecialchars($slug) ?>') ?>" class="inline-block bg-[var(--theme2)] text-[var(--white)] font-black py-5 px-14 rounded-2xl text-[11px] uppercase tracking-[0.25em] transition-all duration-500 shadow-2xl hover:bg-[var(--header)] hover:-translate-y-2 active:scale-95">Book Another Suite</a>
            </div>
        <?php else: ?>

            <div class="flex-1 p-12 lg:p-20 bg-[var(--white)]">
                <div class="mb-14">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-px w-8 bg-[var(--theme)]"></div>
                        <span class="text-[var(--theme)] font-black tracking-[4px] uppercase text-[10px]">Authorized Suite Reservation</span>
                    </div>
                    <h3 class="text-4xl md:text-5xl font-black text-[var(--header)] tracking-tighter mb-4">Secure Your Experience.</h3>
                    <p class="text-[var(--text)] text-base font-medium opacity-60">Finalize your luxury stay at our priority boutique property.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-[var(--danger)]/5 border-l-4 border-[var(--danger)] text-[var(--danger)] p-6 rounded-r-2xl mb-12 text-sm font-black shadow-sm flex items-center gap-4 animate-shake">
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form id="bookingForm" action="<?= base_url('/book/<?= htmlspecialchars($slug) ?>') ?>" method="POST" class="space-y-8">
                    <input type="hidden" name="booking_id" id="booking_id" value="">
                    <?= csrf_field() ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Guest Identity</label>
                            <input type="text" name="guest_name" placeholder="Legal Full Name" required class="w-full p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] placeholder:opacity-30">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Communication Hub</label>
                            <input type="email" name="guest_email" placeholder="example@luxury.com" required class="w-full p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] placeholder:opacity-30">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Suite Selection</label>
                        <div class="relative">
                            <select name="room_type_id" id="roomSelect" required class="w-full p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] appearance-none cursor-pointer">
                                <option value="" disabled selected>Select Your Suite experience...</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= (int)$room['id'] ?>"><?= htmlspecialchars($room['name']) ?> — ₹<?= number_format((float)$room['base_price']) ?> / Night</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-5 text-[var(--header)] opacity-40">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Privilege Code</label>
                        <div class="flex gap-4">
                            <input type="text" name="promo_code" id="promoCode" placeholder="e.g. ELITE2024" class="flex-1 p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black uppercase transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] tracking-[0.3em] placeholder:tracking-normal placeholder:opacity-30">
                            <button type="button" id="applyPromoBtn" class="bg-[var(--header)] text-[var(--theme)] px-10 border-none rounded-2xl font-black uppercase cursor-pointer text-[10px] tracking-[0.2em] transition-all duration-300 hover:bg-[var(--theme2)] hover:text-[var(--white)] shadow-lg active:scale-95">Verify</button>
                        </div>
                        <p id="promoMessage" class="text-[11px] mt-3 font-black tracking-wide px-1"></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Arrival Date</label>
                            <input type="date" name="check_in" id="checkIn" min="<?= date('Y-m-d') ?>" required class="w-full p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] uppercase tracking-widest cursor-pointer">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40 ml-1">Departure Date</label>
                            <input type="date" name="check_out" id="checkOut" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required class="w-full p-5 rounded-2xl border-2 border-[var(--border)] bg-[var(--light)] text-[var(--header)] font-black transition-all focus:bg-[var(--white)] focus:outline-none focus:ring-4 focus:ring-[var(--theme)]/10 focus:border-[var(--theme)] uppercase tracking-widest cursor-pointer">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[var(--header)] text-[var(--white)] p-6 border-none rounded-2xl font-black uppercase tracking-[0.3em] text-[11px] cursor-pointer transition-all duration-500 hover:-translate-y-2 hover:shadow-[0_20px_40px_-5px_var(--header)] shadow-2xl flex justify-center items-center gap-4 group">
                        <svg class="w-6 h-6 text-[var(--theme)] group-hover:scale-125 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Initiate Secure Checkout
                    </button>
                </form>
            </div>

            <div class="w-full lg:w-[450px] bg-[var(--header)] p-12 lg:p-20 text-[var(--white)] flex flex-col justify-center relative overflow-hidden">
                <div class="absolute -top-32 -right-32 w-80 h-80 bg-[var(--theme)] opacity-10 rounded-full blur-[100px]"></div>
                <div class="absolute -bottom-32 -left-32 w-80 h-80 bg-[var(--theme2)] opacity-40 rounded-full blur-[100px]"></div>

                <div class="relative z-10">
                    <h4 class="text-3xl font-black text-[var(--white)] mb-12 tracking-tighter italic">Direct <span class="text-[var(--theme)]">Privileges.</span></h4>
                    
                    <ul class="list-none p-0 m-0 space-y-10">
                        <li class="flex items-start gap-5">
                            <div class="mt-1 bg-[var(--theme)]/20 text-[var(--theme)] rounded-xl p-2 shadow-sm shrink-0 border border-[var(--theme)]/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <strong class="block text-[var(--white)] font-black text-[11px] uppercase tracking-widest mb-2">Best Rate Guarantee</strong>
                                <span class="text-xs opacity-50 leading-relaxed font-medium block">Secure the absolute lowest verified price globally by booking direct.</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-5">
                            <div class="mt-1 bg-[var(--theme)]/20 text-[var(--theme)] rounded-xl p-2 shadow-sm shrink-0 border border-[var(--theme)]/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <strong class="block text-[var(--white)] font-black text-[11px] uppercase tracking-widest mb-2">Priority Selection</strong>
                                <span class="text-xs opacity-50 leading-relaxed font-medium block">Direct guests receive priority for suite upgrades and early access.</span>
                            </div>
                        </li>
                        <li class="flex items-start gap-5">
                            <div class="mt-1 bg-[var(--theme)]/20 text-[var(--theme)] rounded-xl p-2 shadow-sm shrink-0 border border-[var(--theme)]/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div>
                                <strong class="block text-[var(--white)] font-black text-[11px] uppercase tracking-widest mb-2">Zero Commission Fee</strong>
                                <span class="text-xs opacity-50 leading-relaxed font-medium block">No hidden platform markups or third-party reservation expenses.</span>
                            </div>
                        </li>
                    </ul>
                    
                    <div class="mt-20 pt-10 border-t border-[var(--white)]/10">
                        <p class="text-[10px] text-[var(--theme)] uppercase mb-3 font-black tracking-[0.3em]">Concierge Desk</p>
                        <p class="text-3xl font-black tracking-tighter"><?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['phone'] ?? '+1 800 LUXURY') ?></p>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>

<footer class="bg-[var(--header)] py-20 px-5 text-center border-t-4 border-[var(--theme)]">
    <div class="max-w-4xl mx-auto">
        <div class="text-[var(--white)] text-3xl font-black uppercase tracking-widest mb-8"><?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name']) ?></div>
        
        <div class="mb-10 text-xs font-bold uppercase tracking-widest flex flex-wrap justify-center gap-6 md:gap-10">
            <a href="<?= base_url('/terms') ?>" class="text-[var(--light)] no-underline opacity-60 transition-all duration-300 hover:opacity-100 hover:text-[var(--theme)] hover:-translate-y-0.5">Terms & Conditions</a>
            <a href="<?= base_url('/privacy') ?>" class="text-[var(--light)] no-underline opacity-60 transition-all duration-300 hover:opacity-100 hover:text-[var(--theme)] hover:-translate-y-0.5">Privacy Policy</a>
            <a href="<?= base_url('/shipping') ?>" class="text-[var(--light)] no-underline opacity-60 transition-all duration-300 hover:opacity-100 hover:text-[var(--theme)] hover:-translate-y-0.5">Shipping Policy</a>
            <a href="<?= base_url('/refund') ?>" class="text-[var(--light)] no-underline opacity-60 transition-all duration-300 hover:opacity-100 hover:text-[var(--theme)] hover:-translate-y-0.5">Refund Policy</a>
        </div>

        <p class="text-[var(--light)] opacity-40 text-[10px] uppercase tracking-widest font-semibold border-t border-[var(--white)]/10 pt-8 mt-8 inline-block px-10">
            Powered by <span class="text-[var(--theme)] opacity-100">Syncro PMS</span> &bull; The Enterprise Hospitality Engine
        </p>
    </div>
</footer>

<script>
    let activePromo = null;

    document.getElementById('applyPromoBtn')?.addEventListener('click', async function() {
        const code = document.getElementById('promoCode').value.trim();
        const msgEl = document.getElementById('promoMessage');
        if (!code) return;
        
        try {
            const formData = new FormData();
            formData.append('code', code);
            formData.append('hotel_id', <?= (int)$hotel['id'] ?>);
            
            const res = await fetch('/ajax/promo/validate', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                activePromo = data;
                msgEl.style.color = 'var(--theme2)'; 
                msgEl.textContent = `✓ Promo code applied successfully!`;
            } else {
                activePromo = null;
                msgEl.style.color = 'var(--theme)'; 
                msgEl.textContent = `⚠️ ${data.message}`;
            }
        } catch (e) {
            console.error("Promo validation failed:", e);
        }
    });

    function selectRoom(roomId) {
        document.getElementById('roomSelect').value = roomId;
        document.getElementById('booking-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const form = document.getElementById('bookingForm');
    
    document.getElementById('checkIn')?.addEventListener('change', function(e) {
        let checkInDate = new Date(e.target.value);
        checkInDate.setDate(checkInDate.getDate() + 1);
        let nextDay = checkInDate.toISOString().split('T')[0];
        let checkOutElem = document.getElementById('checkOut');
        
        checkOutElem.setAttribute('min', nextDay);
        
        if(checkOutElem.value <= e.target.value) {
            checkOutElem.value = nextDay;
        }
    });

    if(form) {
        form.addEventListener('submit', function(e) {
            const roomId = document.getElementById('roomSelect').value;
            const checkIn = new Date(document.getElementById('checkIn').value);
            const checkOut = new Date(document.getElementById('checkOut').value);
            
            if (!roomId || isNaN(checkIn) || isNaN(checkOut)) {
                e.preventDefault();
                return;
            }

            const nights = Math.ceil((checkOut - checkIn) / (1000 * 3600 * 24));
            
            if (nights <= 0) { 
                e.preventDefault();
                alert("Check-out date must be strictly after the Check-in date."); 
                return; 
            }
            
            // Add loading state to button
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 mr-3 text-[var(--white)]" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...`;
            btn.classList.add('opacity-80', 'cursor-not-allowed');
        });
    }
</script>