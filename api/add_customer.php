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
    
    $name = $data['name'] ?? '';
    $serviceType = $data['service_type'] ?? '';

    if (empty($name) || empty($serviceType)) {
        echo json_encode(['success' => false, 'message' => 'Name and service type are required']);
        exit;
    }
    
    $name = trim($name);
    if (strlen($name) < 2 || strlen($name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Name must be between 2 and 100 characters']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT queue_prefix FROM service_types WHERE code = ? AND is_active = 1");
    $stmt->execute([$serviceType]);
    $serviceInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$serviceInfo) {
        throw new Exception('Invalid service type');
    }
    
    $prefix = $serviceInfo['queue_prefix'];
    
    $stmt = $conn->prepare("UPDATE queue_sequences SET current_value = current_value + 1 WHERE prefix = ?");
    $stmt->execute([$prefix]);
    
    $stmt = $conn->prepare("SELECT current_value FROM queue_sequences WHERE prefix = ?");
    $stmt->execute([$prefix]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = $result['current_value'];
    
    $queueNumber = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("
        SELECT c.id as counter_id, c.display_name 
        FROM counters c
        JOIN counter_service_assignments csa ON csa.counter_id = c.id
        WHERE csa.service_type = ? AND csa.is_active = 1 AND c.is_online = 1
        LIMIT 1
    ");
    $stmt->execute([$serviceType]);
    $availableCounter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $counterId = $availableCounter ? $availableCounter['counter_id'] : null;
    
    $stmt = $conn->prepare("INSERT INTO customers (queue_number, name, service_type, counter_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$queueNumber, $name, $serviceType, $counterId]);
    $customerId = $conn->lastInsertId();
    
    $conn->commit();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'queue_number' => $queueNumber,
        'message' => 'Customer added successfully',
        'data' => [
            'customer' => $customer,
            'assigned_counter' => $availableCounter ? $availableCounter['display_name'] : null,
            'queue_position' => getQueuePosition($conn, $customerId)
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getQueuePosition($conn, $customerId) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) + 1 as position 
        FROM customers 
        WHERE status = 'waiting' 
        AND service_type = (SELECT service_type FROM customers WHERE id = ?)
        AND created_at < (SELECT created_at FROM customers WHERE id = ?)
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$customerId, $customerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['position'];
}
?>