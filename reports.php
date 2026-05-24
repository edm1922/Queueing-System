<?php include 'config.php';
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
$branch_name = htmlspecialchars($s['branch_name'] ?? '');
$company_logo = htmlspecialchars($s['company_logo'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        .filter-field { padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 0.8125rem; background: var(--card); }
        .filter-field:focus { outline: none; border-color: var(--ring); box-shadow: 0 0 0 3px hsl(215 60% 18% / 0.15); }
        .rpt-table th { padding: 0.625rem 1rem; text-align: left; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted); background: var(--secondary); border-bottom: 1px solid var(--border); }
        .rpt-table td { padding: 0.625rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.8125rem; }
        .rpt-table tr:hover td { background: var(--secondary); }
        .progress-bar { height: 0.5rem; background: var(--secondary); border-radius: 9999px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.5s var(--ease-out-expo); }
        @media print {
            header, .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; }
            .rpt-table th, .rpt-table td { border: 1px solid #ddd !important; }
        }
        .print-header { display: none; }
        @media print { .print-header { display: block !important; text-align: center; margin-bottom: 1.5rem; } }
    </style>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background);">
    <div class="print-header">
        <h1 class="text-xl font-bold">Queue Management System Report</h1>
        <p id="printDateRange" class="text-sm" style="color: var(--muted);"></p>
    </div>

    <!-- SiteNav -->
    <nav class="sticky top-0 z-50 no-print" style="background: #b91c1c; color: white; border-bottom: 1px solid rgba(255,255,255,0.15);">
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
                    <a href="display.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Live Display</a>
                    <a href="kiosk.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Kiosk</a>
                    <a href="index.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Operator</a>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="background: rgba(255,255,255,0.1); color: white;">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded" style="background: rgba(255,255,255,0.1);">
                    <div class="w-1.5 h-1.5 rounded-full" style="background: #34d399; animation: pulse-dot 2s infinite;"></div>
                    <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">All Systems Operational</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full max-w-[1440px] mx-auto p-8 flex flex-col gap-8">
        <div class="flex items-baseline justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color: var(--muted);">Analytics</h2>
                <h1 class="text-3xl font-extrabold tracking-tight mt-1">Today &middot; <span style="color: var(--muted);"><?php echo date('D, M j, Y'); ?></span></h1>
            </div>
            <div class="flex gap-2 font-mono text-[10px] no-print">
                <button onclick="setRange('today')" class="px-3 py-2 uppercase tracking-widest rounded border" id="rangeToday" style="background: var(--foreground); color: var(--background); border-color: var(--foreground);">Today</button>
                <button onclick="setRange('week')" id="rangeWeek" class="px-3 py-2 uppercase tracking-widest rounded border" style="border-color: var(--border); background: var(--card);">7d</button>
                <button onclick="setRange('month')" id="rangeMonth" class="px-3 py-2 uppercase tracking-widest rounded border" style="border-color: var(--border); background: var(--card);">30d</button>
                <button onclick="toggleCustomRange()" class="px-3 py-2 uppercase tracking-widest rounded border" style="border-color: var(--border); background: var(--card);">Custom</button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card p-5 animate-entry no-print">
            <div class="flex flex-wrap gap-4 items-end">
                <div id="customDateFields" class="hidden flex gap-4">
                    <div><label class="label-md block mb-1">From</label><input type="date" id="dateFrom" class="filter-field"></div>
                    <div><label class="label-md block mb-1">To</label><input type="date" id="dateTo" class="filter-field"></div>
                </div>
                <div><label class="label-md block mb-1">Service</label>
                    <select id="serviceFilter" class="filter-field">
                        <option value="">All Services</option>
                        <option value="insurance">Insurance</option><option value="benefits">Benefits</option>
                        <option value="id_renewal">ID Renewal</option><option value="atm_renewal">ATM claim</option><option value="other">Other</option>
                    </select>
                </div>
                <button onclick="loadReport()" class="btn btn-primary text-[11px] py-2 px-4"><i class="fas fa-filter mr-2"></i>Apply</button>
                <div class="ml-auto flex gap-2">
                    <button onclick="exportToExcel()" class="btn btn-secondary text-[11px] py-2 px-4"><i class="fas fa-file-excel mr-2" style="color: #059669;"></i>Excel</button>
                    <button onclick="window.print()" class="btn btn-secondary text-[11px] py-2 px-4"><i class="fas fa-print mr-2"></i>Print</button>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card p-5 animate-entry" style="animation-delay: 0ms;">
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Tickets served</div>
                <div class="text-3xl font-extrabold tracking-tight mt-1.5" id="totalCustomers" style="color: var(--foreground);">0</div>
                <div class="text-[11px] font-mono mt-1" id="totalDelta" style="color: var(--muted);">+0%</div>
            </div>
            <div class="card p-5 animate-entry" style="animation-delay: 60ms;">
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Avg wait</div>
                <div class="text-3xl font-extrabold tracking-tight mt-1.5 font-mono tabular-nums" id="avgWaitTime">0:00</div>
                <div class="text-[11px] font-mono mt-1" id="waitDelta" style="color: var(--muted);">--</div>
            </div>
            <div class="card p-5 animate-entry" style="animation-delay: 120ms;">
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Avg handle</div>
                <div class="text-3xl font-extrabold tracking-tight mt-1.5 font-mono tabular-nums" id="avgServiceTime">0:00</div>
                <div class="text-[11px] font-mono mt-1" id="serviceDelta" style="color: var(--muted);">--</div>
            </div>
            <div class="card p-5 animate-entry" style="animation-delay: 180ms;">
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--muted);">Per hour</div>
                <div class="text-3xl font-extrabold tracking-tight mt-1.5 font-mono tabular-nums" id="customersPerHour" style="color: var(--brand-gold);">0</div>
                <div class="text-[11px] font-mono mt-1" id="perHourDelta" style="color: var(--muted);">--</div>
            </div>
        </div>

        <!-- Hourly distribution + Service breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card p-6 animate-entry" style="animation-delay: 100ms;">
                <div class="flex items-baseline justify-between mb-6">
                    <h3 class="text-xs font-bold uppercase tracking-widest">Hourly Distribution</h3>
                    <span class="text-[10px] font-mono" style="color: var(--muted);">Peak <span id="peakHour">--</span></span>
                </div>
                <div class="flex items-end gap-1.5" style="height: 12rem;" id="hourlyBars">
                    <!-- Injected by JS -->
                </div>
            </div>
            <div class="card p-6 animate-entry" style="animation-delay: 150ms;">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-6">Service Breakdown</h3>
                <div id="serviceBreakdown" class="flex flex-col gap-5">
                    <!-- Injected by JS -->
                </div>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="card animate-entry" style="animation-delay: 200ms;">
            <div class="p-4 border-b border-border flex items-center justify-between flex-wrap gap-3" style="background: var(--secondary);">
                <span class="text-xs font-bold uppercase tracking-widest">Detailed Report</span>
                <input type="text" id="searchTable" placeholder="Search..." class="filter-field text-sm" style="max-width: 200px;">
            </div>
            <div class="overflow-x-auto">
                <table class="w-full rpt-table">
                    <thead><tr><th>Date</th><th>Queue #</th><th>Customer</th><th>Service</th><th>Window</th><th>Status</th><th>Wait</th><th>Service</th></tr></thead>
                    <tbody id="reportTable"></tbody>
                </table>
            </div>
            <div id="pagination" class="flex justify-center gap-2 p-4"></div>
        </div>
    </main>

    <!-- StatusFooter -->
    <footer class="sticky bottom-0 left-0 w-full p-6 flex justify-between items-center no-print" style="background: hsl(210 30% 97% / 0.8); backdrop-filter: blur(12px); pointer-events: none;">
        <div class="flex items-center gap-6">
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Terminal ID</span>
                <span class="text-[11px] font-mono" style="color: var(--foreground);">REPORTS</span>
            </div>
            <div class="flex flex-col">
                <span class="text-[9px] font-bold uppercase tracking-widest" style="color: var(--muted);">Last Sync</span>
                <span class="text-[11px] font-mono tabular-nums" id="footerTime" style="color: var(--foreground);">--:--:--</span>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1 rounded shadow-sm" style="background: var(--card); border: 1px solid var(--border);">
            <div class="w-1.5 h-1.5 rounded-full" style="background: var(--primary);"></div>
            <span style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em;">v4.2.0-stable</span>
        </div>
    </footer>

    <div id="toast" class="fixed bottom-4 right-4 hidden px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium" style="background: #059669;"></div>

    <script>
        var reportData = [], currentPage = 1, itemsPerPage = 50, currentRange = 'today';

        function updateFooterTime() {
            var el = document.getElementById('footerTime');
            if (!el) return;
            var d = new Date();
            el.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
        }
        setInterval(updateFooterTime, 200);
        updateFooterTime();

        function setRange(range) {
            currentRange = range;
            ['today','week','month'].forEach(function(r) {
                var el = document.getElementById('range' + r.charAt(0).toUpperCase() + r.slice(1));
                if (r === range) { el.style.background = 'var(--foreground)'; el.style.color = 'var(--background)'; el.style.borderColor = 'var(--foreground)'; }
                else { el.style.background = 'var(--card)'; el.style.color = 'var(--foreground)'; el.style.borderColor = 'var(--border)'; }
            });
            document.getElementById('customDateFields').classList.add('hidden');
            loadReport();
        }

        function toggleCustomRange() {
            document.getElementById('customDateFields').classList.toggle('hidden');
            if (!document.getElementById('customDateFields').classList.contains('hidden')) {
                document.getElementById('dateFrom').value = new Date().toISOString().split('T')[0];
                document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];
            }
        }

        function getDateRange() {
            var today = new Date(), from, to;
            switch(currentRange) {
                case 'today': from = to = today.toISOString().split('T')[0]; break;
                case 'week': from = new Date(today.setDate(today.getDate()-today.getDay())).toISOString().split('T')[0]; to = new Date().toISOString().split('T')[0]; break;
                case 'month': from = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]; to = new Date().toISOString().split('T')[0]; break;
                case 'custom': from = document.getElementById('dateFrom').value; to = document.getElementById('dateTo').value; break;
            }
            return { from: from, to: to };
        }

        async function loadReport() {
            var d = getDateRange(), serviceType = document.getElementById('serviceFilter').value;
            document.getElementById('printDateRange').textContent = 'Period: ' + d.from + ' to ' + d.to;
            try {
                var params = new URLSearchParams({ from: d.from, to: d.to });
                if (serviceType) params.append('service_type', serviceType);
                var res = await fetch('api/reports/daily.php?' + params.toString());
                var data = await res.json();
                if (data.success) { reportData = data.data.customers; currentPage = 1; updateSummary(data.data.summary); updateServiceBreakdown(data.data.by_service); updateHourlyChart(data.data.hourly); updateTable(); }
            } catch (e) { showToast('Failed to load report', 'error'); }
        }

        function updateSummary(summary) {
            document.getElementById('totalCustomers').textContent = summary.total_served || 0;
            document.getElementById('totalDelta').textContent = '+0%';
            document.getElementById('avgWaitTime').textContent = formatDuration(summary.avg_wait_seconds);
            document.getElementById('waitDelta').textContent = '--';
            document.getElementById('avgServiceTime').textContent = formatDuration(summary.avg_service_seconds);
            document.getElementById('serviceDelta').textContent = '--';
            document.getElementById('customersPerHour').textContent = summary.customers_per_hour || 0;
            document.getElementById('perHourDelta').textContent = '--';
        }

        function updateServiceBreakdown(services) {
            var c = document.getElementById('serviceBreakdown');
            if (!services || services.length === 0) { c.innerHTML = '<div style="color: var(--muted); text-align: center; padding: 2rem;">No data</div>'; return; }
            var total = services.reduce(function(a,s){return a+(s.total_served||0);}, 0);
            c.innerHTML = services.map(function(s) {
                var pct = total > 0 ? Math.round((s.total_served/total)*100) : 0;
                var colors = { insurance:'var(--primary)', benefits:'#059669', id_renewal:'#7c3aed', atm_renewal:'var(--brand-gold)' };
                var color = colors[s.service_type] || 'var(--muted)';
                return '<div>' +
                    '<div class="flex justify-between items-baseline mb-2"><div class="flex items-center gap-3"><span class="font-mono text-[10px] uppercase" style="color: var(--muted);">' + (s.service_type||'') + '</span><span class="text-sm font-medium">' + s.service_name + '</span></div><span class="font-mono text-sm tabular-nums">' + s.total_served + ' <span style="color: var(--muted);">/ ' + pct + '%</span></span></div>' +
                    '<div class="progress-bar"><div class="progress-bar-fill" style="width:' + pct + '%;background:' + color + ';"></div></div>' +
                '</div>';
            }).join('');
        }

        function updateHourlyChart(hourly) {
            var container = document.getElementById('hourlyBars');
            if (!hourly || hourly.length === 0) { container.innerHTML = '<div style="color: var(--muted); text-align: center; width: 100%;">No data</div>'; return; }
            var maxCount = Math.max.apply(null, hourly.map(function(h){return h.count;})) || 1;
            var peakHour = '', peakVal = 0;
            hourly.forEach(function(h) { if (h.count > peakVal) { peakVal = h.count; peakHour = String(h.hour).padStart(2,'0') + ':00'; } });
            document.getElementById('peakHour').textContent = peakHour + ' — ' + peakVal + ' tickets';
            var html = '';
            hourly.forEach(function(h) {
                var p = (h.count / maxCount) * 100;
                var isPeak = h.count === peakVal;
                html += '<div class="flex-1 flex flex-col items-center gap-2"><div class="w-full rounded-t-sm" style="height:' + p + '%;background:' + (isPeak ? 'var(--primary)' : 'var(--primary)/15') + ';"></div><span class="text-[9px] font-mono" style="color: var(--muted);">' + String(h.hour).padStart(2,'0') + '</span></div>';
            });
            container.innerHTML = html;
        }

        function updateTable() {
            var tbody = document.getElementById('reportTable'), search = document.getElementById('searchTable').value.toLowerCase();
            var filtered = search ? reportData.filter(function(c) { return (c.name||'').toLowerCase().includes(search) || (c.queue_number||'').toLowerCase().includes(search) || (c.service_type||'').toLowerCase().includes(search); }) : reportData;
            var start = (currentPage - 1) * itemsPerPage, pageData = filtered.slice(start, start + itemsPerPage);
            if (pageData.length === 0) { tbody.innerHTML = '<tr><td colspan="8" style="padding:2rem;text-align:center;color:var(--muted);">No records</td></tr>'; updatePagination(0); return; }
            var svcClasses = { insurance:'badge badge-primary', benefits:'badge badge-online', id_renewal:'badge badge-primary', atm_renewal:'badge badge-break' };
            var stClasses = { completed:'badge badge-online', cancelled:'badge badge-offline', serving:'badge badge-primary' };
            tbody.innerHTML = pageData.map(function(c) {
                var svcClass = svcClasses[c.service_type] || 'badge';
                var stClass = stClasses[c.status] || 'badge';
                return '<tr><td class="font-mono text-xs" style="color: var(--muted);">' + new Date(c.created_at).toLocaleDateString() + '</td><td class="font-mono font-bold" style="color: var(--foreground);">' + c.queue_number + '</td><td>' + c.name + '</td><td><span class="' + svcClass + '">' + (c.service_name || c.service_type).replace(/_/g,' ') + '</span></td><td style="color: var(--muted);">' + (c.window_name || '-') + '</td><td><span class="' + stClass + '">' + c.status + '</span></td><td class="font-mono text-xs" style="color: var(--muted);">' + formatDuration(c.wait_duration) + '</td><td class="font-mono text-xs" style="color: var(--muted);">' + formatDuration(c.service_duration) + '</td></tr>';
            }).join('');
            updatePagination(filtered.length);
        }

        function updatePagination(total) {
            var c = document.getElementById('pagination'), pages = Math.ceil(total / itemsPerPage);
            if (pages <= 1) { c.innerHTML = ''; return; }
            var html = '';
            for (var i = 1; i <= pages; i++) {
                html += '<button onclick="goToPage(' + i + ')" class="px-3 py-1 rounded text-xs font-bold ' + (i === currentPage ? 'btn-primary' : 'btn-secondary') + '">' + i + '</button>';
            }
            c.innerHTML = html;
        }

        function goToPage(page) { currentPage = page; updateTable(); }
        function formatDuration(seconds) { if (!seconds || seconds === 0) return '0:00'; var abs = Math.abs(seconds); var m = Math.floor(abs / 60); var s = abs % 60; return (seconds < 0 ? '-' : '') + m + ':' + String(s).padStart(2, '0'); }
        function exportToExcel() { var d = getDateRange(); window.open('api/reports/daily.php?from=' + d.from + '&to=' + d.to + '&export=excel', '_blank'); }
        function showToast(m, t) { var toast = document.getElementById('toast'); toast.textContent = m; toast.className = 'fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium ' + (t === 'success' ? 'bg-emerald-600' : 'bg-red-600'); toast.classList.remove('hidden'); setTimeout(function() { toast.classList.add('hidden'); }, 3000); }
        document.getElementById('searchTable').addEventListener('input', updateTable);
        loadReport();
    </script>
</body>
</html>
