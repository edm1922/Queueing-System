<?php
header('Content-Type: application/json');
include '../../config.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $displayName = trim($data['display_name'] ?? '');
    $role = $data['role'] ?? 'staff';
    $windowId = $data['window_id'] ? intval($data['window_id']) : null;

    if (!$username || !$password || !$displayName) throw new Exception('Username, password, and display name are required');
    if (strlen($username) < 3) throw new Exception('Username must be at least 3 characters');
    if (strlen($password) < 6) throw new Exception('Password must be at least 6 characters');
    if (!in_array($role, ['admin', 'supervisor', 'staff'])) throw new Exception('Invalid role');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) throw new Exception('Username already exists');

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, display_name, role, window_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$username, $hash, $displayName, $role, $windowId]);

    echo json_encode(['success' => true, 'message' => 'User created', 'data' => ['id' => $conn->lastInsertId()]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
