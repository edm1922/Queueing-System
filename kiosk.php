<?php include 'config.php';
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($s['branch_name'] ?? '');
$company_logo = htmlspecialchars($s['company_logo'] ?? '');
$services = [];
try {
    $stmt = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name ASC");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$isDisplay = isset($_GET['mode']) && $_GET['mode'] === 'display';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Self-Service Kiosk — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        :root {
            <?php if ($isDisplay): ?>
            --kiosk-scale: 1.15;
            --card-min-h: 220px;
            --btn-min-h: 64px;
            <?php else: ?>
            --kiosk-scale: 1;
            --card-min-h: auto;
            --btn-min-h: auto;
            <?php endif; ?>
        }

        .service-btn {
            cursor: pointer;
            transition: all 0.3s var(--ease-out-expo);
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
        .service-btn:hover { transform: translateY(-2px); }
        .service-btn:active { transform: scale(0.97); }
        <?php if ($isDisplay): ?>
        .service-btn {
            min-height: var(--card-min-h);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 2.5rem !important;
            border-radius: 1.25rem !important;
        }
        .service-btn h3 { font-size: 1.75rem !important; }
        .service-btn .desc-text { font-size: 1rem !important; }
        <?php endif; ?>

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-animation { animation: successPop 500ms var(--ease-out-expo); }

        @keyframes kioskFadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .kiosk-enter {
            animation: kioskFadeIn 500ms var(--ease-out-expo) both;
        }

        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            transform: scale(0);
            animation: ripple 600ms ease-out forwards;
            pointer-events: none;
        }

        .touch-input {
            caret-color: var(--primary);
        }

        <?php if ($isDisplay): ?>
        body {
            overflow: hidden;
            background: linear-gradient(160deg, hsl(210 30% 97%) 0%, hsl(215 45% 95%) 100%);
        }
        #kioskContainer {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .kiosk-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            overflow-y: auto;
        }
        .kiosk-main > div {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }
        #step1 h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        #servicesGrid {
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }
        #step2 { max-width: 480px !important; }
        #step2 input#customerName {
            font-size: 2rem;
            padding: 1.25rem 1.5rem;
            min-height: var(--btn-min-h);
        }
        #step2 .btn {
            min-height: var(--btn-min-h);
            font-size: 1.1rem;
        }
        #step2 h1 {
            font-size: 2rem;
            text-align: center;
        }
        #step2 p {
            text-align: center;
            font-size: 1.1rem;
        }
        #step3 .card {
            border-radius: 2rem !important;
            padding: 3rem 2rem !important;
        }
        #step3 #queueNumber {
            font-size: 140px !important;
        }
        .display-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            padding: 1.5rem 2rem 0.5rem;
        }
        .display-header .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .display-header .brand .logo-box {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            background: var(--brand-gold);
            border-radius: 8px;
        }
        .display-header .brand .logo-box img { width: 32px; height: 32px; object-fit: contain; }
        .display-header .brand .logo-box span { color: var(--primary); font-size: 18px; font-weight: 900; letter-spacing: -0.05em; }
        .display-header .brand-text { line-height: 1.2; text-align: center; }
        .display-header .brand-text .company { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; color: var(--primary); }
        .display-header .brand-text .tagline { font-size: 10px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.5; color: var(--muted); }
        .display-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            background: hsl(210 30% 97% / 0.7);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border);
            z-index: 10;
            pointer-events: none;
        }
        .display-footer .dot {
            width: 8px; height: 8px; border-radius: 50%; background: var(--success);
            animation: pulse-dot 2s infinite;
        }
        .display-footer span {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
        }
        .touch-hint {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 999;
            opacity: 0;
            transition: opacity 0.5s;
        }
        #fsOverlay {
            position: fixed; inset: 0; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: var(--primary);
            cursor: pointer;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        #fsOverlay.hidden-fs { opacity: 0; visibility: hidden; pointer-events: none; }
        #fsOverlay .fs-icon { font-size: 64px; color: white; margin-bottom: 1.5rem; }
        #fsOverlay .fs-label { color: rgba(255,255,255,0.9); font-size: 1.5rem; font-weight: 700; letter-spacing: 0.05em; }
        #fsOverlay .fs-sublabel { color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-top: 0.5rem; }
        <?php endif; ?>

        @media (max-width: 640px) {
            <?php if ($isDisplay): ?>
            .kiosk-main { padding: 1rem; align-items: flex-start; }
            #step1 h1 { font-size: 1.5rem; }
            #servicesGrid { grid-template-columns: 1fr; }
            .service-btn { min-height: 160px; padding: 1.25rem 1.5rem !important; }
            .service-btn h3 { font-size: 1.3rem !important; }
            .display-header { flex-direction: column; gap: 0.5rem; padding: 1rem 1rem 0; }
            .display-header .brand-text .company { font-size: 20px; }
            #step2 input#customerName { font-size: 1.5rem; }
            #step3 #queueNumber { font-size: 80px !important; }
            <?php endif; ?>
        }
    </style>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background);">

<?php if ($isDisplay): ?>
<!-- ====== DISPLAY MODE ====== -->
<div id="kioskContainer">
    <!-- Fullscreen overlay -->
    <div id="fsOverlay" onclick="enterFullscreen()">
        <div class="fs-icon"><i class="fas fa-hand-pointer"></i></div>
        <div class="fs-label">Tap to Enter Fullscreen</div>
        <div class="fs-sublabel">Touch anywhere to continue</div>
    </div>
    <header class="display-header">
        <div class="brand">
            <div class="logo-box">
                <?php if ($company_logo): ?>
                <img src="<?php echo $company_logo; ?>" alt="">
                <?php else: ?>
                <span>CQ</span>
                <?php endif; ?>
            </div>
            <div class="brand-text">
                <div class="company"><?php echo $company_name; ?></div>
                <div class="tagline"><?php if ($branch_name) echo htmlspecialchars($branch_name) . ' · '; ?>Self-Service Kiosk</div>
            </div>
        </div>
    </header>

    <main class="kiosk-main">
        <!-- Step 1: Service Selection -->
        <div id="step1" class="kiosk-enter" style="animation-delay:0ms;">
            <h1 class="font-extrabold tracking-tight">Select the service you need today.</h1>
            <div id="servicesGrid" class="grid gap-4 mt-6">
                <?php if (empty($services)): ?>
                <div class="col-span-full text-center py-12" style="color: var(--muted);">No services available</div>
                <?php else: ?>
                <?php foreach ($services as $i => $svc): ?>
                <div onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn kiosk-enter card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $i * 100; ?>ms;position:relative;">
                    <div class="flex items-start justify-between mb-4">
                        <span class="font-mono text-sm uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                    </div>
                    <h3 class="font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="desc-text mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Name Entry -->
        <div id="step2" class="hidden kiosk-enter" style="max-width: 32rem; margin: 0 auto;">
            <button onclick="goBack()" class="btn btn-ghost mb-6 text-base" style="color: var(--muted);"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <h1 class="font-extrabold tracking-tight mb-2" id="serviceTitle">Enter Your Name</h1>
            <p class="mb-8" style="color: var(--muted);">Type your full name to receive a queue ticket</p>
            <div class="card p-8">
                <input type="text" id="customerName" class="touch-input w-full px-6 py-4 text-center rounded-xl" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);" placeholder="Tap here to enter name" autocomplete="off" inputmode="text">
                <div class="flex gap-4 mt-6">
                    <button onclick="goBack()" class="btn btn-secondary flex-1 py-4 text-base">Back</button>
                    <button onclick="submitCustomer()" id="submitBtn" class="btn flex-1 py-4 text-base font-bold" style="background: #059669; color: white;">Get Queue Number</button>
                </div>
            </div>
        </div>

        <!-- Step 3: Success -->
        <div id="step3" class="hidden" style="max-width: 44rem; margin: 0 auto;">
            <div class="kiosk-enter card rounded-2xl p-12 text-center shadow-sm" style="border: 1px solid var(--border);">
                <span class="text-sm font-medium uppercase tracking-[0.3em]" style="color: var(--muted);">Your ticket</span>
                <div id="queueNumber" class="font-extrabold tracking-tighter leading-none tabular-nums my-6" style="color: var(--primary);">---</div>
                <p class="text-2xl font-medium"><?php echo $company_name; ?></p>
                <p class="text-lg" style="color: var(--muted);">Service: <span id="serviceName">---</span> &middot; <span id="windowAssigned">Available Window</span></p>
                <p class="mt-4 text-lg max-w-prose mx-auto" style="color: var(--muted);">Please take a seat. Your number will be called shortly on the public display.</p>
                <button onclick="resetKiosk()" class="mt-8 px-10 py-4 text-sm font-bold uppercase tracking-widest rounded-xl" style="background: var(--foreground); color: var(--background);">Done</button>
            </div>
            <div class="text-center mt-6"><p class="text-base" style="color: var(--muted);">Auto-resetting in <span id="countdown">30</span> seconds...</p></div>
        </div>
    </main>

    <footer class="display-footer">
        <div class="dot"></div>
        <span>Kiosk Ready</span>
        <span class="font-mono tabular-nums" id="footerTime">--:--:--</span>
    </footer>
</div>

<?php else: ?>
<!-- ====== NORMAL MODE ====== -->
<!-- SiteNav -->
<nav class="sticky top-0 z-50" style="background: #b91c1c; color: white; border-bottom: 1px solid rgba(255,255,255,0.15);">
    <div class="max-w-[1600px] mx-auto flex items-center justify-between px-6" style="height: 3.5rem;">
        <div class="flex items-center gap-10">
            <a href="display.php" class="flex items-center gap-3">
                <div class="relative w-7 h-7 grid place-items-center" style="background: var(--brand-gold); border-radius: 2px;">
                    <?php if ($company_logo): ?><img src="<?php echo $company_logo; ?>" alt="" class="w-5 h-5 object-contain"><?php else: ?><span style="color: var(--primary); font-size: 11px; font-weight: 900; letter-spacing: -0.05em;">CQ</span><?php endif; ?>
                </div>
                <div class="flex flex-col leading-none">
                    <span style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: white;"><?php echo $company_name; ?></span>
                    <span style="font-size: 9px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.5;"><?php if ($branch_name) echo htmlspecialchars($branch_name) . ' · '; ?>Queue Management</span>
                </div>
            </a>
            <div class="hidden md:flex gap-1 text-[11px] font-semibold uppercase tracking-wider">
                <a href="display.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Live Display</a>
                <a href="kiosk.php" class="px-3 py-1.5 rounded" style="background: rgba(255,255,255,0.1); color: white;">Kiosk</a>
                <a href="index.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Operator</a>
                <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded" style="background: rgba(255,255,255,0.1);">
                <div class="w-1.5 h-1.5 rounded-full" style="background: #34d399; animation: pulse-dot 2s infinite;"></div>
                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">All Systems Operational</span>
            </div>
        </div>
    </div>
</nav>

<main class="flex-1 w-full max-w-[1200px] mx-auto p-8 flex flex-col gap-8">
    <div class="flex items-center justify-between">
        <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Self-Service Kiosk</h2>
        <div class="flex items-center gap-3">
            <a href="kiosk.php?mode=display" id="displayModeBtn" class="text-[10px] font-bold uppercase tracking-widest px-3 py-1.5 rounded" style="background: var(--primary); color: white; text-decoration: none; cursor: pointer;"><i class="fas fa-expand-alt mr-1.5"></i>Display Mode</a>
            <span class="font-mono text-[10px]" style="color: var(--muted);">TERMINAL: KIOSK-01</span>
        </div>
    </div>

    <!-- Step 1: Service Selection -->
    <div id="step1" class="animate-entry">
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight max-w-[28ch]">Select the service you need today.</h1>
        <div id="servicesGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
            <?php if (empty($services)): ?>
            <div class="col-span-full text-center py-12" style="color: var(--muted);">No services available</div>
            <?php else: ?>
            <?php foreach ($services as $i => $svc): ?>
            <div onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn animate-entry text-left card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $i * 80; ?>ms;position:relative;">
                <div class="flex items-start justify-between mb-8">
                    <span class="font-mono text-[11px] uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                    <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded" style="background: hsl(215 60% 18% / 0.1); color: var(--primary);">Window</span>
                </div>
                <h3 class="text-2xl font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                <p class="text-sm mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                <div class="mt-8 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Name Entry -->
    <div id="step2" class="hidden animate-entry" style="max-width: 32rem; margin: 0 auto;">
        <button onclick="goBack()" class="btn btn-ghost mb-6" style="color: var(--muted);"><i class="fas fa-arrow-left mr-2"></i>Back</button>
        <h1 class="text-3xl font-extrabold tracking-tight mb-2" id="serviceTitle">Enter Your Name</h1>
        <p class="text-sm mb-8" style="color: var(--muted);">Type your full name to receive a queue ticket</p>
        <div class="card p-8">
            <input type="text" id="customerName" class="touch-input w-full px-6 py-4 text-2xl text-center rounded-xl" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);" placeholder="Tap here to enter name" autocomplete="off" inputmode="text">
            <div class="flex gap-4 mt-6">
                <button onclick="goBack()" class="btn btn-secondary flex-1 py-4 text-base">Back</button>
                <button onclick="submitCustomer()" id="submitBtn" class="btn flex-1 py-4 text-base font-bold" style="background: #059669; color: white;">Get Queue Number</button>
            </div>
        </div>
    </div>

    <!-- Step 3: Success -->
    <div id="step3" class="hidden" style="max-width: 40rem; margin: 0 auto;">
        <div class="animate-entry card rounded-2xl p-12 text-center shadow-sm" style="border: 1px solid var(--border);">
            <span class="text-xs font-medium uppercase tracking-[0.3em]" style="color: var(--muted);">Your ticket</span>
            <div id="queueNumber" class="text-[120px] md:text-[160px] font-extrabold tracking-tighter leading-none tabular-nums my-6" style="color: var(--primary);">---</div>
            <p class="text-lg font-medium"><?php echo $company_name; ?></p>
            <p class="text-sm" style="color: var(--muted);">Service: <span id="serviceName">---</span> &middot; <span id="windowAssigned">Available Window</span></p>
            <p class="mt-4 text-sm max-w-prose mx-auto" style="color: var(--muted);">Please take a seat. Your number will be called shortly on the public display.</p>
            <button onclick="resetKiosk()" class="mt-8 px-6 py-3 text-[11px] font-bold uppercase tracking-widest rounded" style="background: var(--foreground); color: var(--background);">Done</button>
        </div>
        <div class="text-center mt-6"><p class="text-xs" style="color: var(--muted);">Auto-resetting in <span id="countdown">30</span> seconds...</p></div>
    </div>
</main>

<!-- StatusFooter -->
<footer class="sticky bottom-0 left-0 w-full p-6 flex justify-between items-center" style="background: hsl(210 30% 97% / 0.8); backdrop-filter: blur(12px); pointer-events: none;">
    <div class="flex items-center gap-6">
        <div class="flex flex-col">
            <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Terminal ID</span>
            <span class="text-[11px] font-mono" style="color: var(--foreground);">KIOSK-01</span>
        </div>
        <div class="flex flex-col">
            <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Last Sync</span>
            <span class="text-[11px] font-mono tabular-nums" id="footerTime" style="color: var(--foreground);">--:--:--</span>
        </div>
    </div>
    <div class="flex items-center gap-2 px-3 py-1 rounded shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
        <div class="w-1.5 h-1.5 rounded-full" style="background: var(--primary);"></div>
        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">v4.2.0-stable</span>
    </div>
</footer>
<?php endif; ?>

<script>
    var finalServiceType = null, countdownInterval = null;
    var serviceNames = {<?php foreach ($services as $svc): echo "'" . $svc['code'] . "': '" . addslashes($svc['name']) . "',"; endforeach; ?>};

    function updateFooterTime() {
        var el = document.getElementById('footerTime');
        if (!el) return;
        var d = new Date();
        el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
    }
    setInterval(updateFooterTime, 200);
    updateFooterTime();

    function selectService(code) {
        finalServiceType = code;
        document.getElementById('serviceTitle').textContent = 'Service: ' + (serviceNames[code] || code);
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step2').classList.remove('hidden');
        setTimeout(function() { document.getElementById('customerName').focus(); }, 300);
    }

    function goBack() {
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('customerName').value = '';
    }

    async function submitCustomer() {
        var name = document.getElementById('customerName').value.trim();
        if (!name || name.length < 2) { alert('Please enter your name (min 2 characters)'); return; }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        try {
            var res = await fetch('api/add_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: name, service_type: finalServiceType }) });
            var data = await res.json();
            if (data.success) {
                showQueueNumber(data.queue_number, finalServiceType, data.data);
            } else { alert(data.message || 'Failed'); btn.disabled = false; btn.innerHTML = 'Get Queue Number'; }
        } catch (err) { console.error('Kiosk error:', err); alert('Connection error'); btn.disabled = false; btn.innerHTML = 'Get Queue Number'; }
    }

    function showQueueNumber(queueNumber, serviceType, data) {
        document.getElementById('queueNumber').textContent = queueNumber;
        document.getElementById('serviceName').textContent = serviceNames[serviceType] || serviceType;
        document.getElementById('windowAssigned').textContent = data.assigned_counter || 'Available Window';
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.remove('hidden');
        var seconds = 30;
        document.getElementById('countdown').textContent = seconds;
        countdownInterval = setInterval(function() { seconds--; document.getElementById('countdown').textContent = seconds; if (seconds <= 0) resetKiosk(); }, 1000);
    }

    function resetKiosk() {
        if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
        document.getElementById('step3').classList.add('hidden');
        document.getElementById('step1').classList.remove('hidden');
        document.getElementById('customerName').value = '';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').innerHTML = 'Get Queue Number';
        finalServiceType = null;
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') resetKiosk();
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            if (!window.location.search.includes('mode=display')) {
                navigateDisplayMode();
            }
        }
    });

    function goFullscreen() {
        var el = document.documentElement;
        if (el.requestFullscreen) { el.requestFullscreen(); }
        else if (el.webkitRequestFullscreen) { el.webkitRequestFullscreen(); }
        else if (el.msRequestFullscreen) { el.msRequestFullscreen(); }
    }

    function navigateDisplayMode() {
        window.location.href = 'kiosk.php?mode=display';
    }

    document.getElementById('displayModeBtn').addEventListener('click', function(e) {
        e.preventDefault();
        navigateDisplayMode();
    });

    // Fullscreen overlay handler (display mode only)
    <?php if ($isDisplay): ?>
    function enterFullscreen() {
        var el = document.documentElement;
        var fs = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
        if (fs) {
            fs.call(el)['catch'](function(){});
        }
        var overlay = document.getElementById('fsOverlay');
        if (overlay) overlay.classList.add('hidden-fs');
    }
    <?php endif; ?>

    // Touch ripple effect
    document.addEventListener('click', function(e) {
        var target = e.target.closest('.service-btn, .btn, button');
        if (!target) return;
        var rect = target.getBoundingClientRect();
        var ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        var size = Math.max(rect.width, rect.height);
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
        target.style.position = 'relative';
        target.style.overflow = 'hidden';
        target.appendChild(ripple);
        setTimeout(function() { ripple.remove(); }, 700);
    });
</script>
</body>
</html>
