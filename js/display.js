// Global variables to track changes
let lastServingNumber = '';
let lastNextNumber = '';

// Update time and date
function updateDisplayTime() {
    const now = new Date();
    
    // Update time
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    // Update date
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Initialize time update
setInterval(updateDisplayTime, 1000);
updateDisplayTime();

// Play notification sound
function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(e => console.log('Audio play failed:', e));
    }
}

// Fetch and update display data
async function updateDisplay() {
    try {
        const response = await fetch('api/get_display_data.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            console.error('Server error:', data.error);
            return;
        }

        // Update Now Serving with animation
        const nowServingElement = document.getElementById('nowServing');
        const currentServing = data.now_serving ? data.now_serving.queue_number : '---';
        
        if (currentServing !== lastServingNumber && currentServing !== '---') {
            nowServingElement.textContent = currentServing;
            nowServingElement.classList.add('flip-in');
            
            // Play sound when new number is called
            if (lastServingNumber !== '' && currentServing !== lastServingNumber) {
                playNotificationSound();
            }
            
            setTimeout(() => {
                nowServingElement.classList.remove('flip-in');
            }, 600);
            
            lastServingNumber = currentServing;
        } else if (currentServing === '---') {
            nowServingElement.textContent = '---';
        }

        // Update Next in Line
        const nextInLineElement = document.getElementById('nextInLine');
        const nextCustomerNameElement = document.getElementById('nextCustomerName');
        const currentNext = data.next_in_line ? data.next_in_line.queue_number : '---';
        
        if (currentNext !== lastNextNumber) {
            nextInLineElement.textContent = currentNext;
            nextCustomerNameElement.textContent = data.next_in_line ? data.next_in_line.name : 'Waiting for next customer';
            lastNextNumber = currentNext;
        }

        // Update Waiting Count
        document.getElementById('waitingCount').textContent = data.waiting_count;

        // Update Average Wait Time (simple calculation)
        const avgWaitElement = document.getElementById('averageWait');
        if (data.waiting_count > 0) {
            const avgMinutes = Math.max(2, Math.min(15, Math.floor(data.waiting_count * 3)));
            avgWaitElement.textContent = `${avgMinutes} min`;
        } else {
            avgWaitElement.textContent = '0 min';
        }

        // Update Recent Numbers
        const recentContainer = document.getElementById('recentNumbers');
        if (data.recent_called && data.recent_called.length > 0) {
            recentContainer.innerHTML = '';
            data.recent_called.slice(0, 8).forEach(customer => {
                const div = document.createElement('div');
                div.className = 'bg-white bg-opacity-25 rounded-xl px-6 py-4 text-3xl font-bold queue-number transform hover:scale-105 transition duration-300';
                div.textContent = customer.queue_number;
                div.title = `Called at ${new Date(customer.called_at).toLocaleTimeString()}`;
                recentContainer.appendChild(div);
            });
        } else {
            recentContainer.innerHTML = '<div class="text-2xl opacity-70">No recent calls</div>';
        }

        // Update Waiting Queue Ticker
        const tickerElement = document.getElementById('waitingQueueTicker');
        if (data.waiting_queue && data.waiting_queue.length > 0) {
            const queueNumbers = data.waiting_queue.map(customer => customer.queue_number).join(' • ');
            tickerElement.textContent = `Waiting: ${queueNumbers} • `;
            tickerElement.classList.add('ticker-item');
        } else {
            tickerElement.textContent = 'Queue is empty';
            tickerElement.classList.remove('ticker-item');
        }

    } catch (error) {
        console.error('Error updating display:', error);
        
        // Show error state
        document.getElementById('nowServing').textContent = '---';
        document.getElementById('nextInLine').textContent = '---';
        document.getElementById('waitingCount').textContent = '0';
        document.getElementById('recentNumbers').innerHTML = '<div class="text-2xl opacity-70">Connection error</div>';
    }
}

// Enhanced auto-refresh with progressive backoff
let refreshInterval = 3000; // Start with 3 seconds
let errorCount = 0;

function startAutoRefresh() {
    setInterval(() => {
        updateDisplay().then(() => {
            // Reset error count and interval on success
            errorCount = 0;
            refreshInterval = 3000;
        }).catch(() => {
            errorCount++;
            // Increase interval on consecutive errors (max 30 seconds)
            refreshInterval = Math.min(30000, 3000 + (errorCount * 2000));
        });
    }, refreshInterval);
}

// Add CSS animations for numbers
const style = document.createElement('style');
style.textContent = `
    @keyframes numberPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .number-pulse {
        animation: numberPulse 2s ease-in-out infinite;
    }
    
    @keyframes slideInFromRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .slide-in-right {
        animation: slideInFromRight 0.5s ease-out;
    }
`;
document.head.appendChild(style);

// Initialize display
updateDisplay();
startAutoRefresh();

// Add keyboard shortcut for manual refresh (for testing)
document.addEventListener('keydown', (e) => {
    if (e.key === 'r' || e.key === 'R') {
        updateDisplay();
    }
});

// Add visibility change handler to refresh when tab becomes visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        updateDisplay();
    }
});