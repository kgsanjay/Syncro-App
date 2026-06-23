<?php declare(strict_types=1); ?>

<div class="max-w-[1000px] mx-auto pb-10">

    <div class="mb-8">
        <a href="<?= base_url('/user/support') ?>" class="text-[var(--text)] hover:text-[var(--theme2)] font-black text-[10px] uppercase tracking-[0.2em] flex items-center mb-6 w-fit transition-all bg-[var(--light)] px-4 py-2 rounded-xl border border-[var(--border)] hover:bg-[var(--white)] hover:-translate-y-1 active:scale-95 shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Return to Desk
        </a>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-1">
            <div>
                <h1 class="text-3xl font-black text-[var(--header)] tracking-tight">
                    <?= htmlspecialchars($ticket['subject']) ?>
                </h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="text-[10px] font-black text-[var(--text)] uppercase tracking-widest opacity-40">Identity: #<?= str_pad((string)$ticket['id'], 5, '0', STR_PAD_LEFT) ?></span>
                    <span class="w-1 h-1 rounded-full bg-[var(--border)]"></span>
                    <span class="text-[10px] font-black text-[var(--text)] uppercase tracking-widest opacity-40">Temporal Opening: <?= date('M j, Y', strtotime($ticket['created_at'])) ?></span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if(($ticket['priority'] ?? '') === 'urgent'): ?>
                    <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg bg-[var(--danger)]/10 text-[var(--danger)] border border-[var(--danger)]/20 shadow-sm animate-pulse">Urgent Priority</span>
                <?php elseif(($ticket['priority'] ?? '') === 'high'): ?>
                    <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg bg-[var(--theme)] text-[var(--header)] border border-[var(--theme2)]/20 shadow-sm">High Priority</span>
                <?php else: ?>
                    <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg bg-[var(--light)] text-[var(--text)] border border-[var(--border)] shadow-sm"><?= ucfirst($ticket['priority'] ?? 'Normal') ?> Priority</span>
                <?php endif; ?>

                <?php 
                    $statusClass = [
                        'open' => 'bg-indigo-500/10 text-indigo-500 border-indigo-500/30 shadow-[0_0_8px_rgba(99,102,241,0.2)]',
                        'in_progress' => 'bg-[var(--theme)]/10 text-[var(--header)] border-[var(--theme)] shadow-[0_0_8px_rgba(255,193,7,0.2)]',
                        'waiting_on_customer' => 'bg-orange-500/10 text-orange-600 border-orange-500/30',
                        'resolved' => 'bg-[var(--success)]/10 text-[var(--success)] border-[var(--success)]/30',
                        'closed' => 'bg-[var(--text)]/10 text-[var(--text)] border-[var(--border)] opacity-60',
                    ];
                    $css = $statusClass[$ticket['status']] ?? $statusClass['open'];
                ?>
                <span class="px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] rounded-lg border shadow-sm <?= $css ?>">
                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-2 inline-block <?= in_array($ticket['status'], ['open', 'in_progress']) ? 'animate-pulse' : '' ?>"></span>
                    <?= str_replace('_', ' ', $ticket['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="bg-[var(--white)] rounded-2xl shadow-2xl border border-[var(--border)] overflow-hidden flex flex-col h-[700px] transition-all hover:shadow-indigo-500/5">
        
        <div class="flex-1 overflow-y-auto p-8 space-y-8 bg-[var(--light)]/30 backdrop-blur-sm custom-scrollbar">
            <?php foreach ($replies as $reply): ?>
                <?php $isAdmin = (bool)$reply['is_admin_reply']; ?>
                
                <div class="flex <?= $isAdmin ? 'justify-start' : 'justify-end' ?>">
                    <div class="max-w-[85%] md:max-w-[75%]">
                        <div class="flex items-center mb-2 <?= $isAdmin ? '' : 'justify-end' ?> px-2">
                            <span class="text-[9px] font-black text-[var(--header)] uppercase tracking-[0.2em] opacity-40">
                                <?= $isAdmin ? 'Official Syncro Support' : htmlspecialchars($reply['sender_name'] ?? 'Authorized Personnel') ?>
                            </span>
                            <span class="w-1 h-1 rounded-full bg-[var(--border)] mx-3"></span>
                            <span class="text-[9px] font-bold text-[var(--text)] uppercase tracking-widest opacity-30">
                                <?= date('M j, g:i A', strtotime($reply['created_at'])) ?>
                            </span>
                        </div>
                        <div class="p-6 rounded-2xl shadow-xl text-[13px] leading-relaxed whitespace-pre-wrap transition-all hover:shadow-2xl <?= $isAdmin ? 'bg-[var(--white)] border-2 border-[var(--border)] text-[var(--header)] rounded-tl-none transform hover:translate-x-1' : 'bg-[var(--theme2)] text-[var(--white)] rounded-tr-none transform hover:-translate-x-1' ?>">
                            <div class="font-medium"><?= nl2br(htmlspecialchars($reply['message'])) ?></div>
                            
                            <?php if (!empty($reply['attachment_path'])): ?>
                                <div class="mt-6 pt-6 border-t-2 <?= $isAdmin ? 'border-[var(--border)]' : 'border-[var(--white)]/10' ?>">
                                    <a href="<?= htmlspecialchars($reply['attachment_path']) ?>" target="_blank" class="block group relative rounded-xl overflow-hidden shadow-2xl bg-black/5">
                                        <img src="<?= htmlspecialchars($reply['attachment_path']) ?>" alt="Telemetry Attachment" class="max-h-64 mx-auto object-contain group-hover:scale-110 transition-transform duration-500">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                            <span class="bg-[var(--white)]/20 backdrop-blur-md text-[var(--white)] px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">View Full Asset</span>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-6 bg-[var(--white)] border-t-2 border-[var(--border)] shadow-[0_-10px_30px_rgba(0,0,0,0.02)]">
            <?php if (in_array($ticket['status'], ['resolved', 'closed'])): ?>
                <div class="text-center py-8 text-[var(--text)] text-[10px] font-black uppercase tracking-[0.3em] opacity-40">
                    <svg class="w-8 h-8 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Operational Flow Concluded &bull; Ticket Locked
                </div>
            <?php else: ?>
                <form action="<?= base_url('/user/support/view') ?>" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    <?= csrf_field() ?>">
                    <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    
                    <div id="imagePreviewContainer" class="hidden relative w-fit mb-2 group">
                        <img id="imagePreview" src="" class="max-h-24 rounded-xl border-2 border-[var(--theme2)] shadow-xl object-cover">
                        <button type="button" onclick="clearImage()" class="absolute -top-3 -right-3 bg-[var(--danger)] text-[var(--white)] rounded-full w-7 h-7 flex items-center justify-center text-xs font-black shadow-2xl cursor-pointer transition-all hover:scale-110 hover:shadow-red-500/50">&times;</button>
                    </div>

                    <div class="relative">
                        <textarea name="message" required rows="2" placeholder="Formulate response..." class="w-full px-6 py-5 border-2 border-[var(--border)] rounded-2xl text-[14px] font-bold focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)] focus:bg-[var(--white)] transition-all text-[var(--header)] resize-none shadow-inner"></textarea>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="w-full sm:flex-1">
                            <label for="fileInput" class="flex items-center gap-3 px-4 py-2 bg-[var(--light)] border border-[var(--border)] rounded-xl cursor-pointer hover:bg-[var(--white)] hover:border-[var(--theme2)]/30 transition-all group w-fit">
                                <svg class="w-4 h-4 text-[var(--text)] group-hover:text-[var(--theme2)] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="text-[9px] font-black text-[var(--text)] uppercase tracking-widest group-hover:text-[var(--theme2)] transition-colors">Attach Visual Asset</span>
                                <input type="file" id="fileInput" name="attachment" accept="image/jpeg, image/png, image/webp" onchange="previewImage(this)" class="hidden">
                            </label>
                        </div>
                        <button type="submit" class="w-full sm:w-auto bg-[var(--header)] text-[var(--white)] font-black px-10 py-4 rounded-2xl transition-all shadow-2xl hover:bg-[var(--theme2)] text-[10px] uppercase tracking-[0.2em] transform hover:-translate-y-1 active:scale-95 flex items-center justify-center hover:shadow-indigo-500/20">
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Dispatch Packet
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>v>

<script>
    // 1. Auto-scroll chat to the bottom on load
    document.addEventListener("DOMContentLoaded", function() {
        const chatContainer = document.querySelector('.overflow-y-auto');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
    });

    // 2. Image Preview Logic
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        const container = document.getElementById('imagePreviewContainer');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                container.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 3. Clear Image Logic
    function clearImage() {
        document.getElementById('fileInput').value = '';
        document.getElementById('imagePreviewContainer').classList.add('hidden');
        document.getElementById('imagePreview').src = '';
    }
</script>