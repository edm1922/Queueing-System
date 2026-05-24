<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT c.*, 
               ct.display_name as counter_name,
               st.queue_prefix,
               st.name as service_name
        FROM customers c
        LEFT JOIN counters ct ON ct.id = c.counter_id
        LEFT JOIN service_types st ON st.code = c.service_type
        WHERE DATE(c.created_at) = CURDATE()
        ORDER BY 
            FIELD(c.status, 'serving', 'waiting', 'completed', 'cancelled'),
            c.created_at ASC
    ");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT c.*,
               cust.queue_number as current_queue_number,
               cust.name as current_customer_name,
               (SELECT GROUP_CONCAT(DISTINCT csa.service_type) 
                FROM counter_service_assignments csa 
                WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id
        ORDER BY c.window_number ASC
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name ASC");
    $serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COUNT(*) as offline_count FROM counters WHERE is_online = 0");
    $offlineCount = $stmt->fetch(PDO::FETCH_ASSOC)['offline_count'];
    
    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'counters' => $counters,
        'service_types' => $serviceTypes,
        'redistribution_status' => [
            'active' => $offlineCount > 0,
            'offline_counters' => $offlineCount
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>