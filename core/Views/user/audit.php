<div class="max-w-[1400px] mx-auto py-10 px-4">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-[var(--header)] tracking-tight">Audit Logs</h1>
            <p class="text-sm text-[var(--text)] mt-1">Monitor staff activity and security events across your property.</p>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded shadow-sm border border-[var(--border)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[var(--light)] border-b border-[var(--border)]">
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">Timestamp</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">User</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">Action</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider">Description</th>
                        <th class="py-3 px-6 text-xs font-bold text-[var(--text)] uppercase tracking-wider text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="py-12 px-6 text-center text-[var(--text)] text-sm">No recent activity.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 px-6 text-sm text-[var(--text)] whitespace-nowrap">
                                <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                            </td>
                            <td class="py-4 px-6 text-sm font-medium text-[var(--header)]">
                                <?= htmlspecialchars((string)($log['full_name'] ?? 'System/Guest')) ?>
                            </td>
                            <td class="py-4 px-6 text-sm">
                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider border border-gray-200">
                                    <?= htmlspecialchars($log['action_type']) ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-sm text-[var(--text)]">
                                <?= htmlspecialchars($log['description']) ?>
                            </td>
                            <td class="py-4 px-6 text-sm text-right text-[var(--text)] font-mono text-xs">
                                <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
