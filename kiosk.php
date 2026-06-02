<?php include 'config.php';
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($s['branch_name'] ?? '');
$company_logo = htmlspecialchars($s['company_logo'] ?? '');
$services = [];
$groups = [];
$knownCompanies = [];
$groupedServices = [];
$ungroupedServices = [];
try {
    $stmt = $conn->query("
        SELECT st.*, sg.name as group_name, sg.id as group_id
        FROM service_types st
        LEFT JOIN service_groups sg ON sg.id = st.group_id
        WHERE st.is_active = 1 AND st.code != 'custom'
        ORDER BY sg.name ASC, st.name ASC
    ");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as $svc) {
        if ($svc['group_id']) {
            $gName = $svc['group_name'];
            $groupedServices[$gName][] = $svc;
            if (!isset($groups[$gName])) $groups[$gName] = $svc['group_id'];
        } else {
            $ungroupedServices[] = $svc;
        }
    }
    $stmt = $conn->query("SELECT name FROM known_companies ORDER BY name ASC");
    $knownCompanies = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
                <?php $globalIdx = 0; ?>
                <?php $groupIdx = 0; ?>
                <?php foreach ($groupedServices as $groupName => $svcs): ?>
                <?php $gid = 'grp' . $groupIdx; $groupIdx++; ?>
                <div onclick="openGroupDisplay('<?php echo $gid; ?>', '<?php echo htmlspecialchars($groupName, ENT_QUOTES); ?>')" class="service-btn kiosk-enter card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $globalIdx * 100; ?>ms;cursor:pointer;position:relative;">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold tracking-tight"><?php echo htmlspecialchars($groupName); ?></h3>
                        <i class="fas fa-chevron-right text-lg" style="color: var(--muted);"></i>
                    </div>
                    <p class="desc-text mt-1" style="color: var(--muted);"><?php echo count($svcs); ?> service<?php echo count($svcs) > 1 ? 's' : ''; ?></p>
                </div>
                <?php foreach ($svcs as $svc): ?>
                <div data-group="<?php echo $gid; ?>" class="hidden">
                    <div onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn kiosk-enter card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);position:relative;">
                        <div class="flex items-start justify-between mb-4">
                            <span class="font-mono text-sm uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                        </div>
                        <h3 class="font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                        <p class="desc-text mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                        <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                    </div>
                </div>
                <?php $globalIdx++; endforeach; ?>
                <?php endforeach; ?>
                <?php if (!empty($ungroupedServices)): ?>
                <?php foreach ($ungroupedServices as $svc): ?>
                <div onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn kiosk-enter card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $globalIdx * 100; ?>ms;position:relative;">
                    <div class="flex items-start justify-between mb-4">
                        <span class="font-mono text-sm uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                    </div>
                    <h3 class="font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="desc-text mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php $globalIdx++; endforeach; ?>
                <?php endif; ?>
                <!-- Custom Card (always visible, uneditable) -->
                <div onclick="selectService('custom')" class="service-btn kiosk-enter card rounded-xl p-8 shadow-sm" style="border: 2px dashed var(--primary);animation-delay:<?php echo $globalIdx * 100; ?>ms;position:relative;background:transparent;">
                    <div class="flex items-start justify-between mb-4">
                        <span class="font-mono text-sm uppercase tracking-wider" style="color: var(--muted);">Prefix C</span>
                    </div>
                    <h3 class="font-bold tracking-tight" style="color: var(--primary);"><i class="fas fa-pen mr-2"></i>Custom</h3>
                    <p class="desc-text mt-1" style="color: var(--muted);">Type your own concern or inquiry</p>
                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 1b: Group Drill-down -->
        <div id="step1b" class="hidden kiosk-enter" style="max-width: 48rem; margin: 0 auto;">
            <button onclick="closeGroupDisplay()" class="btn btn-ghost mb-6 text-base" style="color: var(--muted);"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <h1 class="font-extrabold tracking-tight mb-2" id="groupDisplayTitle">Select a Service</h1>
            <p class="mb-8" style="color: var(--muted);" id="groupDisplaySubtitle">Choose from the services below</p>
            <div id="groupDisplayGrid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
        </div>

        <!-- Step 2: Name Entry + Company + Purpose -->
        <div id="step2" class="hidden kiosk-enter" style="max-width: 32rem; margin: 0 auto;">
            <button onclick="goBack()" class="btn btn-ghost mb-6 text-base" style="color: var(--muted);"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <h1 class="font-extrabold tracking-tight mb-2" id="serviceTitle">Enter Your Details</h1>
            <p class="mb-8" style="color: var(--muted);">Fill in your information to receive a queue ticket</p>
            <div class="card p-8">
                <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Company (optional)</label>
                <input type="text" id="companyName" list="companyList" class="touch-input w-full px-6 py-4 text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);" placeholder="Enter company name" autocomplete="off" inputmode="text">
                <datalist id="companyList">
                    <?php foreach ($knownCompanies as $cname): ?>
                    <option value="<?php echo htmlspecialchars($cname); ?>">
                    <?php endforeach; ?>
                </datalist>

                <div id="customDescriptionRow" class="hidden">
                    <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Describe your concern</label>
                    <input type="text" id="customDescription" class="touch-input w-full px-6 py-4 text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--primary);" placeholder="e.g. Meeting with Sir Murphy" autocomplete="off" inputmode="text" maxlength="150">
                </div>

                <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Purpose</label>
                <select id="purposeSelect" class="touch-input w-full px-6 py-4 text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);">
                    <option value="">-- Select purpose --</option>
                    <option value="inquiry">Inquiry</option>
                    <option value="complain">Complain</option>
                    <option value="follow-up">Follow-up</option>
                </select>

                <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Your Name</label>
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
                <p class="text-base" style="color: var(--muted);"><span id="successCompany"></span><span id="successPurpose"></span></p>
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
                <?php $globalIdx = 0; ?>
                <?php $groupIdx2 = 0; ?>
                <?php foreach ($groupedServices as $groupName => $svcs): ?>
                <?php $gid = 'grpd' . $groupIdx2; $groupIdx2++; ?>
                <div onclick="toggleGroup('<?php echo $gid; ?>')" class="service-btn animate-entry card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $globalIdx * 80; ?>ms;cursor:pointer;position:relative;">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold tracking-tight"><?php echo htmlspecialchars($groupName); ?></h3>
                        <i id="<?php echo $gid; ?>Icon" class="fas fa-chevron-down text-xl transition-transform duration-300" style="color: var(--muted);"></i>
                    </div>
                    <p class="text-sm mt-1" style="color: var(--muted);"><?php echo count($svcs); ?> service<?php echo count($svcs) > 1 ? 's' : ''; ?></p>
                </div>
                <?php foreach ($svcs as $svc): ?>
                <div data-group="<?php echo $gid; ?>" onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn animate-entry text-left card rounded-xl p-8 shadow-sm hidden" style="border: 1px solid var(--border);animation-delay:<?php echo $globalIdx * 80; ?>ms;position:relative;">
                    <div class="flex items-start justify-between mb-8">
                        <span class="font-mono text-[11px] uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded" style="background: hsl(215 60% 18% / 0.1); color: var(--primary);">Window</span>
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="text-sm mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div class="mt-8 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php $globalIdx++; endforeach; ?>
                <?php endforeach; ?>
                <?php if (!empty($ungroupedServices)): ?>
                <?php foreach ($ungroupedServices as $svc): ?>
                <div onclick="selectService('<?php echo $svc['code']; ?>')" class="service-btn animate-entry text-left card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);animation-delay:<?php echo $globalIdx * 80; ?>ms;position:relative;">
                    <div class="flex items-start justify-between mb-8">
                        <span class="font-mono text-[11px] uppercase tracking-wider" style="color: var(--muted);">Prefix <?php echo htmlspecialchars($svc['queue_prefix']); ?></span>
                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded" style="background: hsl(215 60% 18% / 0.1); color: var(--primary);">Window</span>
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight"><?php echo htmlspecialchars($svc['name']); ?></h3>
                    <p class="text-sm mt-1" style="color: var(--muted);"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
                    <div class="mt-8 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php $globalIdx++; endforeach; ?>
                <?php endif; ?>
                <!-- Custom Card (always visible, uneditable) -->
                <div onclick="selectService('custom')" class="service-btn animate-entry text-left card rounded-xl p-8 shadow-sm" style="border: 2px dashed var(--primary);animation-delay:<?php echo $globalIdx * 80; ?>ms;position:relative;background:transparent;">
                    <div class="flex items-start justify-between mb-8">
                        <span class="font-mono text-[11px] uppercase tracking-wider" style="color: var(--muted);">Prefix C</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-1 rounded" style="background: hsl(215 60% 18% / 0.1); color: var(--primary);">Window</span>
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight" style="color: var(--primary);"><i class="fas fa-pen mr-2"></i>Custom</h3>
                    <p class="text-sm mt-1" style="color: var(--muted);">Type your own concern or inquiry</p>
                    <div class="mt-8 inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest" style="color: var(--primary);">Take ticket <span aria-hidden>→</span></div>
                </div>
                <?php endif; ?>
            </div>
    </div>

    <!-- Step 2: Name Entry + Company + Purpose -->
    <div id="step2" class="hidden animate-entry" style="max-width: 32rem; margin: 0 auto;">
        <button onclick="goBack()" class="btn btn-ghost mb-6" style="color: var(--muted);"><i class="fas fa-arrow-left mr-2"></i>Back</button>
        <h1 class="text-3xl font-extrabold tracking-tight mb-2" id="serviceTitle">Enter Your Details</h1>
        <p class="text-sm mb-8" style="color: var(--muted);">Fill in your information to receive a queue ticket</p>
        <div class="card p-8">
            <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Company (optional)</label>
            <input type="text" id="companyName" list="companyList" class="touch-input w-full px-5 py-3 text-lg text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);" placeholder="Enter company name" autocomplete="off" inputmode="text">
            <datalist id="companyList">
                <?php foreach ($knownCompanies as $cname): ?>
                <option value="<?php echo htmlspecialchars($cname); ?>">
                <?php endforeach; ?>
            </datalist>

            <div id="customDescriptionRow" class="hidden">
                <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Describe your concern</label>
                <input type="text" id="customDescription" class="touch-input w-full px-5 py-3 text-lg text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--primary);" placeholder="e.g. Meeting with Sir Murphy" autocomplete="off" inputmode="text" maxlength="150">
            </div>

            <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Purpose</label>
            <select id="purposeSelect" class="touch-input w-full px-5 py-3 text-lg text-center rounded-xl mb-4" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);">
                <option value="">-- Select purpose --</option>
                <option value="inquiry">Inquiry</option>
                <option value="complain">Complain</option>
                <option value="follow-up">Follow-up</option>
            </select>

            <label class="text-xs font-bold uppercase tracking-widest mb-1 block" style="color: var(--muted);">Your Name</label>
            <input type="text" id="customerName" class="touch-input w-full px-5 py-3 text-lg text-center rounded-xl" style="background: var(--background); color: var(--foreground); border: 1px solid var(--border);" placeholder="Tap here to enter name" autocomplete="off" inputmode="text">
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
            <p class="text-sm" style="color: var(--muted);"><span id="successCompany"></span><span id="successPurpose"></span></p>
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
        <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">v4.3.0-stable</span>
    </div>
</footer>
<?php endif; ?>

<script>
    var finalServiceType = null, countdownInterval = null;
    var serviceNames = {<?php foreach ($services as $svc): echo "'" . $svc['code'] . "': '" . addslashes($svc['name']) . "',"; endforeach; ?>};
    var serviceStatuses = { custom: 'active', other: 'active' };

    async function fetchCounterStatuses() {
        try {
            var res = await fetch('api/get_kiosk_status.php');
            var data = await res.json();
            if (!data.success) return;
            var statusMap = {};
            data.data.counters.forEach(function(c) {
                c.services.forEach(function(s) {
                    if (s.is_primary != '1' && s.is_primary != 1) return;
                    if (!statusMap[s.service_type]) statusMap[s.service_type] = { online: 0, break: 0, offline: 0 };
                    if (c.status_text === 'On Break') statusMap[s.service_type].break++;
                    else if (c.status_text === 'Offline' || c.is_online == 0) statusMap[s.service_type].offline++;
                    else statusMap[s.service_type].online++;
                });
            });
            // Custom uses ALL counters (not just primary)
            var allOnline = 0, allBreak = 0, allOffline = 0;
            data.data.counters.forEach(function(c) {
                if (c.status_text === 'On Break') allBreak++;
                else if (c.status_text === 'Offline' || c.is_online == 0) allOffline++;
                else allOnline++;
            });
            if (allOnline === 0 && allBreak === 0) serviceStatuses.custom = 'offline';
            else if (allBreak > 0 && allOnline === 0) serviceStatuses.custom = 'break';
            else serviceStatuses.custom = 'active';
            for (var code in statusMap) {
                var st = statusMap[code];
                if (st.online === 0 && st.break === 0) serviceStatuses[code] = 'offline';
                else if (st.break > 0) serviceStatuses[code] = 'break';
                else serviceStatuses[code] = 'online';
            }
            applyServiceStates();
        } catch (e) {}
    }

    function applyServiceStates() {
        var cards = document.querySelectorAll('.service-btn');
        for (var ci = 0; ci < cards.length; ci++) { var card = cards[ci];
            var onclick = card.getAttribute('onclick') || '';
            var m = onclick.match(/selectService\('([^']+)'\)/);
            if (!m) continue;
            var code = m[1];
            var status = serviceStatuses[code] || 'active';
            card.removeAttribute('data-status');
            card.setAttribute('data-status', status);
            var existingBadge = card.querySelector('.status-badge');
            if (existingBadge) existingBadge.remove();
            if (status === 'offline') {
                card.style.opacity = '0.4';
                card.style.cursor = 'not-allowed';
                card.style.pointerEvents = 'none';
                card.style.filter = 'grayscale(1)';
                var badge = document.createElement('span');
                badge.className = 'status-badge';
                badge.style.cssText = 'position:absolute;top:10px;right:10px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;';
                badge.textContent = 'Offline';
                card.appendChild(badge);
            } else {
                card.style.opacity = '';
                card.style.cursor = '';
                card.style.pointerEvents = '';
                card.style.filter = '';
                if (status === 'break') {
                    var badge = document.createElement('span');
                    badge.className = 'status-badge';
                    badge.style.cssText = card.classList.contains('kiosk-enter') ?
                        'position:absolute;top:10px;right:10px;background:#f59e0b;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;' :
                        'position:absolute;top:10px;right:10px;background:#f59e0b;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;';
                    badge.textContent = 'On Break';
                    card.appendChild(badge);
                }
            }
        }
        // Also update group headers that show service count
        var groupCards = document.querySelectorAll('[onclick*="openGroupDisplay"]');
        for (var gi = 0; gi < groupCards.length; gi++) {
            var gc = groupCards[gi];
            var gid = gc.getAttribute('onclick').match(/openGroupDisplay\('([^']+)'/);
            if (!gid) continue;
            var svcs = document.querySelectorAll('[data-group="' + gid[1] + '"] .service-btn');
            var hasOffline = false, hasBreak = false;
            for (var si = 0; si < svcs.length; si++) {
                var scode = svcs[si].getAttribute('onclick').match(/selectService\('([^']+)'\)/);
                if (!scode) continue;
                var st = serviceStatuses[scode[1]];
                if (st === 'offline') hasOffline = true;
                if (st === 'break') hasBreak = true;
            }
            var gExisting = gc.querySelector('.status-badge');
            if (gExisting) gExisting.remove();
            if (hasOffline) {
                var badge = document.createElement('span');
                badge.className = 'status-badge';
                badge.style.cssText = 'position:absolute;top:10px;right:10px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;';
                badge.textContent = 'Unavailable';
                gc.appendChild(badge);
            } else if (hasBreak) {
                var badge = document.createElement('span');
                badge.className = 'status-badge';
                badge.style.cssText = 'position:absolute;top:10px;right:10px;background:#f59e0b;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;padding:3px 8px;border-radius:4px;';
                badge.textContent = 'On Break';
                gc.appendChild(badge);
            }
        }
    }

    function updateFooterTime() {
        var el = document.getElementById('footerTime');
        if (!el) return;
        var d = new Date();
        el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
    }
    setInterval(updateFooterTime, 200);
    updateFooterTime();
    fetchCounterStatuses();
    setInterval(fetchCounterStatuses, 10000);

    function toggleGroup(gid) {
        var items = document.querySelectorAll('[data-group="' + gid + '"]');
        var icon = document.getElementById(gid + 'Icon');
        if (!items.length) return;
        var isHidden = items[0].classList.contains('hidden');
        for (var i = 0; i < items.length; i++) items[i].classList.toggle('hidden');
        if (icon) icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    function openGroupDisplay(gid, name) {
        document.getElementById('groupDisplayTitle').textContent = name;
        document.getElementById('groupDisplaySubtitle').textContent = 'Choose from the services below';
        var grid = document.getElementById('groupDisplayGrid');
        grid.innerHTML = '';
        var templates = document.querySelectorAll('[data-group="' + gid + '"]');
        for (var i = 0; i < templates.length; i++) {
            var card = templates[i].querySelector('.service-btn');
            if (card) grid.appendChild(card.cloneNode(true));
        }
        applyServiceStates();
        document.getElementById('step1').classList.add('hidden');
        document.getElementById('step1b').classList.remove('hidden');
    }

    function closeGroupDisplay() {
        document.getElementById('step1b').classList.add('hidden');
        document.getElementById('groupDisplayGrid').innerHTML = '';
        document.getElementById('step1').classList.remove('hidden');
    }

    function selectService(code) {
        if (serviceStatuses[code] === 'offline') return;
        finalServiceType = code;
        document.getElementById('serviceTitle').textContent = 'Service: ' + (serviceNames[code] || code);
        var customRow = document.getElementById('customDescriptionRow');
        if (code === 'custom') {
            if (customRow) customRow.classList.remove('hidden');
            document.getElementById('customDescription').value = '';
        } else {
            if (customRow) customRow.classList.add('hidden');
        }
        var step1b = document.getElementById('step1b');
        if (step1b && !step1b.classList.contains('hidden')) {
            window._fromStep1b = true;
            step1b.classList.add('hidden');
        } else {
            window._fromStep1b = false;
            document.getElementById('step1').classList.add('hidden');
        }
        document.getElementById('step2').classList.remove('hidden');
        setTimeout(function() { document.getElementById('customerName').focus(); }, 300);
    }

    function goBack() {
        document.getElementById('step2').classList.add('hidden');
        var customRow = document.getElementById('customDescriptionRow');
        if (customRow) customRow.classList.add('hidden');
        if (window._fromStep1b) {
            document.getElementById('step1b').classList.remove('hidden');
            window._fromStep1b = false;
        } else {
            document.getElementById('step1').classList.remove('hidden');
        }
        document.getElementById('customerName').value = '';
        document.getElementById('companyName').value = '';
        document.getElementById('purposeSelect').value = '';
    }

    async function submitCustomer() {
        var name = document.getElementById('customerName').value.trim();
        var company = document.getElementById('companyName').value.trim();
        var purpose = document.getElementById('purposeSelect').value;
        var customDesc = document.getElementById('customDescription') ? document.getElementById('customDescription').value.trim() : '';
        if (!name || name.length < 2) { alert('Please enter your name (min 2 characters)'); return; }
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        try {
            var body = { name: name, service_type: finalServiceType, company_name: company, purpose: purpose };
            if (customDesc) body.custom_description = customDesc;
            var res = await fetch('api/add_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
            var data = await res.json();
            if (data.success) {
                showQueueNumber(data.queue_number, finalServiceType, data.data);
            } else { alert(data.message || 'Failed'); btn.disabled = false; btn.innerHTML = 'Get Queue Number'; }
        } catch (err) { console.error('Kiosk error:', err); alert('Connection error'); btn.disabled = false; btn.innerHTML = 'Get Queue Number'; }
    }

    function showQueueNumber(queueNumber, serviceType, data) {
        document.getElementById('queueNumber').textContent = queueNumber;
        var customDesc = document.getElementById('customDescription') ? document.getElementById('customDescription').value.trim() : '';
        var displayName = serviceNames[serviceType] || serviceType;
        if (serviceType === 'custom' && customDesc) displayName += ' (' + customDesc + ')';
        document.getElementById('serviceName').textContent = displayName;
        document.getElementById('windowAssigned').textContent = data.assigned_counter || 'Available Window';
        var company = document.getElementById('companyName').value.trim();
        var purpose = document.getElementById('purposeSelect').value;
        var companyEl = document.getElementById('successCompany');
        var purposeEl = document.getElementById('successPurpose');
        if (company) { companyEl.textContent = 'Company: ' + company; }
        else { companyEl.textContent = ''; }
        if (purpose) { purposeEl.textContent = (company ? '  \u00b7  ' : '') + 'Purpose: ' + purpose.charAt(0).toUpperCase() + purpose.slice(1); }
        else { purposeEl.textContent = ''; }
        document.getElementById('step2').classList.add('hidden');
        document.getElementById('step3').classList.remove('hidden');
        var seconds = 30;
        document.getElementById('countdown').textContent = seconds;
        countdownInterval = setInterval(function() { seconds--; document.getElementById('countdown').textContent = seconds; if (seconds <= 0) resetKiosk(); }, 1000);
    }

    function resetKiosk() {
        if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
        document.getElementById('step3').classList.add('hidden');
        document.getElementById('step1b').classList.add('hidden');
        document.getElementById('step1').classList.remove('hidden');
        var customRow = document.getElementById('customDescriptionRow');
        if (customRow) customRow.classList.add('hidden');
        document.getElementById('customerName').value = '';
        document.getElementById('companyName').value = '';
        document.getElementById('purposeSelect').value = '';
        if (document.getElementById('customDescription')) document.getElementById('customDescription').value = '';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').innerHTML = 'Get Queue Number';
        finalServiceType = null;
        fetchCounterStatuses();
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
