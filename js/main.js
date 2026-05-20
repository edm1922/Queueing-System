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

function showSkeleton() {
    var table = document.getElementById('queueTable');
    if (!table) return;
    var sk = '';
    for (var i = 0; i < 4; i++) {
        sk += '<tr><td colspan="6" class="px-4 py-3"><div class="skeleton h-5 w-16 mb-1"></div></td></tr>' +
              '<tr class="' + (i % 2 === 0 ? 'bg-gray-50' : '') + '">' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-20"></div></td>' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-32"></div></td>' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-24"></div></td>' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-16"></div></td>' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-16"></div></td>' +
              '<td class="px-4 py-3"><div class="skeleton h-5 w-20"></div></td>' +
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
        var rowBg = (i % 2 === 0) ? '' : ' bg-gray-50';
        var statusClass = 'bg-gray-100 text-gray-800';
        if (c.status === 'waiting') statusClass = 'bg-yellow-100 text-yellow-800';
        if (c.status === 'serving') statusClass = 'bg-blue-100 text-blue-800';
        if (c.status === 'completed') statusClass = 'bg-green-100 text-green-800';
        
        var actions = '';
        if (c.status === 'waiting') {
            actions = '<button onclick="callCustomer(' + c.id + ')" class="inline-flex items-center gap-1 bg-green-50 text-green-700 hover:bg-green-100 px-2.5 py-1.5 rounded-lg text-xs font-medium transition" title="Call Customer"><i class="fas fa-bullhorn text-xs"></i> Call</button>';
        } else if (c.status === 'serving') {
            actions = '<button onclick="recallCustomer(' + c.id + ')" class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 px-2.5 py-1.5 rounded-lg text-xs font-medium transition mr-1" title="Recall (Announce Again)"><i class="fas fa-bell text-xs"></i></button>' +
                      '<button onclick="completeCustomer(' + c.id + ')" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg text-xs font-medium transition" title="Complete Service"><i class="fas fa-check text-xs"></i> Complete</button>';
        }
        
        html += '<tr class="hover:bg-gray-100' + rowBg + ' transition-colors">' +
                '<td class="px-4 py-3 font-bold text-gray-900">' + c.queue_number + '</td>' +
                '<td class="px-4 py-3 text-gray-700">' + c.name + '</td>' +
                '<td class="px-4 py-3 text-gray-600">' + c.service_type + '</td>' +
                '<td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + statusClass + '">' + c.status + '</span></td>' +
                '<td class="px-4 py-3 text-gray-500 text-xs">' + new Date(c.created_at).toLocaleTimeString() + '</td>' +
                '<td class="px-4 py-3">' + actions + '</td></tr>';
    }
    table.innerHTML = html;
}

function updateCounters(counters) {
    var container = document.getElementById('countersStatus');
    if (!container) return;
    var html = '';
    for (var i = 0; i < counters.length; i++) {
        var c = counters[i];
        var borderColor = 'border-l-green-400';
        var statusColor = 'bg-white';
        var dotColor = 'text-green-500';
        if (c.status_text === 'On Break') {
            borderColor = 'border-l-yellow-400';
            statusColor = 'bg-yellow-50';
            dotColor = 'text-yellow-500';
        } else if (c.status_text === 'Offline') {
            borderColor = 'border-l-gray-300';
            statusColor = 'bg-gray-50';
            dotColor = 'text-gray-400';
        }
        
        var servicesText = c.active_services || 'None';
        
        html += '<div class="rounded-lg p-4 mb-3 border border-l-4 ' + borderColor + ' ' + statusColor + '">' +
                '<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-2">' +
                    '<div class="flex items-center gap-2"><span class="inline-block w-2 h-2 rounded-full ' + dotColor + '"></span><div class="font-bold text-lg text-gray-900">' + c.display_name + '</div></div>' +
                    '<div class="flex items-center gap-2 mt-2 md:mt-0">' +
                        '<select onchange="changeWindowStatus(' + c.id + ', this.value)" class="text-sm border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">' +
                            '<option value="Online" ' + (c.status_text === 'Online' ? 'selected' : '') + '>Online</option>' +
                            '<option value="On Break" ' + (c.status_text === 'On Break' ? 'selected' : '') + '>On Break</option>' +
                            '<option value="Offline" ' + (c.status_text === 'Offline' ? 'selected' : '') + '>Offline</option>' +
                        '</select>' +
                        '<button onclick="openEditServicesModal(' + c.id + ')" class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition" title="Edit Services"><i class="fas fa-edit text-xs"></i></button>' +
                        '<button onclick="deleteWindow(' + c.id + ')" class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 rounded-lg transition" title="Delete Window"><i class="fas fa-trash text-xs"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="text-sm text-gray-500 ml-4"><i class="fas fa-tags mr-1.5 text-gray-400"></i> ' + servicesText + '</div>' +
                '<div class="text-sm ml-4 mt-1">' + (c.current_customer_name ? 'Serving: <span class="font-semibold text-blue-600">' + c.current_queue_number + '</span>' : '<span class="text-gray-400">Available</span>') + '</div>' +
                '</div>';
    }
    container.innerHTML = html;
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
    document.getElementById('addWindowModal').classList.remove('hidden');
}

function closeAddWindowModal() {
    document.getElementById('addWindowModal').classList.add('hidden');
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
        html += '<label class="flex items-center space-x-3 p-2 hover:bg-white rounded cursor-pointer">' +
                '<input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600 service-cb" value="' + s.code + '" ' + isChecked + '>' +
                '<span class="text-gray-700">' + s.name + '</span>' +
                '</label>';
    }
    document.getElementById('servicesCheckboxes').innerHTML = html;
    document.getElementById('editServicesModal').classList.remove('hidden');
}

function closeEditServicesModal() {
    document.getElementById('editServicesModal').classList.add('hidden');
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
            btn.className = 'filter-btn active px-4 py-2 rounded-lg bg-blue-100 text-blue-700 text-sm';
        } else {
            btn.className = 'filter-btn px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm';
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

function logout() {
    if (!confirm('Sign out of Queue Management System?')) return;
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('auth_token');
    sessionStorage.removeItem('user_data');
    window.location.href = 'login.php';
}

function init() {
    refreshQueue();
    refreshStats();
    setInterval(function() { refreshQueue(); refreshStats(); }, 5000);
}

window.onload = init;