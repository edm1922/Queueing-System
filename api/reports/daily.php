<?php
header('Content-Type: application/json');
include __DIR__ . '/../../config.php';

$authUser = requireRole(['admin', 'supervisor', 'staff']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $from = $_GET['from'] ?? date('Y-m-d');
    $to = $_GET['to'] ?? date('Y-m-d');
    $serviceType = $_GET['service_type'] ?? null;
    $serviceTypesMulti = $_GET['service_types'] ?? null;
    $counterId = isset($_GET['counter_id']) ? intval($_GET['counter_id']) : 0;
    
    $where = "WHERE DATE(c.created_at) BETWEEN ? AND ?";
    $params = [$from, $to];
    
    $codes = $serviceTypesMulti ? array_map('trim', explode(',', $serviceTypesMulti)) : [];
    if ($serviceTypesMulti) {
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $where .= " AND c.service_type IN ($placeholders)";
        $params = array_merge($params, $codes);
    } elseif ($serviceType) {
        $where .= " AND c.service_type = ?";
        $params[] = $serviceType;
    }
    if ($counterId) {
        $customOverride = " AND (c.counter_id = ? OR (c.is_follow_up = 1 AND c.forwarded_to_counter_id = ?))";
        $customParams = [$counterId, $counterId];
    } else {
        $customOverride = '';
        $customParams = [];
    }
    
    $sql = "SELECT c.*, 
                   ct.display_name as window_name,
                   st.name as service_name
            FROM customers c
            LEFT JOIN counters ct ON ct.id = c.counter_id
            LEFT JOIN service_types st ON st.code = c.service_type
            $where $customOverride
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_merge($params, $customParams));
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
    
    $bySvcWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.status = 'completed'";
    $bySvcParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $bySvcWhere .= " AND c.service_type IN ($placeholders)";
        $bySvcParams = array_merge($bySvcParams, $codes);
    } elseif ($serviceType) {
        $bySvcWhere .= " AND c.service_type = ?";
        $bySvcParams[] = $serviceType;
    }
    $stmt = $conn->prepare("
        SELECT c.service_type,
               st.name as service_name,
               COUNT(*) as total_served,
               AVG(c.wait_duration) as avg_wait,
               AVG(c.service_duration) as avg_service
        FROM customers c
        LEFT JOIN service_types st ON st.code = c.service_type
        $bySvcWhere $customOverride
        GROUP BY c.service_type, st.name
    ");
    $stmt->execute(array_merge($bySvcParams, $customParams));
    $byService = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($byService as &$service) {
        $service['percent'] = $totalServed > 0 ? round(($service['total_served'] / $totalServed) * 100) : 0;
        $service['avg_wait'] = (int)($service['avg_wait'] ?? 0);
        $service['avg_service'] = (int)($service['avg_service'] ?? 0);
    }
    
    $hourlyWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ?";
    $hourlyParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $hourlyWhere .= " AND c.service_type IN ($placeholders)";
        $hourlyParams = array_merge($hourlyParams, $codes);
    } elseif ($serviceType) {
        $hourlyWhere .= " AND c.service_type = ?";
        $hourlyParams[] = $serviceType;
    }
    $stmt = $conn->prepare("
        SELECT HOUR(c.created_at) as hour,
               COUNT(*) as count
        FROM customers c
        $hourlyWhere $customOverride
        GROUP BY HOUR(c.created_at)
        ORDER BY hour
    ");
    $stmt->execute(array_merge($hourlyParams, $customParams));
    $hourly = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Purpose breakdown
    $purposeWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.purpose IS NOT NULL";
    $purposeParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $purposeWhere .= " AND c.service_type IN ($placeholders)";
        $purposeParams = array_merge($purposeParams, $codes);
    } elseif ($serviceType) {
        $purposeWhere .= " AND c.service_type = ?";
        $purposeParams[] = $serviceType;
    }
    $stmt = $conn->prepare("
        SELECT c.purpose, COUNT(*) as count
        FROM customers c
        $purposeWhere $customOverride
        GROUP BY c.purpose
        ORDER BY count DESC
    ");
    $stmt->execute(array_merge($purposeParams, $customParams));
    $purposeBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Company breakdown (top 10)
    $companyWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.company_name IS NOT NULL AND c.company_name != ''";
    $companyParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $companyWhere .= " AND c.service_type IN ($placeholders)";
        $companyParams = array_merge($companyParams, $codes);
    } elseif ($serviceType) {
        $companyWhere .= " AND c.service_type = ?";
        $companyParams[] = $serviceType;
    }
    $stmt = $conn->prepare("
        SELECT c.company_name, COUNT(*) as count
        FROM customers c
        $companyWhere $customOverride
        GROUP BY c.company_name
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute(array_merge($companyParams, $customParams));
    $companyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (isset($_GET['export']) && $_GET['export'] === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=daily_report_' . $from . '_to_' . $to . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['Queue Number', 'Customer Name', 'Service', 'Status', 'Date/Time', 'Wait Time (sec)', 'Service Time (sec)', 'Window']);
        
        // Data rows
        foreach ($customers as $c) {
            fputcsv($output, [
                $c['queue_number'],
                $c['name'],
                $c['service_name'],
                $c['follow_up_rejected_at'] ? 'Rejected' : ucfirst($c['status']),
                $c['created_at'],
                $c['wait_duration'] ?? 0,
                $c['service_duration'] ?? 0,
                $c['window_name'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        exit;
    }

    // Follow-up tracking stats per operator
    $fuWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ?";
    $fuParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $fuWhere .= " AND c.service_type IN ($placeholders)";
        $fuParams = array_merge($fuParams, $codes);
    } elseif ($serviceType) {
        $fuWhere .= " AND c.service_type = ?";
        $fuParams[] = $serviceType;
    }
    $fuWindowId = intval($authUser['window_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT 
            ? as operator_name,
            COUNT(DISTINCT CASE WHEN c.follow_up_resolved_at IS NOT NULL THEN c.id END) as resolved,
            COUNT(DISTINCT CASE WHEN c.follow_up_rejected_at IS NOT NULL THEN c.id END) as rejected,
            COUNT(DISTINCT CASE WHEN c.is_follow_up = 1 AND c.follow_up_resolved_at IS NULL AND c.follow_up_rejected_at IS NULL THEN c.id END) as pending
        FROM customers c
        $fuWhere
        AND (c.follow_up_marked_by = ? OR c.follow_up_completed_by = ?" . ($fuWindowId ? " OR c.forwarded_to_counter_id = $fuWindowId" : "") . ")
    ");
    $stmt->execute(array_merge($fuParams, [$authUser['display_name'], $authUser['id'], $authUser['id']]));
    $followUpStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Per-window breakdown
    $winWhere = "WHERE DATE(c.created_at) BETWEEN ? AND ? AND c.counter_id IS NOT NULL";
    $winParams = [$from, $to];
    if ($serviceTypesMulti) {
        $codes = array_map('trim', explode(',', $serviceTypesMulti));
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $winWhere .= " AND c.service_type IN ($placeholders)";
        $winParams = array_merge($winParams, $codes);
    } elseif ($serviceType) {
        $winWhere .= " AND c.service_type = ?";
        $winParams[] = $serviceType;
    }
    $stmt = $conn->prepare("
        SELECT 
            ct.id as counter_id,
            ct.display_name as window_name,
            ct.window_number,
            COUNT(CASE WHEN c.status = 'completed' THEN 1 END) as total_served,
            AVG(CASE WHEN c.status = 'completed' THEN c.wait_duration END) as avg_wait,
            AVG(CASE WHEN c.status = 'completed' THEN c.service_duration END) as avg_service,
            COUNT(CASE WHEN c.status = 'cancelled' THEN 1 END) as total_cancelled,
            COUNT(CASE WHEN c.status = 'skipped' THEN 1 END) as total_skipped,
            COUNT(CASE WHEN c.status = 'no-show' THEN 1 END) as total_noshow,
            COUNT(CASE WHEN c.status = 'serving' THEN 1 END) as currently_serving
        FROM customers c
        LEFT JOIN counters ct ON ct.id = c.counter_id
        $winWhere $customOverride
        GROUP BY c.counter_id
        ORDER BY ct.window_number
    ");
    $stmt->execute(array_merge($winParams, $customParams));
    $byWindow = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            'by_window' => $byWindow,
            'by_service' => $byService,
            'hourly' => $hourly,
            'purpose_breakdown' => $purposeBreakdown,
            'company_breakdown' => $companyBreakdown,
            'follow_up_stats' => $followUpStats,
            'date_range' => ['from' => $from, 'to' => $to]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>