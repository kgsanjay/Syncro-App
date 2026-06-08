<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle ?? 'Housekeeping') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --theme: #1a365d;
            --theme2: #2563eb;
            --light: #f8fafc;
            --white: #ffffff;
            --text: #475569;
            --header: #0f172a;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        body { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-[var(--light)] text-[var(--text)] font-sans antialiased min-h-screen pb-20">

<div class="bg-[var(--theme)] text-white p-4 sticky top-0 z-50 shadow-md flex justify-between items-center">
    <h1 class="text-xl font-bold tracking-tight">Housekeeping</h1>
    <div class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">
        <?= date('M j, Y') ?>
    </div>
</div>

<div class="p-4">
    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-2 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-[var(--border)] p-3 text-center">
            <p class="text-2xl font-black text-[var(--success)]"><?= $stats['clean'] ?></p>
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--text)]">Clean</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-[var(--border)] p-3 text-center">
            <p class="text-2xl font-black text-[var(--danger)]"><?= $stats['dirty'] ?></p>
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--text)]">Dirty</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-[var(--border)] p-3 text-center">
            <p class="text-2xl font-black text-[var(--warning)]"><?= $stats['maintenance'] ?></p>
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--text)]">Maint.</p>
        </div>
    </div>

    <!-- Room List -->
    <div class="space-y-3">
        <?php foreach ($rooms as $room): 
            $statusColor = 'bg-gray-100 text-gray-800 border-gray-200';
            if ($room['housekeeping_status'] === 'clean') {
                $statusColor = 'bg-green-50 text-green-700 border-green-200';
            } elseif ($room['housekeeping_status'] === 'dirty' || $room['housekeeping_status'] === 'cleaning') {
                $statusColor = 'bg-red-50 text-red-700 border-red-200';
            } elseif ($room['housekeeping_status'] === 'maintenance') {
                $statusColor = 'bg-yellow-50 text-yellow-700 border-yellow-200';
            }
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-[var(--border)] overflow-hidden">
            <div class="p-4 flex justify-between items-center border-b border-[var(--border)]">
                <div>
                    <h2 class="text-2xl font-black text-[var(--header)]"><?= htmlspecialchars($room['room_number']) ?></h2>
                    <p class="text-xs text-[var(--text)] font-medium"><?= htmlspecialchars($room['room_type']) ?></p>
                </div>
                <div class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest border <?= $statusColor ?>" id="status-badge-<?= $room['id'] ?>">
                    <?= htmlspecialchars($room['housekeeping_status']) ?>
                </div>
            </div>
            <div class="flex border-t border-[var(--border)] divide-x divide-[var(--border)]">
                <button onclick="updateStatus(<?= $room['id'] ?>, 'clean')" class="flex-1 py-3 text-sm font-bold text-[var(--success)] hover:bg-green-50 transition-colors">
                    Mark Clean
                </button>
                <button onclick="updateStatus(<?= $room['id'] ?>, 'dirty')" class="flex-1 py-3 text-sm font-bold text-[var(--danger)] hover:bg-red-50 transition-colors">
                    Mark Dirty
                </button>
                <button onclick="updateStatus(<?= $room['id'] ?>, 'maintenance')" class="flex-1 py-3 text-sm font-bold text-[var(--warning)] hover:bg-yellow-50 transition-colors">
                    Maint.
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<form id="statusForm" class="hidden">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <input type="hidden" name="room_id" id="form_room_id">
    <input type="hidden" name="status" id="form_status">
</form>

<script>
function updateStatus(roomId, status) {
    document.getElementById('form_room_id').value = roomId;
    document.getElementById('form_status').value = status;
    
    const formData = new FormData(document.getElementById('statusForm'));
    
    fetch('/housekeeping/update', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Failed to update: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Network error. Please try again.');
    });
}
</script>

</body>
</html>
