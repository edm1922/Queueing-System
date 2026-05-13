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
    
    $counterId = $data['counter_id'] ?? null;
    $services = $data['services'] ?? [];
    
    if ($counterId === null || !is_array($services)) {
        throw new Exception('counter_id and services array are required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    // Get counter status
    $stmt = $conn->prepare("SELECT is_online FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$counter) {
        throw new Exception('Counter not found');
    }
    
    $isOnline = $counter['is_online'];
    
    // Delete existing primary assignments
    $stmt = $conn->prepare("DELETE FROM counter_service_assignments WHERE counter_id = ? AND is_primary = 1");
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
    
    // Update service_types in counters table
    $servicesJson = json_encode($services);
    $stmt = $conn->prepare("UPDATE counters SET service_types = ? WHERE id = ?");
    $stmt->execute([$servicesJson, $counterId]);
    
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
