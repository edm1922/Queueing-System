var currentFilter = 'all';
var countersData = [];
var serviceTypesData = [];

// time updated inline on each page

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
        var response = await fetch('api/get_stats.php');
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
        var response = await fetch('api/get_queue.php');
        var data = await response.json();
        if (data.success) {
            if (data.service_types) serviceTypesData = data.service_types;
            if (data.counters) countersData = data.counters;
            updateQueueTable(data.customers || []);
            updateCounters(data.counters || []);
            updateServingDisplay(data.customers || []);
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
        table.innerHTML = '<tr><td colspan="6" style="padding:2rem;text-align:center;color:var(--color-muted);">No customers</td></tr>';
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
                actions += '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Remove follow-up mark" style="color:var(--color-destructive);"><i class="fas fa-flag"></i></button>';
            } else {
                actions += '<button onclick="toggleFollowUp(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2 ml-1" title="Mark incomplete — needs follow-up"><i class="fas fa-flag"></i></button>';
            }
        }
        html += '<tr>' +
                '<td class="font-bold font-mono" style="color: var(--color-fg);">' + c.queue_number + '</td>' +
                '<td style="color: var(--color-fg);">' + c.name + '</td>' +
                '<td style="color: var(--color-muted);">' + (c.service_name || c.service_type) + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '<td class="font-mono text-xs" style="color: var(--color-muted);">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                '<td>' + actions + '</td></tr>';
    }
    table.innerHTML = html;
    renderFollowUpList(customers);
}

function updateCounters(counters) {
    var container = document.getElementById('countersStatus');
    if (!container) return;
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
        var servicesText = c.active_services || 'None';
        html += '<div class="p-4">' +
                '<div class="flex items-start justify-between mb-3">' +
                    '<div>' +
                        '<div class="flex items-center gap-2 mb-1">' +
                            '<span class="' + dotClass + '"></span>' +
                            '<span class="text-sm font-bold" style="color: var(--color-fg);">' + c.display_name + '</span>' +
                        '</div>' +
                        '<div class="text-xs" style="color: var(--color-muted);"><i class="fas fa-tags mr-1"></i> ' + servicesText + '</div>' +
                    '</div>' +
                    '<span class="' + statusBadge + '">' + c.status_text + '</span>' +
                '</div>' +
                '<div class="flex items-center gap-2 mb-2">' +
                    '<select onchange="changeWindowStatus(' + c.id + ', this.value)" class="text-xs px-2 py-1.5 rounded" style="border:1px solid var(--color-border);background:var(--color-card);">' +
                        '<option value="Online" ' + (c.status_text === 'Online' ? 'selected' : '') + '>Online</option>' +
                        '<option value="On Break" ' + (c.status_text === 'On Break' ? 'selected' : '') + '>On Break</option>' +
                        '<option value="Offline" ' + (c.status_text === 'Offline' ? 'selected' : '') + '>Offline</option>' +
                    '</select>' +
                    '<button onclick="openEditServicesModal(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" title="Edit Services"><i class="fas fa-edit"></i></button>' +
                    '<button onclick="deleteWindow(' + c.id + ')" class="btn btn-ghost text-[10px] py-1 px-2" style="color:var(--color-destructive);" title="Delete"><i class="fas fa-trash"></i></button>' +
                '</div>' +
                '<div class="text-xs" style="color: var(--color-muted);">' +
                    (c.current_customer_name ? '<span style="color:var(--color-primary);">Serving: <strong>' + c.current_queue_number + '</strong></span>' : '<span>Available</span>') +
                '</div>' +
                '</div>';
    }
    container.innerHTML = html;
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
        if (info) info.textContent = (serving.name || '') + ' — ' + (serving.service_name || serving.service_type || '');
    } else {
        num.textContent = '---';
        if (info) info.textContent = 'No active customer';
    }
}

async function callNext() {
    try {
        var r = await fetch('api/get_queue.php');
        var d = await r.json();
        if (!d.success) return;
        var serving = null;
        for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
        if (serving) {
            await fetch('api/complete_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id }) });
        }
        var waiting = null;
        for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
        if (waiting) {
            await fetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id }) });
        }
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error in Complete & Next', 'error'); }
}

async function skipCustomer() {
    try {
        var r = await fetch('api/get_queue.php');
        var d = await r.json();
        if (!d.success) return;
        var serving = null;
        for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
        if (serving) {
            await fetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'skipped' }) });
        }
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error skipping customer', 'error'); }
}

async function noShow() {
    try {
        var r = await fetch('api/get_queue.php');
        var d = await r.json();
        if (!d.success) return;
        var serving = null;
        for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'serving') { serving = d.customers[i]; break; } }
        if (serving) {
            await fetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: serving.id, reason: 'no-show' }) });
        }
        var waiting = null;
        for (var i = 0; i < d.customers.length; i++) { if (d.customers[i].status === 'waiting' && d.customers[i].is_follow_up != 1) { waiting = d.customers[i]; break; } }
        if (waiting) {
            await fetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: waiting.id }) });
        }
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error in No-Show', 'error'); }
}

async function changeWindowStatus(counterId, status) {
    try {
        var response = await fetch('api/counter/toggle_status.php', {
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
        var response = await fetch('api/counter/add_window.php', {
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
    var assignedServices = counter.active_services ? counter.active_services.split(',') : [];
    var html = '';
    for (var i = 0; i < serviceTypesData.length; i++) {
        var s = serviceTypesData[i];
        var isChecked = assignedServices.includes(s.code) ? 'checked' : '';
        html += '<label class="flex items-center gap-3 p-2 rounded cursor-pointer hover:bg-white transition-colors">' +
                '<input type="checkbox" class="service-cb" value="' + s.code + '" ' + isChecked + ' style="accent-color:var(--color-primary);">' +
                '<span class="text-sm" style="color:var(--color-fg);">' + s.name + '</span>' +
                '</label>';
    }
    document.getElementById('servicesCheckboxes').innerHTML = html;
    document.getElementById('editServicesModal').style.display = 'flex';
}

function closeEditServicesModal() {
    document.getElementById('editServicesModal').style.display = 'none';
}

async function submitEditServices() {
    var counterId = document.getElementById('editServicesCounterId').value;
    var checkboxes = document.querySelectorAll('.service-cb:checked');
    var services = [];
    for (var i = 0; i < checkboxes.length; i++) {
        services.push(checkboxes[i].value);
    }
    try {
        var response = await fetch('api/counter/update_services.php', {
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

async function addNewService() {
    var name = document.getElementById('newServiceName').value.trim();
    var code = document.getElementById('newServiceCode').value.trim();
    var prefix = document.getElementById('newServicePrefix').value.trim().toUpperCase();
    if (!name || !code || !prefix) { showToast('Name, code, and prefix are required', 'error'); return; }
    try {
        var response = await fetch('api/service/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, code: code, prefix: prefix })
        });
        var data = await response.json();
        if (data.success) {
            showToast('Service added successfully', 'success');
            document.getElementById('newServiceName').value = '';
            document.getElementById('newServiceCode').value = '';
            document.getElementById('newServicePrefix').value = '';
            refreshQueue();
            closeEditServicesModal();
        } else {
            showToast(data.message || 'Failed to add service', 'error');
        }
    } catch (e) { showToast('Error adding service', 'error'); }
}

async function callCustomer(id) {
    try {
        await fetch('api/call_customer.php', { method: 'POST', body: JSON.stringify({ customer_id: id }) });
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error calling customer', 'error'); }
}

async function recallCustomer(id) {
    try {
        var response = await fetch('api/recall_customer.php', {
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
        await fetch('api/complete_customer.php', { method: 'POST', body: JSON.stringify({ customer_id: id }) });
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
            var response = await fetch('api/add_customer.php', {
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
                        '<span class="text-[10px] uppercase tracking-wider" style="color:var(--muted);">' + (c.service_name || c.service_type) + statusLabel + '</span>' +
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
        var response = await fetch('api/toggle_followup.php', {
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
        var response = await fetch('api/serve_followup.php', {
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

function init() {
    refreshQueue();
    refreshStats();
    setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
}

window.onload = init;
