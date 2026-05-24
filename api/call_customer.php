<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $customerId = $data['customer_id'] ?? null;
    $counterId = $data['counter_id'] ?? null;
    
    if (!$customerId) {
        throw new Exception('customer_id is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ? AND status = 'waiting'");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found or not in waiting status');
    }
    
    if (!$counterId) {
        $stmt = $conn->prepare("
            SELECT c.id as counter_id 
            FROM counters c
            JOIN counter_service_assignments csa ON csa.counter_id = c.id
            WHERE csa.service_type = ? AND csa.is_active = 1 AND c.is_online = 1
            LIMIT 1
        ");
        $stmt->execute([$customer['service_type']]);
        $availableCounter = $stmt->fetch(PDO::FETCH_ASSOC);
        $counterId = $availableCounter ? $availableCounter['counter_id'] : null;
    }
    
    if (!$counterId) {
        $stmt = $conn->query("SELECT id as counter_id FROM counters WHERE is_online = 1 LIMIT 1");
        $anyCounter = $stmt->fetch(PDO::FETCH_ASSOC);
        $counterId = $anyCounter ? $anyCounter['counter_id'] : null;
    }
    
    if (!$counterId) {
        throw new Exception('No counter is currently online');
    }
    
    $stmt = $conn->prepare("SELECT current_customer_id FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $currentCustId = $stmt->fetchColumn();
    if ($currentCustId) {
        $stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
        $stmt->execute([$currentCustId]);
        $currentStatus = $stmt->fetchColumn();
        if ($currentStatus === 'serving') {
            throw new Exception('Window is currently serving a customer. Complete or skip the current ticket first.');
        }
    }
    
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE customers 
        SET status = 'serving', 
            called_at = ?, 
            served_at = ?,
            counter_id = ?
        WHERE id = ?
    ");
    $stmt->execute([$now, $now, $counterId, $customerId]);
    
    $stmt = $conn->prepare("
        UPDATE customers 
        SET wait_duration = TIMESTAMPDIFF(SECOND, created_at, ?)
        WHERE id = ?
    ");
    $stmt->execute([$now, $customerId]);
    
    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $counterId]);
    
    $conn->commit();
    
    $stmt = $conn->prepare("SELECT * FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $updatedCustomer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer called successfully',
        'data' => [
            'customer' => $updatedCustomer,
            'counter' => [
                'id' => $counter['id'],
                'name' => $counter['display_name'],
                'window_number' => $counter['window_number']
            ]
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