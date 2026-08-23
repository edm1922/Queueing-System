<?php
header('Content-Type: application/json');
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireRole(['admin']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $counterId = $data['counter_id'] ?? null;
    $name = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');

    if ($counterId === null || !is_numeric($counterId)) {
        throw new Exception('Valid counter_id is required');
    }
    if (empty($name)) {
        throw new Exception('Window name is required');
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    if (!$stmt->fetch()) {
        throw new Exception('Window not found');
    }

    $stmt = $conn->prepare("UPDATE counters SET name = ?, display_name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $name, $description ?: null, $counterId]);

    echo json_encode(['success' => true, 'message' => 'Window updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
