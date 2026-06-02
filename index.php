<?php include 'config.php';
$user = requireAuth();
if (!$user) { header('Location: login.php'); exit; }
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($s['branch_name'] ?? '');
$company_logo = htmlspecialchars($s['company_logo'] ?? '');
$display_name = htmlspecialchars($user['display_name'] ?? 'Admin');
$user_role = htmlspecialchars($user['role'] ?? 'staff');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Desk — <?php echo $company_name; ?></title>
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
        .modal-overlay { backdrop-filter: blur(4px); }
        .toast { animation: slideIn 300ms var(--ease-out-expo); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .skeleton { background: linear-gradient(90deg, var(--secondary) 25%, hsl(215 16% 92%) 50%, var(--secondary) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: var(--radius); }
        .queue-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted); background: var(--secondary); border-bottom: 1px solid var(--border); }
        .queue-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.8125rem; }
        .queue-table tr:hover td { background: var(--secondary); }
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
                    <a href="index.php" class="px-3 py-1.5 rounded" style="background: rgba(255,255,255,0.1); color: white;">Operator</a>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded" style="background: rgba(255,255,255,0.1);">
                    <div class="w-1.5 h-1.5 rounded-full" style="background: #34d399; animation: pulse-dot 2s infinite;"></div>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">All Systems Operational</span>
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
                <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Operator Console &middot; Main Desk</h2>
                <span class="font-mono text-[10px]" style="color: var(--muted);" id="sessionTime">SESSION --</span>
            </div>

            <!-- Windows Serving Grid -->
            <div class="animate-entry card rounded-xl p-6 shadow-sm" style="border: 1px solid var(--border);">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Windows Serving</span>
                    <?php if ($user_role === 'staff'): ?><a href="window.php" class="text-[10px] font-semibold uppercase tracking-wider px-3 py-1.5 rounded" style="background: var(--primary); color: var(--primary-foreground);"><i class="fas fa-external-link-alt mr-1"></i>Open Window Portal</a><?php endif; ?>
                </div>
                <div id="servingGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3"></div>
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
                <div id="followUpList" class="divide-y divide-border">
                    <!-- Injected by JS -->
                </div>
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
                    <h3 class="text-xs font-bold uppercase tracking-widest">Active Waiting List</h3>
                    <span class="text-[10px] font-medium uppercase tracking-tighter font-mono" style="color: var(--muted);"><span id="queueCount">0</span> pending</span>
                </div>
                <div class="p-4 border-b border-border flex flex-wrap gap-2" style="background: var(--secondary);">
                    <button onclick="filterQueue('all')" class="filter-btn active" data-filter="all">All</button>
                    <button onclick="filterQueue('waiting')" class="filter-btn" data-filter="waiting">Waiting</button>
                    <button onclick="filterQueue('serving')" class="filter-btn" data-filter="serving">Serving</button>
                    <button onclick="filterQueue('completed')" class="filter-btn" data-filter="completed">Completed</button>
                    <button onclick="filterQueue('follow_up')" class="filter-btn" data-filter="follow_up">Follow-up</button>
                    <div class="ml-auto flex gap-2">
                        <button onclick="openAnnouncementModal()" class="btn btn-ghost text-[10px] py-1 px-2"><i class="fas fa-bullhorn mr-1"></i>Announce</button>
                        <button onclick="refreshQueue()" class="btn btn-ghost text-[10px] py-1 px-2"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full queue-table">
                        <thead>
                            <tr><th>Queue No.</th><th>Customer</th><th>Service</th><th>Company</th><th>Purpose</th><th>Status</th><th>Time</th><th>Remark</th></tr>
                        </thead>
                        <tbody id="queueTable"></tbody>
                    </table>
                </div>
            </div>

            <!-- Redistribution History -->
            <div class="card p-6 animate-entry" style="animation-delay: 250ms;">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Redistribution History</h3>
                <div id="redistributionLogs" class="space-y-2 max-h-48 overflow-y-auto civic-scrollbar text-sm"></div>
            </div>
        </section>

        <!-- Sidebar -->
        <aside class="col-span-12 lg:col-span-4 flex flex-col gap-6">
            <?php if ($user_role !== 'staff'): ?>
            <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Counter Status</h2>
            <div class="bg-card border border-border rounded-xl shadow-sm divide-y divide-border/60" id="countersStatus">
                <!-- Injected by JS -->
            </div>
            <?php endif; ?>

            <!-- Session totals -->
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

            <?php if ($user_role === 'admin'): ?>
            <button onclick="openAddWindowModal()" class="btn btn-secondary w-full text-[11px]"><i class="fas fa-plus mr-2"></i>Add Window</button>
            <button onclick="openGroupModal()" class="btn btn-secondary w-full text-[11px] mt-2"><i class="fas fa-layer-group mr-2"></i>Manage Service Groups</button>
            <button onclick="openUserModal()" class="btn btn-secondary w-full text-[11px] mt-2"><i class="fas fa-users mr-2"></i>Manage Users</button>
            <?php endif; ?>
        </aside>
    </main>

    <!-- StatusFooter -->
    <footer class="sticky bottom-0 left-0 w-full p-6 flex justify-between items-center" style="background: hsl(210 30% 97% / 0.8); backdrop-filter: blur(12px); pointer-events: none;">
        <div class="flex items-center gap-6">
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Terminal ID</span>
                <span class="text-[11px] font-mono" style="color: var(--foreground);">DESKTOP-MAIN</span>
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

    <!-- Modals -->
    <div id="announcementModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-lg max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" style="color: var(--foreground);">Manage Announcements</h3>
                    <button onclick="closeAnnouncementModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-6">
                    <h4 class="label-md mb-2">Create Announcement</h4>
                    <div class="space-y-3">
                        <input type="text" id="announcementTitle" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border);" placeholder="Title (optional)">
                        <textarea id="announcementMessage" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border);" rows="2" placeholder="Announcement message"></textarea>
                        <div class="flex gap-3">
                            <select id="announcementType" class="flex-1 px-4 py-2 rounded" style="border: 1px solid var(--border);">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <button onclick="addAnnouncement()" class="btn btn-primary">Add</button>
                        </div>
                    </div>
                </div>
                <div>
                    <h4 class="label-md mb-2">Active Announcements</h4>
                    <div id="activeAnnouncements" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="addWindowModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" style="color: var(--foreground);">Add New Window</h3>
                    <button onclick="closeAddWindowModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4">
                    <div><label class="label-md block mb-1">Window Name</label><input type="text" id="newWindowName" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border);" placeholder="e.g. Window 3"></div>
                    <button onclick="submitNewWindow()" class="btn btn-primary w-full">Add Window</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editServicesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" style="color: var(--foreground);">Edit Window Services</h3>
                    <button onclick="closeEditServicesModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="space-y-4">
                    <input type="hidden" id="editServicesCounterId">
                    <div id="servicesCheckboxes" class="space-y-2 max-h-60 overflow-y-auto border rounded p-3" style="border-color: var(--border); background: var(--secondary);"></div>
                    <button onclick="submitEditServices()" class="btn btn-primary w-full">Save Services</button>
                </div>
            </div>
        </div>
    </div>

    <div id="serviceGroupsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-4xl max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" style="color: var(--foreground);">Manage Service Groups</h3>
                    <button onclick="closeGroupModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-6 p-4 rounded" style="background: var(--secondary); border: 1px solid var(--border);">
                    <h4 class="label-md mb-2">Create New Group</h4>
                    <div class="flex gap-3 flex-wrap">
                        <input type="text" id="newGroupName" class="flex-1 px-4 py-2 rounded min-w-[140px]" style="border: 1px solid var(--border);" placeholder="Group name (e.g. Others)">
                        <input type="text" id="newGroupDesc" class="flex-1 px-4 py-2 rounded min-w-[140px]" style="border: 1px solid var(--border);" placeholder="Description (optional)">
                        <button onclick="createGroup()" class="btn btn-primary shrink-0">Add Group</button>
                    </div>
                </div>
                <div class="mb-6 p-4 rounded" style="background: var(--secondary); border: 1px solid var(--border);">
                    <h4 class="label-md mb-2">Assign Service to Group</h4>
                    <div class="flex gap-3">
                        <select id="assignGroupServiceId" class="flex-1 px-4 py-2 rounded" style="border: 1px solid var(--border);"></select>
                        <select id="assignGroupTargetId" class="flex-1 px-4 py-2 rounded" style="border: 1px solid var(--border);"></select>
                        <button onclick="assignServiceToGroup()" class="btn btn-primary shrink-0">Assign</button>
                    </div>
                </div>
                <div id="groupsListContainer" class="space-y-4">
                    <!-- Injected by JS -->
                </div>

                <hr class="my-6" style="border-color: var(--border);">

                <div class="mb-6 p-4 rounded" style="background: var(--secondary); border: 1px solid var(--border);">
                    <h4 class="label-md mb-2">Add New Service</h4>
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <input type="text" id="grpNewServiceName" class="w-full px-3 py-2 text-xs rounded" style="border:1px solid var(--border);" placeholder="Name (e.g. New Service)">
                        <input type="text" id="grpNewServiceCode" class="w-full px-3 py-2 text-xs rounded" style="border:1px solid var(--border);" placeholder="Code (e.g. new_svc)">
                        <input type="text" id="grpNewServicePrefix" class="w-full px-3 py-2 text-xs rounded" style="border:1px solid var(--border);" placeholder="Prefix (e.g. N)">
                    </div>
                    <button onclick="addServiceInGroup()" class="btn btn-secondary w-full text-[11px]"><i class="fas fa-plus mr-1"></i>Add Service</button>
                </div>

                <div class="p-4 rounded" style="background: var(--secondary); border: 1px solid var(--border);">
                    <h4 class="label-md mb-2">All Services <span id="allServicesCount" class="text-[10px] font-normal" style="color: var(--muted);"></span></h4>
                    <div id="allServicesList" class="space-y-1 max-h-48 overflow-y-auto civic-scrollbar">
                        <!-- Injected by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-2xl max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold" style="color: var(--foreground);">Manage Users</h3>
                    <button onclick="closeUserModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-6 p-4 rounded" style="background: var(--secondary); border: 1px solid var(--border);">
                    <h4 class="label-md mb-3">Create New User</h4>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <input type="text" id="newUserUsername" class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);" placeholder="Username (min 3 chars)">
                        <input type="password" id="newUserPassword" class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);" placeholder="Password (min 6 chars)">
                        <input type="text" id="newUserDisplayName" class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);" placeholder="Display name">
                        <select id="newUserRole" onchange="toggleWindowField()" class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);background:var(--card);">
                            <option value="staff">Staff</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <div id="windowFieldRow" class="flex-1">
                            <select id="newUserWindow" class="w-full px-3 py-2 rounded text-xs" style="border:1px solid var(--border);background:var(--card);">
                                <option value="">No window assignment</option>
                            </select>
                        </div>
                        <button onclick="createUser()" class="btn btn-primary text-xs shrink-0">Create User</button>
                    </div>
                </div>
                <div>
                    <h4 class="label-md mb-2">Users <span id="usersCount" class="text-[10px] font-normal" style="color: var(--muted);"></span></h4>
                    <div id="usersList" class="space-y-2 max-h-80 overflow-y-auto civic-scrollbar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div id="editServiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeEditServiceModal()">
        <div class="card w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold" style="color: var(--foreground);">Edit Service</h3>
                <button onclick="closeEditServiceModal()" class="btn btn-ghost p-1"><i class="fas fa-times"></i></button>
            </div>
            <input type="hidden" id="editServiceId">
            <div class="space-y-4">
                <div><label class="label-md block mb-1">Code</label><input type="text" id="editServiceCode" class="w-full px-4 py-2 rounded font-mono text-sm" style="border: 1px solid var(--border);" placeholder="e.g. insurance"></div>
                <div><label class="label-md block mb-1">Name</label><input type="text" id="editServiceNameInput" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border);" placeholder="Service name"></div>
                <div><label class="label-md block mb-1">Queue Prefix</label><input type="text" id="editServicePrefix" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border);" maxlength="1" placeholder="e.g. I"></div>
                <div><label class="label-md block mb-1">Description</label><textarea id="editServiceDescription" rows="2" class="w-full px-4 py-2 rounded" style="border: 1px solid var(--border); resize: vertical;" placeholder="Service description"></textarea></div>
                <button onclick="submitEditService()" class="btn btn-primary w-full">Save Changes</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>var currentUserRole = '<?php echo $user_role; ?>';</script>
    <script src="js/main.js?v=12"></script>
    <script>
        function updateFooterTime() {
            var el = document.getElementById('footerTime');
            if (!el) return;
            var d = new Date();
            el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
        }
        setInterval(updateFooterTime, 200);
        updateFooterTime();

        // Session time
        var sessionStart = Date.now();
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
    </script>
</body>
</html>
