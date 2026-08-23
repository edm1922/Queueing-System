<?php
header('Content-Type: application/json');
include '../../config.php';
requireRole(['admin']);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    if ($userId) {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.display_name, u.role, u.window_id, u.is_active, u.last_login,
                   c.display_name as window_name
            FROM users u
            LEFT JOIN counters c ON c.id = u.window_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) throw new Exception('User not found');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        $stmt = $conn->query("
            SELECT u.id, u.username, u.display_name, u.role, u.window_id, u.is_active, u.last_login,
                   c.display_name as window_name
            FROM users u
            LEFT JOIN counters c ON c.id = u.window_id
            ORDER BY u.username ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $windows = $conn->query("SELECT id, display_name, window_number FROM counters ORDER BY window_number ASC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => ['users' => $users, 'windows' => $windows]]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
