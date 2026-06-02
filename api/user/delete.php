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

    $userId = intval($data['user_id'] ?? 0);
    if (!$userId) throw new Exception('User ID is required');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception('User not found');
    if ($user['username'] === 'admin') throw new Exception('Cannot delete the admin user');

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'User deactivated']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
