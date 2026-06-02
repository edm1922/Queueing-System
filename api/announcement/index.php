<?php
header('Content-Type: application/json');
include '../../config.php';

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

    if ($method === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true) ?? [];
        
        // Support _method=DELETE in POST for restricted environments
        if (isset($data['_method']) && strtoupper($data['_method']) === 'DELETE') {
            $method = 'DELETE';
            $_GET['id'] = $data['id'] ?? null;
        }
    }
    
    switch ($method) {
        case 'GET':
            // Public — no auth required
            $type = $_GET['type'] ?? null;
            $activeOnly = isset($_GET['active']) ? (bool)$_GET['active'] : true;
            
            $sql = "SELECT * FROM display_announcements WHERE 1=1";
            $params = [];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
                $sql .= " AND (starts_at IS NULL OR starts_at <= NOW())";
                $sql .= " AND (expires_at IS NULL OR expires_at > NOW())";
            }
            
            if ($type) {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY priority DESC, created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $announcements
            ]);
            break;
            
        case 'POST':
            requireRole(['admin', 'supervisor', 'staff']);
            if (empty($data) && !empty($input)) {
                $data = json_decode($input, true) ?? [];
            }
            
            $title = $data['title'] ?? '';
            $message = $data['message'] ?? '';
            $type = $data['type'] ?? 'info';
            $fontFamily = $data['font_family'] ?? 'Inter';
            $priority = $data['priority'] ?? 0;
            $isPreset = $data['is_preset'] ?? 0;
            $startsAt = !empty($data['starts_at']) ? $data['starts_at'] : null;
            $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
            $duration = !empty($data['display_duration']) ? (int)$data['display_duration'] : 10;
            
            if (empty($message)) {
                throw new Exception('Message is required');
            }
            
            if (!in_array($type, ['info', 'warning', 'urgent'])) {
                throw new Exception('Invalid announcement type');
            }
            
            $stmt = $conn->prepare("
                INSERT INTO display_announcements (title, message, type, font_family, priority, is_active, is_preset, starts_at, expires_at, display_duration)
                VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $message, $type, $fontFamily, (int)$priority, $isPreset ? 1 : 0, $startsAt, $expiresAt, $duration]);
            $announcementId = $conn->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Announcement added',
                'data' => [
                    'id' => $announcementId,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'font_family' => $fontFamily,
                    'priority' => $priority
                ]
            ]);
            break;
            
        case 'DELETE':
            requireRole(['admin', 'supervisor', 'staff']);
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('Announcement ID is required');
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