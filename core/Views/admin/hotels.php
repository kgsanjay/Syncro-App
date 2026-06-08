<?php 
declare(strict_types=1); 

// 1. Capture the flash data from the session
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashCreds = $_SESSION['flash_credentials'] ?? null;

// 2. IMMEDIATELY delete it from the session for security (Zero-Trust)
unset($_SESSION['flash_success'], $_SESSION['flash_credentials']);
?>

<div class="max-w-[1400px] mx-auto pb-10">

    <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
        <div class="mb-6 bg-[var(--light)] border-l-4 border-[var(--theme2)] p-4 rounded shadow-sm flex items-center">
            <svg class="w-5 h-5 text-[var(--theme2)] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <p class="text-sm font-bold text-[var(--header)]">Property and all associated data permanently deleted.</p>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end space-y-4 sm:space-y-0 mb-8 pb-4 border-b border-[var(--border)]">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight mb-1">Client Hotels Directory</h1>
            <p class="text-sm text-[var(--text)] font-medium">Manage tenant accounts, edit details, and monitor system access.</p>
        </div>
        <a href="/admin/hotels/create" class="w-full sm:w-auto text-center bg-[var(--theme2)] text-[var(--white)] font-bold py-2.5 px-6 rounded shadow-md hover:bg-[var(--header)] hover:shadow-lg transition-all uppercase text-sm tracking-wider flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Onboard Property
        </a>
    </div>

    <?php if ($flashSuccess && $flashCreds): ?>
        <div class="mb-10 bg-[var(--header)] border-2 border-[var(--theme2)] rounded-2xl p-8 shadow-2xl relative overflow-hidden group">
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-48 h-48 bg-[var(--theme2)] rounded-full opacity-10 blur-3xl pointer-events-none group-hover:opacity-20 transition-opacity"></div>
            <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-32 h-32 bg-[var(--theme)] rounded-full opacity-5 blur-2xl pointer-events-none"></div>
            
            <h3 class="text-xl font-black text-[var(--white)] mb-3 flex items-center relative z-10 tracking-tight">
                <div class="w-8 h-8 rounded-lg bg-[var(--theme2)] text-[var(--white)] flex items-center justify-center mr-3 shadow-lg shadow-[var(--theme2)]/20 animate-pulse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <?= \Syncro\Security\SecurityManager::sanitizeOutput($flashSuccess) ?>
            </h3>
            <p class="text-[10px] text-[var(--theme)] mb-6 font-black uppercase tracking-[0.3em] relative z-10 opacity-80">
                CRITICAL PROTOCOL: EXPORT CREDENTIALS IMMEDIATELY. VOLATILE MEMORY PURGE IN EFFECT.
            </p>
            
            <div class="bg-[var(--white)]/5 backdrop-blur-md rounded-2xl border border-[var(--white)]/10 p-6 font-mono text-sm space-y-4 relative z-10 shadow-inner">
                <div class="flex flex-col md:flex-row md:justify-between items-center bg-[var(--white)]/5 p-4 rounded-xl border border-[var(--white)]/5">
                    <span class="font-black text-[var(--white)]/40 uppercase tracking-[0.2em] text-[9px] mb-2 md:mb-0">Control Identifier</span>
                    <span class="text-[var(--white)] font-black tracking-wider text-sm"><?= \Syncro\Security\SecurityManager::sanitizeOutput($flashCreds['email']) ?></span>
                </div>
                <div class="flex flex-col md:flex-row md:justify-between items-center bg-[var(--white)]/5 p-4 rounded-xl border border-[var(--white)]/5">
                    <span class="font-black text-[var(--white)]/40 uppercase tracking-[0.2em] text-[9px] mb-2 md:mb-0">Entropy Password</span>
                    <span class="text-[var(--header)] font-black bg-[var(--theme)] px-4 py-2 rounded-lg border-2 border-[var(--theme2)]/30 shadow-lg shadow-[var(--theme)]/20"><?= \Syncro\Security\SecurityManager::sanitizeOutput($flashCreds['password']) ?></span>
                </div>
                <div class="flex flex-col md:flex-row md:justify-between items-center bg-[var(--white)]/5 p-4 rounded-xl border border-[var(--white)]/5">
                    <span class="font-black text-[var(--white)]/40 uppercase tracking-[0.2em] text-[9px] mb-2 md:mb-0">Infrastructure API Key</span>
                    <span class="text-[var(--theme)] font-black break-all text-xs tracking-widest"><?= \Syncro\Security\SecurityManager::sanitizeOutput($flashCreds['api_key']) ?></span>
                </div>
                <div class="flex flex-col md:flex-row md:justify-between items-center bg-[var(--white)]/5 p-4 rounded-xl border border-[var(--white)]/5">
                    <span class="font-black text-[var(--white)]/40 uppercase tracking-[0.2em] text-[9px] mb-2 md:mb-0">Signature Secret</span>
                    <span class="text-[var(--theme2)] font-black break-all text-xs tracking-widest"><?= \Syncro\Security\SecurityManager::sanitizeOutput($flashCreds['api_secret']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden transition-all hover:shadow-indigo-500/5">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-[var(--light)]/50 border-b-2 border-[var(--border)] text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-8 py-5 text-left">Sector ID</th>
                        <th class="px-8 py-5 text-left">Entity Identity</th>
                        <th class="px-8 py-5 text-left">Command Credential</th>
                        <th class="px-8 py-5 text-left">Sovereignty</th>
                        <th class="px-8 py-5 text-left">Activation</th>
                        <th class="px-8 py-5 text-right">Operational HUD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    <?php if (empty($hotels)): ?>
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center text-[var(--text)] font-black uppercase tracking-[0.3em] opacity-40">
                                Global Registry Empty &mdash; Awaiting Provisioning
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hotels as $hotel): ?>
                        <tr class="hover:bg-[var(--light)]/50 transition-all group">
                            <td class="px-8 py-5 whitespace-nowrap text-xs text-[var(--text)] font-black font-mono opacity-30">
                                #<?= str_pad((string)$hotel['id'], 4, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-[14px] font-black text-[var(--header)] tracking-tight">
                                <?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['property_name']) ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-xs text-[var(--text)] font-black font-mono opacity-60 group-hover:opacity-100 transition-opacity">
                                <?= \Syncro\Security\SecurityManager::sanitizeOutput($hotel['admin_email'] ?? '') ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap">
                                <?php if ($hotel['status'] === 'active'): ?>
                                    <span class="px-3 py-1 inline-flex text-[9px] font-black rounded-lg uppercase tracking-[0.2em] bg-[var(--success)]/10 text-[var(--success)] border border-[var(--success)]/30 shadow-sm animate-pulse">Running</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 inline-flex text-[9px] font-black rounded-lg uppercase tracking-[0.2em] bg-[var(--header)] text-[var(--theme)] border-2 border-[var(--theme2)]/30 shadow-lg">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-[11px] font-bold text-[var(--text)] opacity-40">
                                <?= date('M d, Y', strtotime($hotel['created_at'])) ?>
                            </td>
                            <td class="px-8 py-5 whitespace-nowrap text-right">
                                <a href="/admin/hotels/edit?id=<?= (int)$hotel['id'] ?>" class="inline-flex items-center px-6 py-2.5 bg-[var(--header)] text-[var(--white)] text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--theme2)] transition-all shadow-xl hover:-translate-y-0.5 active:scale-95">
                                    Control &rarr;
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>