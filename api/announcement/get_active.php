<?php
header('Content-Type: application/json');
include '../../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT * FROM display_announcements 
        WHERE is_active = 1 
        AND (starts_at IS NULL OR starts_at <= NOW())
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY priority DESC, created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("
        SELECT id, title, message, type, priority 
        FROM display_announcements 
        WHERE is_preset = 1 AND is_active = 1
        ORDER BY priority DESC
    ");
    $stmt->execute();
    $presets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("
        SELECT * FROM redistribution_logs 
        WHERE event_type = 'counter_offline' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $recentRedistribution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT id, display_name FROM counters WHERE is_online = 0");
    $stmt->execute();
    $offlineCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $dynamicMessage = null;
    if (!empty($offlineCounters)) {
        $counterNames = array_column($offlineCounters, 'display_name');
        $dynamicMessage = "Service notice: Some windows are temporarily serving all customers. Please approach any available window.";
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'announcements' => $announcements,
            'presets' => $presets,
            'dynamic_message' => $dynamicMessage,
            'offline_counters' => $offlineCounters,
            'redistribution_active' => $recentRedistribution !== false
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>