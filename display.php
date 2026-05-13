<?php 
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'Service Center', 'welcome_message' => 'Welcome to our Service Center'];
}
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
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; }
        .video-container iframe, .video-container video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
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
                <div class="text-right"><div id="currentTime" class="text-2xl md:text-3xl font-mono font-bold text-yellow-300"></div><div id="currentDate" class="text-sm opacity-80"></div></div>
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
                <div class="window-card rounded-2xl p-4 h-full">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold"><i class="fas fa-play-circle mr-2 text-red-500"></i>Now Playing</h3>
                        <button onclick="toggleVideo()" class="text-white opacity-70 hover:opacity-100"><i id="videoToggleBtn" class="fas fa-pause"></i></button>
                    </div>
                    <div id="videoContainer" class="video-container rounded-xl overflow-hidden">
                        <div id="videoPlaceholder" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                            <div class="text-center"><i class="fas fa-film text-6xl opacity-30 mb-4"></i><p class="opacity-50">Video Area</p></div>
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
        let lastServing = { '1': '', '2': '' }; let videoPlaying = true; let currentVideoUrl = '';
        
        function updateDisplayTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true});
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        }
        setInterval(updateDisplayTime, 1000); updateDisplayTime();

        function playNotificationSound() { const audio = document.getElementById('notificationSound'); if (audio) { audio.currentTime = 0; audio.play().catch(e => {}); } }

        async function updateDisplay() {
            try {
                const response = await fetch('api/get_display_data.php');
                if (!response.ok) throw new Error('Network error');
                const data = await response.json();
                if (data.error) return;
                updateWindow('1', data.windows?.[0] || null, data);
                updateWindow('2', data.windows?.[1] || null, data);
                updateWaitingQueue(data.waiting_queue || []);
                updateRecentNumbers(data.recent_called || []);
                updateAnnouncements(data.announcements || []);
                if (data.redistribution_notice) showAlert(data.redistribution_notice.message, data.redistribution_notice.type);
                if (data.settings?.video_url && data.settings.video_url !== currentVideoUrl) loadVideo(data.settings.video_url, data.settings.video_type);
            } catch (error) { console.error('Error:', error); }
        }

        function updateWindow(windowNum, windowData, fullData) {
            const isOffline = !windowData?.is_online;
            const card = document.getElementById(`window${windowNum}Card`);
            const serving = document.getElementById(`window${windowNum}Serving`);
            const next = document.getElementById(`window${windowNum}Next`);
            const waiting = document.getElementById(`window${windowNum}Waiting`);
            const status = document.getElementById(`window${windowNum}Status`);
            
            if (isOffline) {
                card.classList.remove('window-card'); card.classList.add('window-offline');
                status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Offline';
                status.className = 'px-3 py-1 rounded-full text-sm bg-red-200 text-red-800';
                serving.textContent = '---'; serving.classList.remove('flip-in');
                next.textContent = 'Unavailable'; waiting.textContent = '0';
                return;
            }
            
            card.classList.remove('window-offline'); card.classList.add('window-card');
            status.innerHTML = '<i class="fas fa-circle text-xs mr-1"></i>Online';
            status.className = 'px-3 py-1 rounded-full text-sm bg-green-200 text-green-800';
            
            const newServing = windowData?.queue_number || '---';
            if (newServing !== lastServing[windowNum]) {
                serving.textContent = newServing; serving.classList.add('flip-in');
                setTimeout(() => serving.classList.remove('flip-in'), 600);
                lastServing[windowNum] = newServing;
                if (newServing !== '---') playNotificationSound();
            }
            
            const serviceType = windowNum === '1' ? ['insurance', 'benefits'] : ['id_renewal', 'atm_renewal'];
            const nextCustomer = fullData.next_by_service?.find(n => serviceType.includes(n.service_type));
            next.textContent = nextCustomer?.queue_number || '---';
            const waitCount = (fullData.waiting_queue || []).filter(c => serviceType.includes(c.service_type)).length;
            waiting.textContent = waitCount;
        }

        function updateWaitingQueue(queue) {
            const ticker = document.getElementById('waitingQueueTicker');
            if (!queue || queue.length === 0) { ticker.innerHTML = '<span class="text-gray-400">No customers waiting</span>'; ticker.classList.remove('ticker-scroll'); return; }
            ticker.classList.add('ticker-scroll');
            const iQueue = queue.filter(c => c.queue_number.startsWith('I')).map(c => c.queue_number);
            const rQueue = queue.filter(c => c.queue_number.startsWith('R')).map(c => c.queue_number);
            let tickerText = '';
            if (iQueue.length > 0) tickerText += `<span class="text-yellow-300 mr-8">I: ${iQueue.slice(0, 10).join(' • ')}</span>`;
            if (rQueue.length > 0) tickerText += `<span class="text-blue-300">R: ${rQueue.slice(0, 10).join(' • ')}</span>`;
            ticker.innerHTML = tickerText + '&nbsp;&nbsp;&nbsp;&nbsp;' + tickerText;
        }

        function updateRecentNumbers(recent) {
            const container = document.getElementById('recentNumbers');
            if (!recent || recent.length === 0) { container.innerHTML = '<span class="text-gray-400">No recent calls</span>'; return; }
            container.innerHTML = recent.slice(0, 8).map(c => {
                const colorClass = c.queue_number.startsWith('I') ? 'bg-yellow-500 bg-opacity-30' : 'bg-blue-500 bg-opacity-30';
                return `<span class="${colorClass} px-4 py-2 rounded-xl text-2xl font-bold queue-number">${c.queue_number}</span>`;
            }).join('');
        }

        function updateAnnouncements(announcements) {
            const ticker = document.getElementById('announcementTicker');
            const alert = document.getElementById('alertBanner');
            const urgent = announcements.find(a => a.type === 'urgent');
            const warning = announcements.find(a => a.type === 'warning');
            if (urgent) showAlert(urgent.message, 'urgent');
            else if (warning) showAlert(warning.message, 'warning');
            else if (announcements.length > 0) {
                const message = announcements.map(a => a.message).join(' • ');
                ticker.innerHTML = `<i class="fas fa-bullhorn mr-4 text-yellow-400"></i>${message}`;
                alert.classList.add('hidden');
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alertBanner');
            const messageEl = document.getElementById('alertMessage');
            alert.classList.remove('hidden');
            alert.querySelector('div').className = type === 'urgent' ? 'alert-urgent py-3 px-4 text-center' : 'alert-warning py-3 px-4 text-center';
            messageEl.textContent = message;
        }

        function loadVideo(url, type) {
            const container = document.getElementById('videoContainer');
            if (!url || type === 'none') { container.innerHTML = '<div id="videoPlaceholder" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50"><div class="text-center"><i class="fas fa-film text-6xl opacity-30 mb-4"></i><p class="opacity-50">Video Area</p></div></div>'; return; }
            currentVideoUrl = url;
            if (type === 'youtube') {
                let videoId = '';
                let playlistId = '';
                try {
                    const u = new URL(url);
                    if (u.hostname.includes('youtube.com')) {
                        videoId = u.searchParams.get('v');
                        playlistId = u.searchParams.get('list');
                        if (!videoId && u.pathname.includes('/embed/')) videoId = u.pathname.split('/embed/')[1].split('/')[0];
                    } else if (u.hostname.includes('youtu.be')) {
                        videoId = u.pathname.substring(1);
                        playlistId = u.searchParams.get('list');
                    }
                } catch (e) {
                    const vMatch = url.match(/[?&]v=([^&]+)/);
                    if (vMatch) videoId = vMatch[1];
                    const lMatch = url.match(/[?&]list=([^&]+)/);
                    if (lMatch) playlistId = lMatch[1];
                    if (!videoId && url.includes('youtu.be/')) videoId = url.split('youtu.be/')[1].split(/[?#]/)[0];
                }
                let embedUrl = `https://www.youtube.com/embed/${videoId || ''}?autoplay=1&mute=0&rel=0`;
                if (playlistId) embedUrl += `&listType=playlist&list=${playlistId}`;
                else if (videoId) embedUrl += `&loop=1&playlist=${videoId}`;
                container.innerHTML = `<iframe src="${embedUrl}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>`;
            } else {
                container.innerHTML = `<video id="localVideo" src="${url}" autoplay loop muted class="w-full h-full object-cover">Your browser does not support video.</video>`;
            }
        }

        function toggleVideo() {
            const video = document.getElementById('localVideo');
            const btn = document.getElementById('videoToggleBtn');
            if (video) { videoPlaying ? video.pause() : video.play(); btn.className = videoPlaying ? 'fas fa-play' : 'fas fa-pause'; videoPlaying = !videoPlaying; }
        }

        let refreshInterval = 3000; let errorCount = 0;
        function startAutoRefresh() {
            setInterval(() => {
                updateDisplay().then(() => { errorCount = 0; refreshInterval = 3000; }).catch(() => { errorCount++; refreshInterval = Math.min(30000, 3000 + (errorCount * 2000)); });
            }, refreshInterval);
        }

        document.addEventListener('keydown', (e) => { if (e.key === 'r' || e.key === 'R') updateDisplay(); if (e.key === 'm' || e.key === 'M') toggleVideo(); });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) updateDisplay(); });

        updateDisplay(); startAutoRefresh();
    </script>
</body>
</html>