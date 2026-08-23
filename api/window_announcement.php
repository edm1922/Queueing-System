<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];
    $input = '';
    $data = [];

    if (in_array($method, ['POST', 'DELETE'])) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
    }

    switch ($method) {
        case 'GET':
            $counterId = isset($_GET['counter_id']) ? intval($_GET['counter_id']) : 0;
            if (!$counterId) {
                throw new Exception('counter_id is required');
            }

            $stmt = $conn->prepare("
                SELECT a.*, c.display_name as window_name
                FROM display_announcements a
                LEFT JOIN counters c ON c.id = a.counter_id
                WHERE a.counter_id = ? AND a.is_active = 1
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$counterId]);
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $announcements
            ]);
            break;

        case 'POST':
            $user = requireRole(['admin', 'supervisor', 'staff']);

            $message = trim($data['message'] ?? '');
            $counterId = intval($data['counter_id'] ?? ($user['window_id'] ?? 0));

            if (!$counterId) {
                throw new Exception('No window assigned');
            }
            if (empty($message)) {
                throw new Exception('Message is required');
            }
            if (mb_strlen($message) > 100) {
                throw new Exception('Message must be 100 characters or less');
            }

            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM display_announcements WHERE counter_id = ? AND is_active = 1");
            $stmt->execute([$counterId]);
            $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            if ($count >= 3) {
                throw new Exception('Maximum of 3 active announcements per window');
            }

            $stmt = $conn->prepare("
                INSERT INTO display_announcements (title, message, type, priority, is_active, counter_id, display_duration)
                VALUES (?, ?, 'info', 0, 1, ?, 10)
            ");
            $stmt->execute([null, $message, $counterId]);
            $announcementId = $conn->lastInsertId();

            echo json_encode([
                'success' => true,
                'message' => 'Announcement posted',
                'data' => ['id' => $announcementId]
            ]);
            break;

        case 'DELETE':
            requireRole(['admin', 'supervisor', 'staff']);
            $id = intval($data['id'] ?? 0);

            if (!$id) {
                throw new Exception('Announcement ID is required');
            }

            $stmt = $conn->prepare("SELECT * FROM display_announcements WHERE id = ?");
            $stmt->execute([$id]);
            $ann = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ann) {
                throw new Exception('Announcement not found');
            }

            $stmt = $conn->prepare("DELETE FROM display_announcements WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Announcement deleted'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>