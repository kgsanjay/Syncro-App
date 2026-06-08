<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="mb-8 border-b border-[var(--border)] pb-4 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Support Inbox</h1>
            <p class="mt-1 text-sm text-[var(--text)] font-medium">Manage helpdesk tickets from all properties.</p>
        </div>
        <a href="/admin/dashboard" class="bg-[var(--white)] text-[var(--text)] border border-[var(--border)] hover:bg-[var(--light)] font-bold px-4 py-2 rounded text-sm transition-colors shadow-sm">
            Back to Dashboard
        </a>
    </div>

    <?php if ($successMsg = \Syncro\Security\SessionManager::getFlash('success')): ?>
        <div class="mb-8 bg-[var(--success)]/5 border-l-4 border-[var(--success)] p-5 rounded-xl shadow-lg flex items-center animate-bounce">
            <div class="w-8 h-8 rounded-lg bg-[var(--success)]/10 text-[var(--success)] flex items-center justify-center mr-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--header)]"><?= htmlspecialchars($successMsg) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden transition-all hover:shadow-indigo-500/5">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-[var(--light)]/50 border-b-2 border-[var(--border)] text-[10px] font-black text-[var(--header)] uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-8 py-5 text-left">Sector / Origin</th>
                        <th class="px-8 py-5 text-left">Transmission Core</th>
                        <th class="px-8 py-5 text-left">Internal State</th>
                        <th class="px-8 py-5 text-right">Operational HUD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="4" class="px-8 py-20 text-center text-[10px] font-black uppercase tracking-[0.3em] opacity-40">Universal Support Queue Static &mdash; No Transmissions Found</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr class="hover:bg-[var(--light)]/50 transition-all group">
                            <td class="px-8 py-6 align-top">
                                <div class="text-[15px] font-black text-[var(--header)] mb-1 tracking-tight">
                                    <?= htmlspecialchars($t['property_name']) ?>
                                </div>
                                <div class="text-[11px] text-[var(--text)] font-black uppercase tracking-widest opacity-40">
                                    Operator: <?= htmlspecialchars($t['user_name']) ?>
                                </div>
                            </td>
                            <td class="px-8 py-6 align-top">
                                <div class="text-[14px] font-black text-[var(--header)] mb-2 tracking-tight group-hover:text-[var(--theme2)] transition-colors">
                                    <?= htmlspecialchars($t['subject']) ?>
                                </div>
                                <div class="flex items-center gap-4 mt-3">
                                    <span class="text-[10px] font-black font-mono opacity-20 group-hover:opacity-100 transition-opacity">#<?= str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                    <?php 
                                        $priorityStyles = [
                                            'urgent' => 'bg-[var(--danger)] text-[var(--white)] shadow-[var(--danger)]/20 animate-pulse',
                                            'high' => 'bg-[var(--theme)] text-[var(--header)] shadow-[var(--theme)]/20',
                                            'normal' => 'bg-[var(--light)] text-[var(--text)] border border-[var(--border)]'
                                        ];
                                        $pStyle = $priorityStyles[$t['priority'] ?? 'normal'] ?? $priorityStyles['normal'];
                                    ?>
                                    <span class="px-2.5 py-1 text-[8px] font-black uppercase tracking-[0.2em] rounded-md shadow-lg <?= $pStyle ?>">
                                        <?= ucfirst($t['priority'] ?? 'Normal') ?>
                                    </span>
                                </div>
                                <div class="text-[9px] text-[var(--text)] mt-4 uppercase font-black tracking-[0.2em] opacity-40">
                                    Pulse: <?= date('M j, H:i', strtotime($t['updated_at'])) ?>
                                </div>
                            </td>
                            <td class="px-8 py-6 align-top">
                                <?php 
                                    $statusMaps = [
                                        'open' => ['pulse' => true, 'label' => 'Awaiting Agent', 'class' => 'bg-[var(--theme2)]/10 text-[var(--theme2)] border-[var(--theme2)]/30'],
                                        'in_progress' => ['pulse' => true, 'label' => 'Active Comm', 'class' => 'bg-[var(--theme)]/10 text-[var(--header)] border-[var(--theme)]/30'],
                                        'waiting_on_customer' => ['pulse' => false, 'label' => 'Tenant Latency', 'class' => 'bg-[var(--text)]/5 text-[var(--text)] border-[var(--border)]'],
                                        'resolved' => ['pulse' => false, 'label' => 'Sector Restored', 'class' => 'bg-[var(--success)]/10 text-[var(--success)] border-[var(--success)]/20'],
                                        'closed' => ['pulse' => false, 'label' => 'Archived Cycle', 'class' => 'bg-[var(--header)] text-[var(--white)] border-[var(--header)]'],
                                    ];
                                    $s = $statusMaps[$t['status']] ?? $statusMaps['open'];
                                ?>
                                <span class="px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.2em] rounded-xl border-2 shadow-sm flex items-center w-fit <?= $s['class'] ?> <?= $s['pulse'] ? 'animate-pulse' : '' ?>">
                                    <div class="w-1.5 h-1.5 rounded-full bg-current mr-2"></div>
                                    <?= $s['label'] ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right align-top">
                                <a href="/admin/support/view?id=<?= $t['id'] ?>" class="inline-flex items-center px-6 py-2.5 bg-[var(--header)] text-[var(--white)] text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-[var(--theme2)] transition-all shadow-xl hover:-translate-y-0.5 active:scale-95">
                                    Dispatch &rarr;
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>            </table>
        </div>
    </div>
</div>