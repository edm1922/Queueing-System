<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = [];
    
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $cutoffRaw = $settings['cutoff_time'] ?? '17:00:00';
    $data['settings'] = [
        'company_name' => $settings['company_name'] ?? 'Service Center',
        'welcome_message' => $settings['welcome_message'] ?? 'Welcome',
        'company_logo' => $settings['company_logo'] ?? null,
        'theme_color' => $settings['theme_color'] ?? '#1e3a5f',
        'cutoff_time' => $cutoffRaw,
        'cutoff_time_formatted' => date('g:i A', strtotime($cutoffRaw))
    ];
    $data['force_refresh_token'] = intval($settings['force_refresh_token'] ?? 0);
    
    $settingsHashFields = [];
    foreach (['company_name','branch_name','address','welcome_message','video_url','video_type','video_title','video_sponsor','video_cta','video_volume','cutoff_time','company_logo','poster_duration','poster_images','poster_announcements'] as $f) {
        $settingsHashFields[$f] = $settings[$f] ?? '';
    }
    $data['settings_hash'] = md5(json_encode($settingsHashFields));
    
    $stmt = $conn->query("
        SELECT c.id, c.display_name, c.description, c.is_online, c.window_number, c.status_text, c.custom_enabled,
               cust.id as customer_id, cust.queue_number, cust.name as customer_name, 
               cust.service_type, cust.company_name, cust.purpose, cust.called_at,
               cust.custom_description,
               (SELECT GROUP_CONCAT(csa.service_type) FROM counter_service_assignments csa WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services,
               (SELECT GROUP_CONCAT(st.name SEPARATOR ', ') FROM counter_service_assignments csa JOIN service_types st ON st.code = csa.service_type WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services_names
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id AND DATE(cust.created_at) = CURDATE()
        ORDER BY c.window_number ASC
    ");
    $data['windows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT c.service_type, st.queue_prefix, st.name as service_name,
               c.id, c.queue_number, c.name, c.custom_description
        FROM customers c
        JOIN service_types st ON st.code = c.service_type
        WHERE c.status = 'waiting' AND DATE(c.created_at) = CURDATE()
        ORDER BY c.created_at ASC
    ");
    $allWaiting = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data['next_by_service'] = [];
    $seenServices = [];
    foreach ($allWaiting as $row) {
        if (!isset($seenServices[$row['service_type']])) {
            $seenServices[$row['service_type']] = true;
            $data['next_by_service'][] = $row;
        }
    }

    $stmt = $conn->query("
        SELECT COUNT(*) as count 
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE()
    ");
    $data['waiting_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->prepare("
        SELECT a.*, c.display_name as window_name
        FROM display_announcements a
        LEFT JOIN counters c ON c.id = a.counter_id
        WHERE a.is_active = 1 
        AND (a.starts_at IS NULL OR a.starts_at <= NOW())
        AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY a.priority DESC, a.created_at DESC
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
        SELECT queue_number, service_type, name, company_name, purpose, custom_description, counter_id
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE()
        ORDER BY created_at ASC
        LIMIT 20
    ");
    $data['waiting_queue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT cust.queue_number, cust.service_type, cust.called_at, c.window_number, c.display_name, cust.custom_description
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