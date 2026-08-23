<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("
        SELECT 
            c.id as counter_id,
            c.name,
            c.display_name,
            c.status_text,
            c.is_online,
            c.custom_enabled
        FROM counters c
        ORDER BY c.window_number ASC
    ");
    $counters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($counters as &$counter) {
        $stmt = $conn->prepare("
            SELECT csa.service_type, csa.is_active, csa.is_primary,
                   st.name as service_name, st.queue_prefix
            FROM counter_service_assignments csa
            JOIN service_types st ON st.code = csa.service_type
            WHERE csa.counter_id = ?
        ");
        $stmt->execute([$counter['counter_id']]);
        $counter['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $conn->query("SELECT force_refresh_token FROM display_settings LIMIT 1");
    $refreshToken = intval($stmt->fetch(PDO::FETCH_ASSOC)['force_refresh_token'] ?? 0);

    echo json_encode([
        'success' => true,
        'force_refresh_token' => $refreshToken,
        'data' => [
            'counters' => $counters
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
