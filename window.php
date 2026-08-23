<?php include 'config.php';
$user = requireAuth();
if (!$user) { header('Location: login.php'); exit; }
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($s['branch_name'] ?? '');
$company_logo = htmlspecialchars($s['company_logo'] ?? '');
$display_name = htmlspecialchars($user['display_name'] ?? 'Operator');
$user_role = htmlspecialchars($user['role'] ?? 'staff');

// Determine window_id: URL param takes precedence, else user's assigned window
$window_id = isset($_GET['window_id']) ? intval($_GET['window_id']) : (int)($user['window_id'] ?? 0);

// Staff without a window assignment cannot use window.php
if ($user_role === 'staff' && !$window_id) {
    header('Location: index.php');
    exit;
}

$window_name = 'Window Unassigned';
try {
    if ($window_id) {
        $stmt = $conn->prepare("SELECT display_name, id FROM counters WHERE id = ?");
        $stmt->execute([$window_id]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($counter) $window_name = htmlspecialchars($counter['display_name']);
        else $window_id = 0;
    }
    // Get all counters for admin selector
    $allCounters = $conn->query("SELECT id, display_name FROM counters ORDER BY window_number ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $allCounters = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $window_name; ?> — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        .filter-btn { padding: 0.5rem 1rem; border-radius: var(--radius); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer; transition: all 0.15s; }
        .filter-btn.active { background: var(--primary); color: var(--primary-foreground); }
        .filter-btn:not(.active) { background: var(--card); color: var(--muted); border: 1px solid var(--border); }
        .filter-btn:not(.active):hover { background: var(--secondary); }
        .queue-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--foreground); background: var(--secondary); border-bottom: 2px solid var(--border); position: sticky; top: 0; z-index: 1; }
        .queue-table td { padding: 0.75rem 1rem; border-bottom: 1px solid hsl(215 20% 92%); font-size: 0.8125rem; vertical-align: middle; }
        .queue-table tbody tr:nth-child(even) td { background: hsl(215 20% 97% / 0.5); }
        .queue-table tbody tr:hover td { background: hsl(215 30% 95%); }
        .queue-table tr.is-serving td { background: hsl(215 60% 18% / 0.04); border-left: 3px solid var(--primary); }
        .queue-table tr.is-serving td:first-child { padding-left: calc(1rem - 3px); }
        .skeleton { background: linear-gradient(90deg, var(--secondary) 25%, hsl(215 16% 92%) 50%, var(--secondary) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: var(--radius); }
        .toast { animation: slideIn 300ms var(--ease-out-expo); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .notif-toast { animation: notifSlideIn 350ms cubic-bezier(0.16, 1, 0.3, 1); }
        .notif-toast.dismissing { animation: notifSlideOut 250ms ease-in forwards; }
        @keyframes notifSlideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes notifSlideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(120%); opacity: 0; } }
    </style>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background);">
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
                    <?php if ($user_role !== 'staff'): ?>
                    <a href="display.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Live Display</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                    <a href="kiosk.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Kiosk</a>
                    <a href="settings.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Settings</a>
                    <?php endif; ?>
                    <a href="window.php<?php echo $window_id ? '?window_id=' . $window_id : ''; ?>" class="px-3 py-1.5 rounded" style="background: rgba(255,255,255,0.1); color: white;">Operator</a>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded" style="background: rgba(255,255,255,0.1);">
                    <span style="font-size: 11px; font-weight: 700; font-family: var(--font-mono);" id="windowStatusBadge">--</span>
                </div>
                <div class="flex items-center gap-2 pl-3" style="border-left: 1px solid rgba(255,255,255,0.15);">
                    <div class="w-7 h-7 rounded-full grid place-items-center text-[10px] font-bold" style="background: rgba(255,255,255,0.15);"><?php echo substr($display_name, 0, 2); ?></div>
                    <div class="hidden sm:flex flex-col leading-none">
                        <span style="font-size: 11px; font-weight: 600;"><?php echo $display_name; ?></span>
                        <span style="font-size: 9px; opacity: 0.5; text-transform: uppercase; letter-spacing: 0.15em;"><?php echo $user_role; ?></span>
                    </div>
                    <button onclick="logout()" class="ml-2 px-2 py-1 rounded text-[10px]" style="background: rgba(255,255,255,0.1);" title="Sign Out"><i class="fas fa-sign-out-alt"></i></button>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full max-w-[1440px] mx-auto p-8 grid grid-cols-12 gap-8">
        <!-- Main Column -->
        <section class="col-span-12 lg:col-span-8 flex flex-col gap-6">
            <div class="flex items-baseline justify-between">
                <div class="flex items-center gap-4">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Operator Console &middot; <span id="windowNameDisplay"><?php echo $window_name; ?></span></h2>
                    <?php if ($user_role !== 'staff'): ?>
                    <select id="windowSelector" onchange="changeWindow(this.value)" class="text-xs px-2 py-1 rounded" style="border:1px solid var(--border);background:var(--card);">
                        <?php foreach ($allCounters as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $window_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <span class="font-mono text-[10px]" style="color: var(--muted);" id="sessionTime">SESSION --</span>
            </div>

            <!-- Now Serving -->
            <div class="animate-entry card rounded-xl p-8 shadow-sm" style="border: 1px solid var(--border);">
                <div class="flex items-start justify-between gap-6 flex-wrap">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Now Serving</span>
                        <div class="text-7xl font-extrabold tracking-tighter tabular-nums mt-2" id="servingNumber" style="color: var(--primary);">---</div>
                        <p class="text-sm mt-2" style="color: var(--muted);" id="servingInfo">No active customer</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="callNext()" class="px-5 py-3 rounded text-[11px] font-bold uppercase tracking-widest" style="background: var(--primary); color: var(--primary-foreground);">Complete &amp; Next</button>
                        <button onclick="skipCustomer()" class="px-5 py-3 border rounded text-[11px] font-bold uppercase tracking-widest" style="border-color: var(--border); background: var(--card);">Skip</button>
                        <button onclick="noShow()" class="px-5 py-3 border rounded text-[11px] font-bold uppercase tracking-widest" style="border-color: var(--border); background: var(--card); color: var(--destructive);">No-Show</button>
                    </div>
                </div>
            </div>

            <!-- Status & Counter Control Bar -->
            <div class="flex items-center gap-4 p-4 card rounded-xl" style="border: 1px solid var(--border);">
                <div class="flex items-center gap-3">
                    <span class="status-dot" id="counterStatusDot"></span>
                    <span class="text-sm font-bold" id="counterStatusText">--</span>
                </div>
                <select onchange="changeWindowStatus(windowId, this.value)" class="text-xs px-3 py-1.5 rounded" style="border:1px solid var(--border);background:var(--card);">
                    <option value="Online">Online</option>
                    <option value="On Break">On Break</option>
                    <option value="Offline">Offline</option>
                </select>
                <div class="flex-1"></div>
                <span class="text-xs" style="color: var(--muted);">Serving: <strong id="counterWaitingCount">0</strong> waiting</span>
            </div>

            <!-- KPI row -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Waiting</div>
                    <div class="text-3xl font-extrabold tracking-tight mt-1" id="waiting-count" style="color: #d97706;">0</div>
                </div>
                <div class="card p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Serving</div>
                    <div class="text-3xl font-extrabold tracking-tight mt-1" id="serving-count" style="color: var(--primary);">0</div>
                </div>
                <div class="card p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Completed</div>
                    <div class="text-3xl font-extrabold tracking-tight mt-1" id="completed-count" style="color: var(--success);">0</div>
                </div>
                <div class="card p-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Today</div>
                    <div class="text-3xl font-extrabold tracking-tight mt-1" id="today-count" style="color: var(--foreground);">0</div>
                </div>
            </div>

            <!-- Queue Table -->
            <div class="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between p-5 border-b border-border" style="background: hsl(215 20% 94% / 0.6);">
                    <h3 class="text-xs font-bold uppercase tracking-widest">Queue</h3>
                    <span class="text-[10px] font-medium uppercase tracking-tighter font-mono" style="color: var(--muted);"><span id="queueCount">0</span> pending</span>
                </div>
                <div class="p-4 border-b border-border flex flex-wrap gap-2" style="background: var(--secondary);">
                    <button onclick="filterQueue('all')" class="filter-btn active" data-filter="all">All</button>
                    <button onclick="filterQueue('waiting')" class="filter-btn" data-filter="waiting">Waiting</button>
                    <button onclick="filterQueue('serving')" class="filter-btn" data-filter="serving">Serving</button>
                    <button onclick="filterQueue('completed')" class="filter-btn" data-filter="completed">Completed</button>
                    <button onclick="filterQueue('follow_up')" class="filter-btn" data-filter="follow_up">Follow-up</button>
                    <div class="ml-auto">
                        <button onclick="refreshQueue()" class="btn btn-ghost text-[10px] py-1 px-2"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    </div>
                </div>
                <div class="px-4 pb-3" style="background: var(--secondary);">
                    <input type="text" id="searchInput" oninput="onSearchInput(this.value)" placeholder="Search by name or ticket number..." class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);background:var(--card);">
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full queue-table">
                        <thead>
                            <tr><th>Queue No.</th><th>Customer</th><th>Service</th><th>Company</th><th>Purpose</th><th>Status</th><th>Time</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="queueTable"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Sidebar -->
        <aside class="col-span-12 lg:col-span-4 flex flex-col gap-6">
            <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Window Summary</h2>

            <div class="bg-card border border-border rounded-xl shadow-sm divide-y divide-border/60" id="windowDetail">
                <div class="p-4">
                    <div class="text-xs" style="color: var(--muted);">Loading...</div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl p-6" style="background: var(--surface-dark); color: var(--surface-dark-foreground);">
                <div class="absolute inset-0 bg-grid" style="opacity: 0.3;"></div>
                <div class="relative">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest mb-4" style="color: rgba(255,255,255,0.5);">Session totals</h4>
                    <div class="grid grid-cols-2 gap-4" id="sessionTotals">
                        <div><div class="text-2xl font-bold tracking-tight tabular-nums" id="sessionServed">0</div><div class="text-[9px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.4);">Served</div></div>
                        <div><div class="text-2xl font-bold tracking-tight tabular-nums" id="sessionNoshows">0</div><div class="text-[9px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.4);">No-shows</div></div>
                        <div><div class="text-2xl font-bold tracking-tight tabular-nums" id="sessionAvgHandle">0:00</div><div class="text-[9px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.4);">Avg. handle</div></div>
                        <div><div class="text-2xl font-bold tracking-tight tabular-nums" id="sessionIdle">--</div><div class="text-[9px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.4);">Idle</div></div>
                    </div>
                </div>
            </div>

            <!-- Follow-Up Queue -->
            <div id="followUpPanel" class="animate-entry card rounded-xl p-5 shadow-sm" style="border: 2px solid var(--brand-gold); display: none;">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-flag" style="color: var(--brand-gold);"></i>
                        <h3 class="text-xs font-bold uppercase tracking-widest">Follow-Up Queue</h3>
                    </div>
                    <span class="text-[10px] font-mono" style="color: var(--muted);"><span id="followUpCount">0</span> pending</span>
                </div>
                <div id="followUpList" class="divide-y divide-border"></div>
            </div>

            <?php if ($window_id): ?>
            <!-- Make Announcement -->
            <div class="animate-entry card rounded-xl p-5 shadow-sm" style="border: 1px solid var(--border);">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-3"><i class="fas fa-bullhorn mr-2" style="color: var(--primary);"></i>Make an Announcement</h3>
                <div class="flex gap-2 mb-3">
                    <input type="text" id="announcementInput" maxlength="100" placeholder="Type your message..." class="flex-1 px-3 py-2 text-sm rounded" style="border: 1px solid var(--border); background: var(--card); outline: none;" oninput="updateAnnounceCharCount()">
                    <button onclick="postAnnouncement()" class="btn btn-primary text-[10px] px-3 py-2"><i class="fas fa-paper-plane mr-1"></i>Send</button>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px]" style="color: var(--muted);">Max 100 characters</span>
                    <span class="text-[10px] font-mono" id="announceCharCount" style="color: var(--muted);">0/100</span>
                </div>
                <div id="windowAnnouncementList" class="space-y-1.5 max-h-[180px] overflow-y-auto">
                    <div class="text-center py-3 text-[11px]" style="color: var(--muted);">No announcements</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role'] !== 'staff'): ?>
            <a href="index.php" class="btn btn-secondary w-full text-[11px]"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard Overview</a>
            <?php endif; ?>
        </aside>
    </main>

    <!-- StatusFooter -->
    <footer class="sticky bottom-0 left-0 w-full p-6 flex justify-between items-center" style="background: hsl(210 30% 97% / 0.8); backdrop-filter: blur(12px); pointer-events: none;">
        <div class="flex items-center gap-6">
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Window</span>
                <span class="text-[11px] font-mono" id="footerWindow" style="color: var(--foreground);"><?php echo $window_name; ?></span>
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Last Sync</span>
                <span class="text-[11px] font-mono tabular-nums" id="footerTime" style="color: var(--foreground);">--:--:--</span>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1 rounded shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
            <div class="w-1.5 h-1.5 rounded-full" style="background: var(--primary);"></div>
            <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">window v1.0</span>
        </div>
    </footer>

    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
    <div id="newTicketNotifContainer" class="fixed bottom-4 right-4 z-50 flex flex-col-reverse gap-2" style="max-width: 380px; pointer-events: none;"></div>

    <!-- Remark Prompt Modal -->
    <div id="remarkOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-1" id="remarkActionLabel">Remark</h3>
            <p class="text-xs mb-4" style="color: var(--muted);">Enter a remark for this action (required):</p>
            <textarea id="remarkInput" rows="3" class="w-full px-4 py-3 rounded text-sm" style="border: 1px solid var(--border); resize: vertical;" placeholder="Describe what was done or the reason..." oninput="document.getElementById('remarkConfirm').disabled = this.value.trim() === ''"></textarea>
            <div class="flex gap-3 mt-4">
                <button id="remarkCancel" class="btn btn-secondary flex-1 py-2 text-sm">Cancel</button>
                <button id="remarkConfirm" class="btn btn-primary flex-1 py-2 text-sm" disabled>Confirm</button>
            </div>
        </div>
    </div>

    <!-- Forward Follow-Up Modal -->
    <div id="forwardOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md p-6">
            <h3 class="text-lg font-bold mb-1">Forward Follow-Up</h3>
            <p class="text-xs mb-4" style="color: var(--muted);">Send this ticket to another counter's follow-up queue.</p>
            <label class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color: var(--muted);">Target Counter</label>
            <select id="forwardTarget" class="w-full px-4 py-3 rounded text-sm mb-3" style="border: 1px solid var(--border); background: var(--background); color: var(--foreground);" onchange="document.getElementById('forwardConfirm').disabled = !this.value || !document.getElementById('forwardRemark').value.trim()">
                <option value="">Select a counter...</option>
            </select>
            <label class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color: var(--muted);">Remark (required)</label>
            <textarea id="forwardRemark" rows="3" class="w-full px-4 py-3 rounded text-sm" style="border: 1px solid var(--border); resize: vertical;" placeholder="Explain why this is being forwarded..." oninput="document.getElementById('forwardConfirm').disabled = !this.value.trim() || !document.getElementById('forwardTarget').value"></textarea>
            <div class="flex gap-3 mt-4">
                <button id="forwardCancel" class="btn btn-secondary flex-1 py-2 text-sm">Cancel</button>
                <button id="forwardConfirm" class="btn btn-primary flex-1 py-2 text-sm" disabled><i class="fas fa-share mr-1"></i>Forward</button>
            </div>
        </div>
    </div>

    <script>
        var windowId = <?php echo $window_id ?: 'null'; ?>;
        var currentUserId = <?php echo $user['id']; ?>;
        var currentFilter = 'all';
        var searchQuery = '';
        var sessionStart = Date.now();
        var currentStatus = '';
        var _knownTicketIds = {};
        var _knownForwardedIds = {};
        var _initialLoad = true;
        var _lastRefreshToken = 0;
        var _originalTitle = '';
        var _speechLoopCount = 0;

        function isCounterOnline() {
            return currentStatus === 'Online';
        }

        function _playNewTicketAlert() {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            _speechLoopCount = 0;
            _speakLoop();
        }

        function _speakLoop() {
            if (_speechLoopCount >= 3) return;
            var u = new SpeechSynthesisUtterance('New ticket waiting');
            u.lang = 'en-US';
            u.rate = 0.9;
            u.onend = function() { _speechLoopCount++; _speakLoop(); };
            window.speechSynthesis.speak(u);
        }

        function _flashTitle() {
            _originalTitle = document.title;
            var flashes = 0;
            var interval = setInterval(function() {
                document.title = flashes % 2 === 0 ? '🔔 New Ticket!' : '● New Ticket!';
                flashes++;
                if (flashes >= 8) { clearInterval(interval); document.title = _originalTitle; }
            }, 400);
        }

        function _showNewTicketNotif(customers) {
            var container = document.getElementById('newTicketNotifContainer');
            if (!container) return;
            var el = document.createElement('div');
            el.className = 'notif-toast';
            el.style.cssText = 'pointer-events:auto;background:#1e293b;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:16px 20px;box-shadow:0 12px 40px rgba(0,0,0,0.5);color:white;cursor:pointer;';

            var html = '<div class="flex items-center gap-2 mb-2"><span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:pulse 1.2s infinite;"></span><span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#94a3b8;">New Ticket';
            if (customers.length > 1) html += 's (' + customers.length + ')';
            html += '</span></div>';
            for (var i = 0; i < Math.min(customers.length, 3); i++) {
                var c = customers[i];
                var svcName = c.service_name || c.service_type || '';
                html += '<div class="flex items-center gap-4 py-1.5' + (i > 0 ? ' border-t border-white/10' : '') + '">' +
                        '<span style="font-size:22px;font-weight:900;font-family:var(--font-mono);letter-spacing:-0.03em;color:#fbbf24;">' + c.queue_number + '</span>' +
                        '<div><div style="font-size:14px;font-weight:600;">' + escapeHtml(c.name || '') + '</div>' +
                        '<div style="font-size:11px;color:#94a3b8;">' + escapeHtml(svcName) + '</div></div></div>';
            }
            if (customers.length > 3) html += '<div class="text-xs text-center pt-1.5" style="color:#64748b;">+ ' + (customers.length - 3) + ' more</div>';
            html += '<button onclick="event.stopPropagation();this.closest(\'.notif-toast\').remove()" class="absolute top-2 right-2 text-xs" style="background:none;border:none;color:#64748b;cursor:pointer;"><i class="fas fa-times"></i></button>';
            el.innerHTML = html;
            el.style.position = 'relative';
            container.appendChild(el);

            var timer = setTimeout(function() { dismissNotif(el); }, 6000);
            el.addEventListener('click', function() { clearTimeout(timer); dismissNotif(el); });
        }

        function dismissNotif(el) {
            if (el.classList.contains('dismissing')) return;
            el.classList.add('dismissing');
            setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 250);
        }

        var _forwardSpeechLoopCount = 0;
        function _playForwardAlert() {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();
            _forwardSpeechLoopCount = 0;
            _forwardSpeakLoop();
        }
        function _forwardSpeakLoop() {
            if (_forwardSpeechLoopCount >= 3) return;
            var u = new SpeechSynthesisUtterance('Follow-up ticket forwarded to you');
            u.lang = 'en-US';
            u.rate = 0.9;
            u.onend = function() { _forwardSpeechLoopCount++; _forwardSpeakLoop(); };
            window.speechSynthesis.speak(u);
        }

        function _flashForwardTitle() {
            _originalTitle = document.title;
            var flashes = 0;
            var interval = setInterval(function() {
                document.title = flashes % 2 === 0 ? '🔀 Follow-up Forwarded!' : '● Follow-up Forwarded!';
                flashes++;
                if (flashes >= 8) { clearInterval(interval); document.title = _originalTitle; }
            }, 400);
        }

        function _showForwardNotif(forwards) {
            var container = document.getElementById('newTicketNotifContainer');
            if (!container) return;
            var el = document.createElement('div');
            el.className = 'notif-toast';
            el.style.cssText = 'pointer-events:auto;background:#1e293b;border:1px solid rgba(251,191,36,0.3);border-radius:12px;padding:16px 20px;box-shadow:0 12px 40px rgba(0,0,0,0.5);color:white;cursor:pointer;position:relative;';

            var html = '<div class="flex items-center gap-2 mb-2"><span style="width:8px;height:8px;border-radius:50%;background:#fbbf24;display:inline-block;animation:pulse 1.2s infinite;"></span><span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#fbbf24;">Follow-up Forwarded';
            if (forwards.length > 1) html += ' (' + forwards.length + ')';
            html += '</span></div>';
            for (var i = 0; i < Math.min(forwards.length, 3); i++) {
                var c = forwards[i];
                html += '<div class="flex items-center gap-4 py-1.5' + (i > 0 ? ' border-t border-white/10' : '') + '">' +
                        '<span style="font-size:22px;font-weight:900;font-family:var(--font-mono);letter-spacing:-0.03em;color:#fbbf24;">' + escapeHtml(c.queue_number) + '</span>' +
                        '<div><div style="font-size:14px;font-weight:600;">' + escapeHtml(c.name || '') + '</div>' +
                        '<div style="font-size:11px;color:#94a3b8;">From ' + escapeHtml(c.forwarded_by_name || 'Unknown') +
                        (c.forward_remark ? ' — "' + escapeHtml(c.forward_remark) + '"' : '') + '</div></div></div>';
            }
            if (forwards.length > 3) html += '<div class="text-xs text-center pt-1.5" style="color:#64748b;">+ ' + (forwards.length - 3) + ' more</div>';
            html += '<button onclick="event.stopPropagation();this.closest(\'.notif-toast\').remove()" class="absolute top-2 right-2 text-xs" style="background:none;border:none;color:#64748b;cursor:pointer;"><i class="fas fa-times"></i></button>';
            el.innerHTML = html;
            container.appendChild(el);
            var timer = setTimeout(function() { dismissNotif(el); }, 8000);
            el.addEventListener('click', function() { clearTimeout(timer); dismissNotif(el); });
        }

        function getAuthHeaders() {
            var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
            if (token) return { 'Authorization': 'Bearer ' + token };
            return {};
        }

        async function apiFetch(url, options) {
            options = options || {};
            options.headers = Object.assign(getAuthHeaders(), options.headers || {});
            return fetch(url, options);
        }

        function showToast(message, type) {
            var container = document.getElementById('toastContainer');
            if (!container) return;
            var toast = document.createElement('div');
            var bg = '#2563eb';
            if (type === 'success') bg = '#059669';
            if (type === 'error') bg = '#dc2626';
            if (type === 'warning') bg = '#d97706';
            toast.className = 'toast text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-3 mb-2 text-sm font-medium';
            toast.style.background = bg;
            toast.innerHTML = '<span>' + message + '</span>';
            container.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 4000);
        }

        function showSkeleton() {
            var table = document.getElementById('queueTable');
            if (!table) return;
            var sk = '';
            for (var i = 0; i < 3; i++) {
                sk += '<tr><td colspan="8"><div class="skeleton" style="height:1rem;width:6rem;margin:0.5rem 1rem;"></div></td></tr>';
            }
            table.innerHTML = sk;
        }

        function changeWindow(id) {
            if (id) window.location.href = 'window.php?window_id=' + id;
        }

        async function refreshQueue() {
            try {
                var url = 'api/get_queue.php';
                if (windowId) url += '?counter_id=' + windowId;
                var response = await apiFetch(url);
                var data = await response.json();
                if (data.success) {
                    if (data.force_refresh_token && _lastRefreshToken && data.force_refresh_token !== _lastRefreshToken) {
                        location.reload();
                        return;
                    }
                    if (data.force_refresh_token) _lastRefreshToken = data.force_refresh_token;
                    updateQueueTable(data.customers || []);
                    updateServingDisplay(data.customers || []);
                    if (data.counters) { updateWindowStatus(data.counters); _latestCounters = data.counters; }
                    if (!_initialLoad) {
                        var newTickets = [];
                        for (var ni = 0; ni < data.customers.length; ni++) {
                            var nc = data.customers[ni];
                            if (nc.status === 'waiting' && !_knownTicketIds[nc.id]) newTickets.push(nc);
                            _knownTicketIds[nc.id] = true;
                        }
                        if (newTickets.length > 0) {
                            _playNewTicketAlert();
                            _flashTitle();
                            _showNewTicketNotif(newTickets);
                        }

                        var newForwards = [];
                        for (var fi = 0; fi < data.customers.length; fi++) {
                            var fc = data.customers[fi];
                            if (fc.is_follow_up == 1 && fc.forwarded_to_counter_id && fc.forwarded_to_counter_id == windowId && fc.forwarded_at) {
                                var prevForward = _knownForwardedIds[fc.id];
                                if (!prevForward || prevForward !== fc.forwarded_at) {
                                    newForwards.push(fc);
                                }
                                _knownForwardedIds[fc.id] = fc.forwarded_at;
                            }
                        }
                        if (newForwards.length > 0) {
                            _playForwardAlert();
                            _flashForwardTitle();
                            _showForwardNotif(newForwards);
                        }
                    } else {
                        for (var ni = 0; ni < data.customers.length; ni++) {
                            _knownTicketIds[data.customers[ni].id] = true;
                            if (data.customers[ni].forwarded_at) {
                                _knownForwardedIds[data.customers[ni].id] = data.customers[ni].forwarded_at;
                            }
                        }
                        _initialLoad = false;
                    }
                }
            } catch (e) { console.error('Queue Error:', e); }
        }

        async function refreshStats() {
            try {
                var url = 'api/get_stats.php';
                if (windowId) url += '?counter_id=' + windowId;
                var response = await apiFetch(url);
                var res = await response.json();
                if (res.success && res.data) {
                    var stats = res.data.basic || {};
                    document.getElementById('waiting-count').textContent = (stats.waiting !== undefined) ? stats.waiting : 0;
                    document.getElementById('serving-count').textContent = (stats.serving !== undefined) ? stats.serving : 0;
                    document.getElementById('completed-count').textContent = (stats.completed !== undefined) ? stats.completed : 0;
                    document.getElementById('today-count').textContent = (stats.today_total !== undefined) ? stats.today_total : 0;

                    var timings = res.data.timings || {};
                    if (document.getElementById('sessionServed')) document.getElementById('sessionServed').textContent = stats.completed || 0;
                    if (document.getElementById('sessionNoshows')) document.getElementById('sessionNoshows').textContent = stats.cancelled || 0;
                    if (document.getElementById('sessionAvgHandle')) document.getElementById('sessionAvgHandle').textContent = timings.avg_service_formatted || '0:00';
                    if (document.getElementById('sessionIdle')) document.getElementById('sessionIdle').textContent = timings.idle_formatted || '--';

                    if (res.data.counters && res.data.counters.length > 0) {
                        var c = res.data.counters[0];
                        updateCounterDetail(c);
                    }
                }
            } catch (e) { console.error('Stats Error:', e); }
        }

        function updateQueueTable(customers) {
            var table = document.getElementById('queueTable');
            if (!table) return;
            var filtered;
            if (currentFilter === 'all') {
                filtered = customers;
            } else if (currentFilter === 'follow_up') {
                filtered = customers.filter(function(c) { return c.is_follow_up == 1; });
            } else {
                filtered = customers.filter(function(c) { return c.status === currentFilter; });
            }
            if (searchQuery) {
                filtered = filtered.filter(function(c) {
                    return (c.name && c.name.toLowerCase().indexOf(searchQuery) !== -1) ||
                           (c.queue_number && c.queue_number.toLowerCase().indexOf(searchQuery) !== -1);
                });
            }
            if (filtered.length === 0) {
                table.innerHTML = '<tr><td colspan="8" style="padding:2rem;text-align:center;color:var(--muted);">No customers</td></tr>';
                renderFollowUpList(customers);
                return;
            }
            var html = '';
            for (var i = 0; i < filtered.length; i++) {
                var c = filtered[i];
                var statusClass = 'badge';
                if (c.status === 'waiting') statusClass += ' badge-primary';
                if (c.status === 'serving') statusClass += ' badge-online';
                if (c.status === 'completed') statusClass += ' badge';
                var followUpBadge = c.is_follow_up == 1 ? '<span class="badge badge-break ml-1"><i class="fas fa-flag mr-0.5"></i>FU</span>' : '';
                var statusHtml = '<span class="' + statusClass + '">' + c.status + '</span>' + followUpBadge;
                var actions = '';
                if (c.status === 'waiting') {
                    actions = '<button onclick="callCustomer(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2.5"><i class="fas fa-bullhorn text-xs mr-1"></i>Call</button>';
                } else if (c.status === 'serving') {
                    actions = '<button onclick="recallCustomer(' + c.id + ')" class="btn btn-secondary text-[10px] py-1 px-2 mr-1" title="Re-announce"><i class="fas fa-bell text-xs"></i></button>' +
                              '<button onclick="completeCustomer(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2.5"><i class="fas fa-check text-xs mr-1"></i>Complete</button>';
                }
                if (c.status === 'serving' || c.status === 'completed') {
                    if (c.is_follow_up == 1) {
                        actions += '<button onclick="completeFollowUp(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2 ml-1" title="Complete follow-up"><i class="fas fa-check text-xs mr-0.5"></i>Complete</button>' +
                                   '<button onclick="rejectFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Reject follow-up" style="color:var(--destructive);"><i class="fas fa-times text-xs mr-0.5"></i>Reject</button>';
                    } else {
                        actions += '<button onclick="markFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Mark as follow-up"><i class="fas fa-flag text-xs mr-0.5"></i>FU</button>';
                    }
                }
                var companyDisplay = c.company_name || '';
                var purposeDisplay = c.purpose ? c.purpose.charAt(0).toUpperCase() + c.purpose.slice(1) : '';
                var rowClass = c.status === 'serving' ? ' class="is-serving"' : '';
                var remarkHtml = c.remark ? '<br><span class="text-[10px] italic" style="color: hsl(215 15% 35%);">' + escapeHtml(c.remark) + '</span>' : '';
                html += '<tr' + rowClass + '>' +
                        '<td><span style="font-family: var(--font-mono); font-weight: 700; font-size: 13px; color: var(--foreground); letter-spacing: -0.02em;">' + c.queue_number + '</span></td>' +
                        '<td style="font-weight: 600; color: hsl(215 35% 12%);">' + c.name + remarkHtml + '</td>' +
                        '<td class="text-xs" style="color: hsl(215 15% 35%);">' + formatService(c) + '</td>' +
                        '<td class="text-xs" style="color: hsl(215 15% 35%);">' + companyDisplay + '</td>' +
                        '<td class="text-xs" style="color: hsl(215 15% 35%);">' + purposeDisplay + '</td>' +
                        '<td>' + statusHtml + '</td>' +
                        '<td class="font-mono text-xs" style="color: hsl(215 10% 40%);">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                        '<td>' + actions + '</td></tr>';
            }
            var scrollContainer = table.closest('.overflow-x-auto') || table.parentElement;
            var scrollTop = scrollContainer ? scrollContainer.scrollTop : 0;
            table.innerHTML = html;
            if (scrollContainer) scrollContainer.scrollTop = scrollTop;
            renderFollowUpList(customers);
        }

        function updateServingDisplay(customers) {
            var num = document.getElementById('servingNumber');
            var info = document.getElementById('servingInfo');
            if (!num) return;
            var serving = null;
            for (var i = 0; i < customers.length; i++) {
                if (customers[i].status === 'serving') { serving = customers[i]; break; }
            }
            if (serving) {
                num.textContent = serving.queue_number;
                if (info) info.textContent = (serving.name || '') + ' — ' + (serving.service_name || serving.service_type || '') + (serving.service_type === 'custom' && serving.serving_custom_description ? ' (' + serving.serving_custom_description + ')' : '');
            } else {
                num.textContent = '---';
                if (info) info.textContent = 'No active customer';
            }
        }

        function updateWindowStatus(counters) {
            if (!windowId || !counters) return;
            var c = null;
            for (var i = 0; i < counters.length; i++) {
                if (counters[i].id == windowId) { c = counters[i]; break; }
            }
            if (!c) return;
            currentStatus = c.status_text || '';
            var dot = document.getElementById('counterStatusDot');
            var text = document.getElementById('counterStatusText');
            var badge = document.getElementById('windowStatusBadge');
            var select = document.querySelector('select[onchange*="changeWindowStatus"]');
            if (dot) {
                dot.className = 'status-dot';
                if (c.status_text === 'Online') dot.classList.add('online');
                else if (c.status_text === 'On Break') dot.classList.add('break');
                else dot.classList.add('offline');
            }
            if (text) text.textContent = c.status_text;
            if (badge) badge.textContent = c.status_text;
            if (select) select.value = c.status_text;
        }

        function updateCounterDetail(c) {
            var el = document.getElementById('windowDetail');
            if (!el) return;
            var windowLabel = c.display_name || c.name || 'Window ' + (c.window_number || c.id);
            var services = c.active_services || c.display_name || 'None';
            el.innerHTML = '<div class="p-4 space-y-2">' +
                '<div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-wider" style="color:var(--muted);">Window</span><span class="text-sm font-bold">' + windowLabel + '</span></div>' +
                '<div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-wider" style="color:var(--muted);">Services</span><span class="text-xs" style="color:var(--foreground);">' + services + '</span></div>' +
                '<div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-wider" style="color:var(--muted);">Served</span><span class="text-sm font-bold font-mono">' + (c.customers_served || 0) + '</span></div>' +
                '<div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-wider" style="color:var(--muted);">Avg Time</span><span class="text-sm font-bold font-mono">' + formatDuration(c.avg_service_time) + '</span></div>' +
                '<div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-wider" style="color:var(--muted);">Currently</span><span class="text-sm font-bold" style="color:var(--primary);">' + (c.currently_serving > 0 ? 'Serving (' + c.currently_serving + ')' : 'Available') + '</span></div>' +
                '</div>';
        }

        function formatDuration(sec) {
            if (!sec || sec == 0) return '0:00';
            var m = Math.floor(sec / 60);
            var s = Math.floor(sec % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        function formatService(c) {
            var base = c.service_name || c.service_type || '';
            if (c.service_type === 'custom' && c.custom_description) base += ' (' + c.custom_description + ')';
            return base;
        }

        function formatServingService(svc) {
            var base = svc.service_name || svc.service_type || '';
            if (svc.service_type === 'custom' && svc.serving_custom_description) base += ' (' + svc.serving_custom_description + ')';
            return base;
        }

        // ====== Action functions ======

        function requireRemark(actionLabel, callback) {
            var overlay = document.getElementById('remarkOverlay');
            document.getElementById('remarkActionLabel').textContent = actionLabel;
            document.getElementById('remarkInput').value = '';
            document.getElementById('remarkConfirm').disabled = true;
            document.getElementById('remarkConfirm').onclick = function() {
                var remark = document.getElementById('remarkInput').value.trim();
                overlay.classList.add('hidden');
                callback(remark);
            };
            document.getElementById('remarkCancel').onclick = function() {
                overlay.classList.add('hidden');
            };
            overlay.classList.remove('hidden');
            setTimeout(function() { document.getElementById('remarkInput').focus(); }, 100);
        }

        async function callNext() {
            if (!isCounterOnline()) { showToast('Cannot call next — window is not online. Set status to Online first.', 'error'); return; }
            try {
                var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                var d = await r.json();
                if (!d.success) return;
                var serving = null;
                for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                if (serving) {
                    requireRemark('Complete & Next', async function(remark) {
                        try {
                            await apiFetch('api/complete_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, remark: remark }) });
                            callNextWaiting(d);
                        } catch (e) { showToast('Error completing customer', 'error'); }
                    });
                } else {
                    callNextWaiting(d);
                }
            } catch (e) { showToast('Error in Complete & Next', 'error'); }
        }

        function callNextWaiting(d) {
            if (!isCounterOnline()) { refreshQueue(); refreshStats(); return; }
            var waiting = null;
            for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
            if (waiting) {
                apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id, counter_id: windowId }) });
            }
            refreshQueue(); refreshStats();
        }

        async function skipCustomer() {
            try {
                var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                var d = await r.json();
                if (!d.success) return;
                var serving = null;
                for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                if (serving) {
                    await apiFetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'skipped' }) });
                }
                refreshQueue(); refreshStats();
            } catch (e) { showToast('Error skipping customer', 'error'); }
        }

        async function noShow() {
            if (!isCounterOnline()) { showToast('Cannot mark no-show — window is not online. Set status to Online first.', 'error'); return; }
            try {
                var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                var d = await r.json();
                if (!d.success) return;
                var serving = null;
                for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                if (serving) {
                    await apiFetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'no-show' }) });
                }
                var waiting = null;
                for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
                if (waiting) {
                    await apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id, counter_id: windowId }) });
                }
                refreshQueue(); refreshStats();
            } catch (e) { showToast('Error in No-Show', 'error'); }
        }

        async function callCustomer(id) {
            if (!isCounterOnline()) { showToast('Cannot call customer — window is not online. Set status to Online first.', 'error'); return; }
            try {
                await apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id, counter_id: windowId }) });
                refreshQueue(); refreshStats();
            } catch (e) { showToast('Error calling customer', 'error'); }
        }

        async function recallCustomer(id) {
            if (!isCounterOnline()) { showToast('Cannot recall customer — window is not online. Set status to Online first.', 'error'); return; }
            try {
                var body = { customer_id: id };
                if (windowId) body.counter_id = windowId;
                await apiFetch('api/recall_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                showToast('Customer recalled', 'success');
                refreshQueue();
            } catch (e) { showToast('Error recalling customer', 'error'); }
        }

        async function completeCustomer(id) {
            requireRemark('Complete', async function(remark) {
                try {
                    await apiFetch('api/complete_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id, remark: remark }) });
                    refreshQueue(); refreshStats();
                } catch (e) { showToast('Error completing customer', 'error'); }
            });
        }

        async function changeWindowStatus(counterId, status) {
            try {
                var response = await apiFetch('api/counter/toggle_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ counter_id: counterId, status: status })
                });
                var data = await response.json();
                if (data.success) {
                    showToast('Status updated to ' + status, 'success');
                    refreshQueue(); refreshStats();
                } else {
                    showToast(data.message || 'Failed to update status', 'error');
                    refreshQueue();
                }
            } catch (e) {
                showToast('Error updating status', 'error');
                refreshQueue();
            }
        }

        // ====== Filter ======
        function filterQueue(filter) {
            currentFilter = filter;
            var btns = document.querySelectorAll('.filter-btn');
            for (var i = 0; i < btns.length; i++) {
                var btn = btns[i];
                if (btn.getAttribute('data-filter') === filter) {
                    btn.className = 'filter-btn active';
                } else {
                    btn.className = 'filter-btn';
                }
            }
            refreshQueue();
        }

        function onSearchInput(value) {
            searchQuery = value.trim().toLowerCase();
            refreshQueue();
        }

        // ====== Follow-up ======

        var _latestCounters = [];

        function renderFollowUpList(allCustomers) {
            var el = document.getElementById('followUpList');
            if (!el) return;
            var followUps = allCustomers.filter(function(c) { return c.is_follow_up == 1; });
            if (followUps.length === 0) {
                el.innerHTML = '<div class="text-center py-6 text-sm" style="color: var(--muted);">No follow-up tickets</div>';
                document.getElementById('followUpPanel').style.display = 'none';
                return;
            }
            document.getElementById('followUpPanel').style.display = 'block';
            document.getElementById('followUpCount').textContent = followUps.length;
            var html = '';
            for (var i = 0; i < followUps.length; i++) {
                var c = followUps[i];
                var fwdInfo = '';
                if (c.forwarded_by_user_id && c.forwarded_by_user_id == currentUserId && c.forwarded_to_name) {
                    var fwdLabel = c.forwarded_to_description ? c.forwarded_to_name + ' — ' + c.forwarded_to_description : c.forwarded_to_name;
                    fwdInfo = '<div class="mt-1.5 pl-8">' +
                        '<span class="text-[10px] italic" style="color: var(--brand-gold);"><i class="fas fa-share mr-1"></i>Forwarded to ' + escapeHtml(fwdLabel) + '</span>';
                    if (c.forward_remark) {
                        fwdInfo += '<div class="text-[10px] mt-0.5 pl-3" style="color: var(--muted);">"' + escapeHtml(c.forward_remark) + '"</div>';
                    }
                    fwdInfo += '</div>';
                } else if (c.forwarded_by_name) {
                    fwdInfo = '<div class="mt-1.5 pl-8">' +
                        '<span class="text-[10px] italic" style="color: var(--brand-gold);"><i class="fas fa-share mr-1"></i>Forwarded by ' + escapeHtml(c.forwarded_by_name) + '</span>';
                    if (c.forward_remark) {
                        fwdInfo += '<div class="text-[10px] mt-0.5 pl-3" style="color: var(--muted);">"' + escapeHtml(c.forward_remark) + '"</div>';
                    }
                    fwdInfo += '</div>';
                }
                html += '<div class="p-3 border-b border-border">' +
                        '<div class="flex items-center justify-between">' +
                            '<div class="flex items-center gap-3">' +
                                '<span class="text-[10px] font-mono w-5 tabular-nums" style="color:var(--muted);">' + (i+1) + '</span>' +
                                '<div class="flex flex-col">' +
                                    '<div class="flex items-baseline gap-2">' +
                                        '<span class="font-mono text-base font-bold tracking-tight" style="color:var(--brand-gold);">' + c.queue_number + '</span>' +
                                        '<span class="text-xs font-medium truncate" style="color:var(--foreground);">' + escapeHtml(c.name || '') + '</span>' +
                                    '</div>' +
                                    '<span class="text-[10px] uppercase tracking-wider" style="color:var(--muted);">' + formatService(c) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '<div class="flex gap-1 items-center">' +
                                '<button onclick="recallCustomer(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Re-announce" style="color:var(--brand-gold);"><i class="fas fa-bell text-xs"></i></button>' +
                                '<div class="relative">' +
                                    '<button onclick="toggleFwdDropdown(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2.5" id="fwdBtn' + c.id + '"><i class="fas fa-check text-xs mr-1"></i>Complete <i class="fas fa-caret-down ml-1"></i></button>' +
                                    '<div id="fwdMenu' + c.id + '" class="hidden absolute right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-40" style="min-width: 150px;">' +
                                        '<button onclick="closeFwdDropdown(' + c.id + '); completeFollowUp(' + c.id + ')" class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 rounded-t-lg" style="color: var(--foreground);"><i class="fas fa-check mr-2 text-green-600"></i>Complete</button>' +
                                        '<button onclick="closeFwdDropdown(' + c.id + '); openForwardModal(' + c.id + ')" class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 rounded-b-lg" style="color: var(--foreground);"><i class="fas fa-share mr-2 text-blue-600"></i>Forward</button>' +
                                    '</div>' +
                                '</div>' +
                                '<button onclick="rejectFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Reject" style="color:var(--destructive);"><i class="fas fa-times"></i></button>' +
                            '</div>' +
                        '</div>' +
                        fwdInfo +
                        '</div>';
                }
                el.innerHTML = html;
        }

        function toggleFwdDropdown(id) {
            var menu = document.getElementById('fwdMenu' + id);
            if (!menu) return;
            var wasHidden = menu.classList.contains('hidden');
            closeAllFwdDropdowns();
            if (wasHidden) menu.classList.remove('hidden');
        }

        function closeFwdDropdown(id) {
            var menu = document.getElementById('fwdMenu' + id);
            if (menu) menu.classList.add('hidden');
        }

        function closeAllFwdDropdowns() {
            var menus = document.querySelectorAll('[id^="fwdMenu"]');
            for (var i = 0; i < menus.length; i++) menus[i].classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="fwdBtn"]') && !e.target.closest('[id^="fwdMenu"]')) {
                closeAllFwdDropdowns();
            }
        });

        function openForwardModal(customerId) {
            var overlay = document.getElementById('forwardOverlay');
            var targetSel = document.getElementById('forwardTarget');
            var remarkEl = document.getElementById('forwardRemark');
            var confirmBtn = document.getElementById('forwardConfirm');
            targetSel.innerHTML = '<option value="">Select a counter...</option>';
            remarkEl.value = '';
            confirmBtn.disabled = true;
            for (var i = 0; i < _latestCounters.length; i++) {
                var ct = _latestCounters[i];
                if (ct.is_online != 1 || ct.status_text === 'Offline') continue;
                if (windowId && ct.id == windowId) continue;
                var opt = document.createElement('option');
                opt.value = ct.id;
                opt.textContent = ct.display_name || ct.name || ('Window ' + ct.window_number);
                if (ct.description) opt.textContent += ' — ' + ct.description;
                targetSel.appendChild(opt);
            }
            confirmBtn.onclick = function() {
                var targetId = targetSel.value;
                var remark = remarkEl.value.trim();
                if (!targetId || !remark) return;
                overlay.classList.add('hidden');
                apiFetch('api/forward_followup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: customerId, target_counter_id: parseInt(targetId), remark: remark })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.success) {
                        showToast('Follow-up forwarded', 'success');
                        refreshQueue(); refreshStats();
                    } else {
                        showToast(data.message || 'Failed to forward', 'error');
                    }
                }).catch(function() { showToast('Error forwarding follow-up', 'error'); });
            };
            document.getElementById('forwardCancel').onclick = function() {
                overlay.classList.add('hidden');
            };
            overlay.classList.remove('hidden');
            setTimeout(function() { targetSel.focus(); }, 100);
        }

        function completeFollowUp(id) {
            requireRemark('Complete Follow-Up', function(remark) {
                apiFetch('api/complete_followup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: id, remark: remark })
                }).then(function(r){ return r.json(); }).then(function(data) {
                    if (data.success) {
                        showToast('Follow-up completed', 'success');
                        refreshQueue(); refreshStats();
                    } else {
                        showToast(data.message || 'Failed', 'error');
                    }
                }).catch(function() { showToast('Error completing follow-up', 'error'); });
            });
        }

        function rejectFollowUp(id) {
            requireRemark('Reject Follow-Up', function(remark) {
                apiFetch('api/reject_followup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: id, remark: remark })
                }).then(function(r){ return r.json(); }).then(function(data) {
                    if (data.success) {
                        showToast('Follow-up removed', 'success');
                        refreshQueue();
                    } else {
                        showToast(data.message || 'Failed', 'error');
                    }
                }).catch(function() { showToast('Error rejecting follow-up', 'error'); });
            });
        }

        function markFollowUp(id) {
            if (!confirm('Mark this ticket as a follow-up?')) return;
            apiFetch('api/toggle_followup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ customer_id: id })
            }).then(function(r){ return r.json(); }).then(function(data) {
                if (data.success) {
                    showToast(data.message || 'Marked as follow-up', 'success');
                    refreshQueue();
                } else {
                    showToast(data.message || 'Failed', 'error');
                }
            }).catch(function() { showToast('Error marking follow-up', 'error'); });
        }

        // ====== Init ======

        function updateFooterTime() {
            var el = document.getElementById('footerTime');
            if (!el) return;
            var d = new Date();
            el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
        }
        setInterval(updateFooterTime, 200);
        updateFooterTime();

        setInterval(function() {
            var el = document.getElementById('sessionTime');
            if (!el) return;
            var sec = Math.floor((Date.now() - sessionStart) / 1000);
            var m = Math.floor(sec / 60);
            var s = sec % 60;
            el.textContent = 'SESSION ' + m + 'm ' + String(s).padStart(2,'0') + 's';
        }, 1000);

        function logout() {
            if (confirm('Sign out of Operator Console?')) {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                if (token) {
                    fetch('api/auth/logout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ token: token }) });
                }
                sessionStorage.removeItem('auth_token');
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_data');
                sessionStorage.removeItem('user_data');
                document.cookie = 'auth_token=; path=/; max-age=0; SameSite=Lax';
                window.location.href = 'login.php';
            }
        }

        function escapeHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        // ====== Window Announcements ======
        function updateAnnounceCharCount() {
            var input = document.getElementById('announcementInput');
            var count = document.getElementById('announceCharCount');
            if (input && count) count.textContent = input.value.length + '/100';
        }

        async function loadWindowAnnouncements() {
            if (!windowId) return;
            try {
                var res = await apiFetch('api/window_announcement.php?counter_id=' + windowId);
                var result = await res.json();
                var el = document.getElementById('windowAnnouncementList');
                if (!el) return;
                if (!result.success || !result.data || result.data.length === 0) {
                    el.innerHTML = '<div class="text-center py-3 text-[11px]" style="color: var(--muted);">No announcements</div>';
                    return;
                }
                var html = '';
                for (var ai = 0; ai < result.data.length; ai++) {
                    var a = result.data[ai];
                    html += '<div class="flex items-start justify-between gap-2 px-3 py-2 rounded" style="background: var(--secondary);">' +
                        '<span class="text-[12px] font-medium" style="color: var(--foreground); flex:1;">' + escapeHtml(a.message) + '</span>' +
                        '<button onclick="deleteWindowAnnouncement(' + a.id + ')" class="shrink-0 w-5 h-5 flex items-center justify-center rounded hover:bg-red-50" style="color: #9ca3af;" title="Remove"><i class="fas fa-times text-[9px]"></i></button>' +
                    '</div>';
                }
                el.innerHTML = html;
            } catch (e) { console.error('Load window announcements error:', e); }
        }

        async function postAnnouncement() {
            var input = document.getElementById('announcementInput');
            if (!input) return;
            var msg = input.value.trim();
            if (!msg) { showToast('Message is required', 'error'); return; }
            if (msg.length > 100) { showToast('Message too long (max 100)', 'error'); return; }
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var authHeaders = token ? { 'Authorization': 'Bearer ' + token } : {};
                var res = await fetch('api/window_announcement.php', {
                    method: 'POST',
                    headers: Object.assign(authHeaders, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ message: msg, counter_id: windowId })
                });
                var result = await res.json();
                if (result.success) {
                    input.value = '';
                    updateAnnounceCharCount();
                    showToast('Announcement posted', 'success');
                    loadWindowAnnouncements();
                } else {
                    showToast(result.message || 'Failed to post', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        async function deleteWindowAnnouncement(id) {
            if (!confirm('Remove this announcement?')) return;
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var authHeaders = token ? { 'Authorization': 'Bearer ' + token } : {};
                var res = await fetch('api/window_announcement.php', {
                    method: 'DELETE',
                    headers: Object.assign(authHeaders, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ id: id })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Announcement removed', 'success');
                    loadWindowAnnouncements();
                } else {
                    showToast(result.message || 'Failed to remove', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        function init() {
            if (!windowId) {
                document.getElementById('servingNumber').textContent = '---';
                document.getElementById('servingInfo').textContent = 'Select a window above';
                document.querySelector('button[onclick*="callNext"]').disabled = true;
                document.querySelector('button[onclick*="skipCustomer"]').disabled = true;
                document.querySelector('button[onclick*="noShow"]').disabled = true;
                return;
            }
            refreshQueue();
            refreshStats();
            loadWindowAnnouncements();
            setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
        }

        window.onload = init;
        window.addEventListener('storage', function(e) {
            if (e.key === 'cq_settings_updated') location.reload();
        });
    </script>
</body>
</html>
