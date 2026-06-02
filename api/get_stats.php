<?php
header('Content-Type: application/json');
include '../config.php';

requireRole(['admin', 'supervisor', 'staff']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    $counterId = isset($_GET['counter_id']) ? intval($_GET['counter_id']) : 0;

    $serviceFilter = '';
    $timingFilter = '';
    $params = [];
    $timingParams = [];
    if ($counterId) {
        $serviceFilter = ' AND c.service_type IN (SELECT service_type FROM counter_service_assignments WHERE counter_id = ? AND is_active = 1)';
        $timingFilter = ' AND service_type IN (SELECT service_type FROM counter_service_assignments WHERE counter_id = ? AND is_active = 1)';
        $params[] = $counterId;
        $timingParams[] = $counterId;
    }

    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM customers c
        WHERE DATE(c.created_at) = CURDATE()" . $serviceFilter . "
        GROUP BY status
    ");
    $stmt->execute($params);
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
    
    $stmt = $conn->prepare("
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
        WHERE DATE(c.created_at) = CURDATE()" . $serviceFilter . "
        GROUP BY c.service_type, st.name, st.queue_prefix
    ");
    $stmt->execute($params);
    $statsByService = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("
        SELECT 
            AVG(wait_duration) as avg_wait,
            AVG(service_duration) as avg_service,
            MAX(wait_duration) as max_wait,
            MAX(service_duration) as max_service
        FROM customers 
        WHERE DATE(created_at) = CURDATE() 
        AND status = 'completed'
        AND wait_duration IS NOT NULL
        AND service_duration IS NOT NULL" . $timingFilter . "
    ");
    $stmt->execute($timingParams);
    $timings = $stmt->fetch(PDO::FETCH_ASSOC);

    $counterSql = "
        SELECT 
            c.id, c.display_name, c.window_number, c.is_online, c.customers_served, c.avg_service_time,
            (SELECT GROUP_CONCAT(DISTINCT csa.service_type) FROM counter_service_assignments csa WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services,
            COUNT(CASE WHEN c2.status = 'serving' AND c2.counter_id = c.id THEN 1 END) as currently_serving
        FROM counters c
        LEFT JOIN customers c2 ON DATE(c2.created_at) = CURDATE()
    ";
    if ($counterId) {
        $counterSql .= " WHERE c.id = ?";
        $stmt = $conn->prepare($counterSql . " GROUP BY c.id, c.display_name, c.window_number, c.is_online, c.customers_served, c.avg_service_time");
        $stmt->execute([$counterId]);
    } else {
        $stmt = $conn->query($counterSql . " GROUP BY c.id, c.display_name, c.window_number, c.is_online, c.customers_served, c.avg_service_time");
    }
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