import Pusher from 'pusher-js';

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

    // --- WEBSOCKET LISTENER ---
    

    const pusherKey = document.querySelector('meta[name="pusher-key"]')?.getAttribute('content');
    const pusherCluster = document.querySelector('meta[name="pusher-cluster"]')?.getAttribute('content');
    const hotelId = document.querySelector('meta[name="hotel-id"]')?.getAttribute('content');

    if (pusherKey && hotelId && hotelId !== '0') {
        const pusher = new Pusher(pusherKey, {
            cluster: pusherCluster,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-Token': csrfToken
                }
            }
        });

        const channel = pusher.subscribe(`hotel_channel_${hotelId}`);

        channel.bind('new_booking', function(data) {
            console.log('New real-time booking received:', data);
            
            // Create a toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 right-4 bg-[var(--theme)] text-white px-6 py-4 rounded-lg shadow-xl z-50 transform transition-all duration-500 translate-y-10 opacity-0';
            toast.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="font-bold text-sm">New Booking (#${data.bookingId})</h3>
                        <p class="text-sm mt-1">${data.guestName} - ${data.source}</p>
                        <p class="text-xs text-gray-200 mt-1">${data.checkIn} to ${data.checkOut}</p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            }, 100);
            
            // Remove after 10s
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 10000);

            // Dynamically inject into a bookings table if we're on the dashboard
            const recentBookingsList = document.getElementById('recentBookingsList');
            if (recentBookingsList) {
                // If the user is on the dashboard looking at the recent bookings table
                // This will depend on the exact HTML structure, but here is a safe basic injection
                const row = document.createElement('tr');
                row.className = 'bg-green-50 transition-colors duration-1000';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">SYNC-${data.bookingId}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.guestName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.checkIn} - ${data.checkOut}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-bold text-green-600">₹${data.totalPrice}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">${data.source}</span>
                    </td>
                `;
                // Insert at top
                if (recentBookingsList.firstChild) {
                    recentBookingsList.insertBefore(row, recentBookingsList.firstChild);
                } else {
                    recentBookingsList.appendChild(row);
                }
                
                // Fade out the green highlight
                setTimeout(() => {
                    row.classList.remove('bg-green-50');
                }, 3000);
            }
        });
    }
});