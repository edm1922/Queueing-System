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
    
    $serviceTypes = $data['service_types'] ?? [];
    $targetCounterId = $data['target_counter_id'] ?? null;
    $action = $data['action'] ?? 'reassign';
    
    if (empty($serviceTypes) || !$targetCounterId) {
        throw new Exception('service_types and target_counter_id are required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT * FROM counters WHERE id = ?");
    $stmt->execute([$targetCounterId]);
    $targetCounter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetCounter) {
        throw new Exception('Target counter not found');
    }
    
    if (!$targetCounter['is_online']) {
        throw new Exception('Target counter is offline. Please bring it online first.');
    }
    
    $reassignedCount = 0;
    
    if ($action === 'reassign') {
        $placeholders = implode(',', array_fill(0, count($serviceTypes), '?'));
        $params = array_merge([$targetCounterId], $serviceTypes);
        
        $stmt = $conn->prepare("
            UPDATE customers 
            SET counter_id = ?, is_redistributed = 1 
            WHERE service_type IN ($placeholders) 
            AND status = 'waiting' 
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute($params);
        $reassignedCount = $stmt->rowCount();
        
        foreach ($serviceTypes as $serviceType) {
            $stmt = $conn->prepare("SELECT id FROM counter_service_assignments WHERE counter_id = ? AND service_type = ?");
            $stmt->execute([$targetCounterId, $serviceType]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                $stmt = $conn->prepare("UPDATE counter_service_assignments SET is_active = 1, is_primary = 0 WHERE id = ?");
                $stmt->execute([$exists['id']]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO counter_service_assignments (counter_id, service_type, is_primary, is_active, display_order)
                    VALUES (?, ?, 0, 1, 99)
                ");
                $stmt->execute([$targetCounterId, $serviceType]);
            }
        }
        
        $message = "Reassigned $reassignedCount customers to counter {$targetCounter['display_name']}";
        
    } else {
        foreach ($serviceTypes as $serviceType) {
            $stmt = $conn->prepare("
                SELECT counter_id FROM counter_service_assignments 
                WHERE service_type = ? AND is_primary = 1
            ");
            $stmt->execute([$serviceType]);
            $primaryAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($primaryAssignment) {
                $originalCounterId = $primaryAssignment['counter_id'];
                
                $stmt = $conn->prepare("SELECT is_online FROM counters WHERE id = ?");
                $stmt->execute([$originalCounterId]);
                $isOnline = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($isOnline && $isOnline['is_online']) {
                    $stmt = $conn->prepare("
                        UPDATE customers 
                        SET counter_id = ?, is_redistributed = 0 
                        WHERE service_type = ? AND status = 'waiting' AND is_redistributed = 1
                        AND DATE(created_at) = CURDATE()
                    ");
                    $stmt->execute([$originalCounterId, $serviceType]);
                    $reassignedCount += $stmt->rowCount();
                }
            }
        }
        
        $message = "Restored $reassignedCount customers to original counters";
    }
    
    $stmt = $conn->prepare("
        INSERT INTO redistribution_logs (event_type, counter_id, affected_services, reassigned_customers, reassigned_to_counter_id, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'manual_override',
        $targetCounterId,
        json_encode($serviceTypes),
        $reassignedCount,
        $targetCounterId,
        $message
    ]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => [
            'reassigned_customers' => $reassignedCount,
            'target_counter_id' => $targetCounterId,
            'service_types' => $serviceTypes,
            'action' => $action
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