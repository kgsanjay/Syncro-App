<div class="max-w-[1200px] mx-auto pb-10">

    <div class="mb-10">
        <a href="/admin/support" class="text-[var(--theme2)] hover:text-[var(--header)] font-black text-[10px] uppercase tracking-[0.2em] flex items-center transition-all mb-6 w-fit group">
            <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Queue
        </a>
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black text-[var(--header)] tracking-tighter mb-2">
                    <?= htmlspecialchars($ticket['subject']) ?>
                </h1>
                <div class="text-[10px] text-[var(--text)] font-black uppercase tracking-[0.2em] mt-3 flex items-center gap-4 opacity-40">
                    <span class="flex items-center"><div class="w-2 h-2 rounded-full bg-[var(--theme2)] mr-2"></div> Property: <?= htmlspecialchars($ticket['property_name']) ?></span>
                    <span class="flex items-center"><div class="w-1.5 h-1.5 rounded-full bg-[var(--border)] mr-2"></div> Author: <?= htmlspecialchars($ticket['user_name']) ?></span>
                    
                    <?php 
                        $priorityStyles = [
                            'urgent' => 'bg-[var(--danger)] text-[var(--white)] shadow-[var(--danger)]/20 animate-pulse',
                            'high' => 'bg-[var(--theme)] text-[var(--header)] shadow-[var(--theme)]/20',
                            'normal' => 'bg-[var(--light)] text-[var(--text)] border border-[var(--border)]'
                        ];
                        $pStyle = $priorityStyles[$ticket['priority'] ?? 'normal'] ?? $priorityStyles['normal'];
                    ?>
                    <span class="ml-4 px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-[0.2em] shadow-xl <?= $pStyle ?>">
                        <?= ucfirst($ticket['priority'] ?? 'Normal') ?> Level
                    </span>
                </div>
            </div>
            
            <form action="/admin/support/status" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                <div class="relative">
                    <select name="status" onchange="this.form.submit()" class="text-[10px] font-black uppercase tracking-[0.3em] px-8 py-4 border-2 border-[var(--border)] rounded-2xl focus:ring-0 focus:border-[var(--theme2)] outline-none cursor-pointer bg-[var(--white)] text-[var(--header)] appearance-none shadow-xl hover:-translate-y-1 transition-all">
                        <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Awaiting Agent</option>
                        <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>Active Communication</option>
                        <option value="waiting_on_customer" <?= $ticket['status'] === 'waiting_on_customer' ? 'selected' : '' ?>>Tenant Latency</option>
                        <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Sector Restored</option>
                        <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Archived Cycle</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($successMsg = \Syncro\Security\SessionManager::getFlash('success')): ?>
        <div class="mb-8 bg-[var(--success)]/5 border-l-4 border-[var(--success)] p-5 rounded-xl shadow-lg flex items-center animate-bounce">
            <div class="w-8 h-8 rounded-lg bg-[var(--success)]/10 text-[var(--success)] flex items-center justify-center mr-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <p class="text-[11px] font-black uppercase tracking-[0.2em] text-[var(--header)]"><?= htmlspecialchars($successMsg) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-[var(--white)] rounded-3xl shadow-2xl border border-[var(--border)] overflow-hidden flex flex-col h-[700px] group transition-all hover:shadow-indigo-500/5">
        
        <div class="flex-1 overflow-y-auto p-10 space-y-10 bg-[var(--light)]/30 backdrop-blur-sm scrollbar-thin scrollbar-thumb-[var(--border)]">
            <?php foreach ($replies as $reply): ?>
                <?php $isAdmin = (bool)$reply['is_admin_reply']; ?>
                
                <div class="flex <?= $isAdmin ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[85%] md:max-w-[75%] lg:max-w-[65%]">
                        <div class="flex items-center mb-2 <?= $isAdmin ? 'justify-end' : '' ?> opacity-40">
                            <span class="text-[9px] font-black text-[var(--text)] uppercase tracking-[0.2em]">
                                <?= $isAdmin ? 'Sovereign Representative' : htmlspecialchars($reply['sender_name'] ?? 'Tenant User') ?>
                            </span>
                            <span class="text-[9px] font-bold ml-4 font-mono">
                                <?= date('H:i:s @ d.M.Y', strtotime($reply['created_at'])) ?>
                            </span>
                        </div>
                        <div class="p-6 rounded-2xl shadow-xl text-[14px] font-medium leading-relaxed whitespace-pre-wrap transition-all transform hover:scale-[1.01] <?= $isAdmin ? 'bg-[var(--header)] text-[var(--white)] rounded-tr-none shadow-[var(--header)]/10' : 'bg-[var(--white)] border border-[var(--border)] text-[var(--header)] rounded-tl-none' ?>">
                            <?= nl2br(htmlspecialchars($reply['message'])) ?>
                            
                            <?php if (!empty($reply['attachment_path'])): ?>
                                <div class="mt-6 pt-6 border-t <?= $isAdmin ? 'border-[var(--white)]/10' : 'border-[var(--border)]/50' ?>">
                                    <a href="<?= htmlspecialchars($reply['attachment_path']) ?>" target="_blank" class="block group/asset relative overflow-hidden rounded-xl">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/asset:opacity-100 transition-opacity flex items-center justify-center font-black text-[var(--white)] text-[10px] uppercase tracking-[0.3em] backdrop-blur-sm">View Expansion</div>
                                        <img src="<?= htmlspecialchars($reply['attachment_path']) ?>" alt="Telemetry Attachment" class="max-h-64 w-full object-cover rounded-xl border <?= $isAdmin ? 'border-[var(--white)]/20' : 'border-[var(--border)]' ?> transition-transform duration-500 group-hover/asset:scale-110">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-8 bg-[var(--white)] border-t-2 border-[var(--border)]/50">
            <form action="/admin/support/reply" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                
                <div id="imagePreviewContainer" class="hidden relative w-fit mb-4">
                    <img id="imagePreview" src="" class="max-h-40 rounded-2xl border-2 border-[var(--theme2)] shadow-2xl object-cover animate-in fade-in zoom-in duration-300">
                    <button type="button" onclick="clearImage()" class="absolute -top-3 -right-3 bg-[var(--danger)] text-[var(--white)] rounded-full w-8 h-8 flex items-center justify-center text-lg font-black shadow-2xl hover:scale-110 active:scale-95 transition-all cursor-pointer border-2 border-[var(--white)]">&times;</button>
                </div>

                <div class="relative">
                    <textarea name="message" required rows="4" placeholder="Draft transmission for tenant collective..." class="w-full px-6 py-5 border-2 border-[var(--border)] rounded-2xl text-[14px] font-medium focus:ring-0 focus:border-[var(--theme2)] outline-none bg-[var(--light)]/50 focus:bg-[var(--white)] transition-all text-[var(--header)] resize-none shadow-inner"></textarea>
                    <div class="absolute bottom-4 right-6 pointer-events-none opacity-20 hidden md:block">
                        <svg class="w-8 h-8 text-[var(--header)]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd"></path></svg>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="w-full md:flex-1 max-w-sm">
                        <label class="group flex items-center px-6 py-3.5 bg-[var(--light)] border-2 border-[var(--border)] border-dashed rounded-2xl cursor-pointer hover:border-[var(--theme2)] hover:bg-[var(--white)] transition-all">
                            <svg class="w-5 h-5 mr-4 text-[var(--text)] group-hover:text-[var(--theme2)] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text)] group-hover:text-[var(--theme2)]">Inject Media Asset</span>
                            <input type="file" id="fileInput" name="attachment" accept="image/jpeg, image/png, image/webp" onchange="previewImage(this)" class="hidden">
                        </label>
                    </div>
                    <button type="submit" class="w-full md:w-auto bg-[var(--header)] text-[var(--white)] font-black px-12 py-4 rounded-2xl shadow-2xl hover:bg-[var(--theme2)] transition-all text-[11px] uppercase tracking-[0.4em] flex items-center justify-center transform hover:-translate-y-1 active:scale-95 group/submit">
                        <svg class="w-5 h-5 mr-4 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        Execute Transmission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const chatContainer = document.querySelector('.overflow-y-auto');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
    });

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

    function clearImage() {
        document.getElementById('fileInput').value = '';
        document.getElementById('imagePreviewContainer').classList.add('hidden');
        document.getElementById('imagePreview').src = '';
    }
</script>