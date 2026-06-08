<?php declare(strict_types=1); ?>

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-[var(--header)]">Staff Audit Trail</h2>
        <p class="text-[var(--text)] text-sm mt-1">A secure, immutable log of all staff activities and system events.</p>
    </div>
    <div class="bg-[var(--white)] px-4 py-2 rounded-lg border border-[var(--border)] shadow-sm text-sm font-bold text-[var(--theme2)]">
        Showing last 500 events
    </div>
</div>

<div class="bg-[var(--white)] rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[var(--light)] text-[var(--text)] text-xs uppercase tracking-wider border-b border-[var(--border)]">
                    <th class="p-4 font-bold">Date & Time</th>
                    <th class="p-4 font-bold">Staff Member</th>
                    <th class="p-4 font-bold">Action Event</th>
                    <th class="p-4 font-bold">Description</th>
                    <th class="p-4 font-bold text-right">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)] text-sm">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-[var(--text)]">No system activity logged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-[var(--light)]/50 transition-colors">
                            <td class="p-4 whitespace-nowrap text-[var(--text)]">
                                <span class="font-bold text-[var(--header)]"><?= date('M j, Y', strtotime($log['created_at'])) ?></span><br>
                                <span class="text-xs"><?= date('g:i:s A', strtotime($log['created_at'])) ?></span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full bg-[var(--theme2)] text-white flex items-center justify-center font-bold text-xs">
                                        <?= substr(htmlspecialchars($log['user_name']), 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-[var(--header)] leading-tight"><?= htmlspecialchars($log['user_name']) ?></p>
                                        <p class="text-xs text-[var(--text)] uppercase tracking-wider"><?= htmlspecialchars(str_replace('_', ' ', $log['role'])) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <?php 
                                    $actionColor = 'bg-[var(--light)] text-[var(--text)] border-[var(--border)]';
                                    if (strpos($log['action'], 'LOGIN') !== false) $actionColor = 'bg-[var(--theme2)]/10 text-[var(--theme2)] border-[var(--theme2)]/20';
                                    if (strpos($log['action'], 'SECURITY') !== false || strpos($log['action'], 'PASSWORD') !== false) $actionColor = 'bg-[var(--danger)]/10 text-[var(--danger)] border-[var(--danger)]/20';
                                    if (strpos($log['action'], 'RENEWAL') !== false) $actionColor = 'bg-[var(--success)]/10 text-[var(--success)] border-[var(--success)]/20';
                                    if (strpos($log['action'], 'BOOKING') !== false) $actionColor = 'bg-[var(--theme)]/10 text-[var(--header)] border-[var(--theme)]/30';
                                ?>
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase tracking-widest border <?= $actionColor ?>">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-[var(--text)]">
                                <?= htmlspecialchars($log['description']) ?>
                            </td>
                            <td class="p-4 text-right font-mono text-xs text-[var(--text)] whitespace-nowrap">
                                <?= htmlspecialchars($log['ip_address']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>