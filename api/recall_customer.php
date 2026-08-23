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
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $customerId = $data['customer_id'] ?? null;
    $counterId = isset($data['counter_id']) ? intval($data['counter_id']) : null;
    
    if (!$customerId) {
        throw new Exception('customer_id is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    // Allow recall for currently serving customers or follow-up tickets
    if ($customer['status'] !== 'serving' && $customer['is_follow_up'] != 1) {
        throw new Exception('Customer is not in serving status');
    }
    
    // Verify the counter is online if specified
    if ($counterId) {
        $stmt = $conn->prepare("SELECT is_online, status_text FROM counters WHERE id = ?");
        $stmt->execute([$counterId]);
        $counterRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$counterRow || !$counterRow['is_online'] || $counterRow['status_text'] !== 'Online') {
            throw new Exception('Cannot recall a customer — window is not online. Set status to Online first.');
        }
    }
    
    $now = date('Y-m-d H:i:s');
    
    // Update called_at to trigger a re-announcement on the display
    if ($customer['is_follow_up'] == 1 && $counterId) {
        $stmt = $conn->prepare("UPDATE customers SET called_at = ?, counter_id = ? WHERE id = ?");
        $stmt->execute([$now, $counterId, $customerId]);
    } else {
        $stmt = $conn->prepare("UPDATE customers SET called_at = ? WHERE id = ?");
        $stmt->execute([$now, $customerId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer recalled successfully',
        'data' => [
            'id' => $customerId,
            'called_at' => $now
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
