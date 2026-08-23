<?php
header('Content-Type: application/json');
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireRole(['admin', 'supervisor', 'staff']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $name = $data['name'] ?? null;
    $description = trim($data['description'] ?? '');
    
    if (empty($name)) {
        throw new Exception('Window name is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    // Get max window number
    $stmt = $conn->query("SELECT MAX(window_number) as max_num FROM counters");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $windowNumber = ($row['max_num'] ?? 0) + 1;
    
    // Insert new counter
    $stmt = $conn->prepare("
        INSERT INTO counters (name, display_name, description, window_number, is_online, status_text)
        VALUES (?, ?, ?, ?, 0, 'Offline')
    ");
    $stmt->execute([$name, $name, $description ?: null, $windowNumber]);
    $counterId = $conn->lastInsertId();
    
    // Insert default service assignment ('other')
    $stmt = $conn->prepare("
        INSERT INTO counter_service_assignments (counter_id, service_type, is_primary, is_active, display_order)
        VALUES (?, 'other', 1, 0, 1)
    ");
    $stmt->execute([$counterId]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'New window added successfully',
        'data' => [
            'id' => $counterId,
            'name' => $name,
            'window_number' => $windowNumber
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
