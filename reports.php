<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Queue Management System</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23667eea'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        @media print {
            header, .gradient-bg, .bg-white.rounded-lg.shadow-lg.p-6.mb-6, #pagination, #searchTable, button {
                display: none !important;
            }
            body { background: white !important; padding: 0 !important; }
            .container { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .shadow-lg { shadow: none !important; box-shadow: none !important; border: 1px solid #eee; }
            .bg-gray-100 { background: white !important; }
            main { padding: 0 !important; }
            .grid { display: block !important; }
            .grid > div { margin-bottom: 20px; break-inside: avoid; }
            table { font-size: 10pt !important; }
            th, td { border: 1px solid #ddd !important; }
            .text-3xl { font-size: 1.5rem !important; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; color: black; }
        }
        @media screen { .print-header { display: none; } }
        button:focus-visible, a:focus-visible, select:focus-visible, input:focus-visible { outline: 2px solid #667eea; outline-offset: 2px; border-radius: 0.375rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="print-header">
        <h1 class="text-2xl font-bold">Queue Management System Report</h1>
        <p id="printDateRange"></p>
        <p class="text-sm">Generated on: <span id="reportGenDate"></span></p>
    </div>
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-white hover:text-gray-200"><i class="fas fa-arrow-left text-xl"></i></a>
                    <h1 class="text-2xl font-bold"><i class="fas fa-chart-bar mr-3"></i>Reports & Analytics</h1>
                </div>
                <div><span id="currentDateTime" class="text-lg"></span></div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6">
        <div class="card p-5 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <select id="dateRange" class="px-4 py-2 border border-gray-300 rounded-lg" onchange="updateDateRange()">
                        <option value="today">Today</option><option value="yesterday">Yesterday</option><option value="week">This Week</option>
                        <option value="month" selected>This Month</option><option value="custom">Custom Range</option>
                    </select>
                </div>
                <div id="customDateFields" class="hidden flex gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">From</label><input type="date" id="dateFrom" class="px-4 py-2 border border-gray-300 rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">To</label><input type="date" id="dateTo" class="px-4 py-2 border border-gray-300 rounded-lg"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                    <select id="serviceFilter" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Services</option>
                        <option value="insurance">Insurance</option><option value="benefits">Benefits</option>
                        <option value="id_renewal">ID Renewal</option><option value="atm_renewal">ATM claim</option><option value="other">Other</option>
                    </select>
                </div>
                <button onclick="loadReport()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-filter mr-2"></i>Apply</button>
                <div class="ml-auto flex gap-2">
                    <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700"><i class="fas fa-file-excel mr-2"></i>Excel</button>
                    <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700"><i class="fas fa-print mr-2"></i>Print</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="card p-5 border-t-4 border-blue-400"><div class="flex items-center"><div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4"><i class="fas fa-users text-xl"></i></div><div><h3 class="text-3xl font-bold text-gray-800" id="totalCustomers">0</h3><p class="text-gray-600 text-sm">Total Served</p></div></div></div>
            <div class="card p-5 border-t-4 border-green-400"><div class="flex items-center"><div class="p-3 rounded-full bg-green-100 text-green-600 mr-4"><i class="fas fa-clock text-xl"></i></div><div><h3 class="text-3xl font-bold text-gray-800" id="avgWaitTime">0:00</h3><p class="text-gray-600 text-sm">Avg Wait</p></div></div></div>
            <div class="card p-5 border-t-4 border-purple-400"><div class="flex items-center"><div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4"><i class="fas fa-user-check text-xl"></i></div><div><h3 class="text-3xl font-bold text-gray-800" id="avgServiceTime">0:00</h3><p class="text-gray-600 text-sm">Avg Service</p></div></div></div>
            <div class="card p-5 border-t-4 border-yellow-400"><div class="flex items-center"><div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4"><i class="fas fa-tachometer-alt text-xl"></i></div><div><h3 class="text-3xl font-bold text-gray-800" id="customersPerHour">0</h3><p class="text-gray-600 text-sm">Per Hour</p></div></div></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="card p-5"><h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-chart-pie mr-2 text-blue-500"></i>Service Breakdown</h3><div id="serviceBreakdown" class="space-y-3"></div></div>
            <div class="card p-5"><h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-chart-line mr-2 text-blue-500"></i>Hourly Distribution</h3><div id="hourlyChart" class="space-y-2"></div></div>
        </div>

        <div class="card p-5">
            <div class="flex justify-between items-center mb-4"><h3 class="text-lg font-bold text-gray-800"><i class="fas fa-table mr-2 text-blue-500"></i>Detailed Report</h3><input type="text" id="searchTable" placeholder="Search..." class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500"></div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead><tr class="bg-gray-50 border-b border-gray-200"><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Date</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Queue #</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Customer</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Service</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Window</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Status</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Wait</th><th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Service</th></tr></thead>
                    <tbody id="reportTable" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
            <div id="pagination" class="flex justify-center mt-4 gap-2"></div>
        </div>
    </main>

    <div id="toast" class="fixed bottom-4 right-4 hidden px-6 py-3 rounded-lg shadow-lg z-50 bg-green-500 text-white"></div>

    <script>
        let reportData = []; let currentPage = 1; const itemsPerPage = 50;
        function updateDateTime() { document.getElementById('currentDateTime').textContent = new Date().toLocaleString(); }
        setInterval(updateDateTime, 1000); updateDateTime();

        function updateDateRange() { document.getElementById('customDateFields').classList.toggle('hidden', document.getElementById('dateRange').value !== 'custom'); }

        function getDateRange() {
            const range = document.getElementById('dateRange').value; const today = new Date(); let from, to;
            switch(range) { case 'today': from = to = today.toISOString().split('T')[0]; break; case 'yesterday': const y = new Date(today); y.setDate(y.getDate()-1); from = to = y.toISOString().split('T')[0]; break; case 'week': from = new Date(today.setDate(today.getDate()-today.getDay())).toISOString().split('T')[0]; to = new Date().toISOString().split('T')[0]; break; case 'month': from = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]; to = new Date().toISOString().split('T')[0]; break; case 'custom': from = document.getElementById('dateFrom').value; to = document.getElementById('dateTo').value; break; }
            return { from, to };
        }

        async function loadReport() {
            const { from, to } = getDateRange(); const serviceType = document.getElementById('serviceFilter').value;
            
            // Update print-only header info
            document.getElementById('reportGenDate').textContent = new Date().toLocaleString();
            document.getElementById('printDateRange').textContent = `Period: ${from} to ${to}`;
            
            try {
                const params = new URLSearchParams({ from, to }); if (serviceType) params.append('service_type', serviceType);
                const response = await fetch(`api/reports/daily.php?${params}`); const data = await response.json();
                if (data.success) { reportData = data.data.customers; currentPage = 1; updateSummary(data.data.summary); updateServiceBreakdown(data.data.by_service); updateHourlyChart(data.data.hourly); updateTable(); }
            } catch (error) { showToast('Failed to load report', 'error'); }
        }

        function updateSummary(summary) { document.getElementById('totalCustomers').textContent = summary.total_served || 0; document.getElementById('avgWaitTime').textContent = formatDuration(summary.avg_wait_seconds); document.getElementById('avgServiceTime').textContent = formatDuration(summary.avg_service_seconds); document.getElementById('customersPerHour').textContent = summary.customers_per_hour || 0; }

        function updateServiceBreakdown(services) {
            const c = document.getElementById('serviceBreakdown');
            if (!services || services.length === 0) { c.innerHTML = '<div class="text-gray-400 text-center py-4">No data</div>'; return; }
            c.innerHTML = services.map(s => { const percent = s.percent || 0; const color = s.service_type === 'insurance' ? 'bg-blue-500' : s.service_type === 'benefits' ? 'bg-green-500' : s.service_type === 'id_renewal' ? 'bg-purple-500' : 'bg-orange-500';
                return `<div class="flex items-center gap-4"><div class="w-24 text-sm">${s.service_name}</div><div class="flex-1 bg-gray-200 rounded-full h-4"><div class="${color} h-4 rounded-full" style="width:${percent}%"></div></div><div class="w-24 text-right text-sm">${s.total_served} (${percent}%)</div></div>`;
            }).join('');
        }

        function updateHourlyChart(hourly) {
            const c = document.getElementById('hourlyChart');
            if (!hourly || hourly.length === 0) { c.innerHTML = '<div class="text-gray-400 text-center py-4">No data</div>'; return; }
            const maxCount = Math.max(...hourly.map(h => h.count), 1);
            c.innerHTML = hourly.map(h => { const p = (h.count / maxCount) * 100; return `<div class="flex items-center gap-2"><div class="w-16 text-sm">${h.hour}:00</div><div class="flex-1 bg-gray-200 rounded-full h-6"><div class="bg-blue-500 h-6 rounded-full flex items-center px-2" style="width:${p}%"><span class="text-white text-xs">${h.count}</span></div></div></div>`; }).join('');
        }

        function updateTable() {
            const tbody = document.getElementById('reportTable'); const search = document.getElementById('searchTable').value.toLowerCase();
            let filtered = search ? reportData.filter(c => (c.name || '').toLowerCase().includes(search) || (c.queue_number || '').toLowerCase().includes(search) || (c.service_type || '').toLowerCase().includes(search)) : reportData;
            const start = (currentPage - 1) * itemsPerPage; const pageData = filtered.slice(start, start + itemsPerPage);
            if (pageData.length === 0) { tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No records</td></tr>'; return; }
            tbody.innerHTML = pageData.map(c => `<tr class="hover:bg-gray-50"><td class="px-4 py-2">${new Date(c.created_at).toLocaleDateString()}</td><td class="px-4 py-2 font-mono font-bold">${c.queue_number}</td><td class="px-4 py-2">${c.name}</td><td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs ${getServiceClass(c.service_type)}">${(c.service_name || c.service_type).replace('_',' ')}</span></td><td class="px-4 py-2">${c.window_name || '-'}</td><td class="px-4 py-2"><span class="px-2 py-1 rounded text-xs ${getStatusClass(c.status)}">${c.status}</span></td><td class="px-4 py-2">${formatDuration(c.wait_duration)}</td><td class="px-4 py-2">${formatDuration(c.service_duration)}</td></tr>`).join('');
            updatePagination(filtered.length);
        }

        function updatePagination(total) {
            const c = document.getElementById('pagination'); const pages = Math.ceil(total / itemsPerPage);
            if (pages <= 1) { c.innerHTML = ''; return; }
            c.innerHTML = Array.from({length: pages}, (_, i) => `<button onclick="goToPage(${i+1})" class="px-3 py-1 rounded ${i+1 === currentPage ? 'bg-blue-600 text-white' : 'bg-gray-200'}">${i+1}</button>`).join('');
        }

        function goToPage(page) { currentPage = page; updateTable(); }
        function getServiceClass(s) { return { insurance:'bg-blue-100 text-blue-800', benefits:'bg-green-100 text-green-800', id_renewal:'bg-purple-100 text-purple-800', atm_renewal:'bg-orange-100 text-orange-800' }[s] || 'bg-gray-100 text-gray-800'; }
        function getStatusClass(s) { return { completed:'bg-green-100 text-green-800', cancelled:'bg-red-100 text-red-800', serving:'bg-blue-100 text-blue-800' }[s] || 'bg-gray-100 text-gray-800'; }
        function formatDuration(seconds) { 
            if (!seconds || seconds === 0) return '0:00'; 
            const isNegative = seconds < 0;
            const absSeconds = Math.abs(seconds);
            const m = Math.floor(absSeconds / 60); 
            const s = absSeconds % 60; 
            return `${isNegative ? '-' : ''}${m}:${s.toString().padStart(2, '0')}`; 
        }
        function exportToExcel() { const { from, to } = getDateRange(); window.open(`api/reports/daily.php?from=${from}&to=${to}&export=excel`, '_blank'); }
        function showToast(m, t) { const toast = document.getElementById('toast'); toast.textContent = m; toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${t === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`; toast.classList.remove('hidden'); setTimeout(() => toast.classList.add('hidden'), 3000); }
        document.getElementById('searchTable').addEventListener('input', updateTable);
        updateDateRange(); loadReport();
    </script>
</body>
</html>