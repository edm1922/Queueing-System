var currentFilter = 'all';
var countersData = [];
var serviceTypesData = [];

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

async function refreshStats() {
    try {
        var response = await apiFetch('api/get_stats.php');
        var res = await response.json();
        if (res.success && res.data) {
            var stats = res.data.basic || {};
            document.getElementById('waiting-count').textContent = (stats.waiting !== undefined) ? stats.waiting : 0;
            document.getElementById('serving-count').textContent = (stats.serving !== undefined) ? stats.serving : 0;
            document.getElementById('completed-count').textContent = (stats.completed !== undefined) ? stats.completed : 0;
            document.getElementById('today-count').textContent = (stats.today_total !== undefined) ? stats.today_total : 0;
            updateServiceMetrics(res.data.by_service || []);

            // Session totals
            var timings = res.data.timings || {};
            if (document.getElementById('sessionServed')) document.getElementById('sessionServed').textContent = stats.completed || 0;
            if (document.getElementById('sessionNoshows')) document.getElementById('sessionNoshows').textContent = stats.cancelled || 0;
            if (document.getElementById('sessionAvgHandle')) document.getElementById('sessionAvgHandle').textContent = timings.avg_service_formatted || '0:00';
        }
    } catch (e) { console.error('Stats Error:', e); }
}

function updateServiceMetrics(metrics) {
    var container = document.getElementById('serviceMetrics');
    if (!container) return;
    if (!metrics || metrics.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-6" style="color: var(--color-muted); font-style: italic; font-size: 0.875rem;">No service data for today</div>';
        return;
    }
    var html = '';
    for (var i = 0; i < metrics.length; i++) {
        var m = metrics[i];
        html += '<div class="rounded-xl p-4" style="background: var(--color-secondary);">' +
                '<div class="flex justify-between items-center mb-3">' +
                '<span class="text-sm font-bold" style="color: var(--color-fg);">' + m.service_name + '</span>' +
                '<span class="badge badge-primary text-[10px]">' + m.queue_prefix + '</span>' +
                '</div>' +
                '<div class="grid grid-cols-3 gap-2 text-center">' +
                '<div><div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-muted);">Wait</div><div class="text-lg font-extrabold" style="color: #d97706;">' + m.waiting + '</div></div>' +
                '<div><div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-muted);">Srv</div><div class="text-lg font-extrabold" style="color: var(--color-primary);">' + m.serving + '</div></div>' +
                '<div><div class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--color-muted);">Done</div><div class="text-lg font-extrabold" style="color: var(--color-success);">' + m.completed + '</div></div>' +
                '</div></div>';
    }
    container.innerHTML = html;
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

function showSkeleton() {
    var table = document.getElementById('queueTable');
    if (!table) return;
    var sk = '';
    for (var i = 0; i < 4; i++) {
        sk += '<tr><td colspan="6"><div class="skeleton" style="height:1rem;width:6rem;margin:0.5rem 1rem;"></div></td></tr>' +
              '<tr>' +
              '<td><div class="skeleton" style="height:1rem;width:5rem;margin:0.5rem 1rem;"></div></td>' +
              '<td><div class="skeleton" style="height:1rem;width:8rem;margin:0.5rem 1rem;"></div></td>' +
              '<td><div class="skeleton" style="height:1rem;width:6rem;margin:0.5rem 1rem;"></div></td>' +
              '<td><div class="skeleton" style="height:1rem;width:4rem;margin:0.5rem 1rem;"></div></td>' +
              '<td><div class="skeleton" style="height:1rem;width:4rem;margin:0.5rem 1rem;"></div></td>' +
              '<td><div class="skeleton" style="height:1rem;width:5rem;margin:0.5rem 1rem;"></div></td>' +
              '</tr>';
    }
    table.innerHTML = sk;
}

async function refreshQueue() {
    showSkeleton();
    try {
        var response = await apiFetch('api/get_queue.php');
        var data = await response.json();
        if (data.success) {
            if (data.service_types) serviceTypesData = data.service_types;
            if (data.counters) countersData = data.counters;
            updateQueueTable(data.customers || []);
            updateCounters(data.counters || []);
            updateServingGrid(data.counters || [], data.customers || []);
        }
    } catch (e) { console.error('Queue Error:', e); }
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
        table.innerHTML = '<tr><td colspan="8" style="padding:2rem;text-align:center;color:var(--color-muted);">No customers</td></tr>';
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
        var remarkDisplay = c.remark ? '<span style="color:var(--color-muted);font-size:11px;max-width:160px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + c.remark.replace(/"/g,'&quot;') + '">' + c.remark + '</span>' : '<span class="text-[10px]" style="color:var(--color-muted);">—</span>';
        var companyDisplay = c.company_name ? c.company_name : '';
        var purposeDisplay = c.purpose ? c.purpose.charAt(0).toUpperCase() + c.purpose.slice(1) : '';
        html += '<tr>' +
                '<td class="font-bold font-mono" style="color: var(--color-fg);">' + c.queue_number + '</td>' +
                '<td style="color: var(--color-fg);">' + c.name + '</td>' +
                '<td style="color: var(--color-muted);">' + formatService(c) + '</td>' +
                '<td class="text-xs" style="color: var(--color-muted);">' + companyDisplay + '</td>' +
                '<td class="text-xs" style="color: var(--color-muted);">' + purposeDisplay + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td class="font-mono text-xs" style="color: var(--color-muted);">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                '<td>' + remarkDisplay + '</td></tr>';
    }
    table.innerHTML = html;
    renderFollowUpList(customers);
}

function updateCounters(counters) {
    var container = document.getElementById('countersStatus');
    if (!container) return;
    var isAdmin = currentUserRole && currentUserRole === 'admin';
    var html = '';
    for (var i = 0; i < counters.length; i++) {
        var c = counters[i];
        var dotClass = 'status-dot online';
        var statusBadge = 'badge badge-online';
        if (c.status_text === 'On Break') {
            dotClass = 'status-dot break';
            statusBadge = 'badge badge-break';
        } else if (c.status_text === 'Offline') {
            dotClass = 'status-dot offline';
            statusBadge = 'badge badge-offline';
        }
        var windowTitle = 'Window ' + (c.window_number || c.id);
        var servicesText = c.active_services || 'None';
        html += '<div class="p-4">' +
                '<div class="flex items-start justify-between mb-3">' +
                    '<div>' +
                        '<div class="flex items-center gap-2 mb-1">' +
                            '<span class="' + dotClass + '"></span>' +
                            '<span class="text-sm font-bold" style="color: var(--color-fg);">' + windowTitle + '</span>' +
                        '</div>' +
                        '<div class="text-xs" style="color: var(--color-muted);"><i class="fas fa-tags mr-1"></i> ' + servicesText + '</div>' +
                    '</div>' +
                    '<span class="' + statusBadge + '">' + c.status_text + '</span>' +
                '</div>' +
                (isAdmin ? '<div class="flex items-center gap-2 mb-2">' +
                    '<select onchange="changeWindowStatus(' + c.id + ', this.value)" class="text-xs px-2 py-1.5 rounded" style="border:1px solid var(--color-border);background:var(--color-card);">' +
                        '<option value="Online" ' + (c.status_text === 'Online' ? 'selected' : '') + '>Online</option>' +
                        '<option value="On Break" ' + (c.status_text === 'On Break' ? 'selected' : '') + '>On Break</option>' +
                        '<option value="Offline" ' + (c.status_text === 'Offline' ? 'selected' : '') + '>Offline</option>' +
                    '</select>' +
                    '<a href="window.php?window_id=' + c.id + '" class="btn btn-ghost text-[10px] py-1 px-2" title="Open Window Portal"><i class="fas fa-external-link-alt"></i></a>' +
                    '<button onclick="openEditServicesModal(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Edit Services"><i class="fas fa-edit"></i></button>' +
                    '<button onclick="deleteWindow(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" style="color:var(--color-destructive);" title="Delete"><i class="fas fa-trash"></i></button>' +
                '</div>' : '<div class="flex items-center gap-2 mb-2">' +
                    '<a href="window.php?window_id=' + c.id + '" class="btn btn-ghost text-[10px] py-1 px-2" title="Open Window Portal"><i class="fas fa-external-link-alt mr-1"></i>Portal</a>' +
                '</div>') +
                '<div class="text-xs" style="color: var(--color-muted);">' +
                    (c.current_customer_name ? '<span style="color:var(--color-primary);">Serving: <strong>' + c.current_queue_number + '</strong></span>' : '<span>Available</span>') +
                '</div>' +
                '</div>';
    }
    container.innerHTML = html;
}

function updateServingGrid(counters, customers) {
    var container = document.getElementById('servingGrid');
    if (!container) return;
    if (!counters || counters.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-6 text-sm" style="color:var(--muted);">No windows configured</div>';
        return;
    }
    var servingMap = {};
    for (var i = 0; i < customers.length; i++) {
        if (customers[i].status === 'serving' && customers[i].counter_id) {
            servingMap[customers[i].counter_id] = customers[i];
        }
    }
    var html = '';
    for (var i = 0; i < counters.length; i++) {
        var c = counters[i];
        var serving = servingMap[c.id];
        var dotClass = 'status-dot online';
        var statusLabel = 'Online';
        if (c.status_text === 'On Break') { dotClass = 'status-dot break'; statusLabel = 'On Break'; }
        else if (c.status_text === 'Offline') { dotClass = 'status-dot offline'; statusLabel = 'Offline'; }
        var servingHtml = serving
            ? '<div class="text-lg font-extrabold tracking-tight" style="color:var(--primary);">' + serving.queue_number + '</div>' +
              '<div class="text-xs mt-1" style="color:var(--muted);">' + (serving.name || '') + ' &middot; ' + formatServingService(serving) + '</div>'
            : '<div class="text-sm" style="color:var(--muted);">Available</div>';
        var borderColor = serving ? 'var(--primary)' : 'var(--border)';
        html += '<div class="rounded-xl p-4" style="border:1px solid ' + borderColor + ';background:var(--card);">' +
                    '<div class="flex items-center justify-between mb-2">' +
                        '<div class="flex items-center gap-2">' +
                            '<span class="' + dotClass + '"></span>' +
                            '<span class="text-xs font-bold" style="color:var(--foreground);">Window ' + (c.window_number || c.id) + '</span>' +
                        '</div>' +
                        '<span class="text-[10px] font-mono" style="color:var(--muted);">' + statusLabel + '</span>' +
                    '</div>' +
                    servingHtml +
                '</div>';
    }
    container.innerHTML = html;
}

// ====== User Management ======

async function loadUsers() {
    try {
        var res = await apiFetch('api/user/list.php');
        var data = await res.json();
        if (!data.success) return;
        var users = data.data.users || [];
        var windows = data.data.windows || [];

        var countEl = document.getElementById('usersCount');
        if (countEl) countEl.textContent = '(' + users.length + ' total)';

        // Populate window dropdown
        var winSelect = document.getElementById('newUserWindow');
        if (winSelect) {
            winSelect.innerHTML = '<option value="">No window assignment</option>';
            for (var i = 0; i < windows.length; i++) {
                winSelect.innerHTML += '<option value="' + windows[i].id + '">' + windows[i].display_name + '</option>';
            }
        }

        var listEl = document.getElementById('usersList');
        if (!listEl) return;
        if (users.length === 0) {
            listEl.innerHTML = '<div class="text-center py-6 text-sm" style="color: var(--muted);">No users found</div>';
            return;
        }
        var html = '';
        for (var i = 0; i < users.length; i++) {
            var u = users[i];
            var roleBadge = 'badge';
            if (u.role === 'admin') roleBadge += ' badge-primary';
            else if (u.role === 'supervisor') roleBadge += ' badge-online';
            else roleBadge += ' badge';
            var activeBadge = u.is_active == 1
                ? '<span class="badge badge-online text-[9px]">Active</span>'
                : '<span class="badge badge-offline text-[9px]">Inactive</span>';
            var winName = u.window_name || '—';
            html += '<div class="flex items-center justify-between py-2 px-3 rounded" style="background: var(--card); border: 1px solid var(--border);">' +
                '<div class="flex items-center gap-3">' +
                    '<div class="w-7 h-7 rounded-full grid place-items-center text-[9px] font-bold" style="background: var(--secondary);">' + (u.display_name || u.username).substring(0, 2) + '</div>' +
                    '<div>' +
                        '<div class="text-sm font-medium" style="color: var(--foreground);">' + u.display_name + ' <span class="text-[10px] font-mono" style="color: var(--muted);">@' + u.username + '</span></div>' +
                        '<div class="flex items-center gap-2 mt-0.5">' +
                            '<span class="' + roleBadge + '">' + u.role + '</span>' +
                            activeBadge +
                            '<span class="text-[10px]" style="color: var(--muted);">' + winName + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="flex gap-1">' +
                    (u.username !== 'admin' ? '<button onclick="deleteUser(' + u.id + ')" class="btn btn-ghost text-[10px] px-2 py-1" style="color: var(--destructive);" title="Deactivate"><i class="fas fa-user-slash"></i></button>' : '') +
                '</div>' +
                '</div>';
        }
        listEl.innerHTML = html;
    } catch (e) { console.error('Error loading users:', e); }
}

function openUserModal() {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('newUserRole').value = 'staff';
    toggleWindowField();
    loadUsers();
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

async function createUser() {
    var username = document.getElementById('newUserUsername').value.trim();
    var password = document.getElementById('newUserPassword').value;
    var display_name = document.getElementById('newUserDisplayName').value.trim();
    var role = document.getElementById('newUserRole').value;
    var window_id = document.getElementById('newUserWindow').value;
    if (!username || !password || !display_name) { showToast('Username, password, and display name required', 'error'); return; }
    if (username.length < 3) { showToast('Username must be at least 3 characters', 'error'); return; }
    if (password.length < 6) { showToast('Password must be at least 6 characters', 'error'); return; }
    try {
        var res = await apiFetch('api/user/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, password: password, display_name: display_name, role: role, window_id: window_id ? parseInt(window_id) : null })
        });
        var data = await res.json();
        if (data.success) {
            showToast('User created successfully', 'success');
            document.getElementById('newUserUsername').value = '';
            document.getElementById('newUserPassword').value = '';
            document.getElementById('newUserDisplayName').value = '';
            loadUsers();
        } else {
            showToast(data.message || 'Failed to create user', 'error');
        }
    } catch (e) { showToast('Error creating user', 'error'); }
}

async function deleteUser(userId) {
    if (!confirm('Deactivate this user? They will no longer be able to log in.')) return;
    try {
        var res = await apiFetch('api/user/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        var data = await res.json();
        if (data.success) {
            showToast('User deactivated', 'success');
            loadUsers();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error deactivating user', 'error'); }
}

function toggleWindowField() {
    var role = document.getElementById('newUserRole').value;
    var row = document.getElementById('windowFieldRow');
    if (row) row.style.display = (role === 'staff') ? 'flex' : 'none';
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
            showToast('Window status updated', 'success');
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

function openAddWindowModal() {
    document.getElementById('newWindowName').value = '';
    document.getElementById('addWindowModal').style.display = 'flex';
}

function closeAddWindowModal() {
    document.getElementById('addWindowModal').style.display = 'none';
}

async function submitNewWindow() {
    var name = document.getElementById('newWindowName').value.trim();
    if (!name) { showToast('Please enter a window name', 'error'); return; }
    try {
        var response = await apiFetch('api/counter/add_window.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name })
        });
        var data = await response.json();
        if (data.success) {
            showToast('New window added successfully', 'success');
            closeAddWindowModal();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed to add window', 'error');
        }
    } catch (e) {
        showToast('Error adding window', 'error');
    }
}

function deleteWindow(counterId) {
    if (!confirm('Are you sure you want to delete this window? This action cannot be undone.')) return;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/counter/delete_window.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    var token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token');
    if (token) xhr.setRequestHeader('Authorization', 'Bearer ' + token);
    xhr.onload = function() {
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success) {
                showToast('Window deleted successfully', 'success');
                refreshQueue();
                refreshStats();
            } else {
                showToast(data.message || 'Failed to delete window', 'error');
            }
        } catch (e) {
            showToast('Error deleting window', 'error');
        }
    };
    xhr.send(JSON.stringify({ counter_id: counterId }));
}

function openEditServicesModal(counterId) {
    var counter = countersData.find(c => c.id == counterId);
    if (!counter) return;
    document.getElementById('editServicesCounterId').value = counterId;
    var counterName = counter.name || 'Window ' + counter.window_number;
    // Fetch both services and assignments data
    Promise.all([
        apiFetch('api/service/groups.php').then(function(r){return r.json();}),
        apiFetch('api/counter/get_assignments.php').then(function(r){return r.json();})
    ]).then(function(results) {
        var svcData = results[0];
        var assignData = results[1];
        if (!svcData.success) return;
        // Build map: service_code → assigned counter name (excluding current counter)
        var serviceOwners = {};
        if (assignData.success) {
            for (var ci = 0; ci < assignData.data.counters.length; ci++) {
                var ac = assignData.data.counters[ci];
                if (ac.counter_id == counterId) continue;
                for (var ai = 0; ai < (ac.service_assignments || []).length; ai++) {
                    var asgn = ac.service_assignments[ai];
                    if (asgn.service_type === 'other' || asgn.service_type === 'custom') continue;
                    if (asgn.is_primary == '1' || asgn.is_primary == 1) {
                        serviceOwners[asgn.service_type] = ac.name || 'Window ' + ac.window_number;
                    }
                }
            }
        }
        var assignedServices = counter.active_services ? counter.active_services.split(',') : [];
        var html = '';
        var d = svcData.data;
        for (var g = 0; g < d.groups.length; g++) {
            var group = d.groups[g];
            var groupSvcs = [];
            for (var si = 0; si < group.services.length; si++) {
                if (group.services[si].code !== 'custom') groupSvcs.push(group.services[si]);
            }
            var allChecked = true, groupDisabled = false;
            for (var si = 0; si < groupSvcs.length; si++) {
                if (!assignedServices.includes(groupSvcs[si].code)) { allChecked = false; }
                var owner = serviceOwners[groupSvcs[si].code];
                if (owner && !assignedServices.includes(groupSvcs[si].code)) { groupDisabled = true; }
            }
            html += '<div class="mb-3">' +
                    '<label class="flex items-center gap-3 p-2 rounded cursor-pointer hover:bg-gray-100 transition-colors" style="border-bottom:1px solid var(--border);font-weight:600;' + (groupDisabled ? 'opacity:0.5;' : '') + '">' +
                    '<input type="checkbox" class="group-cb" data-group="' + g + '" ' + (allChecked ? 'checked' : '') + (groupDisabled ? ' disabled' : '') + ' style="accent-color:var(--color-primary);">' +
                    '<span class="text-sm font-bold">' + group.name + '</span>' +
                    (groupDisabled ? '<span class="text-[10px]" style="color:var(--muted);">(some services assigned to other windows)</span>' : '') +
                    '</label>';
            for (var si = 0; si < groupSvcs.length; si++) {
                var svc = groupSvcs[si];
                var isChecked = assignedServices.includes(svc.code) ? 'checked' : '';
                var owner = serviceOwners[svc.code];
                var isDisabled = owner && !isChecked;
                html += '<label class="flex items-center gap-3 pl-8 p-1.5 rounded ' + (isDisabled ? '' : 'cursor-pointer hover:bg-gray-50') + ' transition-colors">' +
                        '<input type="checkbox" class="service-cb" data-group="' + g + '" value="' + svc.code + '" ' + isChecked + (isDisabled ? ' disabled' : '') + ' style="accent-color:var(--color-primary);' + (isDisabled ? 'opacity:0.4;' : '') + '">' +
                        '<span class="text-sm" style="color:' + (isDisabled ? 'var(--muted)' : 'var(--color-fg)') + ';">' + svc.name + '</span>' +
                        (owner ? '<span class="text-[10px]" style="color:var(--muted);">(' + owner + ')</span>' : '') +
                        '</label>';
            }
            html += '</div>';
        }
        if (d.ungrouped.length > 0) {
            var ungroupedFiltered = [];
            for (var si = 0; si < d.ungrouped.length; si++) {
                if (d.ungrouped[si].code !== 'custom') ungroupedFiltered.push(d.ungrouped[si]);
            }
            if (ungroupedFiltered.length > 0) {
            if (d.groups.length > 0) html += '<div class="mb-3" style="border-top:1px solid var(--border);padding-top:8px;">';
            for (var si = 0; si < ungroupedFiltered.length; si++) {
                var svc = ungroupedFiltered[si];
                var isChecked = assignedServices.includes(svc.code) ? 'checked' : '';
                var owner = serviceOwners[svc.code];
                var isDisabled = owner && !isChecked;
                html += '<label class="flex items-center gap-3 p-2 rounded ' + (isDisabled ? '' : 'cursor-pointer hover:bg-gray-50') + ' transition-colors">' +
                        '<input type="checkbox" class="service-cb" value="' + svc.code + '" ' + isChecked + (isDisabled ? ' disabled' : '') + ' style="accent-color:var(--color-primary);' + (isDisabled ? 'opacity:0.4;' : '') + '">' +
                        '<span class="text-sm" style="color:' + (isDisabled ? 'var(--muted)' : 'var(--color-fg)') + ';">' + svc.name + '</span>' +
                        (owner ? '<span class="text-[10px]" style="color:var(--muted);">(' + owner + ')</span>' : '') +
                        '</label>';
            }
            if (d.groups.length > 0) html += '</div>';
            }
        }
        document.getElementById('servicesCheckboxes').innerHTML = html;
        document.getElementById('editServicesModal').style.display = 'flex';

        var groupCbs = document.querySelectorAll('.group-cb:not(:disabled)');
        for (var i = 0; i < groupCbs.length; i++) {
            groupCbs[i].addEventListener('change', function() {
                var g = this.getAttribute('data-group');
                var checked = this.checked;
                var cbs = document.querySelectorAll('.service-cb[data-group="' + g + '"]:not(:disabled)');
                for (var j = 0; j < cbs.length; j++) cbs[j].checked = checked;
            });
        }
        var svcCbs = document.querySelectorAll('.service-cb:not(:disabled)');
        for (var i = 0; i < svcCbs.length; i++) {
            svcCbs[i].addEventListener('change', function() {
                var g = this.getAttribute('data-group');
                if (!g) return;
                var all = document.querySelectorAll('.service-cb[data-group="' + g + '"]:not(:disabled)');
                var checked = document.querySelectorAll('.service-cb[data-group="' + g + '"]:checked');
                var groupCb = document.querySelector('.group-cb[data-group="' + g + '"]');
                if (groupCb) groupCb.checked = all.length > 0 && checked.length === all.length;
            });
        }
    });
}

function closeEditServicesModal() {
    document.getElementById('editServicesModal').style.display = 'none';
}

async function submitEditServices() {
    var counterId = document.getElementById('editServicesCounterId').value;
    var checkboxes = document.querySelectorAll('.service-cb:checked:not(:disabled)');
    var services = [];
    for (var i = 0; i < checkboxes.length; i++) {
        services.push(checkboxes[i].value);
    }
    try {
        var response = await apiFetch('api/counter/update_services.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ counter_id: counterId, services: services })
        });
        var data = await response.json();
        if (data.success) {
            showToast('Services updated successfully', 'success');
            closeEditServicesModal();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed to update services', 'error');
        }
    } catch (e) {
        showToast('Error updating services', 'error');
    }
}

async function addServiceInGroup() {
    var name = document.getElementById('grpNewServiceName').value.trim();
    var code = document.getElementById('grpNewServiceCode').value.trim();
    var prefix = document.getElementById('grpNewServicePrefix').value.trim().toUpperCase();
    if (!name || !code || !prefix) { showToast('Name, code, and prefix are required', 'error'); return; }
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create_service', name: name, code: code, prefix: prefix })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Service added successfully', 'success');
            document.getElementById('grpNewServiceName').value = '';
            document.getElementById('grpNewServiceCode').value = '';
            document.getElementById('grpNewServicePrefix').value = '';
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed to add service', 'error');
        }
    } catch (e) { showToast('Error adding service', 'error'); }
}

async function deleteService(serviceId, serviceName) {
    if (!confirm('Delete "' + serviceName + '"? This will remove it from all windows and groups.')) return;
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_service', service_id: serviceId })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Service deleted', 'success');
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed to delete service', 'error');
        }
    } catch (e) { showToast('Error deleting service', 'error'); }
}

async function callCustomer(id) {
    try {
        await apiFetch('api/call_customer.php', { method: 'POST', body: JSON.stringify({ customer_id: id }) });
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error calling customer', 'error'); }
}

async function recallCustomer(id) {
    try {
        var response = await apiFetch('api/recall_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: id })
        });
        var data = await response.json();
        if (data.success) {
            showToast('Customer recalled (re-announced)', 'success');
            refreshQueue();
        } else {
            showToast(data.message || 'Error recalling customer', 'error');
        }
    } catch (e) { showToast('Error recalling customer', 'error'); }
}

async function completeCustomer(id) {
    try {
        await apiFetch('api/complete_customer.php', { method: 'POST', body: JSON.stringify({ customer_id: id }) });
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error completing customer', 'error'); }
}

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

var customerForm = document.getElementById('customerForm');
if (customerForm) {
    customerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        var nameInput = document.getElementById('customerName');
        var serviceTypeInput = document.getElementById('serviceType');
        var name = nameInput ? nameInput.value.trim() : '';
        var serviceType = serviceTypeInput ? serviceTypeInput.value : '';
        if (!name || !serviceType) return;
        var submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        }
        try {
            var response = await apiFetch('api/add_customer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, service_type: serviceType })
            });
            var data = await response.json();
            if (data.success) {
                var resDiv = document.getElementById('queueResult');
                if (resDiv) resDiv.classList.remove('hidden');
                var genQ = document.getElementById('generatedQueue');
                if (genQ) genQ.textContent = data.queue_number;
                var qPos = document.getElementById('queuePosition');
                if (qPos) qPos.textContent = 'Position in queue: ' + (data.data.queue_position || '--');
                if (nameInput) nameInput.value = '';
                if (serviceTypeInput) serviceTypeInput.value = '';
                refreshQueue(); refreshStats();
                showToast('Queue number ' + data.queue_number + ' generated', 'success');
            } else {
                showToast(data.message || 'Failed to add customer', 'error');
            }
        } catch (e) {
            showToast('Error adding customer', 'error');
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number';
        }
    });
}

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
        var statusLabel = c.status === 'serving' ? ' (being served)' : c.status === 'completed' ? '' : '';
        html += '<div class="flex items-center justify-between p-3 border-b border-border">' +
                '<div class="flex items-center gap-3">' +
                    '<span class="text-[10px] font-mono w-5 tabular-nums" style="color:var(--muted);">' + (i+1) + '</span>' +
                    '<div class="flex flex-col">' +
                        '<span class="font-mono text-sm font-bold tracking-tight" style="color:var(--brand-gold);">' + c.queue_number + '</span>' +
                        '<span class="text-[10px] uppercase tracking-wider" style="color:var(--muted);">' + formatService(c) + statusLabel + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="flex gap-1">' +
                    '<button onclick="serveFollowUp(' + c.id + ')" class="btn btn-primary text-[10px] py-1 px-2.5"><i class="fas fa-arrow-right text-xs mr-1"></i>Serve</button>' +
                    '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Remove" style="color:var(--color-destructive);"><i class="fas fa-times"></i></button>' +
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
            showToast(data.message || 'Failed to toggle follow-up', 'error');
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
            showToast(data.message || 'Failed to serve follow-up', 'error');
        }
    } catch (e) { showToast('Error serving follow-up', 'error'); }
}

// ====== Service Group Management ======
function openGroupModal() {
    document.getElementById('serviceGroupsModal').style.display = 'flex';
    loadGroups();
}

function closeGroupModal() {
    document.getElementById('serviceGroupsModal').style.display = 'none';
}

async function loadGroups() {
    try {
        var res = await apiFetch('api/service/groups.php');
        var data = await res.json();
        if (!data.success) return;
        var groups = data.data.groups || [];
        var allServices = data.data.all_services || [];
        window._allServices = allServices;

        // Populate assign dropdowns
        var svcSelect = document.getElementById('assignGroupServiceId');
        var grpSelect = document.getElementById('assignGroupTargetId');
        if (svcSelect) {
            svcSelect.innerHTML = '<option value="">-- Select service --</option>';
            for (var i = 0; i < allServices.length; i++) {
                svcSelect.innerHTML += '<option value="' + allServices[i].id + '">' + allServices[i].name + '</option>';
            }
        }
        if (grpSelect) {
            grpSelect.innerHTML = '<option value="">-- Select group --</option>';
            for (var i = 0; i < groups.length; i++) {
                grpSelect.innerHTML += '<option value="' + groups[i].id + '">' + groups[i].name + '</option>';
            }
        }

        // Render groups list
        var container = document.getElementById('groupsListContainer');
        if (!container) return;
        if (groups.length === 0) {
            container.innerHTML = '<div class="text-center py-8" style="color: var(--muted);">No groups yet. Create one above.</div>';
        } else {
            var html = '';
            for (var i = 0; i < groups.length; i++) {
                var g = groups[i];
                var svcs = g.services || [];
                var svcHtml = '';
                for (var j = 0; j < svcs.length; j++) {
                    svcHtml += '<div class="flex items-center justify-between py-1.5 px-3 rounded" style="background: var(--card); border: 1px solid var(--border);">' +
                        '<span class="text-sm">' + svcs[j].name + '</span>' +
                        '<button onclick="removeServiceFromGroup(' + svcs[j].id + ')" class="text-[10px] px-2 py-0.5 rounded" style="color: var(--destructive); border: 1px solid var(--destructive);" title="Remove from group"><i class="fas fa-times"></i></button>' +
                        '</div>';
                }
                if (svcHtml === '') svcHtml = '<div class="text-xs px-3 py-2" style="color: var(--muted);">No services in this group</div>';
                html += '<div class="rounded p-4" style="border: 1px solid var(--border);">' +
                    '<div class="flex items-center justify-between mb-2">' +
                        '<div><h4 class="font-bold text-sm">' + g.name + '</h4>' +
                        (g.description ? '<span class="text-xs" style="color: var(--muted);">' + g.description + '</span>' : '') +
                        '</div>' +
                        '<div class="flex gap-2">' +
                            '<button onclick="deleteGroup(' + g.id + ')" class="btn btn-ghost text-[10px] px-2 py-1" style="color: var(--destructive);"><i class="fas fa-trash mr-1"></i>Delete</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="space-y-1.5">' + svcHtml + '</div>' +
                '</div>';
            }
            container.innerHTML = html;
        }

        // Render All Services list with delete buttons
        var allList = document.getElementById('allServicesList');
        var countEl = document.getElementById('allServicesCount');
        if (allList) {
            var displayServices = [];
            for (var i = 0; i < allServices.length; i++) {
                if (allServices[i].code !== 'custom') displayServices.push(allServices[i]);
            }
            if (countEl) countEl.textContent = '(' + displayServices.length + ' total)';
            if (displayServices.length === 0) {
                allList.innerHTML = '<div class="text-xs py-3 text-center" style="color: var(--muted);">No services</div>';
            } else {
                var allHtml = '';
                for (var i = 0; i < displayServices.length; i++) {
                    var s = displayServices[i];
                    allHtml += '<div class="flex items-center justify-between py-1.5 px-3 rounded" style="background: var(--card); border: 1px solid var(--border);">' +
                        '<div><span class="text-sm">' + s.name + '</span> <span class="text-[10px] font-mono" style="color: var(--muted);">(' + s.code + ')</span>' +
                        (s.queue_prefix ? ' <span class="text-[10px] font-mono badge badge-primary">' + s.queue_prefix + '</span>' : '') +
                        '</div>' +
                        '<div class="flex gap-2">' +
                        '<button onclick="openEditServiceModal(' + s.id + ')" class="text-[10px] px-2 py-0.5 rounded" style="color: var(--primary); border: 1px solid var(--primary);" title="Edit service"><i class="fas fa-pen mr-1"></i>Edit</button>' +
                        '<button onclick="deleteService(' + s.id + ', \'' + s.name.replace(/'/g, "\\'") + '\')" class="text-[10px] px-2 py-0.5 rounded" style="color: var(--destructive); border: 1px solid var(--destructive);" title="Delete service"><i class="fas fa-trash-alt mr-1"></i>Delete</button>' +
                        '</div></div>';
                }
                allList.innerHTML = allHtml;
            }
        }
    } catch (e) { console.error('Error loading groups:', e); }
}

async function createGroup() {
    var name = document.getElementById('newGroupName').value.trim();
    var desc = document.getElementById('newGroupDesc').value.trim();
    if (!name) { showToast('Group name is required', 'error'); return; }
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create', name: name, description: desc })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Group created', 'success');
            document.getElementById('newGroupName').value = '';
            document.getElementById('newGroupDesc').value = '';
            loadGroups();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error creating group', 'error'); }
}

async function assignServiceToGroup() {
    var svcId = document.getElementById('assignGroupServiceId').value;
    var grpId = document.getElementById('assignGroupTargetId').value;
    if (!svcId || !grpId) { showToast('Select service and group', 'error'); return; }
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'assign', service_id: parseInt(svcId), group_id: parseInt(grpId) })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Service assigned to group', 'success');
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error assigning service', 'error'); }
}

async function removeServiceFromGroup(serviceId) {
    if (!confirm('Remove this service from its group?')) return;
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', service_id: serviceId })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Service removed from group', 'success');
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error removing service', 'error'); }
}

async function deleteGroup(groupId) {
    if (!confirm('Delete this group? Services assigned to it will be ungrouped.')) return;
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: groupId })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Group deleted', 'success');
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error deleting group', 'error'); }
}

// ====== Edit Service ======
function openEditServiceModal(serviceId) {
    var allList = document.getElementById('allServicesList');
    if (!allList) return;
    // Find service data from the cached allServices (loaded via loadGroups)
    var svc = null;
    for (var i = 0; i < window._allServices.length; i++) {
        if (window._allServices[i].id == serviceId) { svc = window._allServices[i]; break; }
    }
    if (!svc) { showToast('Service not found', 'error'); return; }
    document.getElementById('editServiceId').value = svc.id;
    document.getElementById('editServiceNameInput').value = svc.name;
    document.getElementById('editServiceCode').value = svc.code;
    document.getElementById('editServicePrefix').value = svc.queue_prefix || '';
    document.getElementById('editServiceDescription').value = svc.description || '';
    document.getElementById('editServiceModal').style.display = 'flex';
}

function closeEditServiceModal() {
    document.getElementById('editServiceModal').style.display = 'none';
}

async function submitEditService() {
    var id = document.getElementById('editServiceId').value;
    var name = document.getElementById('editServiceNameInput').value.trim();
    var prefix = document.getElementById('editServicePrefix').value.trim().toUpperCase();
    var description = document.getElementById('editServiceDescription').value.trim();
    var code = document.getElementById('editServiceCode').value.trim().toLowerCase().replace(/\s+/g, '_');
    if (!name) { showToast('Service name is required', 'error'); return; }
    if (!code) { showToast('Service code is required', 'error'); return; }
    if (!prefix) { showToast('Queue prefix is required', 'error'); return; }
    try {
        var res = await apiFetch('api/service/groups.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit_service', service_id: id, name: name, code: code, prefix: prefix, description: description })
        });
        var data = await res.json();
        if (data.success) {
            showToast('Service updated', 'success');
            closeEditServiceModal();
            loadGroups();
            refreshQueue();
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch (e) { showToast('Error updating service', 'error'); }
}

function init() {
    refreshQueue();
    refreshStats();
    setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
}

window.onload = init;
