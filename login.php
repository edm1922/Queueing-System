<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Queue Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .login-card { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        .input-focus:focus { box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.4); }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="login-card bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-100 mb-4">
                <i class="fas fa-users text-3xl text-purple-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Queue Management</h1>
            <p class="text-gray-500 mt-1">Sign in to your account</p>
        </div>

        <form id="loginForm" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"><i class="fas fa-user"></i></span>
                    <input type="text" id="username" required class="input-focus w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" placeholder="Enter your username" autocomplete="username">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" required class="input-focus w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition" placeholder="Enter your password" autocomplete="current-password">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"><i id="toggleIcon" class="fas fa-eye"></i></button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center"><input type="checkbox" id="remember" class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"><span class="ml-2 text-sm text-gray-600">Remember me</span></label>
            </div>

            <button type="submit" id="loginBtn" class="w-full bg-purple-600 text-white py-3 px-4 rounded-lg hover:bg-purple-700 transition duration-300 font-semibold flex items-center justify-center">
                <span id="loginBtnText">Sign In</span>
                <span id="loginBtnLoader" class="hidden ml-2"><i class="fas fa-spinner fa-spin"></i></span>
            </button>
        </form>

        <div id="errorMessage" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center text-red-700"><i class="fas fa-exclamation-circle mr-2"></i><span id="errorText"></span></div>
        </div>

        <div class="mt-8 text-center text-sm text-gray-400">
            <p>Queue Management System v2.0</p>
            <p class="mt-1">Manpower Agency Edition</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            passwordInput.type = passwordInput.type === 'password' ? 'text' : 'password';
            toggleIcon.className = passwordInput.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            document.getElementById('errorText').textContent = message;
            errorDiv.classList.remove('hidden');
            setTimeout(() => errorDiv.classList.add('hidden'), 5000);
        }

        function showLoading(show) {
            const btn = document.getElementById('loginBtn');
            document.getElementById('loginBtnText').textContent = show ? 'Signing in...' : 'Sign In';
            document.getElementById('loginBtnLoader').classList.toggle('hidden', !show);
            btn.disabled = show;
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            if (!username || !password) { showError('Please enter both username and password'); return; }
            showLoading(true);
            document.getElementById('errorMessage').classList.add('hidden');
            try {
                const response = await fetch('api/auth/login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username, password }) });
                const data = await response.json();
                if (data.success) {
                    const storage = remember ? localStorage : sessionStorage;
                    storage.setItem('auth_token', data.data.token);
                    storage.setItem('user_data', JSON.stringify(data.data.user));
                    showLoading(false);
                    window.location.href = 'index.php';
                } else { showError(data.message || 'Login failed'); showLoading(false); }
            } catch (error) { showError('Connection error. Please try again.'); showLoading(false); }
        });
    </script>
</body>
</html>