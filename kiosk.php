<?php include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = ['company_name' => 'Service Center'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Self Check-In - Queue Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #1e3a5f 100%); overflow: hidden; }
        .service-btn { transition: all 0.3s ease; cursor: pointer; }
        .service-btn:hover, .service-btn:active { transform: scale(1.05); }
        .success-animation { animation: successPop 0.5s ease-out; }
        @keyframes successPop { 0% { transform: scale(0); opacity: 0; } 50% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="text-white min-h-screen flex flex-col">
    <div class="bg-black bg-opacity-40 py-4 px-6">
        <div class="flex items-center justify-center">
            <i class="fas fa-building text-3xl mr-4 text-yellow-400"></i>
            <div class="text-center"><h1 class="text-2xl font-bold">Self Check-In Kiosk</h1><p class="text-sm opacity-80"><?php echo htmlspecialchars($settings['company_name'] ?? 'Service Center'); ?></p></div>
        </div>
    </div>

    <main id="mainContent" class="flex-grow flex items-center justify-center p-4">
        <div id="step1" class="w-full max-w-4xl fade-in">
            <h2 class="text-3xl font-bold text-center mb-8">Select Your Service</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div onclick="selectService('insurance_benefits')" class="service-btn bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-6 text-center min-h-64 flex flex-col items-center justify-center">
                    <i class="fas fa-shield-alt text-6xl mb-4 text-yellow-300"></i>
                    <h3 class="text-xl font-bold mb-2">Insurance & Benefits</h3>
                    <p class="text-sm opacity-80">UCBP Insurance, SSS Benefits</p>
                </div>
                <div onclick="selectService('renewals')" class="service-btn bg-gradient-to-br from-green-600 to-green-800 rounded-3xl p-6 text-center min-h-64 flex flex-col items-center justify-center">
                    <i class="fas fa-id-card text-6xl mb-4 text-yellow-300"></i>
                    <h3 class="text-xl font-bold mb-2">ID Renewal & ATM claim</h3>
                    <p class="text-sm opacity-80">ID Card, ATM Card</p>
                </div>
                <div onclick="selectService('other')" class="service-btn bg-gradient-to-br from-gray-600 to-gray-800 rounded-3xl p-6 text-center min-h-64 flex flex-col items-center justify-center">
                    <i class="fas fa-question-circle text-6xl mb-4 text-yellow-300"></i>
                    <h3 class="text-xl font-bold mb-2">Other</h3>
                    <p class="text-sm opacity-80">General Inquiries</p>
                </div>
            </div>
            <div class="text-center mt-6"><p class="text-sm opacity-60"><i class="fas fa-info-circle mr-2"></i>Tap your service to continue</p></div>
        </div>

        <div id="step1b" class="w-full max-w-2xl hidden fade-in">
            <button onclick="goBackToStep1()" class="text-white opacity-70 hover:opacity-100 mb-6"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <h2 class="text-3xl font-bold text-center mb-8">Which one do you need?</h2>
            <div id="subServiceGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sub-services injected by JS -->
            </div>
            <div class="text-center mt-8"><p class="text-sm opacity-60">Please select the specific service to help us serve you better</p></div>
        </div>

        <div id="step2" class="w-full max-w-2xl hidden fade-in">
            <button onclick="goBack()" class="text-white opacity-70 hover:opacity-100 mb-6"><i class="fas fa-arrow-left mr-2"></i>Back</button>
            <h2 class="text-3xl font-bold text-center mb-8" id="serviceTitle">Enter Your Name</h2>
            <div class="bg-white bg-opacity-20 backdrop-blur rounded-3xl p-8">
                <input type="text" id="customerName" class="w-full px-6 py-4 text-2xl text-center bg-white text-gray-800 rounded-xl focus:outline-none focus:ring-4 focus:ring-yellow-400" placeholder="Tap here to enter name" autocomplete="off">
                <div class="flex gap-4 mt-6">
                    <button onclick="goBack()" class="flex-1 bg-gray-600 text-white py-4 rounded-xl text-xl font-semibold">Back</button>
                    <button onclick="submitCustomer()" id="submitBtn" class="flex-1 bg-green-600 text-white py-4 rounded-xl text-xl font-semibold">Get Queue Number</button>
                </div>
            </div>
        </div>

        <div id="step3" class="w-full max-w-2xl hidden">
            <div class="bg-white rounded-3xl p-12 text-center success-animation">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-green-100 mb-6"><i class="fas fa-check text-5xl text-green-600"></i></div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Your Queue Number</h3>
                <div id="queueNumber" class="text-8xl font-bold text-blue-600 mb-4" style="font-family:'Courier New',monospace;">---</div>
                <div id="queueInfo" class="text-gray-600 mb-6"><p class="text-xl">Service: <span id="serviceName" class="font-semibold">---</span></p><p class="text-lg mt-2">Please proceed to <span id="windowAssigned" class="font-semibold">---</span></p></div>
                <div class="bg-blue-50 rounded-xl p-4 mb-6"><p class="text-blue-800"><i class="fas fa-clock mr-2"></i><span id="positionText">Position in queue: --</span></p></div>
                <div class="text-sm text-gray-500 mb-8"><p>Your number will be called when it's your turn</p><p class="mt-1">Please wait for your number to be displayed</p></div>
                <button onclick="resetKiosk()" class="bg-blue-600 text-white px-8 py-4 rounded-xl text-xl font-semibold"><i class="fas fa-plus mr-2"></i>Add Another</button>
            </div>
            <div class="text-center mt-6 text-white opacity-70"><p class="text-sm">Auto-resetting in <span id="countdown">30</span> seconds...</p></div>
        </div>
    </main>

    <footer class="bg-black bg-opacity-40 py-4 text-center"><p class="text-sm opacity-60">Need assistance? Please approach the information desk</p></footer>

    <script>
        let finalServiceType = null; let queueData = null; let countdownInterval = null;

        function selectService(category) {
            if (category === 'other') {
                finalServiceType = 'other';
                showNameEntry('Other');
                return;
            }
            
            let subServices = [];
            if (category === 'insurance_benefits') {
                subServices = [
                    { name: 'Insurance', code: 'insurance', icon: 'fa-shield-alt' },
                    { name: 'Benefits', code: 'benefits', icon: 'fa-hand-holding-heart' }
                ];
            } else if (category === 'renewals') {
                subServices = [
                    { name: 'ID Renewal', code: 'id_renewal', icon: 'fa-id-card' },
                    { name: 'ATM claim', code: 'atm_renewal', icon: 'fa-credit-card' }
                ];
            }
            
            renderSubServices(subServices);
            document.getElementById('step1').classList.add('hidden');
            document.getElementById('step1b').classList.remove('hidden');
        }

        function renderSubServices(subs) {
            let html = '';
            subs.forEach(s => {
                html += '<div onclick="selectSubService(\'' + s.code + '\', \'' + s.name + '\')" class="service-btn bg-white bg-opacity-20 backdrop-blur rounded-3xl p-8 text-center flex flex-col items-center justify-center border-2 border-transparent hover:border-yellow-400 transition-all shadow-xl">' +
                    '<i class="fas ' + s.icon + ' text-6xl mb-4 text-yellow-300"></i>' +
                    '<h3 class="text-2xl font-bold">' + s.name + '</h3>' +
                '</div>';
            });
            document.getElementById('subServiceGrid').innerHTML = html;
        }

        function selectSubService(code, name) {
            finalServiceType = code;
            showNameEntry(name);
        }

        function showNameEntry(title) {
            document.getElementById('serviceTitle').textContent = 'Service: ' + title;
            document.getElementById('step1').classList.add('hidden');
            document.getElementById('step1b').classList.add('hidden');
            document.getElementById('step2').classList.remove('hidden');
            setTimeout(() => document.getElementById('customerName').focus(), 300);
        }

        function goBackToStep1() {
            document.getElementById('step1b').classList.add('hidden');
            document.getElementById('step1').classList.remove('hidden');
            finalServiceType = null;
        }

        function goBack() { 
            document.getElementById('step2').classList.add('hidden'); 
            if (finalServiceType === 'other') {
                document.getElementById('step1').classList.remove('hidden'); 
            } else {
                document.getElementById('step1b').classList.remove('hidden');
            }
            document.getElementById('customerName').value = ''; 
            // Don't reset finalServiceType yet so we know where to go back
        }

        async function submitCustomer() {
            const name = document.getElementById('customerName').value.trim();
            if (!name || name.length < 2) { alert('Please enter your name (min 2 characters)'); return; }
            const submitBtn = document.getElementById('submitBtn'); 
            submitBtn.disabled = true; 
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const response = await fetch('api/add_customer.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ name, service_type: finalServiceType }) 
                });
                const data = await response.json();
                if (data.success) { 
                    queueData = data.data; 
                    showQueueNumber(data.queue_number, finalServiceType, queueData); 
                } else { 
                    alert(data.message || 'Failed'); 
                    submitBtn.disabled = false; 
                    submitBtn.innerHTML = 'Get Queue Number'; 
                }
            } catch (error) { 
                console.error('Kiosk submission error:', error);
                alert('Connection error'); 
                submitBtn.disabled = false; 
                submitBtn.innerHTML = 'Get Queue Number'; 
            }
        }

        function showQueueNumber(queueNumber, serviceType, data) {
            const serviceNames = { insurance: 'Insurance', benefits: 'Benefits', id_renewal: 'ID Renewal', atm_renewal: 'ATM claim', other: 'Other' };
            document.getElementById('queueNumber').textContent = queueNumber;
            document.getElementById('serviceName').textContent = serviceNames[serviceType] || serviceType;
            document.getElementById('windowAssigned').textContent = data.assigned_counter || 'Available Window';
            document.getElementById('positionText').textContent = `Position in queue: ${data.queue_position || '--'}`;
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('step3').classList.remove('hidden');
            let seconds = 30; document.getElementById('countdown').textContent = seconds;
            countdownInterval = setInterval(() => { seconds--; document.getElementById('countdown').textContent = seconds; if (seconds <= 0) resetKiosk(); }, 1000);
        }

        function resetKiosk() {
            if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
            document.getElementById('step3').classList.add('hidden'); document.getElementById('step1').classList.remove('hidden');
            document.getElementById('customerName').value = '';
            const submitBtn = document.getElementById('submitBtn'); submitBtn.disabled = false; submitBtn.innerHTML = 'Get Queue Number';
            selectedService = null; queueData = null;
        }

        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') resetKiosk(); });
        document.addEventListener('touchend', (e) => { e.preventDefault(); e.target.click(); }, { passive: false });
    </script>
</body>
</html>