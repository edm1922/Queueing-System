<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM customers 
        WHERE DATE(created_at) = CURDATE()
        GROUP BY status
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'waiting' => 0,
        'serving' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'today_total' => 0
    ];
    
    foreach ($results as $row) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = (int)$row['count'];
        }
        if ($row['status'] !== 'cancelled') {
            $stats['today_total'] += (int)$row['count'];
        }
    }
    
    $stmt = $conn->query("
        SELECT 
            c.service_type,
            st.name as service_name,
            st.queue_prefix,
            COUNT(*) as total,
            SUM(CASE WHEN c.status = 'waiting' THEN 1 ELSE 0 END) as waiting,
            SUM(CASE WHEN c.status = 'serving' THEN 1 ELSE 0 END) as serving,
            SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM customers c
        JOIN service_types st ON st.code = c.service_type
        WHERE DATE(c.created_at) = CURDATE()
        GROUP BY c.service_type, st.name, st.queue_prefix
    ");
    $statsByService = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT 
            AVG(wait_duration) as avg_wait,
            AVG(service_duration) as avg_service,
            MAX(wait_duration) as max_wait,
            MAX(service_duration) as max_service
        FROM customers 
        WHERE DATE(created_at) = CURDATE() 
        AND status = 'completed'
        AND wait_duration IS NOT NULL
        AND service_duration IS NOT NULL
    ");
    $timings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT 
            c.id,
            c.display_name,
            c.is_online,
            c.customers_served,
            c.avg_service_time,
            COUNT(CASE WHEN c2.status = 'serving' AND c2.counter_id = c.id THEN 1 END) as currently_serving
        FROM counters c
        LEFT JOIN customers c2 ON DATE(c2.created_at) = CURDATE()
        GROUP BY c.id, c.display_name, c.is_online, c.customers_served, c.avg_service_time
    ");
    $counterStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'basic' => $stats,
            'by_service' => $statsByService,
            'timings' => [
                'avg_wait_seconds' => (int)($timings['avg_wait'] ?? 0),
                'avg_service_seconds' => (int)($timings['avg_service'] ?? 0),
                'avg_wait_formatted' => formatDuration($timings['avg_wait'] ?? 0),
                'avg_service_formatted' => formatDuration($timings['avg_service'] ?? 0),
                'max_wait_seconds' => (int)($timings['max_wait'] ?? 0),
                'max_service_seconds' => (int)($timings['max_service'] ?? 0)
            ],
            'counters' => $counterStats
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function formatDuration($seconds) {
    if (!$seconds) return '0:00';
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return "{$minutes}:{$secs}";
}
?>