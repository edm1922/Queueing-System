<?php
header('Content-Type: application/json');
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireRole(['admin', 'supervisor', 'staff']);

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $counterId = $data['counter_id'] ?? null;
    $services = $data['services'] ?? [];
    
    if ($counterId === null || !is_array($services)) {
        throw new Exception('counter_id and services array are required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    // Get counter
    $stmt = $conn->prepare("SELECT * FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$counter) {
        throw new Exception('Counter not found');
    }
    
    $isOnline = $counter['is_online'];
    
    // Check for cross-counter conflicts: services already assigned (primary) to another counter
    $checkServices = array_filter($services, function($s) { return !in_array($s, ['other', 'custom']); });
    if (!empty($checkServices)) {
        $placeholders = implode(',', array_fill(0, count($checkServices), '?'));
        $stmt = $conn->prepare("
            SELECT csa.service_type, ct.display_name as window_name
            FROM counter_service_assignments csa
            JOIN counters ct ON ct.id = csa.counter_id
            WHERE csa.service_type IN ($placeholders)
            AND csa.counter_id != ?
            AND csa.is_primary = 1
        ");
        $params = array_merge(array_values($checkServices), [$counterId]);
        $stmt->execute($params);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($conflicts)) {
            $msgs = array_map(function($c) { return $c['service_type'] . ' (' . $c['window_name'] . ')'; }, $conflicts);
            throw new Exception('Cannot assign: ' . implode(', ', $msgs) . ' already assigned to another window');
        }
    }
    
    // Delete all existing assignments for this counter (primary + redistributed)
    $stmt = $conn->prepare("DELETE FROM counter_service_assignments WHERE counter_id = ?");
    $stmt->execute([$counterId]);
    
    // Insert new assignments
    $displayOrder = 1;
    $stmt = $conn->prepare("
        INSERT INTO counter_service_assignments (counter_id, service_type, is_primary, is_active, display_order)
        VALUES (?, ?, 1, ?, ?)
    ");
    
    foreach ($services as $service) {
        $stmt->execute([$counterId, $service, $isOnline ? 1 : 0, $displayOrder++]);
    }
    
    // service_types column has been removed in v2, only counter_service_assignments is used
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Services updated successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
