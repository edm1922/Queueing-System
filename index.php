<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Queue Management System</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23667eea'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { background: #fff; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; }
        .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }
        .queue-number { font-family: 'Courier New', monospace; font-weight: bold; }
        .counter-offline { opacity: 0.6; background: repeating-linear-gradient(45deg, #fee2e2, #fee2e2 10px, #fecaca 10px, #fecaca 20px); }
        .toast { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .pulse-dot { animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .modal-overlay { backdrop-filter: blur(4px); }
        .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 0.375rem; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        button:focus-visible, a:focus-visible, select:focus-visible, input:focus-visible { outline: 2px solid #667eea; outline-offset: 2px; border-radius: 0.375rem; }
        .filter-btn:focus-visible { outline: 2px solid #667eea; outline-offset: 2px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl md:text-3xl font-bold"><i class="fas fa-users mr-3"></i>Queue Management</h1>
                    <span class="ml-4 text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full flex items-center gap-1.5"><span class="inline-block w-2 h-2 rounded-full bg-green-300 pulse-dot"></span>Admin Panel</span>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div id="current-time" class="text-lg md:text-xl font-mono"></div>
                        <div class="text-sm opacity-80"><?php echo date('F j, Y'); ?></div>
                    </div>
                    <button onclick="logout()" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-1.5 rounded-lg text-sm transition flex items-center gap-1.5" title="Sign Out"><i class="fas fa-sign-out-alt"></i></button>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-6 flex-grow">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-6">
            <div class="card card-hover p-4 md:p-6 border-t-4 border-yellow-400">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-3 md:mr-4"><i class="fas fa-clock text-xl md:text-2xl"></i></div>
                    <div><h3 class="text-2xl md:text-3xl font-bold text-gray-800" id="waiting-count">0</h3><p class="text-gray-600 text-sm">Waiting</p></div>
                </div>
            </div>
            <div class="card card-hover p-4 md:p-6 border-t-4 border-blue-400">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-3 md:mr-4"><i class="fas fa-user-check text-xl md:text-2xl"></i></div>
                    <div><h3 class="text-2xl md:text-3xl font-bold text-gray-800" id="serving-count">0</h3><p class="text-gray-600 text-sm">Serving</p></div>
                </div>
            </div>
            <div class="card card-hover p-4 md:p-6 border-t-4 border-green-400">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-3 md:mr-4"><i class="fas fa-check-circle text-xl md:text-2xl"></i></div>
                    <div><h3 class="text-2xl md:text-3xl font-bold text-gray-800" id="completed-count">0</h3><p class="text-gray-600 text-sm">Completed</p></div>
                </div>
            </div>
            <div class="card card-hover p-4 md:p-6 border-t-4 border-purple-400">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-3 md:mr-4"><i class="fas fa-chart-line text-xl md:text-2xl"></i></div>
                    <div><h3 class="text-2xl md:text-3xl font-bold text-gray-800" id="today-count">0</h3><p class="text-gray-600 text-sm">Today's Total</p></div>
                </div>
            </div>
        </div>

        <div class="card p-5 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4"><i class="fas fa-chart-bar mr-2 text-blue-500"></i>Service Metrics</h3>
            <div id="serviceMetrics" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="card p-5 card-hover">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-desktop mr-2 text-purple-500"></i>Window Management</h2>
                        <button onclick="openAddWindowModal()" class="bg-purple-100 text-purple-600 hover:bg-purple-200 px-3 py-1 rounded text-sm font-semibold transition"><i class="fas fa-plus mr-1"></i> Add Window</button>
                    </div>
                    <div id="countersStatus" class="space-y-4"></div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="card p-5 card-hover">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3">
                        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-list mr-2 text-blue-500"></i>Queue List</h2>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="refreshQueue()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm"><i class="fas fa-sync-alt mr-2"></i>Refresh</button>
                            <button onclick="openAnnouncementModal()" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition text-sm"><i class="fas fa-bullhorn mr-2"></i>Announcements</button>
                            <a href="reports.php" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition text-sm"><i class="fas fa-chart-bar mr-2"></i>Reports</a>
                            <a href="settings.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm"><i class="fas fa-cog mr-2"></i>Settings</a>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button onclick="filterQueue('all')" class="filter-btn active px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium" data-filter="all">All</button>
                        <button onclick="filterQueue('waiting')" class="filter-btn px-4 py-2 rounded-lg bg-white text-gray-600 border border-gray-200 text-sm hover:bg-gray-50" data-filter="waiting">Waiting</button>
                        <button onclick="filterQueue('serving')" class="filter-btn px-4 py-2 rounded-lg bg-white text-gray-600 border border-gray-200 text-sm hover:bg-gray-50" data-filter="serving">Serving</button>
                        <button onclick="filterQueue('completed')" class="filter-btn px-4 py-2 rounded-lg bg-white text-gray-600 border border-gray-200 text-sm hover:bg-gray-50" data-filter="completed">Completed</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Queue No.</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Customer</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Service</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="queueTable" class="divide-y divide-gray-100"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-5 card-hover">
                    <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-history mr-2 text-gray-500"></i>Redistribution History</h2>
                    <div id="redistributionLogs" class="space-y-2 max-h-48 overflow-y-auto text-sm"></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center text-sm">
            <p>&copy; <?php echo date('Y'); ?> Queue Management System - Manpower Agency Edition</p>
        </div>
    </footer>

    <div id="announcementModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-lg max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Manage Announcements</h3>
                    <button onclick="closeAnnouncementModal()" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Create Announcement</h4>
                    <div class="space-y-3">
                        <input type="text" id="announcementTitle" class="w-full px-4 py-2 border rounded-lg" placeholder="Title (optional)">
                        <textarea id="announcementMessage" class="w-full px-4 py-2 border rounded-lg" rows="2" placeholder="Announcement message"></textarea>
                        <div class="flex gap-3">
                            <select id="announcementType" class="flex-1 px-4 py-2 border rounded-lg">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <button onclick="addAnnouncement()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-plus mr-2"></i>Add</button>
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Quick Templates</h4>
                    <div id="presetAnnouncements" class="space-y-2"></div>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Active Announcements</h4>
                    <div id="activeAnnouncements" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Window Modal -->
    <div id="addWindowModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Add New Window</h3>
                    <button onclick="closeAddWindowModal()" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Window Name</label>
                        <input type="text" id="newWindowName" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g. Window 3">
                    </div>
                    <button onclick="submitNewWindow()" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition">Add Window</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Services Modal -->
    <div id="editServicesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden modal-overlay z-50 flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Edit Window Services</h3>
                    <button onclick="closeEditServicesModal()" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="space-y-4">
                    <input type="hidden" id="editServicesCounterId">
                    <div id="servicesCheckboxes" class="space-y-2 max-h-60 overflow-y-auto border rounded p-3 bg-gray-50">
                        <!-- Checkboxes populated by JS -->
                    </div>
                    <button onclick="submitEditServices()" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">Save Services</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script src="js/main.js?v=3"></script>
</body>
</html>