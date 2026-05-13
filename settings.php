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
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-tv mr-2 text-purple-500"></i>Display Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Refresh Interval (seconds)</label><input type="number" id="refreshInterval" min="3" max="60" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Display Layout</label>
                        <select id="displayLayout" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="video_queue">Video + Queue Side by Side</option>
                            <option value="queue_video">Queue + Video Side by Side</option>
                            <option value="queue_only">Queue Only (No Video)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-play-circle mr-2 text-red-500"></i>Video / Entertainment</h2>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Video Source</label>
                        <select id="videoType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" onchange="toggleVideoFields()">
                            <option value="none">No Video</option>
                            <option value="youtube">YouTube Video</option>
                            <option value="local">Local Video File</option>
                        </select>
                    </div>
                    <div id="youtubeField" class="hidden"><label class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label><input type="url" id="videoUrl" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://www.youtube.com/watch?v=..."></div>
                    <div id="localVideoField" class="hidden"><label class="block text-sm font-medium text-gray-700 mb-2">Video File Path</label><input type="text" id="videoFilePath" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="/videos/intro.mp4"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-2">Volume</label><input type="range" id="videoVolume" min="0" max="100" class="w-full"><div class="flex justify-between text-xs text-gray-500"><span>0%</span><span id="volumeValue">50%</span><span>100%</span></div></div>
                    <div class="flex items-center"><input type="checkbox" id="autoPlayVideo" class="w-5 h-5 rounded" checked><label for="autoPlayVideo" class="ml-2 text-sm text-gray-700">Auto-play video</label></div>
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
                    document.getElementById('refreshInterval').value = s.refresh_interval || 10;
                    document.getElementById('displayLayout').value = s.display_layout || 'video_queue';
                    document.getElementById('videoType').value = s.video_type || 'none';
                    document.getElementById('videoUrl').value = s.video_url || '';
                    document.getElementById('videoVolume').value = s.video_volume || 50;
                    document.getElementById('volumeValue').textContent = (s.video_volume || 50) + '%';
                    document.getElementById('autoPlayVideo').checked = s.auto_play_video !== 0;
                    toggleVideoFields();
                }
            } catch (error) { showToast('Failed to load settings', 'error'); }
        }

        function toggleVideoFields() {
            const videoType = document.getElementById('videoType').value;
            document.getElementById('youtubeField').classList.toggle('hidden', videoType !== 'youtube');
            document.getElementById('localVideoField').classList.toggle('hidden', videoType !== 'local');
        }

        document.getElementById('videoVolume').addEventListener('input', function() { document.getElementById('volumeValue').textContent = this.value + '%'; });

        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const data = {
                company_name: document.getElementById('companyName').value,
                welcome_message: document.getElementById('welcomeMessage').value,
                refresh_interval: parseInt(document.getElementById('refreshInterval').value),
                display_layout: document.getElementById('displayLayout').value,
                video_type: document.getElementById('videoType').value,
                video_url: document.getElementById('videoUrl').value,
                video_volume: parseInt(document.getElementById('videoVolume').value),
                auto_play_video: document.getElementById('autoPlayVideo').checked ? 1 : 0
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

        loadSettings();
    </script>
</body>
</html>