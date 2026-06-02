<?php 
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'Service Center', 'branch_name' => 'Main Office', 'address' => '', 'welcome_message' => 'Welcome', 'cutoff_time' => '17:00:00', 'company_logo' => '', 'video_url' => 'aqz-KE-bpKQ', 'video_type' => 'youtube', 'video_title' => 'Citizen Services Overview', 'video_sponsor' => 'Public Affairs Office', 'video_cta' => '', 'video_volume' => 50, 'poster_duration' => 10, 'poster_images' => '[]'];
}
function getYoutubeId($url) {
    if (!$url) return 'aqz-KE-bpKQ';
    $parsed = parse_url($url);
    if (isset($parsed['query'])) { parse_str($parsed['query'], $q); if (!empty($q['v'])) return $q['v']; }
    $path = $parsed['path'] ?? $url;
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'embed/')) $path = substr($path, 6);
    return preg_match('/^[a-zA-Z0-9_-]{11}$/', $path) ? $path : $url;
}
$company_name = htmlspecialchars($settings['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($settings['branch_name'] ?? 'Main Office');
$address = htmlspecialchars($settings['address'] ?? '');
$company_logo = htmlspecialchars($settings['company_logo'] ?? '');
$cutoff = $settings['cutoff_time'] ?? '17:00:00';
$cutoff_formatted = date("g:i A", strtotime($cutoff));
$welcome = addslashes($settings['welcome_message'] ?? 'Welcome! Please have your queue ticket ready.');
$video_url_raw = $settings['video_url'] ?? '';
$video_type = $settings['video_type'] ?? 'youtube';
$video_id = getYoutubeId($video_url_raw);
$video_volume = intval($settings['video_volume'] ?? 50);
$duck_volume = max(1, intval($video_volume * 0.15));
$poster_duration = intval($settings['poster_duration'] ?? 10);
$poster_images_raw = $settings['poster_images'] ?? '[]';
$poster_images = json_decode($poster_images_raw, true) ?: [];
$poster_announcements_raw = $settings['poster_announcements'] ?? '[]';
$poster_announcements = json_decode($poster_announcements_raw, true) ?: [];

// Merge images and announcements into a unified poster array, interleaving them
$mergedPosters = [];
$imgCount = count($poster_images);
$annCount = count($poster_announcements);
$maxCount = max($imgCount, $annCount);
for ($i = 0; $i < $maxCount; $i++) {
    if ($i < $imgCount) {
        $mergedPosters[] = ['type' => 'image', 'src' => $poster_images[$i]];
    }
    if ($i < $annCount) {
        $mergedPosters[] = ['type' => 'announcement', 'title' => $poster_announcements[$i]['title'] ?? '', 'body' => $poster_announcements[$i]['body'] ?? '', 'bg' => $poster_announcements[$i]['bg'] ?? '#1e3a5f', 'fg' => $poster_announcements[$i]['fg'] ?? '#ffffff', 'text_size' => $poster_announcements[$i]['text_size'] ?? 'md'];
    }
}
$mergedPostersJson = json_encode($mergedPosters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Display — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        .now-serving-card { min-height: 260px; }
        .now-serving-card .num { font-size: 100px; line-height: 0.9; }
        @media (min-width: 768px) { .now-serving-card .num { font-size: 120px; } }
        @keyframes flipIn { 0% { transform: translateY(-20px) scale(0.9); opacity: 0; } 50% { transform: translateY(10px) scale(1.05); } 100% { transform: translateY(0) scale(1); opacity: 1; } }
        .flip-in { animation: flipIn 0.6s var(--ease-out-expo); }
        .history-card { flex-shrink: 0; width: 10rem; }
        @media (prefers-reduced-motion: reduce) { .flip-in, .animate-entry { animation: none !important; } }
        #posterPanel { display: none; flex-direction: column; background: #000; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); }
        #posterPanel.active { display: flex; }
        #nextUpList { max-height: 240px; overflow-y: auto; }
        @media (min-width: 768px) { #nextUpList { max-height: 360px; } }

    </style>
    <script>
        function toggleDisplayMode() {
            var entering = !document.body.classList.contains('display-mode');
            document.body.classList.toggle('display-mode');
            var label = document.getElementById('displayModeLabel');
            var icon = document.getElementById('displayModeIcon');
            if (entering) {
                label.textContent = 'Exit';
                icon.innerHTML = '&#9633;';
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                }
            } else {
                label.textContent = 'Display';
                icon.innerHTML = '&#9632;';
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        }
        function updateClock() {
            var now = new Date();
            var h = now.getHours();
            var ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            var el = document.getElementById('liveClock');
            if (el) el.textContent = h + ':' + m + ':' + s + ' ' + ampm;
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background); color: var(--foreground);">
    <style>
        body.display-mode .nav-links,
        body.display-mode .nav-status,
        body.display-mode .sub-header { display: none !important; }
        body.display-mode .company-name { font-size: 28px; }
        body.display-mode .company-sub { font-size: 11px; }
        body.display-mode .now-serving-card .num { font-size: 130px; }
        body.display-mode .now-serving-card .window-label { font-size: 22px !important; padding: 6px 16px !important; border-radius: 8px !important; background: rgba(0,0,0,0.06) !important; }
        body.display-mode .now-serving-card .now-serving-tag { font-size: 16px !important; }
        body.display-mode .now-serving-card .card-footer { font-size: 16px !important; }
        body.display-mode .now-serving-card .live-badge { font-size: 14px !important; }
        body.display-mode #nextUpList { max-height: 360px !important; }
        body.display-mode #posterPanel { border-width: 2px !important; }
        body.display-mode #posterLabel { font-size: 11px !important; }
        body.display-mode #posterCountdownSide { font-size: 12px !important; }
        @media (min-width: 768px) {
            body.display-mode .now-serving-card .num { font-size: 150px; }
            body.display-mode .now-serving-card .window-label { font-size: 26px !important; padding: 8px 20px !important; }
            body.display-mode .now-serving-card .now-serving-tag { font-size: 18px !important; }
            body.display-mode .now-serving-card .card-footer { font-size: 18px !important; }
            body.display-mode #nextUpList { max-height: 480px !important; }
        }
        #siteNav { cursor: pointer; }
    </style>

    <!-- SiteNav -->
    <nav id="siteNav" class="sticky top-0 z-50" style="background: #b91c1c; color: white; border-bottom: 1px solid rgba(255,255,255,0.15);" onclick="toggleDisplayMode();">
        <div class="max-w-[1600px] mx-auto flex items-center justify-between px-6" style="height: 3.5rem;">
            <div class="flex items-center gap-10">
                <a href="display.php" class="flex items-center gap-3">
                    <div class="relative w-7 h-7 grid place-items-center" style="background: var(--brand-gold); border-radius: 2px;">
                        <?php if ($company_logo): ?><img src="<?php echo $company_logo; ?>" alt="" class="w-5 h-5 object-contain"><?php else: ?><span style="color: var(--primary); font-size: 11px; font-weight: 900; letter-spacing: -0.05em;">CQ</span><?php endif; ?>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="company-name" style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: white;"><?php echo $company_name; ?></span>
                        <span class="company-sub" style="font-size: 9px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.5;"><?php if ($branch_name) echo htmlspecialchars($branch_name) . ' · '; ?>Queue Management</span>
                    </div>
                </a>
                <div class="hidden md:flex gap-1 text-[11px] font-semibold uppercase tracking-wider nav-links">
                    <a href="display.php" class="px-3 py-1.5 rounded" style="background: rgba(255,255,255,0.1); color: white;">Live Display</a>
                    <a href="kiosk.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Kiosk</a>
                    <a href="index.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Operator</a>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span id="liveClock" class="font-mono text-xl font-bold tracking-wider" style="color: white;">--:--:--</span>
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded nav-status" style="background: rgba(255,255,255,0.1);">
                    <div class="w-1.5 h-1.5 rounded-full" style="background: #34d399; animation: pulse-dot 2s infinite;"></div>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">All Systems Operational</span>
                </div>
                <button onclick="event.stopPropagation(); toggleDisplayMode();" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded text-[10px] font-bold uppercase tracking-widest" style="background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.2);">
                    <span id="displayModeIcon">&#9632;</span>
                    <span id="displayModeLabel">Display</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Sub-header bar -->
    <div class="sub-header" style="background: var(--card); border-bottom: 1px solid var(--border);">
        <div class="max-w-[1600px] mx-auto px-6 flex items-center justify-between flex-wrap gap-3" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <div class="flex items-center gap-4">
                <span style="font-size: 10px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted);"><?php echo $branch_name; ?></span>
                <?php if ($address): ?><span style="font-size: 10px; font-family: var(--font-mono); color: var(--muted);"><?php echo $address; ?></span><span style="height: 0.75rem; width: 1px; background: var(--border);"></span><?php endif; ?>
                <span style="font-size: 10px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted);">Cutoff <?php echo $cutoff_formatted; ?></span>
            </div>
            <div class="flex items-center gap-6" style="font-size: 10px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted);">
                <span>Wait · <span class="font-bold" style="color: var(--foreground);" id="statWait">--m</span></span>
                <span>Active Windows · <span class="font-bold" style="color: var(--foreground);" id="statWindows">0</span></span>
                <span>Tickets Today · <span class="font-bold" style="color: var(--foreground);" id="statTickets">0</span></span>
            </div>
        </div>
    </div>

    <main class="flex-1 w-full max-w-[1600px] mx-auto px-6 py-6 grid grid-cols-12 gap-6">
        <!-- Left Column -->
        <section class="col-span-12 lg:col-span-8 flex flex-col gap-6">
            <!-- Now Serving -->
            <div id="windowsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Injected by JS -->
            </div>

            <!-- Sponsored Media Panel -->
            <div id="mediaPanel" class="rounded-md overflow-hidden shadow-lg" style="background: var(--surface-dark); color: var(--surface-dark-foreground); border: 1px solid rgba(0,0,0,0.2);">
                <div class="flex items-center justify-between px-4 py-2" style="background: rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span id="mediaLabel" class="text-[9px] font-mono uppercase tracking-widest" style="color: rgba(255,255,255,0.5);">VIDEO</span>
                </div>
                <div class="relative" style="aspect-ratio: 16 / 9; background: black;">
                    <div id="mediaSlideContainer" class="absolute inset-0 w-full h-full">
                        <div id="mediaVideo" class="absolute inset-0 w-full h-full"></div>
                    </div>
                    <div class="absolute inset-0 pointer-events-none" style="background: linear-gradient(to top, rgba(0,0,0,0.85), transparent 60%);"></div>
                    <div class="absolute bottom-0 left-0 right-0 p-4 flex items-end justify-between gap-4 pointer-events-none">
                        <div>
                            <span id="mediaSponsor" class="inline-block text-[9px] font-bold uppercase tracking-widest mb-1" style="color: var(--brand-gold);">Public Affairs Office</span>
                            <h4 id="mediaTitle" class="text-base font-semibold leading-tight max-w-md">Citizen Services Overview</h4>
                            <p id="mediaCta" class="text-[11px] mt-1" style="color: rgba(255,255,255,0.7);"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Marquee -->
            <div id="alertBar" class="alert-marquee-wrap" style="display: none; background: var(--primary); color: var(--primary-foreground); border-radius: var(--radius); border: 1px solid hsl(215 60% 25%); padding: 0.625rem 1.25rem; align-items: center; gap: 1.25rem; overflow: hidden;">
                <span class="shrink-0 text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-sm" style="background: var(--brand-gold); color: var(--primary);">Advisory</span>
                <div id="tickerScroller" class="overflow-hidden flex-1" style="white-space:nowrap;">
                    <span id="announcementTicker" style="display:inline-block;font-size:0.8125rem;font-weight:500;padding-right:50px;"><?php echo $welcome; ?> &nbsp;&bull;&bull;&bull;&nbsp; <?php echo $welcome; ?></span>
                </div>
            </div>
        </section>

        <!-- Right Column -->
        <aside class="col-span-12 lg:col-span-4 flex flex-col gap-6" style="height:100%;">
            <!-- Queue Next Up -->
            <div style="flex-shrink:0; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius);">
                <div class="flex items-center justify-between px-4 py-3 border-b border-border" style="background: hsl(215 20% 94% / 0.6); border-radius: var(--radius) var(--radius) 0 0;">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full" style="background: var(--success); animation: pulse-dot 2s infinite;"></div>
                        <span class="text-[11px] font-bold uppercase tracking-widest">Queue · Next Up</span>
                    </div>
                    <span class="text-[10px] font-mono" style="color: var(--muted);"><span id="waitingCount">0</span> ahead</span>
                </div>
                <ul id="nextUpList" class="divide-y divide-border civic-scrollbar">
                    <!-- Injected by JS -->
                </ul>
            </div>

            <!-- Follow-Up Tickets -->
            <div id="followUpPanel" style="flex-shrink:0; background: var(--primary); color: var(--primary-foreground); border: 1px solid hsl(215 60% 25%); border-radius: var(--radius); display: none;">
                <div class="flex items-center justify-between px-4 py-3" style="background: rgba(0,0,0,0.2); border-bottom: 1px solid hsl(215 60% 25%); border-radius: var(--radius) var(--radius) 0 0;">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-flag text-xs" style="color: var(--brand-gold);"></i>
                        <span class="text-[10px] font-bold uppercase tracking-widest">Follow-up · Return customers</span>
                    </div>
                    <span class="text-[10px] font-mono" style="color: rgba(255,255,255,0.5);"><span id="followUpCount">0</span> pending</span>
                </div>
                <ul id="followUpList" class="divide-y divide-y civic-scrollbar" style="border-color: hsl(215 60% 25%);">
                    <!-- Injected by JS -->
                </ul>
            </div>

            <!-- Poster Panel (image + announcement) -->
            <div id="posterPanel" style="flex:1; min-height:0; display:none; flex-direction:column; background:#000; border-radius:var(--radius); overflow:hidden; border:1px solid var(--border);">
                <div class="flex items-center justify-between px-4 py-2" style="background:rgba(0,0,0,0.3); border-bottom:1px solid rgba(255,255,255,0.05); flex-shrink:0;">
                    <span id="posterLabel" class="text-[9px] font-mono uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">POSTER</span>
                    <span id="posterCountdownSide" class="text-[10px] font-bold font-mono uppercase tracking-widest" style="color:var(--brand-gold);display:none;">--</span>
                </div>
                <img id="posterDisplayImg" src="" alt="" style="width:100%; flex:1; min-height:0; object-fit:cover; display:none;">
                <div id="posterAnnContent" style="flex:1; min-height:0; display:none; flex-direction:column; align-items:center; justify-content:center; padding:2rem; text-align:center; overflow:hidden;"></div>
            </div>
        </aside>
    </main>

    <!-- StatusFooter -->
    <footer class="sticky bottom-0 left-0 w-full p-6 flex justify-between items-center" style="background: hsl(210 30% 97% / 0.8); backdrop-filter: blur(12px); pointer-events: none;">
        <div class="flex items-center gap-6">
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Terminal ID</span>
                <span class="text-[11px] font-mono" style="color: var(--foreground);">DISPLAY-MAIN</span>
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Last Sync</span>
                <span class="text-[11px] font-mono tabular-nums" id="footerTime" style="color: var(--foreground);">--:--:--</span>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1 rounded shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
            <div class="w-1.5 h-1.5 rounded-full" style="background: var(--primary);"></div>
            <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">v4.3.0-stable</span>
        </div>
    </footer>

    <audio id="notificationSound" preload="auto"><source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg"></audio>

    <script>
        function formatService(q) {
            var base = q.service_type || '';
            if (q.service_type === 'custom' && q.custom_description) base += ' (' + q.custom_description + ')';
            return base;
        }
        var lastCallInfo = {};
        var welcomeMsg = "<?php echo $welcome; ?>";
        var ytPlayer = null;
        var posterSlides = <?php echo $mergedPostersJson; ?>;
        var posterDuration = <?php echo $poster_duration; ?> * 1000;
        var posterIdx = 0;
        var posterTimer = null;
        var lastSettingsHash = '';
        var windowHistory = {};  // tracks previous window state for live alert generation
        var marqueeStep = null;  // interval handle for JS-powered marquee scroll

        function initVideoPlayer() {
            var isYoutube = '<?php echo $video_type; ?>' === 'youtube' || '<?php echo $video_type; ?>' === '';
            if (isYoutube) {
                if (typeof YT !== 'undefined' && YT.Player) {
                    ytPlayer = new YT.Player('mediaVideo', {
                        height: '100%', width: '100%',
                        videoId: '<?php echo $video_id; ?>',
                        playerVars: { autoplay: 1, mute: 0, controls: 0, rel: 0, modestbranding: 1, playsinline: 1 },
                        events: { onReady: function(e) { e.target.setVolume(<?php echo $video_volume; ?>); e.target.playVideo(); } }
                    });
                } else {
                    fallbackYoutubeIframe();
                }
            } else {
                createHtml5Video();
            }
        }

        function fallbackYoutubeIframe() {
            var el = document.getElementById('mediaVideo');
            if (!el) return;
            el.innerHTML = '<iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>?autoplay=1&controls=0&modestbranding=1&rel=0&loop=1&playlist=<?php echo $video_id; ?>" style="width:100%;height:100%;border:0;" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        }

        function createHtml5Video() {
            var el = document.getElementById('mediaVideo');
            if (!el) return;
            el.innerHTML = '<video id="directVideo" autoplay muted loop playsinline style="width:100%;height:100%;object-fit:cover;"><source src="<?php echo htmlspecialchars($video_url_raw, ENT_QUOTES); ?>" type="video/mp4"></video>';
            var v = document.getElementById('directVideo');
            if (v) { v.volume = <?php echo $video_volume; ?> / 100; }
        }

        function onYouTubeIframeAPIReady() { initVideoPlayer(); }

        setTimeout(function() {
            if (!ytPlayer && '<?php echo $video_type; ?>' === 'youtube') { initVideoPlayer(); }
        }, 5000);

        function updateFooterTime() {
            var el = document.getElementById('footerTime');
            if (!el) return;
            var d = new Date();
            var pad = function(n,w){w=w||2;return String(n).padStart(w,'0');};
            el.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        }
        setInterval(updateFooterTime, 200);
        updateFooterTime();

        function duckVolume() {
            var dv = document.getElementById('directVideo');
            if (dv) { dv.volume = <?php echo $duck_volume; ?> / 100; return; }
            if (ytPlayer && typeof ytPlayer.setVolume === 'function') { ytPlayer.setVolume(<?php echo $duck_volume; ?>); }
        }
        function restoreVolume() {
            var dv = document.getElementById('directVideo');
            if (dv) { dv.volume = <?php echo $video_volume; ?> / 100; return; }
            if (ytPlayer && typeof ytPlayer.setVolume === 'function') { ytPlayer.setVolume(<?php echo $video_volume; ?>); }
        }

        function playNotificationSound() {
            var a = document.getElementById('notificationSound');
            if (a) { a.currentTime = 0; a.play().catch(function() {}); }
        }

        function announceNumber(number, windowNum) {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            var spoken = number.split('').join(', ');
            var u = new SpeechSynthesisUtterance('Now serving, ticket number ' + spoken + ', at Window ' + windowNum);
            u.rate = 0.85; u.pitch = 1.1; u.volume = 1.0;
            duckVolume();
            u.onend = function() { restoreVolume(); };
            setTimeout(function() { window.speechSynthesis.speak(u); }, 800);
        }

        function showNextPoster() {
            if (posterSlides.length === 0) return;
            var panel = document.getElementById('posterPanel');
            var img = document.getElementById('posterDisplayImg');
            var ann = document.getElementById('posterAnnContent');
            var countdown = document.getElementById('posterCountdownSide');
            if (!panel) return;
            var slide = posterSlides[posterIdx];
            panel.style.display = 'flex';
            if (countdown) {
                countdown.textContent = (posterIdx + 1) + ' / ' + posterSlides.length;
                countdown.style.display = 'block';
            }
            if (slide.type === 'image') {
                img.src = slide.src;
                img.style.display = 'block';
                if (ann) ann.style.display = 'none';
                document.getElementById('posterLabel').textContent = 'POSTER';
            } else {
                img.style.display = 'none';
                if (ann) {
                    ann.style.display = 'flex';
                    ann.style.background = slide.bg;
                    ann.style.color = slide.fg;
                    var ts = slide.text_size || 'md';
                    var titleSize = {sm:'1.25rem',md:'1.5rem',lg:'2rem'}[ts] || '1.5rem';
                    var bodySize = {sm:'0.875rem',md:'1rem',lg:'1.25rem'}[ts] || '1rem';
                    ann.innerHTML = (slide.title ? '<div style="font-size:' + titleSize + ';font-weight:800;letter-spacing:-0.02em;margin-bottom:0.5rem;line-height:1.2;">' + escapeHtml(slide.title) + '</div>' : '') +
                        (slide.body ? '<div style="font-size:' + bodySize + ';line-height:1.5;opacity:0.9;max-width:90%;">' + escapeHtml(slide.body) + '</div>' : '');
                }
                document.getElementById('posterLabel').textContent = 'ANNOUNCEMENT';
            }
            posterIdx = (posterIdx + 1) % posterSlides.length;
            if (posterTimer) clearTimeout(posterTimer);
            posterTimer = setTimeout(showNextPoster, posterDuration);
        }

        function escapeHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        function startPosterRotation() {
            if (posterSlides.length === 0) return;
            showNextPoster();
        }

        function makeNowServingCard(w, idx) {
            var active = Number(w.is_online) !== 0 && w.status_text !== 'Offline' && w.status_text !== 'On Break';
            var statusBadge = '<span id="window' + w.window_number + 'StatusBadge" class="live-badge text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-sm>' + windowStatusLabel(w) + '</span>';
            var statusLabel = '<span class="window-label font-mono text-lg font-bold uppercase tracking-widest px-4 py-1.5 rounded-lg" style="color: ' + (active ? '#b91c1c' : '#374151') + '; background: ' + (active ? 'rgba(185,28,28,0.08)' : 'rgba(0,0,0,0.04)') + ';">WINDOW ' + w.window_number + '</span>';
            var numColor = active ? '#b91c1c' : 'var(--foreground)';
            var bgStyle = active ? 'background: white; color: #111827; border: 1px solid #e5e7eb;' : 'background: var(--card); border: 1px solid var(--border);';
            var serviceColor = active ? '#6b7280' : 'var(--muted)';
            return '<div id="window' + w.window_number + 'Card" class="animate-entry now-serving-card relative overflow-hidden rounded-md flex flex-col justify-between p-6" style="' + bgStyle + 'min-height:260px;animation-delay:' + (idx * 100) + 'ms;">' +
                '<div class="flex items-center justify-between">' + statusLabel + statusBadge + '</div>' +
                '<div class="flex-1 flex flex-col items-center justify-center">' +
                    '<div id="window' + w.window_number + 'Serving" class="num font-extrabold tracking-tighter leading-none tabular-nums flip-in" style="color: ' + numColor + ';">---</div>' +
                    '<span class="now-serving-tag text-sm font-bold uppercase tracking-[0.3em] mt-3" style="color: ' + serviceColor + ';">Now Serving</span>' +
                    '<div id="window' + w.window_number + 'Extra" class="text-xs mt-2" style="color: ' + serviceColor + ';"></div>' +
                '</div>' +
                '<div class="card-footer flex items-center justify-between text-sm font-mono uppercase tracking-widest" style="color: ' + serviceColor + ';">' +
                    '<span id="window' + w.window_number + 'Service">--</span>' +
                    '<span id="window' + w.window_number + 'Status">--</span>' +
                '</div>' +
            '</div>';
        }

        function updateDisplay() {
            fetch('api/get_display_data.php').then(function(r){return r.json();}).then(function(data) {
                if (data.error) return;

                // Auto-reload on settings change (cross-device)
                if (data.settings_hash && lastSettingsHash && data.settings_hash !== lastSettingsHash) {
                    location.reload();
                    return;
                }
                if (data.settings_hash) lastSettingsHash = data.settings_hash;

                // Now Serving cards
                var wc = document.getElementById('windowsContainer');
                var windowsData = data.windows || [];
                if (wc && (wc.children.length === 0 || wc.children.length !== windowsData.length)) {
                    wc.innerHTML = windowsData.map(function(w, i) { return makeNowServingCard(w, i); }).join('');
                }
                for (var i = 0; i < windowsData.length; i++) updateWindow(windowsData[i], data);

                // Stats in sub-header
                if (document.getElementById('statWindows')) document.getElementById('statWindows').textContent = windowsData.length;
                var activeW = windowsData.filter(function(w){return w.is_online===1&&w.status_text!=='Offline'&&w.status_text!=='On Break';}).length;
                if (document.getElementById('statWindowsActive')) document.getElementById('statWindowsActive').textContent = activeW;
                if (document.getElementById('statTickets')) document.getElementById('statTickets').textContent = data.stats_today || 0;
                if (document.getElementById('statWait')) document.getElementById('statWait').textContent = data.avg_wait || '--m';

                // Live alert marquee — dynamic state messages + DB announcements
                var alertBar = document.getElementById('alertBar');
                var ticker = document.getElementById('announcementTicker');
                var scroller = document.getElementById('tickerScroller');
                if (alertBar && ticker && scroller) {
                    var dynMsgs = [];
                    var windows = data.windows || [];
                    var waitingCount = data.waiting_count || 0;
                    var offlineCount = 0;

                    // Per-window status messages
                    for (var wi = 0; wi < windows.length; wi++) {
                        var w = windows[wi];
                        if (Number(w.is_online) === 0 || w.status_text === 'Offline') {
                            offlineCount++;
                            dynMsgs.push('Window ' + w.window_number + ' is currently offline');
                        } else if (w.status_text === 'On Break') {
                            dynMsgs.push('Window ' + w.window_number + ' is on break, please wait patiently');
                        } else if (w.queue_number) {
                            dynMsgs.push('Window ' + w.window_number + ' is now serving Ticket ' + w.queue_number);
                        }
                    }

                    // All windows offline
                    if (windows.length > 0 && offlineCount === windows.length) {
                        dynMsgs = ['Currently all windows are offline. Please wait for assistance.'];
                    }

                    // High density
                    if (waitingCount >= 10) {
                        dynMsgs.push('Due to the high volume of inquiries, please wait patiently. ' + waitingCount + ' customers ahead.');
                    }

                    // Cutoff approaching (within 60 minutes)
                    if (data.settings && data.settings.cutoff_time) {
                        var parts = data.settings.cutoff_time.split(':');
                        var now = new Date();
                        var cutoffDate = new Date();
                        cutoffDate.setHours(parseInt(parts[0], 10), parseInt(parts[1] || 0, 10), parseInt(parts[2] || 0, 10));
                        var diffMin = (cutoffDate - now) / 60000;
                        if (diffMin > 0 && diffMin <= 60) {
                            dynMsgs.push('Last ticket issuance ends at ' + data.settings.cutoff_time_formatted + '. Please queue now.');
                        }
                    }

                    // Merge with DB announcements
                    var dbAnn = data.announcements || [];
                    var allMsgs = dynMsgs.concat(dbAnn.map(function(a){return a.message;}));

                    if (allMsgs.length === 0 && welcomeMsg) {
                        allMsgs.push(welcomeMsg);
                    }

                    if (allMsgs.length > 0) {
                        alertBar.style.display = 'flex';
                        var text = allMsgs.join(' &nbsp;&bull;&bull;&bull;&nbsp; ');
                        if (ticker.getAttribute('data-text') !== text) {
                            ticker.setAttribute('data-text', text);
                            ticker.innerHTML = text + ' &nbsp;&bull;&bull;&bull;&nbsp; ' + text;
                            scroller.scrollLeft = 0;
                        }
                    } else {
                        alertBar.style.display = 'none';
                    }
                }

                // Next Up list
                var nextUpList = document.getElementById('nextUpList');
                var waitingQueue = data.waiting_queue || [];
                if (nextUpList) {
                    if (waitingQueue.length > 0) {
                        var nextHtml = '';
                        for (var i = 0; i < Math.min(waitingQueue.length, 10); i++) {
                            var q = waitingQueue[i];
                            var bg = i === 0 ? 'background: hsl(42 70% 52% / 0.1);' : '';
                            var extraParts = [];
                            if (q.company_name) extraParts.push(q.company_name);
                            if (q.purpose) extraParts.push(q.purpose.charAt(0).toUpperCase() + q.purpose.slice(1));
                            var extraStr = extraParts.length > 0 ? ' &middot; ' + extraParts.join(' &middot; ') : '';
                            nextHtml += '<li class="flex items-center justify-between px-4 py-3" style="' + bg + '">' +
                                '<div class="flex items-center gap-4">' +
                                    '<span class="text-[10px] font-mono w-6 tabular-nums" style="color: var(--muted);">' + String(i + 1).padStart(2,'0') + '</span>' +
                                    '<div class="flex flex-col">' +
                                        '<span class="font-mono text-sm font-bold tracking-tight">' + q.queue_number + '</span>' +
                                        '<span class="text-[10px] uppercase tracking-wider" style="color: var(--muted);">' + formatService(q) + extraStr + '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</li>';
                        }
                        nextUpList.innerHTML = nextHtml;
                    } else {
                        nextUpList.innerHTML = '<li class="p-8 text-center text-sm" style="color: var(--muted);">No customers waiting</li>';
                    }
                }
                if (document.getElementById('waitingCount')) document.getElementById('waitingCount').textContent = waitingQueue.length;

                // Follow-Up list
                var followUpList = document.getElementById('followUpList');
                var followUpPanel = document.getElementById('followUpPanel');
                var followUpData = data.follow_up_tickets || [];
                if (followUpList && followUpPanel) {
                    if (followUpData.length > 0) {
                        followUpPanel.style.display = 'block';
                        var fuHtml = '';
                        for (var i = 0; i < Math.min(followUpData.length, 10); i++) {
                            var f = followUpData[i];
                            var extraParts = [];
                            if (f.company_name) extraParts.push(f.company_name);
                            if (f.purpose) extraParts.push(f.purpose.charAt(0).toUpperCase() + f.purpose.slice(1));
                            var extraStr = extraParts.length > 0 ? ' &middot; ' + extraParts.join(' &middot; ') : '';
                            fuHtml += '<li class="flex items-center justify-between px-4 py-3" style="border-color: hsl(215 60% 25%);">' +
                                '<div class="flex items-center gap-4">' +
                                    '<span class="text-[10px] font-mono w-6 tabular-nums" style="color: rgba(255,255,255,0.5);">' + String(i + 1).padStart(2,'0') + '</span>' +
                                    '<div class="flex flex-col">' +
                                        '<span class="font-mono text-sm font-bold tracking-tight" style="color: var(--brand-gold);">' + f.queue_number + '</span>' +
                                        '<span class="text-[10px] uppercase tracking-wider" style="color: rgba(255,255,255,0.5);">' + formatService(f) + extraStr + '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</li>';
                        }
                        followUpList.innerHTML = fuHtml;
                        if (document.getElementById('followUpCount')) document.getElementById('followUpCount').textContent = followUpData.length;
                    } else {
                        followUpPanel.style.display = 'none';
                    }
                }
            }).catch(function(e) { console.error('Display Error:', e); });
        }

        function windowStatusLabel(w) {
            var active = Number(w.is_online) !== 0 && w.status_text !== 'Offline' && w.status_text !== 'On Break';
            var st = (w.status_text || (Number(w.is_online) ? 'Online' : 'Offline'));
            if (active) return '<span style="background: var(--brand-gold); color: #b91c1c;">Live</span>';
            if (st === 'On Break') return '<span style="background: #f59e0b; color: white;">On Break</span>';
            if (st === 'Offline') return '<span style="background: #9ca3af; color: white;">Offline</span>';
            return '<span style="background: #9ca3af; color: white;">Offline</span>';
        }

        function updateWindow(w, fullData) {
            var isOffline = !(w && Number(w.is_online));
            var statusText = (w && w.status_text) ? w.status_text : (isOffline ? 'Offline' : 'Online');
            var badgeEl = document.getElementById('window' + w.window_number + 'StatusBadge');
            if (badgeEl) badgeEl.innerHTML = windowStatusLabel(w);
            var card = document.getElementById('window' + w.window_number + 'Card');
            var serving = document.getElementById('window' + w.window_number + 'Serving');
            var service = document.getElementById('window' + w.window_number + 'Service');
            var status = document.getElementById('window' + w.window_number + 'Status');
            var extra = document.getElementById('window' + w.window_number + 'Extra');
            if (!card || !serving) return;

            if (isOffline || statusText === 'Offline') {
                serving.textContent = '---';
                if (service) service.textContent = 'Offline';
                if (status) status.textContent = '---';
                if (extra) extra.textContent = '';
                card.style.opacity = '0.5';
                return;
            } else if (statusText === 'On Break') {
                serving.textContent = '---';
                if (service) service.textContent = 'On Break';
                if (status) status.textContent = '---';
                if (extra) extra.textContent = '';
                card.style.opacity = '0.7';
                return;
            }
            card.style.opacity = '1';

            var newServing = (w && w.queue_number) ? w.queue_number : '---';
            var calledAt = (w && w.called_at) ? w.called_at : '';
            if (!lastCallInfo[w.window_number]) lastCallInfo[w.window_number] = { queue_number: '', called_at: '' };

            if (newServing !== '---' && (newServing !== lastCallInfo[w.window_number].queue_number || calledAt !== lastCallInfo[w.window_number].called_at)) {
                serving.textContent = newServing;
                serving.className = 'num font-extrabold tracking-tighter leading-none tabular-nums flip-in';
                setTimeout(function() { serving.className = 'num font-extrabold tracking-tighter leading-none tabular-nums'; }, 600);
                lastCallInfo[w.window_number] = { queue_number: newServing, called_at: calledAt };
                playNotificationSound();
                announceNumber(newServing, w.window_number);
            } else if (newServing === '---') {
                serving.textContent = '---';
                lastCallInfo[w.window_number] = { queue_number: '---', called_at: '' };
            }

            var svc = '--';
            if (w && w.service_type) {
                svc = w.service_type;
                if (w.service_type === 'custom' && w.custom_description) svc += ' (' + w.custom_description + ')';
            } else if (w && w.active_services) {
                svc = w.active_services.split(',').join(', ');
            }
            if (service) service.textContent = svc;
            if (status) status.textContent = statusText === 'Online' ? 'Desk Active' : statusText;
            if (extra) {
                var parts = [];
                if (w.company_name) parts.push('Company: ' + w.company_name);
                if (w.purpose) parts.push('Purpose: ' + w.purpose.charAt(0).toUpperCase() + w.purpose.slice(1));
                extra.textContent = parts.join('  \u00b7  ');
            }
        }

        // Media Panel — YouTube always plays, poster overlays on timer
        var mediaLabel = document.getElementById('mediaLabel');
        document.getElementById('mediaSponsor').textContent = '<?php echo addslashes($settings['video_sponsor'] ?: 'Public Affairs Office'); ?>';
        document.getElementById('mediaTitle').textContent = '<?php echo addslashes($settings['video_title'] ?: 'Citizen Services Overview'); ?>';
        document.getElementById('mediaCta').textContent = '<?php echo addslashes($settings['video_cta'] ?? ''); ?>';

        startPosterRotation();

        if ('<?php echo $video_type; ?>' !== 'youtube') { initVideoPlayer(); }

        window.addEventListener('storage', function(e) {
            if (e.key === 'cq_settings_updated') location.reload();
        });

        setInterval(updateDisplay, 3000);
        updateDisplay();

        // JS-powered marquee scroll (replaces CSS animation for reliability)
        function scrollMarquee() {
            var s = document.getElementById('tickerScroller');
            if (!s || s.scrollWidth === 0) return;
            if (s.scrollLeft >= Math.ceil(s.scrollWidth / 2)) {
                s.scrollLeft = 0;
            } else {
                s.scrollLeft += 1;
            }
        }
        marqueeStep = setInterval(scrollMarquee, 40);
    </script>
    <script src="https://www.youtube.com/iframe_api"></script>
</body>
</html>
