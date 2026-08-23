<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireRole(['admin', 'supervisor', 'staff']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $customerId = $data['customer_id'] ?? null;
    $remark = $data['remark'] ?? '';

    if (!$customerId) throw new Exception('customer_id is required');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE customers SET is_follow_up = 0, follow_up_rejected_at = NOW(), remark = ? WHERE id = ?");
    $stmt->execute([$remark ?: null, $customerId]);

    echo json_encode([
        'success' => true,
        'message' => 'Follow-up removed'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
