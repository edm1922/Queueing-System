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
    $serviceType = $data['service_type'] ?? null;
    $action = $data['action'] ?? 'assign';
    
    if (!$counterId || !$serviceType) {
        throw new Exception('counter_id and service_type are required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    switch ($action) {
        case 'assign':
            $stmt = $conn->prepare("SELECT id FROM counter_service_assignments WHERE counter_id = ? AND service_type = ?");
            $stmt->execute([$counterId, $serviceType]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $stmt = $conn->prepare("UPDATE counter_service_assignments SET is_active = 1 WHERE id = ?");
                $stmt->execute([$existing['id']]);
            } else {
                $stmt = $conn->prepare("SELECT MAX(display_order) as max_order FROM counter_service_assignments WHERE counter_id = ?");
                $stmt->execute([$counterId]);
                $maxOrder = $stmt->fetch(PDO::FETCH_ASSOC)['max_order'] ?? 0;
                
                $stmt = $conn->prepare("
                    INSERT INTO counter_service_assignments (counter_id, service_type, is_primary, is_active, display_order)
                    VALUES (?, ?, 0, 1, ?)
                ");
                $stmt->execute([$counterId, $serviceType, $maxOrder + 1]);
            }
            $message = 'Service assigned to counter';
            break;
            
        case 'remove':
            $stmt = $conn->prepare("UPDATE counter_service_assignments SET is_active = 0 WHERE counter_id = ? AND service_type = ?");
            $stmt->execute([$counterId, $serviceType]);
            $message = 'Service removed from counter';
            break;
            
        case 'toggle':
            $stmt = $conn->prepare("SELECT is_active FROM counter_service_assignments WHERE counter_id = ? AND service_type = ?");
            $stmt->execute([$counterId, $serviceType]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($assignment) {
                $newStatus = $assignment['is_active'] ? 0 : 1;
                $stmt = $conn->prepare("UPDATE counter_service_assignments SET is_active = ? WHERE counter_id = ? AND service_type = ?");
                $stmt->execute([$newStatus, $counterId, $serviceType]);
                $message = $newStatus ? 'Service activated' : 'Service deactivated';
            } else {
                throw new Exception('Assignment not found');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO redistribution_logs (event_type, counter_id, affected_services, notes)
        VALUES ('manual_override', ?, ?, ?)
    ");
    $stmt->execute([$counterId, json_encode([$serviceType]), "Manual $action: $serviceType"]);
    
    $conn->commit();
    
    $stmt = $conn->prepare("
        SELECT csa.*, st.name as service_name, st.queue_prefix
        FROM counter_service_assignments csa
        JOIN service_types st ON st.code = csa.service_type
        WHERE csa.counter_id = ?
        ORDER BY csa.display_order ASC
    ");
    $stmt->execute([$counterId]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'counter_id' => $counterId,
            'service_type' => $serviceType,
            'action' => $action,
            'assignments' => $assignments
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>