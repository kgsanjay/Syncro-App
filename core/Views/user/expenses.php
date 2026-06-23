<?php declare(strict_types=1); ?>

<div class="max-w-[1400px] mx-auto pb-10">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-[var(--text)] tracking-tight">Expense Tracking</h1>
            <p class="text-[var(--text-muted)] mt-1">Manage and track your operational expenses</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="<?= base_url('/user/expenses') ?>" class="flex items-center gap-2">
                <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="px-4 py-2 bg-[var(--surface)] border border-[var(--border)] rounded-lg text-sm text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] outline-none" onchange="this.form.submit()">
            </form>
            <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')" class="bg-gradient-to-r from-[var(--theme)] to-[var(--theme2)] text-white px-5 py-2.5 rounded-lg text-sm font-bold shadow-lg hover:shadow-xl hover:shadow-[var(--theme2)]/20 transition-all flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Record Expense
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="mb-6 bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded-lg border border-red-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php 
    $totalAmount = array_sum(array_column($expenses, 'amount'));
    ?>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-[var(--surface)] rounded-xl shadow-sm border border-[var(--border)] p-6">
            <div class="flex items-center text-[var(--text-muted)] mb-2">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="font-medium">Total Expenses</h3>
            </div>
            <p class="text-3xl font-bold text-[var(--text)]">₹<?= number_format($totalAmount, 2) ?></p>
            <p class="text-sm text-[var(--text-muted)] mt-1">For <?= date('F Y', strtotime($month . '-01')) ?></p>
        </div>
        <div class="bg-[var(--surface)] rounded-xl shadow-sm border border-[var(--border)] p-6">
            <div class="flex items-center text-[var(--text-muted)] mb-2">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <h3 class="font-medium">Transactions</h3>
            </div>
            <p class="text-3xl font-bold text-[var(--text)]"><?= count($expenses) ?></p>
        </div>
    </div>

    <div class="bg-[var(--surface)] rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[var(--bg)] border-b border-[var(--border)]">
                    <th class="p-4 text-sm font-semibold text-[var(--text-muted)] uppercase tracking-wider">Date</th>
                    <th class="p-4 text-sm font-semibold text-[var(--text-muted)] uppercase tracking-wider">Category</th>
                    <th class="p-4 text-sm font-semibold text-[var(--text-muted)] uppercase tracking-wider">Description</th>
                    <th class="p-4 text-sm font-semibold text-[var(--text-muted)] uppercase tracking-wider text-right">Amount</th>
                    <th class="p-4 text-sm font-semibold text-[var(--text-muted)] uppercase tracking-wider text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[var(--border)]">
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-[var(--text-muted)]">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="text-lg font-medium text-[var(--text)]">No expenses recorded</p>
                            <p class="text-sm">Record your first expense to see it here.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr class="hover:bg-[var(--bg)]/50 transition-colors">
                            <td class="p-4 text-sm font-medium text-[var(--text)]">
                                <?= date('M j, Y', strtotime($expense['date'])) ?>
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded-md border border-gray-200">
                                    <?= htmlspecialchars($expense['category']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm text-[var(--text-muted)]">
                                <?= htmlspecialchars($expense['description'] ?? '—') ?>
                            </td>
                            <td class="p-4 text-sm font-bold text-[var(--text)] text-right">
                                ₹<?= number_format((float)$expense['amount'], 2) ?>
                            </td>
                            <td class="p-4 text-center">
                                <?php if (in_array($_SESSION['role'], ['hotel_admin'])): ?>
                                <form action="<?= base_url('/user/expenses/delete') ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this expense?');" class="inline">
                                    <?= csrf_field() ?>">
                                    <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2 rounded hover:bg-red-50 transition" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-[var(--surface)] w-full max-w-md rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--border)] flex justify-between items-center bg-[var(--bg)]">
            <h3 class="text-lg font-bold text-[var(--text)]">Record New Expense</h3>
            <button onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="text-[var(--text-muted)] hover:text-[var(--text)] transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="<?= base_url('/user/expenses') ?>" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>">
            
            <div>
                <label class="block text-sm font-semibold text-[var(--text)] mb-1">Date</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2.5 bg-[var(--bg)] border border-[var(--border)] rounded-lg text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-[var(--text)] mb-1">Category</label>
                <select name="category" required class="w-full px-4 py-2.5 bg-[var(--bg)] border border-[var(--border)] rounded-lg text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] outline-none">
                    <option value="">Select Category</option>
                    <option value="Utilities">Utilities (Electricity, Water, Internet)</option>
                    <option value="Maintenance">Repairs & Maintenance</option>
                    <option value="Supplies">Guest Supplies & Toiletries</option>
                    <option value="Housekeeping">Housekeeping Materials</option>
                    <option value="Marketing">Marketing & Advertising</option>
                    <option value="Payroll">Payroll / Wages</option>
                    <option value="Software">Software Subscriptions (OTA, PMS)</option>
                    <option value="Other">Other Expenses</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-[var(--text)] mb-1">Amount (₹)</label>
                <input type="number" step="0.01" min="0" name="amount" required placeholder="0.00" class="w-full px-4 py-2.5 bg-[var(--bg)] border border-[var(--border)] rounded-lg text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-[var(--text)] mb-1">Description</label>
                <textarea name="description" rows="2" placeholder="Brief details about this expense..." class="w-full px-4 py-2 bg-[var(--bg)] border border-[var(--border)] rounded-lg text-[var(--text)] focus:ring-2 focus:ring-[var(--theme2)] outline-none"></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="document.getElementById('addExpenseModal').classList.add('hidden')" class="flex-1 px-4 py-2.5 bg-[var(--bg)] border border-[var(--border)] text-[var(--text)] rounded-lg font-semibold hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 bg-[var(--theme)] text-white rounded-lg font-bold hover:bg-[var(--theme2)] transition shadow-lg">Save Expense</button>
            </div>
        </form>
    </div>
</div>
