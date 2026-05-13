<?php
header('Content-Type: application/json');
include '../../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $from = $_GET['from'] ?? date('Y-m-d');
    $to = $_GET['to'] ?? date('Y-m-d');
    $serviceType = $_GET['service_type'] ?? null;
    
    $where = "WHERE DATE(c.created_at) BETWEEN ? AND ?";
    $params = [$from, $to];
    
    if ($serviceType) {
        $where .= " AND c.service_type = ?";
        $params[] = $serviceType;
    }
    
    $sql = "SELECT c.*, 
                   ct.display_name as window_name,
                   st.name as service_name
            FROM customers c
            LEFT JOIN counters ct ON ct.id = c.counter_id
            LEFT JOIN service_types st ON st.code = c.service_type
            $where
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalServed = count(array_filter($customers, fn($c) => $c['status'] === 'completed'));
    $totalWait = array_sum(array_filter(array_column($customers, 'wait_duration'), fn($w) => $w !== null));
    $totalService = array_sum(array_filter(array_column($customers, 'service_duration'), fn($s) => $s !== null));
    $avgWait = $totalServed > 0 ? round($totalWait / $totalServed) : 0;
    $avgService = $totalServed > 0 ? round($totalService / $totalServed) : 0;
    
    $fromDate = new DateTime($from);
    $toDate = new DateTime($to);
    $days = $fromDate->diff($toDate)->days + 1;
    $hoursInRange = $days * 8;
    $customersPerHour = $hoursInRange > 0 ? round($totalServed / $hoursInRange, 1) : 0;
    
    $stmt = $conn->prepare("
        SELECT c.service_type,
               st.name as service_name,
               COUNT(*) as total_served,
               AVG(c.wait_duration) as avg_wait,
               AVG(c.service_duration) as avg_service
        FROM customers c
        LEFT JOIN service_types st ON st.code = c.service_type
        WHERE DATE(c.created_at) BETWEEN ? AND ?
        AND c.status = 'completed'
        GROUP BY c.service_type, st.name
    ");
    $stmt->execute([$from, $to]);
    $byService = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($byService as &$service) {
        $service['percent'] = $totalServed > 0 ? round(($service['total_served'] / $totalServed) * 100) : 0;
        $service['avg_wait'] = (int)($service['avg_wait'] ?? 0);
        $service['avg_service'] = (int)($service['avg_service'] ?? 0);
    }
    
    $stmt = $conn->prepare("
        SELECT HOUR(c.created_at) as hour,
               COUNT(*) as count
        FROM customers c
        WHERE DATE(c.created_at) BETWEEN ? AND ?
        GROUP BY HOUR(c.created_at)
        ORDER BY hour
    ");
    $stmt->execute([$from, $to]);
    $hourly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'customers' => $customers,
            'summary' => [
                'total_served' => $totalServed,
                'avg_wait_seconds' => $avgWait,
                'avg_service_seconds' => $avgService,
                'customers_per_hour' => $customersPerHour
            ],
            'by_service' => $byService,
            'hourly' => $hourly,
            'date_range' => ['from' => $from, 'to' => $to]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>