<?php include 'config.php';
try { $db = new Database(); $conn = $db->getConnection(); $s = $conn->query("SELECT * FROM display_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); } catch (Exception $e) { $s = []; }
$company_name = htmlspecialchars($s['company_name'] ?? 'Service Center');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?php echo $company_name; ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h12v2H3v-2zm14 0l3 3-3 3v-6z'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        body { background: linear-gradient(135deg, hsl(215 45% 10%) 0%, hsl(215 45% 14%) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 420px; padding: 2.5rem; background: hsl(0 0% 100%); border-radius: var(--radius-2xl); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .input-field { width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 1px solid var(--border); border-radius: var(--radius); font-size: 0.875rem; background: var(--card); transition: border-color 0.15s, box-shadow 0.15s; }
        .input-field:focus { outline: none; border-color: var(--ring); box-shadow: 0 0 0 3px hsl(215 60% 18% / 0.15); }
        .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.875rem; pointer-events: none; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-in { animation: slideUp 500ms var(--ease-out-expo) both; }
    </style>
</head>
<body>
    <div class="login-card animate-in">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-xl mb-5" style="background: hsl(215 60% 18% / 0.1);">
                <div class="relative w-7 h-7 grid place-items-center" style="background: var(--brand-gold); border-radius: 2px;">
                    <span style="color: var(--primary); font-size: 11px; font-weight: 900; letter-spacing: -0.05em;">CQ</span>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight" style="color: var(--foreground);"><?php echo $company_name; ?></h1>
            <p class="text-sm mt-1" style="color: var(--muted);">Sign in to the management console</p>
        </div>

        <form id="loginForm" class="space-y-5">
            <div>
                <label class="label-md block mb-1.5">Username</label>
                <div class="relative">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" required class="input-field" placeholder="Enter your username" autocomplete="username">
                </div>
            </div>

            <div>
                <label class="label-md block mb-1.5">Password</label>
                <div class="relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" required class="input-field" style="padding-right: 2.75rem;" placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2" style="color: var(--muted);"><i id="toggleIcon" class="fas fa-eye"></i></button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="remember" class="w-4 h-4 rounded" style="color: var(--primary);">
                    <span class="ml-2 text-sm" style="color: var(--muted);">Remember me</span>
                </label>
            </div>

            <button type="submit" id="loginBtn" class="btn btn-primary w-full">
                <span id="loginBtnText">Sign In</span>
                <span id="loginBtnLoader" class="hidden"><i class="fas fa-spinner fa-spin"></i></span>
            </button>
        </form>

        <div id="errorMessage" class="hidden mt-5 p-4 rounded-xl" style="background: #fef2f2; border: 1px solid #fecaca;">
            <div class="flex items-center" style="color: #b91c1c;"><i class="fas fa-exclamation-circle mr-2"></i><span id="errorText" class="text-sm font-medium"></span></div>
        </div>

        <div class="mt-8 text-center">
            <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--muted);"><?php echo $company_name; ?></p>
            <p class="text-[10px] mt-0.5" style="color: var(--muted);">Queue Management System</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            var p = document.getElementById('password'), i = document.getElementById('toggleIcon');
            p.type = p.type === 'password' ? 'text' : 'password';
            i.className = p.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        }
        function showError(msg) {
            var e = document.getElementById('errorMessage');
            document.getElementById('errorText').textContent = msg;
            e.classList.remove('hidden');
            setTimeout(function() { e.classList.add('hidden'); }, 5000);
        }
        function setLoading(show) {
            document.getElementById('loginBtnText').textContent = show ? 'Signing in...' : 'Sign In';
            document.getElementById('loginBtnLoader').classList.toggle('hidden', !show);
            document.getElementById('loginBtn').disabled = show;
        }
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value;
            var remember = document.getElementById('remember').checked;
            if (!username || !password) { showError('Please enter both username and password'); return; }
            setLoading(true);
            document.getElementById('errorMessage').classList.add('hidden');
            try {
                var res = await fetch('api/auth/login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username: username, password: password }) });
                var data = await res.json();
                if (data.success) {
                    var storage = remember ? localStorage : sessionStorage;
                    storage.setItem('auth_token', data.data.token);
                    storage.setItem('user_data', JSON.stringify(data.data.user));
                    setLoading(false);
                    window.location.href = 'index.php';
                } else { showError(data.message || 'Login failed'); setLoading(false); }
            } catch (err) { showError('Connection error. Please try again.'); setLoading(false); }
        });
    </script>
</body>
</html>
