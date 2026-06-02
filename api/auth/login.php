<?php
header('Content-Type: application/json');
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        logAuthEvent($conn, null, 'login_failed', 'User not found');
        throw new Exception('Invalid username or password');
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        logAuthEvent($conn, $user['id'], 'login_failed', 'Invalid password');
        throw new Exception('Invalid username or password');
    }
    
    if ($user['password_reset_required']) {
        logAuthEvent($conn, $user['id'], 'password_reset_required', '');
        echo json_encode([
            'success' => true,
            'require_password_reset' => true,
            'user_id' => $user['id'],
            'message' => 'Password reset required'
        ]);
        exit;
    }
    
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));
    
    $stmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, token, expires_at, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expiresAt, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
    
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    logAuthEvent($conn, $user['id'], 'login_success', '');
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'token' => $token,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'window_id' => $user['window_id'] ?? null
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function logAuthEvent($conn, $userId, $event, $details) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO auth_logs (user_id, event_type, ip_address, details)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $event, $_SERVER['REMOTE_ADDR'] ?? '', $details]);
    } catch (Exception $e) {}
}
?>