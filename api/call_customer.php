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
    } else if ($customer['service_type'] === 'custom') {
        $stmt = $conn->prepare("SELECT custom_enabled FROM counters WHERE id = ?");
        $stmt->execute([$counterId]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$counter || $counter['custom_enabled'] != 1) {
            throw new Exception('Custom tickets are not enabled for this window');
        }
    } else {
        $stmt = $conn->prepare("
            SELECT id FROM counter_service_assignments 
            WHERE counter_id = ? AND service_type = ? AND is_active = 1
        ");
        $stmt->execute([$counterId, $customer['service_type']]);
        if (!$stmt->fetch()) {
            throw new Exception('This window is not assigned to the service ' . $customer['service_type']);
        }
    }
    
    if (!$counterId) {
        if ($customer['service_type'] === 'custom') {
            $stmt = $conn->prepare("SELECT id as counter_id FROM counters WHERE is_online = 1 AND custom_enabled = 1 LIMIT 1");
        } else {
            $stmt = $conn->query("SELECT id as counter_id FROM counters WHERE is_online = 1 LIMIT 1");
        }
        $anyCounter = $stmt->fetch(PDO::FETCH_ASSOC);
        $counterId = $anyCounter ? $anyCounter['counter_id'] : null;
    }
    
    if (!$counterId) {
        throw new Exception('No counter is currently online');
    }
    
    // Verify the target counter is actually online
    $stmt = $conn->prepare("SELECT is_online, status_text FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counterRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$counterRow || !$counterRow['is_online'] || $counterRow['status_text'] !== 'Online') {
        throw new Exception('Cannot call a customer — window is not online. Set status to Online first.');
    }
    
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("SELECT current_customer_id FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $currentCustId = $stmt->fetchColumn();
    if ($currentCustId) {
        $stmt = $conn->prepare("SELECT status, DATE(created_at) as created_date FROM customers WHERE id = ?");
        $stmt->execute([$currentCustId]);
        $currentCust = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($currentCust && $currentCust['status'] === 'serving') {
            if ($currentCust['created_date'] !== date('Y-m-d')) {
                $stmt = $conn->prepare("UPDATE customers SET status = 'completed', completed_at = ? WHERE id = ?");
                $stmt->execute([$now, $currentCustId]);
                $stmt = $conn->prepare("UPDATE counters SET customers_served = customers_served + 1 WHERE current_customer_id = ?");
                $stmt->execute([$currentCustId]);
            } else {
                throw new Exception('Window is currently serving a customer. Complete or skip the current ticket first.');
            }
        }
    }
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