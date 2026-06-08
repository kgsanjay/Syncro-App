<?php declare(strict_types=1); ?>

<div class="max-w-[1000px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4">
        <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">OTA Channel Manager</h1>
        <p class="mt-1 text-sm text-[var(--text)] font-medium">Map your local room categories to external Online Travel Agencies (OTAs). Only platforms with saved API keys will appear here.</p>
    </div>

    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden mb-10">
        <div class="bg-[var(--header)] px-6 py-4 border-b border-[var(--theme)]">
            <h3 class="font-bold text-[var(--white)] text-lg uppercase tracking-widest flex items-center">
                <svg class="w-5 h-5 mr-2 text-[var(--theme)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                Create New Mapping
            </h3>
        </div>
        
        <form action="/user/channel-manager" method="POST" class="p-6 bg-[var(--light)] space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-1">Local Room</label>
                    <select name="room_type_id" required class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--white)] text-[var(--text)] font-medium cursor-pointer">
                        <option value="">Select local room...</option>
                        <?php foreach($rooms as $room): ?>
                            <option value="<?= $room['id'] ?>"><?= \Syncro\Security\SecurityManager::sanitizeOutput($room['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-1">OTA Platform</label>
                    <select name="channel_name" required class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--white)] text-[var(--text)] font-medium cursor-pointer <?= empty($activeChannels) ? 'opacity-50 cursor-not-allowed bg-[var(--light)]' : '' ?>" <?= empty($activeChannels) ? 'disabled' : '' ?>>
                        <?php if(empty($activeChannels)): ?>
                            <option value="">No API Keys Set</option>
                        <?php else: ?>
                            <option value="">Select Platform...</option>
                            <?php foreach($activeChannels as $channelKey => $channelLabel): ?>
                                <option value="<?= htmlspecialchars($channelKey) ?>"><?= htmlspecialchars($channelLabel) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if(empty($activeChannels)): ?>
                        <p class="text-[10px] font-bold text-red-500 mt-1">Please configure OTA APIs in Settings first.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-[var(--header)] uppercase tracking-widest mb-1">OTA Room ID / Code</label>
                    <input type="text" name="ota_room_code" required placeholder="e.g. 12345601" class="w-full px-4 py-2.5 border border-[var(--border)] rounded text-sm focus:ring-2 focus:ring-[var(--theme2)] outline-none bg-[var(--white)] text-[var(--text)] font-mono" <?= empty($activeChannels) ? 'disabled class="opacity-50 cursor-not-allowed"' : '' ?>>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full md:w-auto bg-[var(--theme2)] hover:bg-[var(--header)] text-[var(--white)] font-bold py-2.5 px-8 rounded shadow transition-colors text-xs uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed" <?= empty($activeChannels) ? 'disabled' : '' ?>>
                    Save Mapping
                </button>
            </div>
        </form>
    </div>

    <h3 class="text-xl font-bold text-[var(--header)] mb-4 border-b border-[var(--border)] pb-2">Active Connections</h3>
    
    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border)]">
                <thead class="bg-[var(--light)]">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">OTA Platform</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Mapping Path</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Sync Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-[var(--text)] uppercase tracking-widest">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--light)]">
                    <?php if(empty($mappings)): ?>
                        <tr><td colspan="4" class="p-8 text-center text-sm font-medium text-[var(--text)]">No channels mapped yet. Add API keys in settings to begin.</td></tr>
                    <?php else: ?>
                        <?php foreach($mappings as $map): ?>
                            <tr class="hover:bg-[var(--light)] transition-colors">
                                <td class="px-6 py-4 font-black text-[var(--header)]">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded bg-[var(--light)] border border-[var(--border)] flex items-center justify-center mr-3 text-[var(--theme2)] font-bold text-xs">
                                            <?= strtoupper(substr(\Syncro\Security\SecurityManager::sanitizeOutput($map['channel_name']), 0, 1)) ?>
                                        </div>
                                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($map['channel_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-[var(--theme2)] mb-1">
                                        <?= \Syncro\Security\SecurityManager::sanitizeOutput($map['local_room_name']) ?>
                                    </div>
                                    <div class="text-[10px] font-mono text-[var(--text)] uppercase tracking-widest flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        ID: <?= \Syncro\Security\SecurityManager::sanitizeOutput($map['ota_room_code']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($map['sync_status'] === 'active'): ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-[var(--success)]/10 border border-[var(--success)]/30 text-[var(--success)] uppercase tracking-[0.1em] shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--success)] mr-2 animate-pulse shadow-[0_0_8px_var(--success)]"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black bg-[var(--danger)]/10 border border-[var(--danger)]/30 text-[var(--danger)] uppercase tracking-[0.1em] shadow-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--danger)] mr-2 shadow-[0_0_8px_var(--danger)]"></span> Error
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($map['last_sync_time']): ?>
                                        <div class="text-[10px] text-[var(--text)] mt-2 font-black uppercase tracking-tighter opacity-40">Last Pulse: <?= date('M j, g:i A', strtotime($map['last_sync_time'])) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($map['sync_status_message'])): ?>
                                        <div class="text-[10px] <?= strpos($map['sync_status_message'], 'Error') !== false || strpos($map['sync_status_message'], 'failed') !== false ? 'text-red-600' : 'text-green-600' ?> mt-1 font-semibold leading-tight max-w-[200px]">
                                            <?= \Syncro\Security\SecurityManager::sanitizeOutput($map['sync_status_message']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="/user/channel-manager/delete" method="POST" onsubmit="return confirm('Immediately sever this connection? Inventory will stop syncing for <?= htmlspecialchars($map['channel_name']) ?>.');">
                                        <input type="hidden" name="csrf_token" value="<?= \Syncro\Security\SecurityManager::sanitizeOutput($csrfToken ?? '') ?>">
                                        <input type="hidden" name="mapping_id" value="<?= $map['id'] ?>">
                                        <button type="submit" class="text-[10px] text-[var(--header)] font-black bg-[var(--theme)] hover:bg-[var(--danger)] hover:text-[var(--white)] transition-all hover:scale-105 active:scale-95 px-4 py-2 rounded-lg uppercase tracking-[0.2em] shadow-sm border border-[var(--theme)]/30">Sever Link</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>