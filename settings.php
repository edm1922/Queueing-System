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
    <title>Settings — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        .input-field { width: 100%; padding: 0.625rem 1rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 0.875rem; background: var(--card); transition: border-color 0.15s; }
        .input-field:focus { outline: none; border-color: var(--ring); box-shadow: 0 0 0 3px hsl(215 60% 18% / 0.15); }
        select.input-field { cursor: pointer; }
        .ann-item { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 1rem; transition: all 0.15s; }
        .ann-item:hover { background: var(--secondary); }
    </style>
</head>
<body class="min-h-screen flex flex-col" style="background: var(--background);">
    <nav class="sticky top-0 z-50" style="background: #b91c1c; color: white; border-bottom: 1px solid rgba(255,255,255,0.15);">
        <div class="max-w-[1600px] mx-auto flex items-center justify-between px-6" style="height: 3.5rem;">
            <div class="flex items-center gap-10">
                <a href="display.php" class="flex items-center gap-3">
                    <div class="relative w-7 h-7 grid place-items-center" style="background: var(--brand-gold); border-radius: 2px;">
                        <?php if ($company_logo): ?><img src="<?php echo $company_logo; ?>" alt="" class="w-5 h-5 object-contain"><?php else: ?><span style="color: var(--primary); font-size: 11px; font-weight: 900; letter-spacing: -0.05em;">CQ</span><?php endif; ?>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span style="font-size: 20px; font-weight: 800; letter-spacing: -0.02em; color: white;"><?php echo $company_name; ?></span>
                        <span style="font-size: 9px; font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 0.2em; opacity: 0.5;">Settings</span>
                    </div>
                </a>
                <div class="hidden md:flex gap-1 text-[11px] font-semibold uppercase tracking-wider">
                    <a href="index.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Operator</a>
                    <a href="display.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Live Display</a>
                    <a href="kiosk.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Kiosk</a>
                    <a href="reports.php" class="px-3 py-1.5 rounded" style="color: rgba(255,255,255,0.6);">Analytics</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span style="font-size: 10px; font-family: var(--font-mono); opacity: 0.7;">ADMIN</span>
            </div>
        </div>
    </nav>

    <main class="flex-1 w-full max-w-4xl mx-auto p-8">
        <form id="settingsForm" class="space-y-6" enctype="multipart/form-data">
            <div class="card p-6 animate-entry">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Company Information</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="label-md block mb-1.5">Company Name</label><input type="text" id="companyName" class="input-field"></div>
                        <div><label class="label-md block mb-1.5">Company Logo URL</label><input type="text" id="companyLogo" class="input-field" placeholder="https://example.com/logo.png"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="label-md block mb-1.5">Branch / Office Name</label><input type="text" id="branchName" class="input-field" placeholder="e.g. Quezon City — Main Hall"></div>
                        <div><label class="label-md block mb-1.5">Address</label><input type="text" id="address" class="input-field" placeholder="e.g. 123 Roxas Blvd, Quezon City"></div>
                    </div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 80ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Queue Settings</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="label-md block mb-1.5">Cut-off Time</label><input type="time" id="cutoffTime" class="input-field"></div>
                    <div><label class="label-md block mb-1.5">Welcome Message</label><input type="text" id="welcomeMessage" class="input-field" placeholder="Welcome! Please have your queue ticket ready."></div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 100ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Media Panel</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Configure the YouTube video shown on the Live Display. Changes take effect on page reload.</p>

                <h3 class="text-[10px] font-bold uppercase tracking-wider mb-3" style="color: var(--brand-gold);">YouTube Video</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div><label class="label-md block mb-1.5">YouTube Video ID</label><input type="text" id="videoUrl" class="input-field" placeholder="e.g. aqz-KE-bpKQ"></div>
                    <div><label class="label-md block mb-1.5">Video Type</label><select id="videoType" class="input-field"><option value="youtube">YouTube</option><option value="none">None</option></select></div>
                    <div><label class="label-md block mb-1.5">Title</label><input type="text" id="videoTitle" class="input-field" placeholder="Citizen Services Overview"></div>
                    <div><label class="label-md block mb-1.5">Sponsor</label><input type="text" id="videoSponsor" class="input-field" placeholder="Public Affairs Office"></div>
                    <div class="md:col-span-2"><label class="label-md block mb-1.5">Call-to-action (optional)</label><input type="text" id="videoCta" class="input-field" placeholder=""></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="label-md block mb-1.5">Media Volume</label>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-volume-down text-sm" style="color: var(--muted);"></i>
                            <input type="range" id="videoVolume" min="0" max="100" value="50" class="flex-1" style="accent-color: var(--brand-gold);">
                            <span id="volumeDisplay" class="font-mono text-xs tabular-nums" style="color: var(--muted);">50%</span>
                        </div>
                        <p class="text-[10px] mt-1" style="color: var(--muted);">Ducks to ~15% when a number is called</p>
                    </div>
                </div>
            </div>

            <div class="card p-6 animate-entry" style="animation-delay: 110ms;">
                <h2 class="text-xs font-bold uppercase tracking-widest mb-4" style="color: var(--muted);">Poster Display</h2>
                <p class="text-[11px] mb-4" style="color: var(--muted);">Posters appear in the right panel of the Live Display.</p>

                <div class="mb-5 max-w-xs">
                    <label class="label-md block mb-1.5">Display Duration</label>
                    <select id="posterDuration" class="input-field">
                        <option value="5">5 seconds</option>
                        <option value="10" selected>10 seconds</option>
                        <option value="15">15 seconds</option>
                        <option value="30">30 seconds</option>
                    </select>
                </div>

                <div>
                    <label class="label-md block mb-2">Poster Images</label>
                    <div class="flex items-center gap-3 mb-3">
                        <input type="file" id="posterUpload" class="input-field" multiple accept="image/*">
                        <button type="button" onclick="uploadPosters()" class="btn btn-primary whitespace-nowrap"><i class="fas fa-upload mr-1"></i> Upload</button>
                    </div>
                    <p class="text-[10px] mb-3" style="color: var(--muted);">Supported: JPG, PNG, GIF, WebP.</p>
                    <div id="posterGallery" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <div class="h-px my-5" style="background: var(--border);"></div>

                <div>
                    <label class="label-md block mb-3">Announcement Posters</label>
                    <p class="text-[10px] mb-3" style="color: var(--muted);">Create text-based announcement posters that rotate alongside image posters.</p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="label-md block mb-1.5">Title</label>
                            <input type="text" id="annPosterTitle" class="input-field" placeholder="e.g. Holiday Schedule">
                        </div>
                        <div>
                            <label class="label-md block mb-1.5">Background</label>
                            <input type="color" id="annPosterBg" class="input-field h-10 p-1" value="#1e3a5f">
                        </div>
                        <div>
                            <label class="label-md block mb-1.5">Text Color</label>
                            <input type="color" id="annPosterFg" class="input-field h-10 p-1" value="#ffffff">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="label-md block mb-1.5">Body Message</label>
                        <textarea id="annPosterBody" class="input-field" rows="3" placeholder="Enter the announcement message..."></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="addAnnouncementPoster()" class="btn btn-primary"><i class="fas fa-plus-circle mr-1"></i> Add to Rotation</button>
                        <span class="text-[10px]" style="color: var(--muted);">Preview:</span>
                        <div id="annPosterPreview" style="width:100px;height:56px;border-radius:4px;overflow:hidden;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;text-align:center;padding:4px;"></div>
                    </div>
                    <div id="annPosterList" class="flex flex-wrap gap-3 mt-4">
                        <!-- Injected by JS -->
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 animate-entry" style="animation-delay: 120ms;">
                <button type="button" onclick="window.location.href='index.php'" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="saveBtn" class="btn btn-primary"><i class="fas fa-save mr-2" id="saveIcon"></i><span id="saveText">Save Settings</span></button>
            </div>
        </form>
    </main>

    <div id="toast" class="fixed bottom-4 right-4 hidden px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium"></div>

    <script>
        var posterImages = [];
        var annPosters = [];

        async function loadSettings() {
            try {
                var res = await fetch('api/settings/index.php');
                var data = await res.json();
                if (data.success) {
                    var s = data.data;
                    document.getElementById('companyName').value = s.company_name || '';
                    document.getElementById('branchName').value = s.branch_name || '';
                    document.getElementById('address').value = s.address || '';
                    document.getElementById('cutoffTime').value = s.cutoff_time || '17:00';
                    document.getElementById('companyLogo').value = s.company_logo || '';
                    document.getElementById('welcomeMessage').value = s.welcome_message || '';
                    document.getElementById('videoUrl').value = s.video_url || '';
                    document.getElementById('videoType').value = s.video_type || 'youtube';
                    document.getElementById('videoTitle').value = s.video_title || 'Citizen Services Overview';
                    document.getElementById('videoSponsor').value = s.video_sponsor || 'Public Affairs Office';
                    document.getElementById('videoCta').value = s.video_cta || '';
                    var vol = s.video_volume || 50;
                    document.getElementById('videoVolume').value = vol;
                    document.getElementById('volumeDisplay').textContent = vol + '%';
                    if (s.poster_duration) document.getElementById('posterDuration').value = s.poster_duration;
                    posterImages = Array.isArray(s.poster_images) ? s.poster_images : [];
                    renderPosterGallery();
                    annPosters = Array.isArray(s.poster_announcements) ? s.poster_announcements : [];
                    renderAnnouncementPosters();
                }
            } catch (e) { console.error('Load error:', e); showToast('Failed to load settings', 'error'); }
        }


        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            var btn = document.getElementById('saveBtn');
            var icon = document.getElementById('saveIcon');
            var txt = document.getElementById('saveText');
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin mr-2';
            txt.textContent = 'Saving...';
            var data = {
                company_name: document.getElementById('companyName').value,
                branch_name: document.getElementById('branchName').value,
                address: document.getElementById('address').value,
                cutoff_time: document.getElementById('cutoffTime').value,
                company_logo: document.getElementById('companyLogo').value,
                welcome_message: document.getElementById('welcomeMessage').value,
                auto_play_video: 1,
                video_url: document.getElementById('videoUrl').value,
                video_type: document.getElementById('videoType').value,
                video_title: document.getElementById('videoTitle').value,
                video_sponsor: document.getElementById('videoSponsor').value,
                video_cta: document.getElementById('videoCta').value,
                video_volume: parseInt(document.getElementById('videoVolume').value),
                poster_duration: parseInt(document.getElementById('posterDuration').value),
                poster_images: JSON.stringify(posterImages),
                poster_announcements: JSON.stringify(annPosters)
            };
            try {
                var res = await fetch('api/settings/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                var result = await res.json();
                if (result.success) {
                    try { localStorage.setItem('cq_settings_updated', Date.now().toString()); } catch(e) {}
                }
                showToast(result.success ? 'Settings saved successfully' : result.message || 'Failed to save settings', result.success ? 'success' : 'error');
            } catch (e) { console.error('Save error:', e); showToast('Failed to save settings: ' + e.message, 'error'); }
            icon.className = 'fas fa-save mr-2';
            txt.textContent = 'Save Settings';
            btn.disabled = false;
        });

        function showToast(message, type) {
            var toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 text-white text-sm font-medium ' + (type === 'success' ? 'bg-emerald-600' : 'bg-red-600');
            toast.classList.remove('hidden');
            setTimeout(function() { toast.classList.add('hidden'); }, 3000);
        }

        loadSettings();

        function updateAnnPreview() {
            var el = document.getElementById('annPosterPreview');
            var title = document.getElementById('annPosterTitle').value;
            var body = document.getElementById('annPosterBody').value;
            var bg = document.getElementById('annPosterBg').value;
            var fg = document.getElementById('annPosterFg').value;
            el.style.background = bg;
            el.style.color = fg;
            el.textContent = (title || body || 'Preview').substring(0, 30);
        }
        document.getElementById('annPosterTitle').addEventListener('input', updateAnnPreview);
        document.getElementById('annPosterBody').addEventListener('input', updateAnnPreview);
        document.getElementById('annPosterBg').addEventListener('input', updateAnnPreview);
        document.getElementById('annPosterFg').addEventListener('input', updateAnnPreview);

        function renderAnnouncementPosters() {
            var el = document.getElementById('annPosterList');
            if (!el) return;
            if (annPosters.length === 0) {
                el.innerHTML = '<span class="text-[10px]" style="color: var(--muted);">No announcement posters created</span>';
                return;
            }
            el.innerHTML = annPosters.map(function(p, i) {
                return '<div style="width:160px;height:90px;border-radius:6px;overflow:hidden;border:1px solid var(--border);position:relative;cursor:pointer;background:' + p.bg + ';color:' + p.fg + ';display:flex;flex-direction:column;align-items:center;justify-content:center;padding:8px;text-align:center;" onclick="removeAnnouncementPoster(' + i + ')">' +
                    '<div style="position:absolute;top:2px;right:2px;background:rgba(0,0,0,0.5);color:white;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;">&times;</div>' +
                    (p.title ? '<div style="font-size:10px;font-weight:700;line-height:1.2;margin-bottom:2px;">' + p.title.substring(0, 30) + '</div>' : '') +
                    (p.body ? '<div style="font-size:7px;line-height:1.2;opacity:0.85;">' + p.body.substring(0, 50) + '</div>' : '') +
                '</div>';
            }).join('');
        }

        function addAnnouncementPoster() {
            var title = document.getElementById('annPosterTitle').value.trim();
            var body = document.getElementById('annPosterBody').value.trim();
            if (!title && !body) { showToast('Enter a title or message', 'error'); return; }
            annPosters.push({
                title: title,
                body: body,
                bg: document.getElementById('annPosterBg').value,
                fg: document.getElementById('annPosterFg').value
            });
            renderAnnouncementPosters();
            document.getElementById('annPosterTitle').value = '';
            document.getElementById('annPosterBody').value = '';
            updateAnnPreview();
            showToast('Announcement poster added', 'success');
        }

        function removeAnnouncementPoster(idx) {
            if (!confirm('Remove this announcement poster?')) return;
            annPosters.splice(idx, 1);
            renderAnnouncementPosters();
        }

        document.getElementById('videoVolume').addEventListener('input', function() {
            document.getElementById('volumeDisplay').textContent = this.value + '%';
        });

        function renderPosterGallery() {
            var el = document.getElementById('posterGallery');
            if (!el) return;
            if (posterImages.length === 0) {
                el.innerHTML = '<div class="col-span-full text-center py-8 text-sm" style="color: var(--muted);">No posters uploaded yet</div>';
                return;
            }
            el.innerHTML = posterImages.map(function(src, i) {
                return '<div class="relative group rounded-md overflow-hidden border border-border" style="aspect-ratio: 16/9;">' +
                    '<img src="' + src + '" class="w-full h-full object-cover">' +
                    '<div class="absolute inset-0 flex items-center justify-center" style="background: rgba(0,0,0,0); transition: background 0.2s;">' +
                        '<div class="flex gap-2">' +
                            '<button type="button" onclick="removePoster(' + i + ')" class="bg-red-600 text-white rounded-full w-8 h-8 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-700"><i class="fas fa-trash-alt text-xs"></i></button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        async function uploadPosters() {
            var input = document.getElementById('posterUpload');
            if (!input.files.length) return;
            var fd = new FormData();
            for (var i = 0; i < input.files.length; i++) {
                fd.append('poster_images[]', input.files[i]);
            }
            try {
                var res = await fetch('api/settings/index.php', { method: 'POST', body: fd });
                var result = await res.json();
                if (result.success) {
                    showToast('Posters uploaded', 'success');
                    input.value = '';
                    await loadSettings();
                } else {
                    showToast(result.message || 'Upload failed', 'error');
                }
            } catch (e) { showToast('Upload error: ' + e.message, 'error'); }
        }

        async function removePoster(idx) {
            if (!confirm('Remove this poster image?')) return;
            try {
                var res = await fetch('api/settings/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ remove_poster: idx })
                });
                var result = await res.json();
                if (result.success) {
                    showToast('Poster removed', 'success');
                    await loadSettings();
                } else {
                    showToast(result.message || 'Failed to remove', 'error');
                }
            } catch (e) { showToast('Error: ' + e.message, 'error'); }
        }

    </script>
</body>
</html>
