let currentFilter = 'all';
let countersData = [];
let serviceTypesData = [];

function updateTime() {
    document.getElementById('current-time').textContent = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateTime, 1000);
updateTime();

document.getElementById('customerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('customerName').value.trim();
    const serviceType = document.getElementById('serviceType').value;
    const submitBtn = document.getElementById('submitBtn');
    if (!name || !serviceType) { showToast('Please fill in all fields', 'error'); return; }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    try {
        const response = await fetch('api/add_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name, service_type: serviceType }) });
        const data = await response.json();
        if (data.success) {
            document.getElementById('generatedQueue').textContent = data.queue_number;
            document.getElementById('queuePosition').textContent = `Position: ${data.data?.queue_position || 'N/A'}`;
            document.getElementById('queueResult').classList.remove('hidden');
            document.getElementById('customerForm').reset();
            refreshQueue(); refreshStats();
            showToast(`Queue number ${data.queue_number} generated`, 'success');
            setTimeout(() => document.getElementById('queueResult').classList.add('hidden'), 8000);
        } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('An error occurred', 'error'); }
    finally { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number'; }
});

async function refreshQueue() {
    try {
        const response = await fetch('api/get_queue.php');
        const data = await response.json();
        if (data.success) { countersData = data.counters; updateQueueTable(data.customers); updateCounters(data.counters); }
    } catch (error) { console.error('Error:', error); }
}

function updateQueueTable(customers) {
    const table = document.getElementById('queueTable');
    const filtered = currentFilter === 'all' ? customers : customers.filter(c => c.status === currentFilter);
    if (!filtered || filtered.length === 0) { table.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-inbox text-4xl mb-2 block"></i>No customers</td></tr>'; return; }
    table.innerHTML = '';
    filtered.forEach(customer => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        let statusClass = '', statusIcon = '';
        switch(customer.status) { case 'waiting': statusClass = 'bg-yellow-100 text-yellow-800'; statusIcon = 'fa-clock'; break; case 'serving': statusClass = 'bg-blue-100 text-blue-800'; statusIcon = 'fa-user'; break; case 'completed': statusClass = 'bg-green-100 text-green-800'; statusIcon = 'fa-check'; break; case 'cancelled': statusClass = 'bg-red-100 text-red-800'; statusIcon = 'fa-times'; break; }
        const serviceLabels = { insurance: { name: 'Insurance', color: 'bg-blue-100 text-blue-800' }, benefits: { name: 'Benefits', color: 'bg-green-100 text-green-800' }, id_renewal: { name: 'ID Renewal', color: 'bg-purple-100 text-purple-800' }, atm_renewal: { name: 'ATM Renewal', color: 'bg-orange-100 text-orange-800' } };
        const serviceLabel = serviceLabels[customer.service_type] || { name: customer.service_type, color: 'bg-gray-100 text-gray-800' };
        row.innerHTML = `<td class="px-3 py-2"><span class="queue-number text-lg font-bold ${customer.is_redistributed ? 'text-orange-500' : ''}">${customer.queue_number}</span>${customer.is_redistributed ? '<span class="text-xs text-orange-500 block">Redistributed</span>' : ''}</td><td class="px-3 py-2 text-sm">${customer.name}</td><td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs font-medium ${serviceLabel.color}">${serviceLabel.name}</span></td><td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs font-medium ${statusClass}"><i class="fas ${statusIcon} mr-1"></i>${customer.status}</span></td><td class="px-3 py-2 text-sm text-gray-500">${new Date(customer.created_at).toLocaleTimeString()}</td><td class="px-3 py-2"><div class="flex space-x-1">${customer.status === 'waiting' ? `<button onclick="callCustomer(${customer.id})" class="bg-green-500 text-white px-2 py-1 rounded text-xs hover:bg-green-600"><i class="fas fa-bullhorn mr-1"></i>Call</button>` : ''}${customer.status === 'serving' ? `<button onclick="completeCustomer(${customer.id})" class="bg-blue-500 text-white px-2 py-1 rounded text-xs hover:bg-blue-600"><i class="fas fa-check mr-1"></i>Complete</button>` : ''}${customer.status !== 'completed' && customer.status !== 'cancelled' ? `<button onclick="cancelCustomer(${customer.id})" class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600"><i class="fas fa-times mr-1"></i>Cancel</button>` : ''}</div></td>`;
        table.appendChild(row);
    });
}

function updateCounters(counters) {
    const container = document.getElementById('countersStatus');
    if (!counters || counters.length === 0) { container.innerHTML = '<div class="text-center text-gray-500">No counters</div>'; return; }
    container.innerHTML = '';
    counters.forEach(counter => {
        const div = document.createElement('div');
        const isOffline = !counter.is_online;
        div.className = `border-2 rounded-lg p-4 ${isOffline ? 'counter-offline border-red-300' : 'border-green-200 bg-green-50'}`;
        const services = counter.service_assignments || [];
        const serviceNames = services.map(s => s.service_type.replace('_', ' ')).join(', ') || 'All services';
        div.innerHTML = `<div class="flex justify-between items-center mb-3"><div><h4 class="font-bold text-lg">${counter.display_name || counter.name}</h4><span class="text-sm text-gray-500">Window ${counter.window_number || counter.id}</span></div><div class="flex items-center"><span class="relative flex h-3 w-3 mr-2"><span class="pulse-dot absolute inline-flex h-full w-full rounded-full ${isOffline ? 'bg-red-400' : 'bg-green-400'}"></span></span><span class="px-3 py-1 rounded-full text-sm font-medium ${isOffline ? 'bg-red-200 text-red-800' : 'bg-green-200 text-green-800'}">${isOffline ? 'Offline' : 'Online'}</span></div></div><div class="text-sm text-gray-600 mb-3"><i class="fas fa-cogs mr-1"></i> ${serviceNames}</div><div class="flex items-center justify-between"><div class="text-sm">${counter.current_customer_name ? `<span class="text-blue-600"><i class="fas fa-user mr-1"></i>${counter.current_queue_number || 'Serving'}</span>` : '<span class="text-gray-400">Available</span>'}</div><div class="flex gap-2">${isOffline ? `<button onclick="toggleCounter(${counter.id}, true)" class="bg-green-500 text-white px-3 py-1 rounded text-xs hover:bg-green-600"><i class="fas fa-power-off mr-1"></i>Go Online</button>` : `<button onclick="toggleCounter(${counter.id}, false)" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600"><i class="fas fa-power-off mr-1"></i>Go Offline</button>`}</div></div>${counter.customers_served > 0 ? `<div class="mt-2 pt-2 border-t border-gray-200 text-xs text-gray-500">Served: ${counter.customers_served} | Avg: ${formatDuration(counter.avg_service_time)}</div>` : ''}`;
        container.appendChild(div);
    });
}

async function toggleCounter(counterId, isOnline) {
    try {
        const response = await fetch('api/counter/toggle_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ counter_id: counterId, is_online: isOnline }) });
        const data = await response.json();
        if (data.success) { showToast(data.message, isOnline ? 'success' : 'warning'); if (data.data.affected_services?.length > 0) showToast(`${data.data.reassigned_customers} customers reassigned`, 'info'); refreshQueue(); refreshStats(); loadRedistributionLogs(); } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('Failed to update counter', 'error'); }
}

async function callCustomer(customerId) {
    try {
        const response = await fetch('api/call_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: customerId }) });
        const data = await response.json();
        if (data.success) { showToast(`Calling ${data.data.customer.queue_number}`, 'success'); refreshQueue(); refreshStats(); } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('Failed to call customer', 'error'); }
}

async function completeCustomer(customerId) {
    try {
        const response = await fetch('api/complete_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: customerId }) });
        const data = await response.json();
        if (data.success) { showToast('Customer completed', 'success'); refreshQueue(); refreshStats(); } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('Failed', 'error'); }
}

async function cancelCustomer(customerId) {
    if (!confirm('Cancel this customer?')) return;
    try {
        const response = await fetch('api/cancel_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: customerId }) });
        const data = await response.json();
        if (data.success) { showToast('Customer cancelled', 'warning'); refreshQueue(); refreshStats(); } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('Failed', 'error'); }
}

function filterQueue(filter) {
    currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(btn => { btn.className = `filter-btn px-4 py-2 rounded-lg ${btn.dataset.filter === filter ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'} text-sm`; });
    refreshQueue();
}

async function refreshStats() {
    try {
        const response = await fetch('api/get_stats.php');
        const data = await response.json();
        if (data.success) { const stats = data.data.basic; document.getElementById('waiting-count').textContent = stats.waiting; document.getElementById('serving-count').textContent = stats.serving; document.getElementById('completed-count').textContent = stats.completed; document.getElementById('today-count').textContent = stats.today_total; }
    } catch (error) { console.error('Error:', error); }
}

async function loadRedistributionLogs() {
    try {
        const response = await fetch('api/counter/get_redistribution_logs.php?hours=24');
        const data = await response.json();
        const container = document.getElementById('redistributionLogs');
        if (data.success && data.data.logs.length > 0) {
            container.innerHTML = data.data.logs.slice(0, 10).map(log => { const icon = log.event_type === 'counter_offline' ? 'fa-arrow-down text-red-500' : log.event_type === 'counter_online' ? 'fa-arrow-up text-green-500' : 'fa-exchange-alt text-orange-500';
                return `<div class="flex items-start gap-2 p-2 bg-gray-50 rounded"><i class="fas ${icon} mt-1"></i><div class="flex-1 text-xs"><div class="font-medium">${log.counter_name} ${log.event_type.replace('_', ' ')}</div><div class="text-gray-500">${new Date(log.created_at).toLocaleTimeString()}</div>${log.reassigned_customers > 0 ? `<div class="text-orange-600">${log.reassigned_customers} customers reassigned</div>` : ''}</div></div>`;
            }).join('');
        } else { container.innerHTML = '<div class="text-gray-400 text-center py-4">No events today</div>'; }
    } catch (error) { console.error('Error:', error); }
}

function openAnnouncementModal() { document.getElementById('announcementModal').classList.remove('hidden'); loadAnnouncements(); }
function closeAnnouncementModal() { document.getElementById('announcementModal').classList.add('hidden'); }

async function loadAnnouncements() {
    try {
        const [activeRes, presetsRes] = await Promise.all([fetch('api/announcement/index.php?active=true'), fetch('api/announcement/index.php?type=preset')]);
        const activeData = await activeRes.json();
        const presetsData = await presetsRes.json();
        if (activeData.success) {
            document.getElementById('activeAnnouncements').innerHTML = activeData.data.length > 0 ? activeData.data.map(a => `<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"><div class="flex-1"><span class="px-2 py-1 rounded text-xs ${getAnnouncementTypeClass(a.type)}">${a.type}</span>${a.title ? `<span class="font-medium ml-2">${a.title}</span>` : ''}<div class="text-sm text-gray-600">${a.message}</div></div><button onclick="deleteAnnouncement(${a.id})" class="text-red-500 hover:text-red-700 ml-2"><i class="fas fa-trash"></i></button></div>`).join('') : '<div class="text-gray-400 text-center py-4">No active</div>';
        }
        if (presetsData.success) { document.getElementById('presetAnnouncements').innerHTML = presetsData.data.filter(p => p.is_preset).map(p => `<button onclick="usePresetAnnouncement(${p.id})" class="w-full text-left p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition"><span class="font-medium text-sm">${p.title || 'Preset'}</span><div class="text-xs text-gray-600">${p.message.substring(0, 60)}...</div></button>`).join(''); }
    } catch (error) { console.error('Error:', error); }
}

async function addAnnouncement() {
    const title = document.getElementById('announcementTitle').value.trim();
    const message = document.getElementById('announcementMessage').value.trim();
    const type = document.getElementById('announcementType').value;
    if (!message) { showToast('Enter a message', 'error'); return; }
    try {
        const response = await fetch('api/announcement/index.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ title, message, type, priority: type === 'urgent' ? 10 : type === 'warning' ? 5 : 1 }) });
        const data = await response.json();
        if (data.success) { showToast('Announcement added', 'success'); document.getElementById('announcementTitle').value = ''; document.getElementById('announcementMessage').value = ''; loadAnnouncements(); } else { showToast(data.message || 'Failed', 'error'); }
    } catch (error) { showToast('Failed', 'error'); }
}

async function deleteAnnouncement(id) {
    if (!confirm('Delete?')) return;
    try { const response = await fetch(`api/announcement/index.php?id=${id}`, { method: 'DELETE' }); const data = await response.json(); if (data.success) { showToast('Deleted', 'success'); loadAnnouncements(); } } catch (error) { showToast('Failed', 'error'); }
}

function getAnnouncementTypeClass(type) { return type === 'urgent' ? 'bg-red-100 text-red-800' : type === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'; }

function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    const bgClass = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    toast.className = `toast ${bgClass} text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'slideIn 0.3s ease-out reverse'; setTimeout(() => toast.remove(), 300); }, 4000);
}

function formatDuration(seconds) { if (!seconds) return '0:00'; const mins = Math.floor(seconds / 60); const secs = seconds % 60; return `${mins}:${secs.toString().padStart(2, '0')}`; }

setInterval(() => { refreshQueue(); refreshStats(); }, 10000);
refreshQueue(); refreshStats(); loadRedistributionLogs();