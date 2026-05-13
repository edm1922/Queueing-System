<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = [];
    
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['settings'] = [
        'company_name' => $settings['company_name'] ?? 'Service Center',
        'welcome_message' => $settings['welcome_message'] ?? 'Welcome',
        'company_logo' => $settings['company_logo'] ?? null,
        'theme_color' => $settings['theme_color'] ?? '#1e3a5f'
    ];
    
    $stmt = $conn->query("
        SELECT c.id, c.display_name, c.is_online, c.status_text, c.window_number,
               cust.id as customer_id, cust.queue_number, cust.name as customer_name, 
               cust.service_type, cust.called_at,
               (SELECT GROUP_CONCAT(csa.service_type) FROM counter_service_assignments csa WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services,
               (SELECT GROUP_CONCAT(st.name SEPARATOR ', ') FROM counter_service_assignments csa JOIN service_types st ON st.code = csa.service_type WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services_names
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id
        ORDER BY c.window_number ASC
    ");
    $data['windows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT c.service_type, st.queue_prefix, st.name as service_name,
               c.id, c.queue_number, c.name
        FROM customers c
        JOIN service_types st ON st.code = c.service_type
        WHERE c.status = 'waiting' AND DATE(c.created_at) = CURDATE()
        GROUP BY c.service_type
        ORDER BY c.created_at ASC
    ");
    $data['next_by_service'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE()
    ");
    $data['waiting_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("
        SELECT * FROM display_announcements 
        WHERE is_active = 1 
        AND (starts_at IS NULL OR starts_at <= NOW())
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY priority DESC, created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $data['announcements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT COUNT(*) as offline_count FROM counters WHERE is_online = 0");
    $stmt->execute();
    $offlineCount = $stmt->fetch(PDO::FETCH_ASSOC)['offline_count'];
    
    $data['redistribution_notice'] = null;
    if ($offlineCount > 0) {
        $stmt = $conn->query("SELECT display_name FROM counters WHERE is_online = 0");
        $offlineCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counterNames = array_column($offlineCounters, 'display_name');
        $data['redistribution_notice'] = [
            'type' => 'warning',
            'message' => 'Service notice: Some windows are temporarily serving all customers. Please approach any available window.'
        ];
    }
    
    $stmt = $conn->query("
        SELECT queue_number, service_type
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE()
        ORDER BY created_at ASC
        LIMIT 20
    ");
    $data['waiting_queue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT cust.queue_number, cust.service_type, cust.called_at, c.window_number, c.display_name
        FROM customers cust
        JOIN counters c ON cust.counter_id = c.id
        WHERE cust.status IN ('serving', 'completed') 
        AND DATE(cust.created_at) = CURDATE() 
        AND cust.called_at IS NOT NULL
        ORDER BY cust.called_at DESC
        LIMIT 10
    ");
    $data['recent_called_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>