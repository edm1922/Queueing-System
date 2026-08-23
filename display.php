<?php 
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'Service Center', 'branch_name' => 'Main Office', 'address' => '', 'welcome_message' => 'Welcome', 'cutoff_time' => '17:00:00', 'company_logo' => '', 'video_url' => 'aqz-KE-bpKQ', 'video_type' => 'youtube', 'video_title' => 'Citizen Services Overview', 'video_sponsor' => 'Public Affairs Office', 'video_cta' => '', 'video_volume' => 50, 'poster_enabled' => 0, 'poster_interval' => 10, 'poster_duration' => 10, 'poster_images' => '[]', 'poster_announcements' => '[]'];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #ffffff;
            color: #111827;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .display-header {
            height: 96px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 64px;
            border-bottom: 2px solid #e5e7eb;
            background: #ffffff;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-logo {
            width: 72px;
            height: 72px;
            border-radius: 10px;
            background: #b91c1c;
            display: grid;
            place-items: center;
            color: #ffffff;
            font-weight: 900;
            font-size: 24px;
            letter-spacing: -0.05em;
            flex-shrink: 0;
            overflow: hidden;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .org-name {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #111827;
            line-height: 1.2;
        }

        .org-sub {
            font-size: 13px;
            font-family: 'IBM Plex Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #6b7280;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            border-radius: 9999px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #374151;
        }

        .status-dot-live {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #9ca3af;
            animation: pulse-dot 2s infinite;
        }

        .header-clock {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 38px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #111827;
            tabular-nums: true;
        }

        .display-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #374151;
            cursor: pointer;
            transition: background 0.15s;
        }

        .display-btn:hover {
            background: #f3f4f6;
        }

        .display-main {
            flex: 1;
            display: grid;
            grid-template-rows: auto 1fr;
            min-height: 0;
        }

        .serving-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px 48px 20px 48px;
            min-height: 0;
            background: #f3f4f6;
        }

        #windowsContainer {
            display: grid;
            gap: 28px;
            width: 100%;
            max-width: 1600px;
            margin: 0 auto;
            align-items: stretch;
        }

        #windowsContainer:has(> :nth-child(1):last-child) {
            grid-template-columns: 1fr;
        }
        #windowsContainer:has(> :nth-child(2):last-child) {
            grid-template-columns: 1fr 1fr;
        }
        #windowsContainer:has(> :nth-child(3):last-child) {
            grid-template-columns: 1fr 1fr 1fr;
        }
        #windowsContainer:has(> :nth-child(4):last-child) {
            grid-template-columns: 1fr 1fr;
        }
        #windowsContainer:has(> :nth-child(n+5):last-child) {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        /* Scaled-down card sizes for 4+ windows */
        #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card,
        #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card {
            padding: 20px 20px 24px 20px !important;
        }
        #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .num,
        #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .num {
            font-size: 140px !important;
        }
        #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .window-label,
        #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .window-label {
            font-size: 28px !important;
            padding: 6px 20px !important;
        }
        #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .now-serving-tag,
        #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .now-serving-tag {
            font-size: 16px !important;
        }
        #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .desc-label,
        #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .desc-label {
            font-size: 14px !important;
        }

        .now-serving-card {
            background: #ffffff !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 16px !important;
            min-height: unset !important;
            padding: 32px 32px 40px 32px !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 8px 30px rgba(0,0,0,0.07) !important;
            transition: border-color 0.3s ease;
        }

        .now-serving-card.card-active {
            border-top: 3px solid #b91c1c !important;
        }

        .now-serving-card.card-break {
            border-top: 3px solid #f59e0b !important;
        }

        .now-serving-card .window-label {
            font-size: 38px !important;
            font-weight: 900 !important;
            letter-spacing: 0.15em !important;
            color: #374151 !important;
            background: #f3f4f6 !important;
            padding: 8px 32px !important;
            border-radius: 8px !important;
        }

        .now-serving-card .num {
            font-size: 180px !important;
            line-height: 1 !important;
            font-weight: 900 !important;
            letter-spacing: -0.03em !important;
            color: #111827 !important;
            margin: 0 0 8px 0;
        }

        .now-serving-card .now-serving-tag {
            font-size: 20px !important;
            font-weight: 600 !important;
            letter-spacing: 0.3em !important;
            text-transform: uppercase !important;
            color: #6b7280 !important;
            margin-top: 0 !important;
            margin-bottom: 8px !important;
        }

        .now-serving-card .card-footer {
            display: none !important;
        }

        .now-serving-card .desc-label {
            font-size: 22px !important;
            font-weight: 500 !important;
        }

        .now-serving-card #window1Extra,
        .now-serving-card #window2Extra,
        .now-serving-card #window3Extra,
        .now-serving-card #window4Extra {
            display: none !important;
        }

        .now-serving-card .live-badge {
            display: inline-block !important;
            font-size: 20px !important;
            font-weight: 900 !important;
            letter-spacing: 0.1em !important;
            border-radius: 8px !important;
            padding: 8px 22px !important;
            text-transform: uppercase !important;
        }

        .now-serving-card > div:first-child {
            order: 2 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-top: 2px !important;
        }

        .now-serving-card > div:nth-child(2) {
            order: 1 !important;
            flex: none !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .bottom-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 0;
            border-top: 2px solid #e5e7eb;
            background: #fafafa;
        }

        .bottom-panel {
            display: flex;
            flex-direction: column;
            padding: 28px 44px;
            overflow: hidden;
        }

        .bottom-panel + .bottom-panel {
            border-left: 1px solid #e5e7eb;
        }

        .panel-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        .panel-header .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #9ca3af;
        }

        .panel-header .label {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #6b7280;
        }

        .panel-header .count {
            font-size: 20px;
            font-family: 'IBM Plex Mono', monospace;
            color: #9ca3af;
            margin-left: auto;
        }

        #nextUpList {
            flex: 1;
            overflow-y: auto;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        #nextUpList li {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 16px 24px;
            border-radius: 10px;
            font-size: 30px;
            flex-shrink: 0;
        }

        #nextUpList li .pos {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 24px;
            font-weight: 600;
            color: #9ca3af;
            width: 56px;
            text-align: right;
            flex-shrink: 0;
        }

        #nextUpList li .ticket-num {
            font-family: 'IBM Plex Mono', monospace;
            font-size: 34px;
            font-weight: 700;
            color: #111827;
        }

        #nextUpList li .ticket-svc {
            font-size: 20px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 10px;
        }

        #nextUpList li.highlighted {
            padding-left: 18px;
            font-weight: 700;
        }

        #nextUpList li.highlighted .ticket-svc {
            font-weight: 600;
        }

        #nextUpList li.highlighted-w1 { background: #fef2f2; border-left: 3px solid #b91c1c; }
        #nextUpList li.highlighted-w1 .ticket-num,
        #nextUpList li.highlighted-w1 .pos { color: #b91c1c; font-weight: 900; }
        #nextUpList li.highlighted-w1 .ticket-num { font-weight: 900; }

        #nextUpList li.highlighted-w2 { background: #fffbeb; border-left: 3px solid #d97706; }
        #nextUpList li.highlighted-w2 .ticket-num,
        #nextUpList li.highlighted-w2 .pos { color: #d97706; font-weight: 900; }

        #nextUpList li.highlighted-w3 { background: #eff6ff; border-left: 3px solid #2563eb; }
        #nextUpList li.highlighted-w3 .ticket-num,
        #nextUpList li.highlighted-w3 .pos { color: #2563eb; font-weight: 900; }

        #nextUpList li.highlighted-w4 { background: #ecfdf5; border-left: 3px solid #059669; }
        #nextUpList li.highlighted-w4 .ticket-num,
        #nextUpList li.highlighted-w4 .pos { color: #059669; font-weight: 900; }

        #nextUpList li.highlighted-w5 { background: #f5f3ff; border-left: 3px solid #7c3aed; }
        #nextUpList li.highlighted-w5 .ticket-num,
        #nextUpList li.highlighted-w5 .pos { color: #7c3aed; font-weight: 900; }

        #nextUpList li.highlighted-w6 { background: #fdf2f8; border-left: 3px solid #db2777; }
        #nextUpList li.highlighted-w6 .ticket-num,
        #nextUpList li.highlighted-w6 .pos { color: #db2777; font-weight: 900; }

        #nextUpList li.dimmed {
            opacity: 0.55;
            font-size: 22px;
            padding: 12px 24px;
        }

        #nextUpList li.dimmed .ticket-num {
            font-size: 26px;
        }

        #nextUpList li.dimmed .ticket-svc {
            font-size: 16px;
        }

        #nextUpList li.dimmed .pos {
            width: 0;
            overflow: hidden;
        }

        .announce-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .announce-carousel {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .announce-slides {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .announce-slide {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 4px 0;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.4s ease, transform 0.4s ease;
            pointer-events: none;
        }

        .announce-slide.active {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .announce-slide .type-badge {
            flex-shrink: 0;
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 8px 18px;
            border-radius: 8px;
            color: #ffffff;
            line-height: 1;
        }

        .announce-slide .type-badge.type-info { background: #6b7280; }
        .announce-slide .type-badge.type-warning { background: #d97706; }
        .announce-slide .type-badge.type-urgent { background: #dc2626; }

        .announce-slide .slide-text {
            font-size: 34px;
            font-weight: 700;
            color: #374151;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .announce-dots {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 0 2px 0;
            flex-shrink: 0;
        }

        .announce-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #d1d5db;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        .announce-dot.active {
            background: #b91c1c;
            transform: scale(1.3);
        }

        .announce-empty {
            font-size: 22px;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes flipIn {
            0% { transform: translateY(-20px) scale(0.9); opacity: 0; }
            50% { transform: translateY(10px) scale(1.05); }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }

        .flip-in { animation: flipIn 0.6s cubic-bezier(0.16, 1, 0.3, 1); }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        @media (prefers-reduced-motion: reduce) {
            .flip-in, .animate-entry { animation: none !important; }
        }

        @media (max-width: 1280px) {
            .display-header { padding: 0 32px; height: 80px; }
            .serving-section { padding: 24px 32px 18px 32px; }
            #windowsContainer { gap: 20px; }
            .now-serving-card .num { font-size: 160px !important; }
            .now-serving-card { padding: 24px 24px 28px 24px !important; }
            #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .num,
            #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .num { font-size: 120px !important; }
            #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .window-label,
            #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .window-label { font-size: 22px !important; }
            #windowsContainer:has(> :nth-child(4):last-child) .now-serving-card .desc-label,
            #windowsContainer:has(> :nth-child(n+5):last-child) .now-serving-card .desc-label { font-size: 13px !important; }
            .header-clock { font-size: 28px; }
            .org-name { font-size: 24px; }
            .bottom-panel { padding: 20px 28px; }
            #nextUpList li { font-size: 22px; padding: 12px 18px; }
            #nextUpList li .ticket-num { font-size: 26px; }
            #nextUpList li .ticket-svc { font-size: 16px; }
            #nextUpList li .pos { font-size: 18px; width: 44px; }
            #nextUpList li.dimmed { font-size: 16px; padding: 10px 18px; }
            #nextUpList li.dimmed .ticket-num { font-size: 20px; }
            .now-serving-card .window-label { font-size: 20px !important; }
            .now-serving-card .now-serving-tag { font-size: 15px !important; }
        }

        @media (max-width: 768px) {
            .status-badge { display: none; }
            .display-header { padding: 0 16px; height: 64px; }
            .header-clock { font-size: 20px; }
            .org-name { font-size: 18px; }
            .header-logo { width: 48px; height: 48px; font-size: 16px; }
            .serving-section { padding: 16px 16px; }
            .now-serving-card .num { font-size: 100px !important; }
            .now-serving-card { padding: 16px !important; }
            .now-serving-card .window-label { font-size: 16px !important; padding: 4px 16px !important; }
            .now-serving-card .now-serving-tag { font-size: 12px !important; }
            .now-serving-card .live-badge { font-size: 11px !important; padding: 3px 10px !important; }
            .header-clock { font-size: 16px; }
            .display-btn { font-size: 10px; padding: 6px 12px; }
            .bottom-section { grid-template-columns: 1fr; }
            .bottom-panel { padding: 14px 20px; }
            #nextUpList li { padding: 8px 14px; font-size: 16px; gap: 12px; }
            #nextUpList li .ticket-num { font-size: 18px; }
            #nextUpList li .ticket-svc { font-size: 12px; }
            #nextUpList li .pos { font-size: 13px; width: 32px; }
            #nextUpList li.dimmed { font-size: 13px; padding: 6px 14px; }
            #nextUpList li.dimmed .ticket-num { font-size: 15px; }
            #nextUpList li.dimmed .pos { display: none; }
            .panel-header .label { font-size: 10px; }
            .announce-slide .slide-text { font-size: 22px; }
            .announce-slide .type-badge { font-size: 14px; padding: 5px 12px; }
            .bottom-panel + .bottom-panel { border-left: none; border-top: 1px solid #e5e7eb; }
        }

        .hidden-js {
            display: none !important;
        }
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
<body>
    <header class="display-header">
        <div class="header-left">
            <div class="header-logo">
                <img src="291326981_417957617015097_5338941729333725938_n.png" alt="Centro Logo">
            </div>
            <span class="org-name"><?php echo $company_name; ?></span>
        </div>
        <div class="header-right">
            <div class="status-badge">
                <span class="status-dot-live"></span>
                <span>All Systems Online</span>
            </div>
            <span id="liveClock" class="header-clock">--:--:-- --</span>
            <button onclick="event.stopPropagation(); toggleDisplayMode();" class="display-btn">
                <span id="displayModeIcon">&#9632;</span>
                <span id="displayModeLabel">Display</span>
            </button>
        </div>
    </header>

    <div class="display-main">
        <div class="serving-section">
            <div id="windowsContainer">
            </div>
        </div>

        <div class="bottom-section">
            <div class="bottom-panel">
                <div class="panel-header">
                    <span class="dot"></span>
                    <span class="label">Next in Line</span>
                    <span class="count"><span id="waitingCount">0</span> ahead</span>
                </div>
                <ul id="nextUpList"></ul>
            </div>
            <div class="bottom-panel">
                <div class="panel-header">
                    <span class="dot"></span>
                    <span class="label">Announcements</span>
                </div>
                <div class="announce-area">
                    <div id="announceCarousel" class="announce-carousel" style="display:none;">
                        <div id="announceSlides" class="announce-slides"></div>
                        <div id="announceDots" class="announce-dots"></div>
                    </div>
                    <div id="announceEmpty" class="announce-empty">No announcements</div>
                </div>
            </div>
        </div>
    </div>

    <div class="hidden-js">
        <div id="mediaVideo"></div>
        <div id="mediaPanel"><span id="mediaLabel"></span><span id="mediaSponsor"></span><span id="mediaTitle"></span><span id="mediaCta"></span></div>
        <div id="posterPanel"><span id="posterLabel"></span><span id="posterCountdownSide"></span><img id="posterDisplayImg" src=""><div id="posterAnnContent"></div></div>
        <span id="footerTime"></span>
        <span id="statWindows"></span><span id="statWindowsActive"></span><span id="statTickets"></span><span id="statWait"></span>
    </div>

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
        var lastRefreshToken = 0;
        var windowHistory = {};
        var announceSlidesData = [];
        var announceIdx = 0;
        var announceTimer = null;
        var lastSlidesJson = '';

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

        function announceNumber(number, windowNum, windowName) {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            var spoken = number.split('').join(', ');
            var label = windowName || ('Window ' + windowNum);
            var u = new SpeechSynthesisUtterance('Now serving, ticket number ' + spoken + ', at ' + label);
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

        function windowLabel(w) { return w.display_name || w.name || ('WINDOW ' + w.window_number); }

        function makeNowServingCard(w, idx) {
            var active = Number(w.is_online) !== 0 && w.status_text !== 'Offline' && w.status_text !== 'On Break';
            var onBreak = Number(w.is_online) !== 0 && w.status_text === 'On Break';
            var activeClass = active ? 'card-active' : '';
            var breakClass = onBreak ? 'card-break' : '';
            var wLabel = windowLabel(w);
            var statusBadge = '<span id="window' + w.window_number + 'StatusBadge" class="live-badge">' + windowStatusLabel(w) + '</span>';
            var statusLabel = '<div style="text-align:center;">' +
                '<span class="window-label font-mono text-lg font-bold uppercase tracking-widest px-4 py-1.5 rounded-lg" style="color: ' + (active ? '#374151' : '#6b7280') + '; background: ' + (active ? '#f3f4f6' : 'rgba(0,0,0,0.03)') + ';">' + wLabel + '</span>' +
                (w.description ? '<div class="desc-label font-medium tracking-wide mt-1" style="color: ' + (active ? '#6b7280' : '#9ca3af') + ';">' + w.description + '</div>' : '') +
                '</div>';
            var numColor = active ? '#111827' : '#9ca3af';
            var bgStyle = active ? 'background: white; color: #111827; border: 1px solid #e5e7eb;' : 'background: var(--card); border: 1px solid var(--border);';
            var serviceColor = active ? '#6b7280' : '#9ca3af';
            return '<div id="window' + w.window_number + 'Card" class="animate-entry now-serving-card ' + activeClass + ' ' + breakClass + '" style="' + bgStyle + 'min-height:280px;animation-delay:' + (idx * 100) + 'ms;">' +
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

        function buildAnnounceSlides(data) {
            var slides = [];
            var windows = data.windows || [];
            var waitingCount = data.waiting_count || 0;
            var offlineCount = 0;
            for (var wi = 0; wi < windows.length; wi++) {
                var w = windows[wi];
                if (Number(w.is_online) === 0 || w.status_text === 'Offline') {
                    offlineCount++;
                    slides.push({message: windowLabel(w) + ' is currently offline', type: 'warning'});
                } else if (w.status_text === 'On Break') {
                    slides.push({message: windowLabel(w) + ' is on break, please wait patiently', type: 'info'});
                } else if (w.queue_number) {
                    slides.push({message: windowLabel(w) + ' is now serving Ticket ' + w.queue_number, type: 'info'});
                }
            }
            if (windows.length > 0 && offlineCount === windows.length) {
                slides = [{message: 'Currently all windows are offline. Please wait for assistance.', type: 'urgent'}];
            }
            if (waitingCount >= 10) {
                slides.push({message: 'Due to the high volume of inquiries, please wait patiently. ' + waitingCount + ' customers ahead.', type: 'warning'});
            }
            if (data.settings && data.settings.cutoff_time) {
                var parts = data.settings.cutoff_time.split(':');
                var now = new Date();
                var cutoffDate = new Date();
                cutoffDate.setHours(parseInt(parts[0], 10), parseInt(parts[1] || 0, 10), parseInt(parts[2] || 0, 10));
                var diffMin = (cutoffDate - now) / 60000;
                if (diffMin > 0 && diffMin <= 60) {
                    slides.push({message: 'Last ticket issuance ends at ' + data.settings.cutoff_time_formatted + '. Please queue now.', type: 'info'});
                }
            }
            var svcToWindow = {};
            for (var wi = 0; wi < windows.length; wi++) {
                var w = windows[wi];
                if (w.active_services) {
                    var svcs = w.active_services.split(',');
                    for (var si = 0; si < svcs.length; si++) {
                        svcToWindow[svcs[si].trim()] = { wn: w.window_number, cid: w.id, wl: windowLabel(w) };
                    }
                }
                svcToWindow['cid_' + w.id] = { wn: w.window_number, cid: w.id, wl: windowLabel(w) };
            }
            var waitingQueue = data.waiting_queue || [];
            var nextPerWindow = {};
            for (var qi = 0; qi < waitingQueue.length; qi++) {
                var q = waitingQueue[qi];
                var win = null;
                if (q.counter_id && svcToWindow['cid_' + q.counter_id]) {
                    win = svcToWindow['cid_' + q.counter_id];
                } else if (svcToWindow[q.service_type]) {
                    win = svcToWindow[q.service_type];
                }
                if (win) {
                    var key = 'W' + win.wn;
                    if (!nextPerWindow[key]) {
                        nextPerWindow[key] = { wn: win.wn, qn: q.queue_number, wl: win.wl };
                    }
                }
            }
            var sortedNext = Object.keys(nextPerWindow).sort();
            for (var ni = 0; ni < sortedNext.length; ni++) {
                var n = nextPerWindow[sortedNext[ni]];
                slides.push({message: (n.wl || 'Window ' + n.wn) + ': Next is ' + n.qn, type: 'info'});
            }

            var dbAnn = data.announcements || [];
            for (var ai = 0; ai < dbAnn.length; ai++) {
                var ann = dbAnn[ai];
                var msg = ann.message;
                if (ann.counter_id && ann.window_name) {
                    msg = ann.window_name + ': ' + msg;
                }
                slides.push({message: msg, type: ann.type || 'info'});
            }
            if (slides.length === 0 && welcomeMsg) {
                slides.push({message: welcomeMsg, type: 'info'});
            }
            return slides;
        }

        function rebuildAnnounceCarousel(slides) {
            var carousel = document.getElementById('announceCarousel');
            var empty = document.getElementById('announceEmpty');
            if (!carousel) return;
            var newJson = JSON.stringify(slides);
            if (newJson === lastSlidesJson && announceSlidesData.length > 0) {
                return;
            }
            lastSlidesJson = newJson;
            if (announceTimer) { clearInterval(announceTimer); announceTimer = null; }
            if (slides.length === 0) {
                carousel.style.display = 'none';
                if (empty) empty.style.display = 'flex';
                announceSlidesData = [];
                return;
            }
            carousel.style.display = 'flex';
            if (empty) empty.style.display = 'none';
            announceSlidesData = slides;
            announceIdx = 0;
            var slidesEl = document.getElementById('announceSlides');
            var dotsEl = document.getElementById('announceDots');
            if (!slidesEl || !dotsEl) return;
            var slidesHtml = '';
            var dotsHtml = '';
            for (var si = 0; si < slides.length; si++) {
                var s = slides[si];
                var isActive = si === 0 ? ' active' : '';
                var typeClass = 'type-' + (s.type || 'info');
                var badgeText = (s.type || 'info').charAt(0).toUpperCase() + (s.type || 'info').slice(1);
                slidesHtml += '<div class="announce-slide' + isActive + '">' +
                    '<span class="type-badge ' + typeClass + '">' + badgeText + '</span>' +
                    '<span class="slide-text">' + htmlEncode(s.message) + '</span>' +
                '</div>';
                dotsHtml += '<div class="announce-dot' + isActive + '" data-index="' + si + '" onclick="showAnnounceSlide(' + si + ')"></div>';
            }
            slidesEl.innerHTML = slidesHtml;
            dotsEl.innerHTML = dotsHtml;
            if (slides.length > 1) {
                announceTimer = setInterval(advanceAnnounceSlide, 5000);
            }
        }

        function advanceAnnounceSlide() {
            if (announceSlidesData.length < 2) return;
            showAnnounceSlide((announceIdx + 1) % announceSlidesData.length);
        }

        function showAnnounceSlide(idx) {
            var slidesEl = document.getElementById('announceSlides');
            var dotsEl = document.getElementById('announceDots');
            if (!slidesEl || !dotsEl) return;
            var prevIdx = announceIdx;
            announceIdx = idx;
            var slideEls = slidesEl.children;
            if (slideEls[prevIdx]) slideEls[prevIdx].classList.remove('active');
            if (slideEls[idx]) slideEls[idx].classList.add('active');
            var dotEls = dotsEl.children;
            if (dotEls[prevIdx]) dotEls[prevIdx].classList.remove('active');
            if (dotEls[idx]) dotEls[idx].classList.add('active');
        }

        function htmlEncode(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function updateDisplay() {
            fetch('api/get_display_data.php').then(function(r){return r.json();}).then(function(data) {
                if (data.error) return;

                if (data.settings_hash && lastSettingsHash && data.settings_hash !== lastSettingsHash) {
                    location.reload();
                    return;
                }
                if (data.settings_hash) lastSettingsHash = data.settings_hash;

                if (data.force_refresh_token && lastRefreshToken && data.force_refresh_token !== lastRefreshToken) {
                    location.reload();
                    return;
                }
                if (data.force_refresh_token) lastRefreshToken = data.force_refresh_token;

                var wc = document.getElementById('windowsContainer');
                var windowsData = data.windows || [];
                if (wc && (wc.children.length === 0 || wc.children.length !== windowsData.length)) {
                    wc.innerHTML = windowsData.map(function(w, i) { return makeNowServingCard(w, i); }).join('');
                }
                for (var i = 0; i < windowsData.length; i++) updateWindow(windowsData[i], data);

                if (document.getElementById('statWindows')) document.getElementById('statWindows').textContent = windowsData.length;
                var activeW = windowsData.filter(function(w){return w.is_online===1&&w.status_text!=='Offline'&&w.status_text!=='On Break';}).length;
                if (document.getElementById('statWindowsActive')) document.getElementById('statWindowsActive').textContent = activeW;
                if (document.getElementById('statTickets')) document.getElementById('statTickets').textContent = data.stats_today || 0;
                if (document.getElementById('statWait')) document.getElementById('statWait').textContent = data.avg_wait || '--m';

                var slides = buildAnnounceSlides(data);
                rebuildAnnounceCarousel(slides);

                var nextUpList = document.getElementById('nextUpList');
                var waitingQueue = data.waiting_queue || [];
                if (nextUpList) {
                    if (waitingQueue.length > 0) {
                        var maxShow = Math.min(waitingQueue.length, 10);
                        var svcToWindow = {};
                        for (var wi = 0; wi < windowsData.length; wi++) {
                            var w = windowsData[wi];
                            if (w.active_services) {
                                var svcs = w.active_services.split(',');
                                for (var si = 0; si < svcs.length; si++) {
                                    svcToWindow[svcs[si].trim()] = { wn: w.window_number, cid: w.id, wl: windowLabel(w) };
                                }
                            }
                            svcToWindow['cid_' + w.id] = { wn: w.window_number, cid: w.id, wl: windowLabel(w) };
                        }
                        var highlightedItems = [];
                        var dimmedItems = [];
                        var claimedWindows = {};
                        for (var i = 0; i < waitingQueue.length && (highlightedItems.length + dimmedItems.length) < 10; i++) {
                            var q = waitingQueue[i];
                            var win = null;
                            if (q.counter_id && svcToWindow['cid_' + q.counter_id]) {
                                win = svcToWindow['cid_' + q.counter_id];
                            } else if (svcToWindow[q.service_type]) {
                                win = svcToWindow[q.service_type];
                            }
                            var winKey = win ? 'W' + win.wn : null;
                            var isHighlighted = winKey && !claimedWindows[winKey];
                            if (isHighlighted) {
                                claimedWindows[winKey] = true;
                                highlightedItems.push({ q: q, win: win, winKey: winKey });
                            } else {
                                dimmedItems.push({ q: q });
                            }
                        }
                        var orderedItems = highlightedItems.concat(dimmedItems);
                        var nextHtml = '';
                        for (var oi = 0; oi < orderedItems.length; oi++) {
                            var item = orderedItems[oi];
                            var q = item.q;
                            var isH = item.winKey ? true : false;
                            var liClass = isH ? 'highlighted highlighted-w' + item.win.wn : 'dimmed';
                            var posLabel = isH ? item.winKey : '';
                            var extraParts = [];
                            if (q.company_name) extraParts.push(q.company_name);
                            if (q.purpose) extraParts.push(q.purpose.charAt(0).toUpperCase() + q.purpose.slice(1));
                            var extraStr = extraParts.length > 0 ? ' &middot; ' + extraParts.join(' &middot; ') : '';
                            nextHtml += '<li class="' + liClass + '">' +
                                '<span class="pos">' + posLabel + '</span>' +
                                '<span class="ticket-num">' + q.queue_number + '</span>' +
                                '<span class="ticket-svc">' + formatService(q) + extraStr + '</span>' +
                            '</li>';
                        }
                        nextUpList.innerHTML = nextHtml;
                    } else {
                        nextUpList.innerHTML = '<li style="justify-content:center;color:#9ca3af;font-size:14px;padding:20px;">No customers waiting</li>';
                    }
                }
                if (document.getElementById('waitingCount')) document.getElementById('waitingCount').textContent = waitingQueue.length;
            }).catch(function(e) { console.error('Display Error:', e); });
        }

        function windowStatusLabel(w) {
            var active = Number(w.is_online) !== 0 && w.status_text !== 'Offline' && w.status_text !== 'On Break';
            var st = (w.status_text || (Number(w.is_online) ? 'Online' : 'Offline'));
            if (active) return '<span style="background: #b91c1c; color: white;">Live</span>';
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

            card.classList.remove('card-active');
            card.classList.remove('card-break');

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
                card.style.opacity = '0.8';
                card.classList.add('card-break');
                return;
            }
            card.style.opacity = '1';
            card.classList.add('card-active');

            var newServing = (w && w.queue_number) ? w.queue_number : '---';
            var calledAt = (w && w.called_at) ? w.called_at : '';
            if (!lastCallInfo[w.window_number]) lastCallInfo[w.window_number] = { queue_number: '', called_at: '' };

            if (newServing !== '---' && (newServing !== lastCallInfo[w.window_number].queue_number || calledAt !== lastCallInfo[w.window_number].called_at)) {
                serving.textContent = newServing;
                serving.className = 'num font-extrabold tracking-tighter leading-none tabular-nums flip-in';
                setTimeout(function() { serving.className = 'num font-extrabold tracking-tighter leading-none tabular-nums'; }, 600);
                lastCallInfo[w.window_number] = { queue_number: newServing, called_at: calledAt };
                playNotificationSound();
                announceNumber(newServing, w.window_number, windowLabel(w));
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

        startPosterRotation();

        window.addEventListener('storage', function(e) {
            if (e.key === 'cq_settings_updated') location.reload();
        });

        setInterval(updateDisplay, 3000);
        updateDisplay();

    </script>
    <script src="https://www.youtube.com/iframe_api"></script>
</body>
</html>
