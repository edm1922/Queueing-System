<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = requireRole(['admin', 'supervisor', 'staff']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $customerId = $data['customer_id'] ?? null;
    $remark = $data['remark'] ?? '';
    if (!$customerId) throw new Exception('customer_id is required');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) throw new Exception('Customer not found');

    $conn->beginTransaction();

    $now = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        UPDATE customers
        SET status = 'completed',
            completed_at = ?,
            is_follow_up = 0,
            follow_up_resolved_at = ?,
            follow_up_completed_by = ?,
            remark = ?,
            service_duration = COALESCE(service_duration, TIMESTAMPDIFF(SECOND, served_at, ?))
        WHERE id = ?
    ");
    $stmt->execute([$now, $now, $user['id'], $remark ?: null, $now, $customerId]);

    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = NULL WHERE current_customer_id = ?");
    $stmt->execute([$customerId]);

    if ($customer['counter_id']) {
        $stmt = $conn->prepare("
            SELECT AVG(service_duration) as avg_time
            FROM customers
            WHERE counter_id = ? AND status = 'completed' AND service_duration IS NOT NULL AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$customer['counter_id']]);
        $avgTime = $stmt->fetch(PDO::FETCH_ASSOC)['avg_time'];
        if ($avgTime) {
            $stmt = $conn->prepare("UPDATE counters SET avg_service_time = ? WHERE id = ?");
            $stmt->execute([(int)$avgTime, $customer['counter_id']]);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Follow-up completed'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
