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
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $name = trim($data['name'] ?? '');
    $code = trim($data['code'] ?? '');
    $prefix = trim($data['prefix'] ?? '');
    $description = trim($data['description'] ?? '');

    if (!$name) throw new Exception('Service name is required');
    if (!$code) throw new Exception('Service code is required');
    if (!$prefix) throw new Exception('Queue prefix is required');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id FROM service_types WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) throw new Exception('Service code already exists');

    $stmt = $conn->prepare("INSERT INTO service_types (name, code, description, queue_prefix, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$name, $code, $description, $prefix]);

    $id = $conn->lastInsertId();

    $stmt = $conn->prepare("INSERT INTO queue_sequences (prefix, current_value) VALUES (?, 0) ON DUPLICATE KEY UPDATE prefix = prefix");
    $stmt->execute([$prefix]);

    echo json_encode([
        'success' => true,
        'message' => 'Service added successfully',
        'data' => ['id' => $id, 'name' => $name, 'code' => $code, 'prefix' => $prefix]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
