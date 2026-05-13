var currentFilter = 'all';
var countersData = [];
var serviceTypesData = [];

function updateTime() {
    var el = document.getElementById('current-time');
    if (el) el.textContent = new Date().toLocaleTimeString();
}
setInterval(updateTime, 1000);
updateTime();

function showToast(message, type) {
    var container = document.getElementById('toastContainer');
    if (!container) return;
    var toast = document.createElement('div');
    var bg = 'bg-blue-500';
    if (type === 'success') bg = 'bg-green-500';
    if (type === 'error') bg = 'bg-red-500';
    if (type === 'warning') bg = 'bg-yellow-500';
    toast.className = 'toast ' + bg + ' text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 mb-2';
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
        }
    } catch (e) { console.error('Stats Error:', e); }
}

function updateServiceMetrics(metrics) {
    var container = document.getElementById('serviceMetrics');
    if (!container) return;
    if (!metrics || metrics.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-4 text-gray-400 italic">No service data for today</div>';
        return;
    }
    var html = '';
    for (var i = 0; i < metrics.length; i++) {
        var m = metrics[i];
        html += '<div class="bg-gray-50 rounded-lg p-3 border border-gray-100">' +
                '<div class="flex justify-between items-center mb-2">' +
                '<span class="font-bold text-sm text-gray-700">' + m.service_name + '</span>' +
                '<span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full font-bold">' + m.queue_prefix + '</span>' +
                '</div>' +
                '<div class="grid grid-cols-3 gap-1 text-center">' +
                '<div><div class="text-xs text-gray-400">Wait</div><div class="font-bold text-yellow-600">' + m.waiting + '</div></div>' +
                '<div><div class="text-xs text-gray-400">Srv</div><div class="font-bold text-blue-600">' + m.serving + '</div></div>' +
                '<div><div class="text-xs text-gray-400">Done</div><div class="font-bold text-green-600">' + m.completed + '</div></div>' +
                '</div></div>';
    }
    container.innerHTML = html;
}

async function refreshQueue() {
    try {
        var response = await fetch('api/get_queue.php');
        var data = await response.json();
        if (data.success) {
            updateQueueTable(data.customers || []);
            updateCounters(data.counters || []);
        }
    } catch (e) { console.error('Queue Error:', e); }
}

function updateQueueTable(customers) {
    var table = document.getElementById('queueTable');
    if (!table) return;
    var filtered = currentFilter === 'all' ? customers : customers.filter(function(c) { return c.status === currentFilter; });
    if (filtered.length === 0) {
        table.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No customers</td></tr>';
        return;
    }
    var html = '';
    for (var i = 0; i < filtered.length; i++) {
        var c = filtered[i];
        var statusClass = 'bg-gray-100 text-gray-800';
        if (c.status === 'waiting') statusClass = 'bg-yellow-100 text-yellow-800';
        if (c.status === 'serving') statusClass = 'bg-blue-100 text-blue-800';
        if (c.status === 'completed') statusClass = 'bg-green-100 text-green-800';
        
        html += '<tr class="hover:bg-gray-50">' +
                '<td class="px-3 py-2 font-bold">' + c.queue_number + '</td>' +
                '<td class="px-3 py-2">' + c.name + '</td>' +
                '<td class="px-3 py-2">' + c.service_type + '</td>' +
                '<td class="px-3 py-2"><span class="px-2 py-1 rounded-full text-xs ' + statusClass + '">' + c.status + '</span></td>' +
                '<td class="px-3 py-2">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                '<td class="px-3 py-2">' +
                (c.status === 'waiting' ? '<button onclick="callCustomer(' + c.id + ')" class="text-green-600 mr-2">Call</button>' : '') +
                (c.status === 'serving' ? '<button onclick="completeCustomer(' + c.id + ')" class="text-blue-600">Complete</button>' : '') +
                '</td></tr>';
    }
    table.innerHTML = html;
}

function updateCounters(counters) {
    var container = document.getElementById('countersStatus');
    if (!container) return;
    var html = '';
    for (var i = 0; i < counters.length; i++) {
        var c = counters[i];
        html += '<div class="border rounded-lg p-4 mb-2 ' + (c.is_online ? 'bg-green-50' : 'bg-gray-50') + '">' +
                '<div class="flex justify-between font-bold"><span>' + c.display_name + '</span><span>' + (c.is_online ? 'Online' : 'Offline') + '</span></div>' +
                '<div class="text-sm">' + (c.current_customer_name ? 'Serving: ' + c.current_queue_number : 'Available') + '</div>' +
                '</div>';
    }
    container.innerHTML = html;
}

async function callCustomer(id) {
    try {
        await fetch('api/call_customer.php', { method: 'POST', body: JSON.stringify({ customer_id: id }) });
        refreshQueue(); refreshStats();
    } catch (e) { showToast('Error calling customer', 'error'); }
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
            btn.className = 'filter-btn active px-4 py-2 rounded-lg bg-blue-100 text-blue-700 text-sm';
        } else {
            btn.className = 'filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm';
        }
    }
    refreshQueue();
}

document.getElementById('customerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    var name = document.getElementById('customerName').value.trim();
    var serviceType = document.getElementById('serviceType').value;
    if (!name || !serviceType) return;
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
    try {
        var response = await fetch('api/add_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, service_type: serviceType })
        });
        var data = await response.json();
        if (data.success) {
            document.getElementById('queueResult').classList.remove('hidden');
            document.getElementById('generatedQueue').textContent = data.queue_number;
            document.getElementById('queuePosition').textContent = 'Position in queue: ' + (data.data.queue_position || '--');
            document.getElementById('customerName').value = '';
            document.getElementById('serviceType').value = '';
            refreshQueue(); refreshStats();
            showToast('Queue number ' + data.queue_number + ' generated', 'success');
        } else {
            showToast(data.message || 'Failed to add customer', 'error');
        }
    } catch (e) {
        showToast('Error adding customer', 'error');
    }
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number';
});

function init() {
    refreshQueue();
    refreshStats();
    setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
}

window.onload = init;