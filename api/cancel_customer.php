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
    $reason = $data['reason'] ?? '';
    $remark = $data['remark'] ?? null;
    
    if (!$customerId) {
        throw new Exception('customer_id is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    $terminals = ['completed', 'cancelled', 'skipped', 'no-show'];
    if (in_array($customer['status'], $terminals)) {
        throw new Exception('Customer is already ' . $customer['status']);
    }
    
    $statusMap = ['skipped' => 'skipped', 'no-show' => 'no-show'];
    $newStatus = $statusMap[$reason] ?? 'cancelled';
    
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE customers 
        SET status = ?,
            completed_at = ?,
            service_duration = TIMESTAMPDIFF(SECOND, served_at, ?),
            remark = COALESCE(?, remark)
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $now, $now, $remark, $customerId]);
    
    if ($customer['status'] === 'serving') {
        $stmt = $conn->prepare("UPDATE counters SET current_customer_id = NULL WHERE current_customer_id = ?");
        $stmt->execute([$customerId]);
    }
    
    $conn->commit();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $updatedCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer cancelled',
        'data' => [
            'customer' => $updatedCustomer,
            'reason' => $reason
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