<?php
header('Content-Type: application/json');
include '../../config.php';

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
    
    $hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
    
    $stmt = $conn->prepare("
        SELECT rl.*, 
               c.display_name as counter_name,
               rc.display_name as reassigned_to_name
        FROM redistribution_logs rl
        LEFT JOIN counters c ON c.id = rl.counter_id
        LEFT JOIN counters rc ON rc.id = rl.reassigned_to_counter_id
        WHERE rl.created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ORDER BY rl.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$hours]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($logs as &$log) {
        if ($log['affected_services']) {
            $log['affected_services'] = json_decode($log['affected_services'], true);
        }
    }
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_events,
            SUM(CASE WHEN event_type = 'counter_offline' THEN 1 ELSE 0 END) as offline_events,
            SUM(CASE WHEN event_type = 'counter_online' THEN 1 ELSE 0 END) as online_events,
            SUM(CASE WHEN event_type = 'manual_override' THEN 1 ELSE 0 END) as override_events,
            SUM(reassigned_customers) as total_reassigned
        FROM redistribution_logs
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'summary' => $summary
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>