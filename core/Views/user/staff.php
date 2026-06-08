<?php declare(strict_types=1); ?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
        <button onclick="document.getElementById('inviteModal').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
            + Invite Staff Member
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <p class="text-sm text-green-700"><?= \Syncro\Security\SecurityManager::sanitizeOutput($_GET['success']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-sm text-red-700"><?= \Syncro\Security\SecurityManager::sanitizeOutput($_GET['error']) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Active Staff -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-medium text-gray-900">Active Staff</h2>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php if(empty($staff)): ?>
                    <li class="px-6 py-4 text-sm text-gray-500">No active staff members found.</li>
                <?php else: ?>
                    <?php foreach($staff as $member): ?>
                        <li class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= \Syncro\Security\SecurityManager::sanitizeOutput($member['name'] ?? 'N/A') ?></p>
                                <p class="text-sm text-gray-500"><?= \Syncro\Security\SecurityManager::sanitizeOutput($member['email']) ?></p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($member['role']) ?>
                                </span>
                                <form method="POST" action="/user/staff/revoke" onsubmit="return confirm('Revoke access for this user?');">
                                    <input type="hidden" name="staff_id" value="<?= $member['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">Revoke</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Pending Invitations -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-medium text-gray-900">Pending Invitations</h2>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php if(empty($invitations)): ?>
                    <li class="px-6 py-4 text-sm text-gray-500">No pending invitations.</li>
                <?php else: ?>
                    <?php foreach($invitations as $invite): ?>
                        <li class="px-6 py-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= \Syncro\Security\SecurityManager::sanitizeOutput($invite['email']) ?></p>
                                <p class="text-xs text-gray-500">Expires: <?= date('M j, Y', strtotime($invite['expires_at'])) ?></p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 capitalize">
                                    <?= \Syncro\Security\SecurityManager::sanitizeOutput($invite['role']) ?>
                                </span>
                                <form method="POST" action="/user/staff/invite/revoke">
                                    <input type="hidden" name="invite_id" value="<?= $invite['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">Cancel</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

<!-- Invite Modal -->
<div id="inviteModal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Invite Staff Member</h3>
            <button onclick="document.getElementById('inviteModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">&times;</button>
        </div>
        <form action="/user/staff/invite" method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="receptionist">Receptionist (Front Desk)</option>
                    <option value="housekeeper">Housekeeper (Cleaning & Maintenance)</option>
                </select>
            </div>
            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('inviteModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">Send Invitation</button>
            </div>
        </form>
    </div>
</div>