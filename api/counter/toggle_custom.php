<?php
header('Content-Type: application/json');
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = requireRole(['admin']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $counterId = $data['counter_id'] ?? null;
    $enabled = $data['enabled'] ?? null;

    if (!$counterId) throw new Exception('counter_id is required');
    if ($enabled === null) throw new Exception('enabled is required');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE counters SET custom_enabled = ? WHERE id = ?");
    $stmt->execute([intval($enabled) ? 1 : 0, $counterId]);

    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Custom tickets enabled' : 'Custom tickets disabled',
        'custom_enabled' => intval($enabled) ? 1 : 0
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>