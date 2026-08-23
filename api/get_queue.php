<?php
header('Content-Type: application/json');
include '../config.php';

$user = requireRole(['admin', 'supervisor', 'staff']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    $counterId = isset($_GET['counter_id']) ? intval($_GET['counter_id']) : 0;
    
    $followUpFilter = '';
    if ($user['role'] === 'staff') {
        $windowId = intval($user['window_id'] ?? 0);
        $followUpFilter = 'AND (c.is_follow_up = 0 OR c.follow_up_marked_by = ' . intval($user['id']) . ')';
        if ($windowId) {
            $followUpFilter = 'AND (c.is_follow_up = 0 OR c.follow_up_marked_by = ' . intval($user['id']) . ' OR c.forwarded_to_counter_id = ' . $windowId . ')';
        }
    }
    
    if ($counterId) {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   ct.display_name as counter_name,
                   st.queue_prefix,
                   st.name as service_name,
                   fu.display_name as forwarded_by_name,
                   fwd.display_name as forwarded_to_name,
                   fwd.description as forwarded_to_description
            FROM customers c
            LEFT JOIN counters ct ON ct.id = c.counter_id
            LEFT JOIN service_types st ON st.code = c.service_type
            LEFT JOIN users fu ON fu.id = c.forwarded_by_user_id
            LEFT JOIN counters fwd ON fwd.id = c.forwarded_to_counter_id
            WHERE DATE(c.created_at) = CURDATE()
            AND (
                (c.service_type IN (
                        SELECT service_type FROM counter_service_assignments WHERE counter_id = ? AND is_active = 1
                    ) OR (c.service_type = 'custom' AND (SELECT custom_enabled FROM counters WHERE id = ?) = 1) OR (c.status = 'serving' AND c.counter_id = ?))
                OR (c.is_follow_up = 1 AND c.forwarded_to_counter_id = ?)
            )
            AND (c.counter_id IS NULL OR c.counter_id = ? OR c.status != 'serving')
            $followUpFilter
            ORDER BY 
                FIELD(c.status, 'serving', 'waiting', 'completed', 'cancelled', 'skipped', 'no-show'),
                c.created_at ASC
        ");
        $stmt->execute([$counterId, $counterId, $counterId, $counterId, $counterId]);
    } else {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   ct.display_name as counter_name,
                   st.queue_prefix,
                   st.name as service_name,
                   fu.display_name as forwarded_by_name,
                   fwd.display_name as forwarded_to_name,
                   fwd.description as forwarded_to_description
            FROM customers c
            LEFT JOIN counters ct ON ct.id = c.counter_id
            LEFT JOIN service_types st ON st.code = c.service_type
            LEFT JOIN users fu ON fu.id = c.forwarded_by_user_id
            LEFT JOIN counters fwd ON fwd.id = c.forwarded_to_counter_id
            WHERE DATE(c.created_at) = CURDATE()
            $followUpFilter
            ORDER BY 
                FIELD(c.status, 'serving', 'waiting', 'completed', 'cancelled', 'skipped', 'no-show'),
                c.created_at ASC
        ");
        $stmt->execute();
    }
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("
        SELECT c.*,
               cust.queue_number as current_queue_number,
               cust.name as current_customer_name,
               cust.custom_description as serving_custom_description,
               (SELECT GROUP_CONCAT(DISTINCT csa.service_type) 
                FROM counter_service_assignments csa 
                WHERE csa.counter_id = c.id AND csa.is_active = 1) as active_services
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id AND DATE(cust.created_at) = CURDATE()
        ORDER BY c.window_number ASC
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT * FROM service_types WHERE is_active = 1 ORDER BY name ASC");
    $serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->query("SELECT COUNT(*) as offline_count FROM counters WHERE is_online = 0");
    $offlineCount = $stmt->fetch(PDO::FETCH_ASSOC)['offline_count'];
    
    $stmt = $conn->query("SELECT force_refresh_token FROM display_settings LIMIT 1");
    $refreshToken = intval($stmt->fetch(PDO::FETCH_ASSOC)['force_refresh_token'] ?? 0);

    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'counters' => $counters,
        'service_types' => $serviceTypes,
        'force_refresh_token' => $refreshToken,
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