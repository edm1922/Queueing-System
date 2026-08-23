<?php include 'config.php';
$user = requireRole(['admin']);
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
    <title>Settings — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        .input-field { width: 100%; padding: 0.625rem 1rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 0.875rem; background: var(--card); transition: border-color 0.15s; }
        .input-field:focus { outline: none; border-color: var(--ring); box-shadow: 0 0 0 3px hsl(215 60% 18% / 0.15); }
        select.input-field { cursor: pointer; }
        .ann-item { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 1rem; transition: all 0.15s; }
        .ann-item:hover { background: var(--secondary); }
    </style>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background);">
    <nav class="sticky top-0 z-50" style="background: #b91c1c; color: white; border-bottom: 1px solid rgba(255,255,255,0.15);">
        <div class="max-w-[1600px] mx-auto flex items-center justify-between px-6" style="height: 3.5rem;">
            <div class="flex items-center gap-10">
                <a href="display.php" class="flex items-center gap-3">
                    <div class="relative w-7 h-7 grid place-items-center" style="background: var(--brand-gold); border-radius: 2px;">
                        <?php if ($company_logo): ?><img src="<?php echo $company_logo; ?>" alt="" class="w-5 h-5 object-contain"><?php else: ?><span style="color: var(--primary); font-size: 11px; font-weight: 900; letter-spacing: -0.05em;">CQ</span><?php endif; ?>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: white;"><?php echo $company_name; ?></span>
                        <span style="font-size: 9px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.5;">Settings</span>
                    </div>
                </a>
                <div class="hidden md:flex gap-1 text-[11px] font-semibold uppercase tracking-wider">
                    <a href="index.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Operator</a>
                    <?php if ($user_role !== 'staff'): ?>
                    <a href="display.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Live Display</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                    <a href="kiosk.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Kiosk</a>
                    <?php endif; ?>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 pl-3">
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

    <main class="flex-1 w-full max-w-4xl mx-auto p-8">
        <form id="settingsForm" class="space-y-6" enctype="multipart/form-data">
            <div class="card p-6 animate-entry">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Company Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="label-md block mb-1.5">Company Name</label><input type="text" id="companyName" class="input-field"></div>
                        <div><label class="label-md block mb-1.5">Company Logo URL</label><input type="text" id="companyLogo" class="input-field" placeholder="https://example.com/logo.png"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="label-md block mb-1.5">Branch / Office Name</label><input type="text" id="branchName" class="input-field" placeholder="e.g. Quezon City — Main Hall"></div>
                        <div><label class="label-md block mb-1.5">Address</label><input type="text" id="address" class="input-field" placeholder="e.g. 123 Roxas Blvd, Quezon City"></div>
                    </div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 80ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Queue Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="label-md block mb-1.5">Cut-off Time</label><input type="time" id="cutoffTime" class="input-field"></div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 95ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Known Companies</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Pre-listed company names shown as suggestions in the kiosk.</p>
                <div class="flex items-center gap-3 mb-4">
                    <input type="text" id="companyInput" class="input-field" placeholder="Enter company name" style="flex:1;">
                    <button type="button" onclick="addCompany()" class="btn btn-primary whitespace-nowrap"><i class="fas fa-plus-circle mr-1"></i> Add</button>
                </div>
                <div id="companyList" class="space-y-2">
                    <div class="text-center py-4 text-sm" style="color: var(--muted);">Loading...</div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 100ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Display Appearance</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="label-md block mb-1.5">Accent Color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="themeColor" class="input-field h-10 p-1" value="#2563eb">
                            <span id="themeColorLabel" class="font-mono text-xs" style="color: var(--muted);">#2563eb</span>
                        </div>
                        <p class="text-[10px] mt-1" style="color: var(--muted);">Used for badges, highlights, and UI accents on the public display.</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 120ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Screen Management</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Force a hard reload on all open screens (display, kiosk, window portals).</p>
                <button type="button" onclick="refreshAllScreens()" id="refreshAllBtn" class="btn btn-primary">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh All Screens
                </button>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 130ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Announcements</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Announcements scroll in the bottom panel of the public display.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="label-md block mb-1.5">Title <span class="text-[9px]" style="color: var(--muted);">(optional)</span></label>
                        <input type="text" id="annTitle" class="input-field" placeholder="e.g. Holiday Schedule">
                    </div>
                    <div>
                        <label class="label-md block mb-1.5">Type</label>
                        <select id="annType" class="input-field">
                            <option value="info">Info</option>
                            <option value="warning">Warning</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-md block mb-1.5">Priority</label>
                        <select id="annPriority" class="input-field">
                            <option value="0">Normal</option>
                            <option value="5">High</option>
                            <option value="10">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="label-md block mb-1.5">Message</label>
                    <textarea id="annMessage" class="input-field" rows="2" placeholder="Enter announcement message..."></textarea>
                </div>
                <div class="flex items-center gap-3 mb-5">
                    <button type="button" onclick="addAnnouncement()" class="btn btn-primary"><i class="fas fa-plus-circle mr-1"></i> Add Announcement</button>
                </div>

                <div class="h-px my-4" style="background: var(--border);"></div>

                <div>
                    <label class="label-md block mb-3">Active Announcements</label>
                    <div id="announcementList" class="space-y-2">
                        <div class="text-center py-6 text-sm" style="color: var(--muted);">No announcements</div>
                    </div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="border: 2px solid #dc2626; animation-delay: 115ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-1" style="color: #dc2626;">Danger Zone</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Permanently delete all customer tickets and reset queue numbers. This cannot be undone.</p>
                <button type="button" onclick="clearAllTickets()" class="btn px-4 py-2 text-xs font-bold uppercase tracking-wider" style="background: #dc2626; color: white;"><i class="fas fa-trash-alt mr-2"></i>Clear All Tickets</button>
            </div>

            <div class="flex justify-end gap-3 animate-entry" style="animation-delay: 120ms;">
                <button type="button" onclick="window.location.href='index.php'" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="saveBtn" class="btn btn-primary"><i class="fas fa-save mr-2" id="saveIcon"></i><span id="saveText">Save Settings</span></button>
            </div>
        </form>
    </main>

    <div id="toast" class="fixed bottom-4 right-4 hidden px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium"></div>

    <script>
        async function loadSettings() {
            try {
                var res = await fetch('api/settings/index.php');
                var data = await res.json();
                if (data.success) {
                    var s = data.data;
                    document.getElementById('companyName').value = s.company_name || '';
                    document.getElementById('branchName').value = s.branch_name || '';
                    document.getElementById('address').value = s.address || '';
                    document.getElementById('cutoffTime').value = s.cutoff_time || '17:00';
                    document.getElementById('companyLogo').value = s.company_logo || '';
                    if (s.theme_color) {
                        document.getElementById('themeColor').value = s.theme_color;
                        document.getElementById('themeColorLabel').textContent = s.theme_color;
                    }
                }
            } catch (e) { console.error('Load error:', e); showToast('Failed to load settings', 'error'); }
        }


        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            var btn = document.getElementById('saveBtn');
            var icon = document.getElementById('saveIcon');
            var txt = document.getElementById('saveText');
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin mr-2';
            txt.textContent = 'Saving...';
            var data = {
                company_name: document.getElementById('companyName').value,
                branch_name: document.getElementById('branchName').value,
                address: document.getElementById('address').value,
                cutoff_time: document.getElementById('cutoffTime').value,
                company_logo: document.getElementById('companyLogo').value,
                theme_color: document.getElementById('themeColor').value
            };
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var authHeaders = token ? { 'Authorization': 'Bearer ' + token } : {};
                var res = await fetch('api/settings/index.php', {
                    method: 'POST',
                    headers: Object.assign(authHeaders, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify(data)
                });
                var result = await res.json();
                if (result.success) {
                    try { localStorage.setItem('cq_settings_updated', Date.now().toString()); } catch(e) {}
                }
                showToast(result.success ? 'Settings saved successfully' : result.message || 'Failed to save settings', result.success ? 'success' : 'error');
            } catch (e) { console.error('Save error:', e); showToast('Failed to save settings: ' + e.message, 'error'); }
            icon.className = 'fas fa-save mr-2';
            txt.textContent = 'Save Settings';
            btn.disabled = false;
        });

        function showToast(message, type) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium ' + (type === 'success' ? 'bg-emerald-600' : 'bg-red-600');
            toast.classList.remove('hidden');
            setTimeout(function() { toast.classList.add('hidden'); }, 3000);
        }

        loadSettings();

        document.getElementById('themeColor').addEventListener('input', function() {
            document.getElementById('themeColorLabel').textContent = this.value;
        });

        async function loadAnnouncements() {
            try {
                var res = await fetch('api/announcement/index.php?active=false');
                var result = await res.json();
                var el = document.getElementById('announcementList');
                if (!result.success || !result.data || result.data.length === 0) {
                    el.innerHTML = '<div class="text-center py-6 text-sm" style="color: var(--muted);">No announcements. Add one above.</div>';
                    return;
                }
                el.innerHTML = result.data.map(function(a) {
                    var typeColors = {info: '#2563eb', warning: '#d97706', urgent: '#dc2626'};
                    var typeLabels = {info: 'Info', warning: 'Warning', urgent: 'Urgent'};
                    return '<div class="flex items-start justify-between gap-3 p-3 rounded-md" style="background: var(--card); border: 1px solid var(--border);">' +
                        '<div class="flex-1 min-w-0">' +
                            '<div class="flex items-center gap-2 mb-1">' +
                                '<span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-sm" style="background: ' + typeColors[a.type] + '; color: white;">' + typeLabels[a.type] + '</span>' +
                                (a.title ? '<span class="text-base font-bold truncate">' + escapeHtml(a.title) + '</span>' : '') +
                            '</div>' +
                            '<p class="text-[15px] font-medium" style="color: var(--foreground);">' + escapeHtml(a.message) + '</p>' +
                        '</div>' +
                        '<button type="button" onclick="deleteAnnouncement(' + a.id + ')" class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50" style="color: #9ca3af;" title="Delete"><i class="fas fa-times text-xs"></i></button>' +
                    '</div>';
                }).join('');
            } catch (e) { console.error('Load announcements error:', e); }
        }

        async function addAnnouncement() {
            var title = document.getElementById('annTitle').value.trim();
            var message = document.getElementById('annMessage').value.trim();
            var type = document.getElementById('annType').value;
            var priority = parseInt(document.getElementById('annPriority').value);

            if (!message) { showToast('Message is required', 'error'); return; }

            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var res = await fetch('api/announcement/index.php', {
                    method: 'POST',
                    headers: Object.assign(token ? { 'Authorization': 'Bearer ' + token } : {}, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ title: title, message: message, type: type, priority: priority })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Announcement added', 'success');
                    document.getElementById('annTitle').value = '';
                    document.getElementById('annMessage').value = '';
                    document.getElementById('annType').value = 'info';
                    document.getElementById('annPriority').value = '0';
                    loadAnnouncements();
                } else {
                    showToast(result.message || 'Failed to add', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        async function deleteAnnouncement(id) {
            if (!confirm('Delete this announcement?')) return;
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var res = await fetch('api/announcement/index.php', {
                    method: 'POST',
                    headers: Object.assign(token ? { 'Authorization': 'Bearer ' + token } : {}, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ _method: 'DELETE', id: id })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Announcement deleted', 'success');
                    loadAnnouncements();
                } else {
                    showToast(result.message || 'Failed to delete', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        function escapeHtml(str) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(str));
            return d.innerHTML;
        }

        async function loadCompanies() {
            try {
                var res = await fetch('api/company/index.php');
                var result = await res.json();
                var el = document.getElementById('companyList');
                if (!result.success || !result.data || result.data.length === 0) {
                    el.innerHTML = '<div class="text-center py-4 text-sm" style="color: var(--muted);">No companies added yet.</div>';
                    return;
                }
                el.innerHTML = result.data.map(function(c) {
                    return '<div class="flex items-center justify-between gap-3 p-3 rounded-md" style="background: var(--card); border: 1px solid var(--border);">' +
                        '<span class="text-sm font-medium">' + escapeHtml(c.name) + '</span>' +
                        '<button type="button" onclick="deleteCompany(' + c.id + ')" class="shrink-0 w-7 h-7 flex items-center justify-center rounded-full hover:bg-red-50" style="color: #9ca3af;" title="Delete"><i class="fas fa-times text-xs"></i></button>' +
                    '</div>';
                }).join('');
            } catch (e) { console.error('Load companies error:', e); }
        }

        async function addCompany() {
            var input = document.getElementById('companyInput');
            var name = input.value.trim();
            if (!name) { showToast('Company name is required', 'error'); return; }
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var res = await fetch('api/company/index.php', {
                    method: 'POST',
                    headers: Object.assign(token ? { 'Authorization': 'Bearer ' + token } : {}, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ name: name })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Company added', 'success');
                    input.value = '';
                    loadCompanies();
                } else {
                    showToast(result.message || 'Failed to add', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        async function deleteCompany(id) {
            if (!confirm('Delete this company?')) return;
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var res = await fetch('api/company/index.php', {
                    method: 'POST',
                    headers: Object.assign(token ? { 'Authorization': 'Bearer ' + token } : {}, { 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ _method: 'DELETE', id: id })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Company deleted', 'success');
                    loadCompanies();
                } else {
                    showToast(result.message || 'Failed to delete', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        document.getElementById('companyInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); addCompany(); }
        });

        loadCompanies();

        loadAnnouncements();

        async function refreshAllScreens() {
            var btn = document.getElementById('refreshAllBtn');
            var icon = btn.querySelector('i');
            var txt = btn.childNodes[btn.childNodes.length - 1];
            icon.className = 'fas fa-spinner fa-spin mr-2';
            txt.textContent = 'Refreshing...';
            btn.disabled = true;
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var authHeaders = token ? { 'Authorization': 'Bearer ' + token } : {};
                var res = await fetch('api/force_refresh.php', {
                    method: 'POST',
                    headers: Object.assign(authHeaders, { 'Content-Type': 'application/json' })
                });
                var result = await res.json();
                if (result.success) {
                    try { localStorage.setItem('cq_settings_updated', Date.now().toString()); } catch(e) {}
                    showToast('Screens will refresh shortly', 'success');
                } else {
                    showToast(result.message || 'Failed to refresh screens', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
            icon.className = 'fas fa-sync-alt mr-2';
            txt.textContent = 'Refresh All Screens';
            btn.disabled = false;
        }

        async function clearAllTickets() {
            if (!confirm('This will permanently delete ALL customer tickets and reset queue numbers. Type "CLEAR" to confirm.')) return;
            var input = prompt('To confirm, type "CLEAR" in the box below:');
            if (input !== 'CLEAR') { showToast('Cancelled — did not type CLEAR', 'error'); return; }
            try {
                var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
                var res = await fetch('api/clear_tickets.php', {
                    method: 'POST',
                    headers: Object.assign(token ? { 'Authorization': 'Bearer ' + token } : {}, { 'Content-Type': 'application/json' })
                });
                var result = await res.json();
                showToast(result.success ? result.message : result.message || 'Failed to clear tickets', result.success ? 'success' : 'error');
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

        function logout() {
            if (confirm('Sign out of Settings?')) {
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
