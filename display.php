<?php 
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'Service Center', 'welcome_message' => 'Welcome to our Service Center', 'cutoff_time' => '17:00:00'];
}
$cutoff = $settings['cutoff_time'] ?? '17:00:00';
$cutoff_formatted = date("g:i A", strtotime($cutoff));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display - <?php echo htmlspecialchars($settings['company_name'] ?? 'Service Center'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link id="dynamicFont" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Roboto:wght@400;700&family=Outfit:wght@400;700&family=Montserrat:wght@400;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link href="css/display.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: <?php echo $settings['theme_color'] ?? '#1e3a5f'; ?>;
        }
        body { 
            background: linear-gradient(135deg, var(--primary-color) 0%, #2d5a87 50%, var(--primary-color) 100%); 
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden; 
        }
        .queue-number { font-family: 'Courier New', monospace; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .window-card { background: linear-gradient(145deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%); backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.2); }
        .window-offline { background: repeating-linear-gradient(45deg, rgba(239,68,68,0.3), rgba(239,68,68,0.3) 10px, rgba(127,29,29,0.5) 10px, rgba(127,29,29,0.5) 20px); border: 2px dashed rgba(239,68,68,0.8); }
        .pulse-glow { animation: pulseGlow 2s infinite; }
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 20px rgba(250, 204, 21, 0.5); } 50% { box-shadow: 0 0 40px rgba(250, 204, 21, 0.8), 0 0 60px rgba(250, 204, 21, 0.4); } }
        .flip-in { animation: flipIn 0.6s ease-in-out; }
        @keyframes flipIn { from { transform: rotateX(90deg) scale(0.8); opacity: 0; } to { transform: rotateX(0deg) scale(1); opacity: 1; } }
        .marquee { animation: marquee 25s linear infinite; white-space: nowrap; }
        @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        .ticker-scroll { animation: tickerScroll 40s linear infinite; }
        @keyframes tickerScroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .alert-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .alert-urgent { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); animation: urgentPulse 1s infinite; }
        @keyframes urgentPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.8; } }
        .bulletin-item { background: rgba(255,255,255,0.1); border-left: 8px solid #3b82f6; transition: all 0.3s ease; }
        .bulletin-item:hover { transform: translateX(5px); }
        .bulletin-info { border-left-color: #60a5fa; background: rgba(30, 64, 175, 0.2); }
        .bulletin-warning { border-left-color: #fbbf24; background: rgba(146, 64, 14, 0.25); }
        .bulletin-urgent { border-left-color: #f87171; background: rgba(153, 27, 27, 0.3); animation: urgentPulse 2s infinite; }
        .fade-transition { transition: opacity 0.5s ease-in-out; }
    </style>
</head>
<body class="text-white min-h-screen">
    <div id="alertBanner" class="hidden">
        <div class="alert-warning py-3 px-4 text-center">
            <div class="flex items-center justify-center gap-3"><i class="fas fa-exclamation-triangle text-2xl"></i><span id="alertMessage" class="text-lg font-semibold"></span></div>
        </div>
    </div>

    <div class="bg-black bg-opacity-40 py-4">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div id="logoContainer" class="mr-4">
                        <?php if (!empty($settings['company_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($settings['company_logo']); ?>" alt="Logo" class="h-16 w-auto object-contain" id="companyLogoImg">
                        <?php else: ?>
                            <i class="fas fa-building text-4xl text-yellow-400" id="companyLogoIcon"></i>
                        <?php endif; ?>
                    </div>
                    <div><h1 class="text-2xl md:text-3xl font-bold" id="companyName"><?php echo htmlspecialchars($settings['company_name'] ?? 'Service Center'); ?></h1><p class="text-sm opacity-80">Queue Management System</p></div>
                </div>
                
                <div class="text-right">
                    <div id="currentTime" class="text-2xl md:text-3xl font-mono font-bold text-yellow-300"></div>
                    <div id="currentDate" class="text-sm opacity-80"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-blue-900 py-3 overflow-hidden">
        <div class="container mx-auto px-4">
            <div class="overflow-hidden">
                <div id="announcementTicker" class="marquee text-lg font-medium">
                    <i class="fas fa-bullhorn mr-4 text-yellow-400"></i>
                    <span id="tickerContent"><?php echo htmlspecialchars($settings['welcome_message'] ?? 'Welcome to our Service Center! Please have your queue ticket ready.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <div id="displayArea" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div id="windowsContainer" class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Windows will be injected dynamically here -->
            </div>

            <div class="lg:col-span-1">
                <div class="window-card rounded-2xl p-4 h-full flex flex-col">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-list-ol text-xl mr-3 text-yellow-400"></i>
                        <h3 class="text-xl font-bold uppercase tracking-wider">Waiting Queue</h3>
                    </div>
                    <div id="waitingQueueList" class="flex-grow overflow-y-auto pr-2 custom-scrollbar" style="max-height: 500px;">
                        <div class="text-center py-10 opacity-50">
                            <i class="fas fa-users text-5xl mb-4"></i>
                            <p>No customers waiting</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-black bg-opacity-40 rounded-xl p-6 border-t-4 border-yellow-500 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-history text-2xl mr-4 text-yellow-400"></i>
                    <h3 class="text-2xl font-bold uppercase tracking-widest text-yellow-400">Recently Called</h3>
                </div>
                <div class="text-xs opacity-60 italic uppercase tracking-tighter">Sequence of calls</div>
            </div>
            <div id="recentlyCalledHistory" class="flex flex-row overflow-x-auto gap-4 py-2 custom-scrollbar">
                <div class="text-center w-full py-10 opacity-30 italic">No recent calls to display</div>
            </div>
        </div>


    </div>

    <footer class="bg-black bg-opacity-40 py-4 mt-6">
        <div class="container mx-auto px-4 text-center"><p class="text-sm opacity-70"><i class="fas fa-heart text-red-400 mr-1"></i>Thank you for your patience • Your turn will be called when ready</p></div>
    </footer>

    <audio id="notificationSound" preload="auto" loop><source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg"></audio>

    <script>
        var lastCallInfo = {}; // Stores {queue_number, called_at} per window
        var cutoffFormatted = "<?php echo $cutoff_formatted; ?>";
        var announcementsPool = [];
        var currentAnnIndex = 0;
        var annTimer = null;
        
        function updateDisplayTime() {
            var now = new Date();
            var timeEl = document.getElementById('currentTime');
            var dateEl = document.getElementById('currentDate');
            if (timeEl) timeEl.textContent = now.toLocaleTimeString();
            if (dateEl) dateEl.textContent = now.toLocaleDateString() + " • Cut-off: " + cutoffFormatted;
        }

        setInterval(updateDisplayTime, 1000); updateDisplayTime();

        function playNotificationSound() { 
            var audio = document.getElementById('notificationSound'); 
            if (audio) { audio.currentTime = 0; audio.play().catch(function(e) {}); } 
        }

        function announceNumber(number, windowNum) {
            if (!window.speechSynthesis) return;
            
            // Cancel any pending speech
            window.speechSynthesis.cancel();
            
            // Format number for clearer speech (e.g. "R001" -> "R, 0, 0, 1")
            var spokenNumber = number.split('').join(', ');
            var text = "Now serving, ticket number " + spokenNumber + ", at Window " + windowNum;
            
            var utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.85; // Slightly slower for better clarity in public spaces
            utterance.pitch = 1.1; // Friendly tone
            utterance.volume = 1.0;
            
            // Wait a tiny bit for the notification chime to play its initial peak
            setTimeout(() => {
                window.speechSynthesis.speak(utterance);
            }, 800);
        }

        async function updateDisplay() {
            try {
                var response = await fetch('api/get_display_data.php');
                var data = await response.json();
                if (data.error) return;
                
                // Update Theme, Logo, and Font
                if (data.settings.theme_color) {
                    document.documentElement.style.setProperty('--primary-color', data.settings.theme_color);
                }
                
                
                const logoContainer = document.getElementById('logoContainer');
                if (logoContainer) {
                    if (data.settings.company_logo) {
                        logoContainer.innerHTML = `<img src="${data.settings.company_logo}" alt="Logo" class="h-16 w-auto object-contain" id="companyLogoImg">`;
                    } else {
                        logoContainer.innerHTML = `<i class="fas fa-building text-4xl text-yellow-400" id="companyLogoIcon"></i>`;
                    }
                }
                
                const companyNameEl = document.getElementById('companyName');
                if (companyNameEl) companyNameEl.textContent = data.settings.company_name;
                
                var wc = document.getElementById('windowsContainer');
                var windowsData = data.windows || [];
                
                // If container is empty or number of windows changed, clear and re-render
                if (wc && (wc.children.length === 0 || wc.children.length !== windowsData.length)) {
                    wc.innerHTML = '';
                }

                for (var i = 0; i < windowsData.length; i++) {
                    var w = windowsData[i];
                    var cardId = 'window' + w.window_number + 'Card';
                    
                    if (!document.getElementById(cardId)) {
                        var isBlue = (i % 2 !== 0);
                        var primaryColor = isBlue ? 'blue-300' : 'yellow-300';
                        var newHtml = `
                            <div id="${cardId}" class="window-card rounded-2xl p-6 text-center">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-xl font-bold">WINDOW ${w.window_number}</h3>
                                    <span id="window${w.window_number}Status" class="px-3 py-1 rounded-full text-sm"><i class="fas fa-circle text-xs mr-1"></i>Loading</span>
                                </div>
                                <div id="window${w.window_number}Services" class="text-sm mb-4 opacity-80 h-5 overflow-hidden">Loading services...</div>
                                <div class="bg-black bg-opacity-30 rounded-xl p-6 mb-4">
                                    <div class="text-${primaryColor} text-lg mb-2 uppercase font-bold">Now Serving</div>
                                    <div id="window${w.window_number}Serving" class="text-7xl font-bold queue-number text-${primaryColor}">---</div>
                                </div>
                                <div class="bg-black bg-opacity-20 rounded-lg p-4 mb-4">
                                    <div class="text-green-300 text-sm mb-1 uppercase font-bold">Next In Line</div>
                                    <div id="window${w.window_number}Next" class="text-4xl font-bold queue-number">---</div>
                                </div>
                                <div class="bg-black bg-opacity-10 rounded-lg p-3">
                                    <div class="text-xs opacity-60 uppercase mb-2 border-b border-white border-opacity-10 pb-1">Previous Calls</div>
                                    <div id="window${w.window_number}History" class="flex justify-center gap-3 text-lg font-bold opacity-80">---</div>
                                </div>
                            </div>
                        `;
                        if (wc) wc.insertAdjacentHTML('beforeend', newHtml);
                    }
                    updateWindow(w.window_number, w, data);
                }
                
                updateWaitingQueue(data.waiting_queue || []);
                updateAnnouncements(data.announcements || []);
                updateRecentCalledHistory(data.recent_called_history || []);
            } catch (error) { console.error('Display Error:', error); }
        }

        function updateWindow(windowNum, windowData, fullData) {
            var isOffline = !(windowData && windowData.is_online);
            var statusText = (windowData && windowData.status_text) ? windowData.status_text : (isOffline ? 'Offline' : 'Online');
            
            var card = document.getElementById('window' + windowNum + 'Card');
            var serving = document.getElementById('window' + windowNum + 'Serving');
            var next = document.getElementById('window' + windowNum + 'Next');
            var status = document.getElementById('window' + windowNum + 'Status');
            var servicesEl = document.getElementById('window' + windowNum + 'Services');
            
            if (!card || !serving || !next || !status) return;
            
            if (servicesEl) {
                servicesEl.textContent = (windowData && windowData.active_services_names) ? windowData.active_services_names : 'No active services';
            }

            if (statusText === 'On Break') {
                card.classList.remove('window-offline'); card.classList.add('window-card');
                status.innerHTML = '<i class="fas fa-pause-circle text-xs mr-1"></i>On Break';
                status.className = 'px-3 py-1 rounded-full text-sm bg-yellow-200 text-yellow-800';
                serving.textContent = '---';
                next.textContent = 'Unavailable'; 
                return;
            } else if (isOffline) {
                card.classList.remove('window-card'); card.classList.add('window-offline');
                status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Offline';
                status.className = 'px-3 py-1 rounded-full text-sm bg-red-200 text-red-800';
                serving.textContent = '---';
                next.textContent = 'Unavailable'; 
                return;
            }
            
            card.classList.remove('window-offline'); card.classList.add('window-card');
            status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Online';
            status.className = 'px-3 py-1 rounded-full text-sm bg-green-200 text-green-800';
            
            var newServing = (windowData && windowData.queue_number) ? windowData.queue_number : '---';
            var calledAt = (windowData && windowData.called_at) ? windowData.called_at : '';
            
            if (!lastCallInfo[windowNum]) {
                lastCallInfo[windowNum] = { queue_number: '', called_at: '' };
            }

            if (newServing !== '---' && (newServing !== lastCallInfo[windowNum].queue_number || calledAt !== lastCallInfo[windowNum].called_at)) {
                serving.textContent = newServing;
                lastCallInfo[windowNum] = { queue_number: newServing, called_at: calledAt };
                
                playNotificationSound();
                announceNumber(newServing, windowNum);
            } else if (newServing === '---') {
                serving.textContent = '---';
                lastCallInfo[windowNum] = { queue_number: '---', called_at: '' };
            }
            
            var serviceType = (windowData && windowData.active_services) ? windowData.active_services.split(',') : [];
            var nextCustomer = null;
            if (fullData.next_by_service) {
                for (var i = 0; i < fullData.next_by_service.length; i++) {
                    if (serviceType.indexOf(fullData.next_by_service[i].service_type) !== -1) {
                        nextCustomer = fullData.next_by_service[i];
                        break;
                    }
                }
            }
            next.textContent = (nextCustomer && nextCustomer.queue_number) ? nextCustomer.queue_number : '---';
            
            var waitCount = 0;
            var q = fullData.waiting_queue || [];
            for (var j = 0; j < q.length; j++) {
                if (serviceType.indexOf(q[j].service_type) !== -1) waitCount++;
            }
            // waiting.textContent = waitCount; // Feature removed as requested

            // Update Window History (3 previous calls)
            var historyEl = document.getElementById('window' + windowNum + 'History');
            if (historyEl && fullData.recent_called_history) {
                var currentNum = (windowData && windowData.queue_number) ? windowData.queue_number : null;
                var history = fullData.recent_called_history
                    .filter(h => h.window_number == windowNum && h.queue_number !== currentNum)
                    .slice(0, 3);
                
                if (history.length > 0) {
                    historyEl.innerHTML = history.map(h => `<span class="px-2 py-0.5 bg-white bg-opacity-5 rounded">${h.queue_number}</span>`).join('');
                } else {
                    historyEl.innerHTML = '<span class="text-xs opacity-40 italic">No history</span>';
                }
            }
        }

        function updateWaitingQueue(queue) {
            var list = document.getElementById('waitingQueueList');
            if (!list) return;
            if (!queue || queue.length === 0) { 
                list.innerHTML = '<div class="text-center py-10 opacity-50"><i class="fas fa-users text-5xl mb-4"></i><p>No customers waiting</p></div>'; 
                return; 
            }
            
            var html = '<div class="grid grid-cols-2 gap-3">';
            for (var i = 0; i < queue.length; i++) {
                html += '<div class="bg-white bg-opacity-10 border border-white border-opacity-10 rounded-lg p-3 text-center">' +
                        '<div class="text-xs opacity-60 uppercase mb-1">' + (queue[i].service_type || 'Queue') + '</div>' +
                        '<div class="text-2xl font-bold text-yellow-300 font-mono">' + queue[i].queue_number + '</div>' +
                        '</div>';
            }
            html += '</div>';
            list.innerHTML = html;
        }



        function updateAnnouncements(announcements) {
            var ticker = document.getElementById('tickerContent');
            if (!ticker) return;
            
            if (!announcements || announcements.length === 0) {
                ticker.textContent = "<?php echo addslashes($settings['welcome_message'] ?? 'Welcome to our Service Center! Please have your queue ticket ready.'); ?>";
                return;
            }
            
            var text = announcements.map(a => a.message).join(' ••• ');
            ticker.textContent = text;
        }

        function updateRecentCalledHistory(history) {
            var container = document.getElementById('recentlyCalledHistory');
            if (!container) return;
            
            if (!history || history.length === 0) {
                container.innerHTML = '<div class="text-center w-full py-10 opacity-30 italic">No recent calls to display</div>';
                return;
            }
            
            var html = '';
            for (var i = 0; i < history.length; i++) {
                var h = history[i];
                var isNewest = (i === 0);
                var newestClass = isNewest ? 'border-yellow-400 bg-yellow-400 bg-opacity-20 scale-105 shadow-yellow-500/20' : 'border-white border-opacity-10 bg-white bg-opacity-5';
                
                // Fix for possible invalid date format in some browsers
                var callTime = h.called_at ? h.called_at.replace(/-/g, '/') : null;
                var timeStr = callTime ? new Date(callTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '--:--';
                
                html += `
                    <div class="flex-shrink-0 w-48 p-4 rounded-xl border-2 ${newestClass} transition-all duration-500">
                        <div class="text-xs opacity-60 uppercase mb-1">${h.display_name || 'Window ' + h.window_number}</div>
                        <div class="text-3xl font-bold text-yellow-300 font-mono">${h.queue_number}</div>
                        <div class="text-[10px] opacity-40 mt-2 italic">${timeStr}</div>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        setInterval(updateDisplay, 3000);
        updateDisplay();
    </script>
</body>
</html>