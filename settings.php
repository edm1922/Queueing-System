<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Queue Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>.gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }</style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-white hover:text-gray-200"><i class="fas fa-arrow-left text-xl"></i></a>
                    <h1 class="text-2xl font-bold"><i class="fas fa-cog mr-3"></i>Display Settings</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8 max-w-4xl">
        <form id="settingsForm" class="space-y-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-building mr-2 text-blue-500"></i>Company Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label><input type="text" id="companyName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Welcome Message</label><input type="text" id="welcomeMessage" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Company Logo URL</label><input type="text" id="companyLogo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://example.com/logo.png"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Display Theme Color</label><input type="color" id="themeColor" class="h-10 w-full px-2 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="#1e3a5f"></div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-clock mr-2 text-red-500"></i>Queue Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cut-off Time (When queueing closes)</label>
                        <input type="time" id="cutoffTime" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-bullhorn mr-2 text-yellow-500"></i>Announcement Ticker Message</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                    <input type="text" id="annMsg" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Enter message to scroll on top...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Show For (Duration)</label>
                    <select id="annDurationPreset" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="60">1 Hour</option>
                        <option value="240">4 Hours</option>
                        <option value="480">8 Hours</option>
                        <option value="1440">24 Hours</option>
                        <option value="10080">1 Week</option>
                        <option value="0">Forever</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="addAnnouncement()" class="w-full px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-bold"><i class="fas fa-paper-plane mr-2"></i>Post</button>
                </div>
            </div>
        </div>


                <div id="announcementList" class="space-y-3">
                    <div class="text-center py-4 text-gray-500">Loading announcements...</div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <button type="button" onclick="window.location.href='index.php'" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"><i class="fas fa-save mr-2"></i>Save Settings</button>
            </div>
        </form>
    </main>

    <div id="toast" class="fixed bottom-4 right-4 hidden px-6 py-3 rounded-lg shadow-lg z-50"></div>

    <script>
        async function loadSettings() {
            try {
                const response = await fetch('api/settings/index.php');
                const data = await response.json();
                if (data.success) {
                    const s = data.data;
                    document.getElementById('companyName').value = s.company_name || '';
                    document.getElementById('welcomeMessage').value = s.welcome_message || '';
                    document.getElementById('cutoffTime').value = s.cutoff_time || '17:00';
                    document.getElementById('companyLogo').value = s.company_logo || '';
                    document.getElementById('themeColor').value = s.theme_color || '#1e3a5f';
                }
            } catch (error) { showToast('Failed to load settings', 'error'); }
        }

        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                company_name: document.getElementById('companyName').value,
                welcome_message: document.getElementById('welcomeMessage').value,
                cutoff_time: document.getElementById('cutoffTime').value,
                company_logo: document.getElementById('companyLogo').value,
                theme_color: document.getElementById('themeColor').value,
                auto_play_video: 1
            };
            try {
                const response = await fetch('api/settings/index.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
                const result = await response.json();
                showToast(result.success ? 'Settings saved successfully' : result.message || 'Failed to save settings', result.success ? 'success' : 'error');
            } catch (error) { showToast('Failed to save settings', 'error'); }
        });

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
            toast.textContent = message;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        async function loadAnnouncements() {
            try {
                const response = await fetch('api/announcement/index.php?active=0');
                const result = await response.json();
                if (result.success) {
                    const list = document.getElementById('announcementList');
                    if (result.data.length === 0) {
                        list.innerHTML = '<div class="text-center py-4 text-gray-500 italic">No announcements found</div>';
                        return;
                    }
                    list.innerHTML = result.data.map(a => {
                        const now = new Date();
                        const start = a.starts_at ? new Date(a.starts_at) : null;
                        const end = a.expires_at ? new Date(a.expires_at) : null;
                        let status = 'Active';
                        let statusColor = 'text-green-600 bg-green-100';
                        
                        if (start && start > now) { status = 'Scheduled'; statusColor = 'text-blue-600 bg-blue-100'; }
                        else if (end && end < now) { status = 'Expired'; statusColor = 'text-gray-600 bg-gray-100'; }
                        
                        return `
                            <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg bg-gray-50 hover:bg-white transition shadow-sm">
                                <div class="flex-grow">
                                    <div class="flex items-center gap-3 mb-1">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold ${statusColor}">${status}</span>
                                        <span class="text-xs font-bold text-gray-400 uppercase">${a.type}</span>
                                    </div>
                                    <p class="text-gray-800 font-medium">${a.message}</p>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Expires: <span class="font-bold text-red-500">${end ? end.toLocaleString() : 'Never'}</span>
                                    </div>
                                </div>
                                <button type="button" onclick="deleteAnnouncement(${a.id})" class="ml-4 p-2 text-red-500 hover:bg-red-50 rounded-full transition"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        `;
                    }).join('');
                }
            } catch (error) { console.error('Error loading announcements:', error); }
        }

        async function addAnnouncement() {
            var message = document.getElementById('annMsg').value;
            if (!message) return;
            
            var presetMinutes = parseInt(document.getElementById('annDurationPreset').value);
            var expiresAt = null;
            if (presetMinutes > 0) {
                var d = new Date();
                d.setMinutes(d.getMinutes() + presetMinutes);
                // Format to MySQL DATETIME (YYYY-MM-DD HH:mm:ss)
                expiresAt = d.getFullYear() + '-' + 
                           String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                           String(d.getDate()).padStart(2, '0') + ' ' + 
                           String(d.getHours()).padStart(2, '0') + ':' + 
                           String(d.getMinutes()).padStart(2, '0') + ':' + 
                           String(d.getSeconds()).padStart(2, '0');
            }

            var data = {
                message: message,
                type: 'info',
                font_family: 'Inter',
                priority: 0,
                starts_at: null,
                expires_at: expiresAt,
                display_duration: 10,
                is_preset: 0
            };
            
            try {
                const response = await fetch('api/announcement/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Announcement added successfully', 'success');
                    document.getElementById('annMsg').value = '';
                    loadAnnouncements();
                } else { showToast(result.message, 'error'); }
            } catch (error) { showToast('Failed to add announcement', 'error'); }
        }

        async function deleteAnnouncement(id) {
            if (!confirm('Are you sure you want to delete this announcement?')) return;
            try {
                // Try DELETE first
                const response = await fetch(`api/announcement/index.php?id=${id}`, { method: 'DELETE' });
                
                // If DELETE is blocked or fails (e.g. 405), try POST with _method fallback
                if (!response.ok && response.status === 405) {
                    throw new Error('Method not allowed');
                }
                
                const result = await response.json();
                if (result.success) {
                    showToast('Announcement deleted', 'success');
                    loadAnnouncements();
                } else {
                    showToast(result.message || 'Failed to delete announcement', 'error');
                }
            } catch (error) { 
                console.warn('DELETE failed, trying POST fallback...', error);
                try {
                    const response = await fetch('api/announcement/index.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ _method: 'DELETE', id: id })
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToast('Announcement deleted', 'success');
                        loadAnnouncements();
                    } else {
                        showToast(result.message || 'Failed to delete announcement', 'error');
                    }
                } catch (err) {
                    console.error('All delete methods failed:', err);
                    showToast('Failed to delete: ' + err.message, 'error');
                }
            }
        }

        loadSettings();
        loadAnnouncements();
    </script>
</body>
</html>