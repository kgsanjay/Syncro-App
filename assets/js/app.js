document.addEventListener('DOMContentLoaded', () => {
    // CRITICAL FIX: Fetch CSRF token from the secure meta tag instead of a hidden input
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        console.error('Security Protocol Failure: CSRF token missing from DOM.');
        return;
    }

    const inputs = document.querySelectorAll('input[data-room]');

    // Debounce function to prevent hammering the server on rapid typing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    const syncInventory = async (input) => {
        const roomId = input.dataset.room;
        const targetDate = input.dataset.date;
        const field = input.dataset.field;
        let value = input.type === 'checkbox' ? (input.checked ? 1 : 0) : input.value;

        // Visual feedback: Saving state
        const originalBg = input.classList.contains('bg-transparent') ? 'bg-transparent' : 'bg-white';
        if (input.type !== 'checkbox') {
            input.classList.add('bg-yellow-50', 'text-yellow-800');
        }

        try {
            const response = await fetch('/ajax/inventory/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    room_id: roomId,
                    target_date: targetDate,
                    field: field,
                    value: value
                })
            });

            const result = await response.json();

            if (result.success) {
                // Visual feedback: Success
                if (input.type !== 'checkbox') {
                    input.classList.remove('bg-yellow-50', 'text-yellow-800');
                    input.classList.add('bg-green-50', 'text-green-800', 'border-green-400');
                    
                    setTimeout(() => {
                        input.classList.remove('bg-green-50', 'text-green-800', 'border-green-400');
                    }, 1000);
                } else if (field === 'stop') {
                    // Toggle the red background on the parent TD for Stop Sells
                    const cell = input.closest('td');
                    if (value === 1) {
                        cell.classList.add('bg-red-50');
                    } else {
                        cell.classList.remove('bg-red-50');
                    }
                }
            } else {
                throw new Error(result.error || 'Server rejected the update.');
            }
        } catch (error) {
            console.error('Sync Error:', error);
            // Visual feedback: Error
            if (input.type !== 'checkbox') {
                input.classList.remove('bg-yellow-50', 'text-yellow-800');
                input.classList.add('bg-red-50', 'text-red-800', 'border-red-500');
                alert('Failed to sync data: ' + error.message);
            } else {
                // Revert checkbox if failed
                input.checked = !input.checked;
                alert('Failed to update stop-sell status.');
            }
        }
    };

    // Attach listeners
    inputs.forEach(input => {
        if (input.type === 'checkbox') {
            input.addEventListener('change', () => syncInventory(input));
        } else {
            // Debounce text/number inputs by 500ms
            input.addEventListener('input', debounce(() => syncInventory(input), 500));
        }
    });

    // --- OUTBOUND PUSH ENGINE ---
    const syncBtn = document.getElementById('syncNowBtn');
    
    if (syncBtn) {
        syncBtn.addEventListener('click', async () => {
            const originalText = syncBtn.innerText;
            
            // UI Feedback: Set to loading state
            syncBtn.disabled = true;
            syncBtn.innerText = 'Syncing...';
            syncBtn.classList.add('opacity-75', 'cursor-not-allowed');

            try {
                const response = await fetch('/ajax/inventory/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ csrf_token: csrfToken })
                });

                const result = await response.json();

                if (result.success) {
                    // UI Feedback: Success
                    syncBtn.innerText = 'Sync Complete!';
                    syncBtn.classList.replace('bg-[var(--theme2)]', 'bg-green-600');
                    syncBtn.classList.replace('hover:bg-blue-900', 'hover:bg-green-700');
                    alert(result.message);
                } else {
                    throw new Error(result.error || 'Server rejected the sync request.');
                }
            } catch (error) {
                console.error('Push Error:', error);
                alert('Sync Failed: ' + error.message);
            } finally {
                // Revert button state after 3 seconds
                setTimeout(() => {
                    syncBtn.disabled = false;
                    syncBtn.innerText = originalText;
                    syncBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                    syncBtn.classList.replace('bg-green-600', 'bg-[var(--theme2)]');
                    syncBtn.classList.replace('hover:bg-green-700', 'hover:bg-blue-900');
                }, 3000);
            }
        });
    }
});