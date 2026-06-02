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
        .queue-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted); background: var(--secondary); border-bottom: 1px solid var(--border); }
        .queue-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.8125rem; }
        .queue-table tr:hover td { background: var(--secondary); }
        .skeleton { background: linear-gradient(90deg, var(--secondary) 25%, hsl(215 16% 92%) 50%, var(--secondary) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: var(--radius); }
        .toast { animation: slideIn 300ms var(--ease-out-expo); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
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
                        <div><div class="text-2xl font-bold tracking-tight tabular-nums" id="sessionIdle">--m</div><div class="text-[9px] font-medium uppercase tracking-wider" style="color: rgba(255,255,255,0.4);">Idle</div></div>
                    </div>
                </div>
            </div>

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

    <script>
        var windowId = <?php echo $window_id ?: 'null'; ?>;
        var currentFilter = 'all';
        var sessionStart = Date.now();

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
            showSkeleton();
            try {
                var url = 'api/get_queue.php';
                if (windowId) url += '?counter_id=' + windowId;
                var response = await apiFetch(url);
                var data = await response.json();
                if (data.success) {
                    updateQueueTable(data.customers || []);
                    updateServingDisplay(data.customers || []);
                    if (data.counters) updateWindowStatus(data.counters);
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
                        actions += '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Remove follow-up mark" style="color:var(--destructive);"><i class="fas fa-flag"></i></button>';
                    } else {
                        actions += '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Mark incomplete"><i class="fas fa-flag"></i></button>';
                    }
                }
                var companyDisplay = c.company_name || '';
                var purposeDisplay = c.purpose ? c.purpose.charAt(0).toUpperCase() + c.purpose.slice(1) : '';
                html += '<tr>' +
                        '<td class="font-bold font-mono" style="color: var(--foreground);">' + c.queue_number + '</td>' +
                        '<td style="color: var(--foreground);">' + c.name + '</td>' +
                        '<td style="color: var(--muted);">' + formatService(c) + '</td>' +
                        '<td class="text-xs" style="color: var(--muted);">' + companyDisplay + '</td>' +
                        '<td class="text-xs" style="color: var(--muted);">' + purposeDisplay + '</td>' +
                        '<td>' + statusHtml + '</td>' +
                        '<td class="font-mono text-xs" style="color: var(--muted);">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                        '<td>' + actions + '</td></tr>';
            }
            table.innerHTML = html;
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
            var windowLabel = 'Window ' + (c.window_number || c.id);
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
            requireRemark('Complete & Next', async function(remark) {
                try {
                    var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                    var d = await r.json();
                    if (!d.success) return;
                    var serving = null;
                    for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                    if (serving) {
                        await apiFetch('api/complete_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, remark: remark }) });
                    }
                    var waiting = null;
                    for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
                    if (waiting) {
                        await apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id }) });
                    }
                    refreshQueue(); refreshStats();
                } catch (e) { showToast('Error in Complete & Next', 'error'); }
            });
        }

        async function skipCustomer() {
            requireRemark('Skip', async function(remark) {
                try {
                    var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                    var d = await r.json();
                    if (!d.success) return;
                    var serving = null;
                    for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                    if (serving) {
                        await apiFetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'skipped', remark: remark }) });
                    }
                    refreshQueue(); refreshStats();
                } catch (e) { showToast('Error skipping customer', 'error'); }
            });
        }

        async function noShow() {
            requireRemark('No-Show', async function(remark) {
                try {
                    var r = await apiFetch('api/get_queue.php' + (windowId ? '?counter_id=' + windowId : ''));
                    var d = await r.json();
                    if (!d.success) return;
                    var serving = null;
                    for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
                    if (serving) {
                        await apiFetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'no-show', remark: remark }) });
                    }
                    var waiting = null;
                    for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
                    if (waiting) {
                        await apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id }) });
                    }
                    refreshQueue(); refreshStats();
                } catch (e) { showToast('Error in No-Show', 'error'); }
            });
        }

        async function callCustomer(id) {
            try {
                await apiFetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id }) });
                refreshQueue(); refreshStats();
            } catch (e) { showToast('Error calling customer', 'error'); }
        }

        async function recallCustomer(id) {
            try {
                await apiFetch('api/recall_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id }) });
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

        // ====== Follow-up ======

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
                html += '<div class="flex items-center justify-between p-3 border-b border-border">' +
                        '<div class="flex items-center gap-3">' +
                            '<span class="text-[10px] font-mono w-5 tabular-nums" style="color:var(--muted);">' + (i+1) + '</span>' +
                            '<div class="flex flex-col">' +
                                '<span class="font-mono text-sm font-bold tracking-tight" style="color:var(--brand-gold);">' + c.queue_number + '</span>' +
                                '<span class="text-[10px] uppercase tracking-wider" style="color:var(--muted);">' + formatService(c) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="flex gap-1">' +
                            '<button onclick="serveFollowUp(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2.5"><i class="fas fa-arrow-right text-xs mr-1"></i>Serve</button>' +
                            '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Remove" style="color:var(--destructive);"><i class="fas fa-times"></i></button>' +
                        '</div>' +
                        '</div>';
                }
                el.innerHTML = html;
        }

        async function toggleFollowUp(id) {
            try {
                var response = await apiFetch('api/toggle_followup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: id })
                });
                var data = await response.json();
                if (data.success) {
                    showToast(data.message, data.is_follow_up ? 'warning' : 'success');
                    refreshQueue();
                } else {
                    showToast(data.message || 'Failed', 'error');
                }
            } catch (e) { showToast('Error toggling follow-up', 'error'); }
        }

        async function serveFollowUp(id) {
            if (!confirm('Serve this follow-up ticket now?')) return;
            try {
                var response = await apiFetch('api/serve_followup.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ customer_id: id })
                });
                var data = await response.json();
                if (data.success) {
                    showToast('Follow-up ticket served', 'success');
                    refreshQueue(); refreshStats();
                } else {
                    showToast(data.message || 'Failed', 'error');
                }
            } catch (e) { showToast('Error serving follow-up', 'error'); }
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
            setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
        }

        window.onload = init;
    </script>
</body>
</html>
