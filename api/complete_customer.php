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
    $remark = $data['remark'] ?? null;
    
    if (!$customerId) {
        throw new Exception('customer_id is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("
        SELECT c.*, ct.id as counter_id 
        FROM customers c
        LEFT JOIN counters ct ON ct.current_customer_id = c.id
        WHERE c.id = ? AND c.status = 'serving'
    ");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found or not in serving status');
    }
    
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        UPDATE customers 
        SET status = 'completed', 
            completed_at = ?,
            service_duration = TIMESTAMPDIFF(SECOND, served_at, ?),
            remark = COALESCE(?, remark)
        WHERE id = ?
    ");
    $stmt->execute([$now, $now, $remark, $customerId]);
    
    $stmt = $conn->prepare("
        UPDATE counters 
        SET current_customer_id = NULL,
            customers_served = customers_served + 1
        WHERE current_customer_id = ?
    ");
    $stmt->execute([$customerId]);
    
    if ($customer['counter_id']) {
        $stmt = $conn->prepare("
            SELECT AVG(service_duration) as avg_time 
            FROM customers 
            WHERE counter_id = ? 
            AND status = 'completed' 
            AND service_duration IS NOT NULL
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$customer['counter_id']]);
        $avgTime = $stmt->fetch(PDO::FETCH_ASSOC)['avg_time'];
        
        if ($avgTime) {
            $stmt = $conn->prepare("UPDATE counters SET avg_service_time = ? WHERE id = ?");
            $stmt->execute([(int)$avgTime, $customer['counter_id']]);
        }
    }
    
    $conn->commit();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $updatedCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer service completed',
        'data' => [
            'customer' => $updatedCustomer,
            'service_duration_formatted' => formatDuration($updatedCustomer['service_duration'])
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function formatDuration($seconds) {
    if (!$seconds) return '0:00';
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return "{$minutes}:{$secs}";
}
?>