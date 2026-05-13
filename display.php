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
    <link href="css/display.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #1e3a5f 100%); font-family: 'Segoe UI', Arial, sans-serif; overflow-x: hidden; }
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
        .bulletin-item { background: rgba(255,255,255,0.05); border-left: 4px solid #fbbf24; transition: all 0.3s ease; }
        .bulletin-item:hover { background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .bulletin-urgent { border-left-color: #ef4444; background: rgba(239,68,68,0.1); }
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
                    <i class="fas fa-building text-3xl mr-4 text-yellow-400"></i>
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
        <div class="container mx-auto px-4"><div class="overflow-hidden"><div id="announcementTicker" class="marquee text-lg font-medium"><i class="fas fa-bullhorn mr-4 text-yellow-400"></i><?php echo htmlspecialchars($settings['welcome_message'] ?? 'Welcome! Please have your queue ticket ready.'); ?></div></div></div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <div id="displayArea" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div id="window1Card" class="window-card rounded-2xl p-6 text-center">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">WINDOW 1</h3>
                        <span id="window1Status" class="px-3 py-1 rounded-full text-sm bg-green-200 text-green-800"><i class="fas fa-circle text-xs mr-1"></i>Online</span>
                    </div>
                    <div id="window1Services" class="text-sm mb-4 opacity-80">Insurance & Benefits</div>
                    <div class="bg-black bg-opacity-30 rounded-xl p-6 mb-4">
                        <div class="text-yellow-300 text-lg mb-2">NOW SERVING</div>
                        <div id="window1Serving" class="text-7xl font-bold queue-number text-yellow-300">---</div>
                    </div>
                    <div class="bg-black bg-opacity-20 rounded-lg p-4">
                        <div class="text-green-300 text-sm mb-1">NEXT IN LINE</div>
                        <div id="window1Next" class="text-4xl font-bold queue-number">---</div>
                    </div>
                    <div class="mt-4 flex justify-between items-center text-sm"><span class="opacity-70"><i class="fas fa-users mr-1"></i>Waiting: <span id="window1Waiting">0</span></span><span class="opacity-70">Avg Wait: <span id="window1AvgWait">--</span></span></div>
                </div>

                <div id="window2Card" class="window-card rounded-2xl p-6 text-center">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold">WINDOW 2</h3>
                        <span id="window2Status" class="px-3 py-1 rounded-full text-sm bg-green-200 text-green-800"><i class="fas fa-circle text-xs mr-1"></i>Online</span>
                    </div>
                    <div id="window2Services" class="text-sm mb-4 opacity-80">ID & ATM Renewals</div>
                    <div class="bg-black bg-opacity-30 rounded-xl p-6 mb-4">
                        <div class="text-blue-300 text-lg mb-2">NOW SERVING</div>
                        <div id="window2Serving" class="text-7xl font-bold queue-number text-blue-300">---</div>
                    </div>
                    <div class="bg-black bg-opacity-20 rounded-lg p-4">
                        <div class="text-green-300 text-sm mb-1">NEXT IN LINE</div>
                        <div id="window2Next" class="text-4xl font-bold queue-number">---</div>
                    </div>
                    <div class="mt-4 flex justify-between items-center text-sm"><span class="opacity-70"><i class="fas fa-users mr-1"></i>Waiting: <span id="window2Waiting">0</span></span><span class="opacity-70">Avg Wait: <span id="window2AvgWait">--</span></span></div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="window-card rounded-2xl p-4 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold"><i class="fas fa-bullhorn mr-2 text-yellow-400"></i>BULLETIN BOARD</h3>
                    </div>
                    <div id="bulletinBoard" class="flex-grow space-y-4 overflow-y-auto pr-2 custom-scrollbar" style="max-height: 500px;">
                        <div class="text-center py-10 opacity-50">
                            <i class="fas fa-clipboard-list text-5xl mb-4"></i>
                            <p>No active announcements</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 bg-black bg-opacity-40 rounded-xl p-4">
            <div class="flex items-center mb-3"><i class="fas fa-list-ol text-xl mr-3 text-yellow-400"></i><h4 class="text-lg font-bold text-yellow-400">WAITING QUEUE</h4></div>
            <div class="overflow-hidden"><div id="waitingQueueTicker" class="ticker-scroll text-xl font-semibold"><span class="text-gray-400">No customers waiting</span></div></div>
        </div>

        <div class="mt-6 window-card rounded-xl p-4">
            <h4 class="text-lg font-bold mb-4 text-center"><i class="fas fa-history mr-2"></i>RECENTLY CALLED</h4>
            <div id="recentNumbers" class="flex flex-wrap justify-center gap-4"><span class="text-gray-400">No recent calls</span></div>
        </div>
    </div>

    <footer class="bg-black bg-opacity-40 py-4 mt-6">
        <div class="container mx-auto px-4 text-center"><p class="text-sm opacity-70"><i class="fas fa-heart text-red-400 mr-1"></i>Thank you for your patience • Your turn will be called when ready</p></div>
    </footer>

    <audio id="notificationSound" preload="auto" loop><source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg"></audio>

    <script>
        var lastServing = { '1': '', '2': '' };
        var cutoffFormatted = "<?php echo $cutoff_formatted; ?>";
        
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

        async function updateDisplay() {
            try {
                var response = await fetch('api/get_display_data.php');
                var data = await response.json();
                if (data.error) return;
                
                updateWindow('1', (data.windows && data.windows[0]) ? data.windows[0] : null, data);
                updateWindow('2', (data.windows && data.windows[1]) ? data.windows[1] : null, data);
                updateWaitingQueue(data.waiting_queue || []);
                updateRecentNumbers(data.recent_called || []);
                updateAnnouncements(data.announcements || []);
            } catch (error) { console.error('Display Error:', error); }
        }

        function updateWindow(windowNum, windowData, fullData) {
            var isOffline = !(windowData && windowData.is_online);
            var card = document.getElementById('window' + windowNum + 'Card');
            var serving = document.getElementById('window' + windowNum + 'Serving');
            var next = document.getElementById('window' + windowNum + 'Next');
            var waiting = document.getElementById('window' + windowNum + 'Waiting');
            var status = document.getElementById('window' + windowNum + 'Status');
            
            if (!card || !serving || !next || !waiting || !status) return;

            if (isOffline) {
                card.classList.remove('window-card'); card.classList.add('window-offline');
                status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Offline';
                status.className = 'px-3 py-1 rounded-full text-sm bg-red-200 text-red-800';
                serving.textContent = '---';
                next.textContent = 'Unavailable'; 
                waiting.textContent = '0';
                return;
            }
            
            card.classList.remove('window-offline'); card.classList.add('window-card');
            status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Online';
            status.className = 'px-3 py-1 rounded-full text-sm bg-green-200 text-green-800';
            
            var newServing = (windowData && windowData.queue_number) ? windowData.queue_number : '---';
            if (newServing !== lastServing[windowNum]) {
                serving.textContent = newServing;
                lastServing[windowNum] = newServing;
                if (newServing !== '---') playNotificationSound();
            }
            
            var serviceType = windowNum === '1' ? ['insurance', 'benefits'] : ['id_renewal', 'atm_renewal'];
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
            waiting.textContent = waitCount;
        }

        function updateWaitingQueue(queue) {
            var ticker = document.getElementById('waitingQueueTicker');
            if (!ticker) return;
            if (!queue || queue.length === 0) { ticker.innerHTML = '<span class="text-gray-400">No customers waiting</span>'; return; }
            
            var tickerText = '';
            for (var i = 0; i < queue.length; i++) {
                tickerText += '<span class="mr-6">' + queue[i].queue_number + '</span>';
            }
            ticker.innerHTML = tickerText;
        }

        function updateRecentNumbers(recent) {
            var container = document.getElementById('recentNumbers');
            if (!container) return;
            if (!recent || recent.length === 0) { container.innerHTML = '<span class="text-gray-400">No recent calls</span>'; return; }
            var html = '';
            for (var i = 0; i < Math.min(recent.length, 8); i++) {
                html += '<span class="bg-blue-500 bg-opacity-30 px-4 py-2 rounded-xl text-2xl font-bold">' + recent[i].queue_number + '</span>';
            }
            container.innerHTML = html;
        }

        function updateAnnouncements(announcements) {
            var board = document.getElementById('bulletinBoard');
            if (!board) return;
            if (!announcements || announcements.length === 0) {
                board.innerHTML = '<div class="text-center py-10 opacity-50"><p>No active announcements</p></div>';
                return;
            }
            var html = '';
            for (var i = 0; i < announcements.length; i++) {
                var a = announcements[i];
                html += '<div class="bulletin-item rounded-lg p-4 mb-4">' +
                        '<h5 class="font-bold text-xs uppercase opacity-70">' + a.type + '</h5>' +
                        '<p class="text-base">' + a.message + '</p>' +
                        '</div>';
            }
            board.innerHTML = html;
        }

        setInterval(updateDisplay, 3000);
        updateDisplay();
    </script>
</body>
</html>