<?php
header('Content-Type: application/json');
include '../../config.php';

requireRole(['admin', 'supervisor', 'staff']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            c.id as counter_id,
            c.name,
            c.display_name,
            c.window_number,
            c.is_online,
            c.status_text,
            c.custom_enabled,
            c.avg_service_time,
            c.customers_served,
            c.current_customer_id,
            c.last_status_change,
            (SELECT name FROM customers WHERE id = c.current_customer_id) as current_customer_name
        FROM counters c
        ORDER BY c.window_number ASC
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counters as &$counter) {
        $stmt = $conn->prepare("
            SELECT csa.service_type, csa.is_primary, csa.is_active, csa.display_order,
                   st.name as service_name, st.queue_prefix
            FROM counter_service_assignments csa
            JOIN service_types st ON st.code = csa.service_type
            WHERE csa.counter_id = ?
            ORDER BY csa.display_order ASC
        ");
        $stmt->execute([$counter['counter_id']]);
        $counter['service_assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (isset($counter['active_services']) && $counter['active_services']) {
            $counter['active_services'] = explode(',', $counter['active_services']);
        }
    }
    
    $stmt = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name ASC");
    $serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT id, display_name FROM counters WHERE is_online = 1");
    $availableCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'counters' => $counters,
            'service_types' => $serviceTypes,
            'available_counters' => $availableCounters
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>