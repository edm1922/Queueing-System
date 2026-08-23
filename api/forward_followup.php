<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = requireRole(['staff']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $customerId = $data['customer_id'] ?? null;
    $targetCounterId = $data['target_counter_id'] ?? null;
    $remark = trim($data['remark'] ?? '');

    if (!$customerId) throw new Exception('customer_id is required');
    if (!$targetCounterId) throw new Exception('target_counter_id is required');
    if ($remark === '') throw new Exception('A remark is required to forward a follow-up');

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception('Customer not found');
    if ($customer['is_follow_up'] != 1) throw new Exception('Ticket is not a follow-up');

    $stmt = $conn->prepare("SELECT id, is_online, status_text FROM counters WHERE id = ?");
    $stmt->execute([$targetCounterId]);
    $targetCounter = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$targetCounter) throw new Exception('Target counter not found');
    if ($targetCounter['is_online'] != 1 || $targetCounter['status_text'] === 'Offline') {
        throw new Exception('Target counter is not online');
    }

    if ($user['window_id'] && intval($targetCounterId) === intval($user['window_id'])) {
        throw new Exception('Cannot forward to your own counter');
    }

    $stmt = $conn->prepare("
        UPDATE customers
        SET forwarded_to_counter_id = ?,
            forwarded_by_user_id = ?,
            forward_remark = ?,
            forwarded_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$targetCounterId, $user['id'], $remark, $customerId]);

    echo json_encode([
        'success' => true,
        'message' => 'Follow-up forwarded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>