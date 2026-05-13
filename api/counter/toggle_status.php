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
    $isOnline = $data['is_online'] ?? null;
    $notes = $data['notes'] ?? '';
    
    if ($counterId === null || $isOnline === null) {
        throw new Exception('counter_id and is_online are required');
    }
    
    if (!is_numeric($counterId) || !is_bool($isOnline)) {
        throw new Exception('Invalid parameter types');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT * FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $counter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$counter) {
        throw new Exception('Counter not found');
    }
    
    $previousStatus = $counter['is_online'];
    $newStatus = $isOnline ? 1 : 0;
    
    if ($previousStatus == $newStatus) {
        $conn->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Counter status unchanged',
            'data' => [
                'counter_id' => $counterId,
                'is_online' => $isOnline,
                'status_changed' => false
            ]
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE counters SET is_online = ?, last_status_change = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $counterId]);
    
    $eventType = $isOnline ? 'counter_online' : 'counter_offline';
    
    $stmt = $conn->prepare("INSERT INTO redistribution_logs (event_type, counter_id, notes) VALUES (?, ?, ?)");
    $stmt->execute([$eventType, $counterId, $notes]);
    $logId = $conn->lastInsertId();
    
    $affectedServices = [];
    $reassignedCustomers = 0;
    $fallbackCounterId = null;
    
    if (!$isOnline) {
        $stmt = $conn->prepare("
            SELECT service_type FROM counter_service_assignments 
            WHERE counter_id = ? AND is_active = 1
        ");
        $stmt->execute([$counterId]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($services as $service) {
            $affectedServices[] = $service['service_type'];
        }
        
        $stmt = $conn->prepare("
            SELECT id FROM counters WHERE id != ? AND is_online = 1 LIMIT 1
        ");
        $stmt->execute([$counterId]);
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fallback) {
            $fallbackCounterId = $fallback['id'];
            
            foreach ($affectedServices as $serviceType) {
                $stmt = $conn->prepare("
                    SELECT id FROM counter_service_assignments 
                    WHERE counter_id = ? AND service_type = ?
                ");
                $stmt->execute([$fallbackCounterId, $serviceType]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists) {
                    $stmt = $conn->prepare("
                        UPDATE counter_service_assignments 
                        SET is_active = 1, is_primary = 0 
                        WHERE counter_id = ? AND service_type = ?
                    ");
                    $stmt->execute([$fallbackCounterId, $serviceType]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO counter_service_assignments 
                        (counter_id, service_type, is_primary, is_active, display_order)
                        VALUES (?, ?, 0, 1, 99)
                    ");
                    $stmt->execute([$fallbackCounterId, $serviceType]);
                }
            }
            
            $placeholders = implode(',', array_fill(0, count($affectedServices), '?'));
            $stmt = $conn->prepare("
                UPDATE customers 
                SET counter_id = ?, is_redistributed = 1 
                WHERE service_type IN ($placeholders) 
                AND status = 'waiting' 
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute(array_merge([$fallbackCounterId], $affectedServices));
            $reassignedCustomers = $stmt->rowCount();
        }
        
        $stmt = $conn->prepare("
            UPDATE counter_service_assignments 
            SET is_active = 0 
            WHERE counter_id = ? AND is_primary = 1
        ");
        $stmt->execute([$counterId]);
        
        $announcementMsg = "Due to high volume at one window, all services are being handled. Please proceed to the available window.";
        if ($fallback) {
            $stmt = $conn->prepare("
                INSERT INTO display_announcements (title, message, type, priority, is_active)
                VALUES ('Service Update', ?, 'warning', 10, 1)
            ");
            $stmt->execute([$announcementMsg]);
        }
    } else {
        $stmt = $conn->prepare("
            UPDATE counter_service_assignments 
            SET is_active = 1 
            WHERE counter_id = ? AND is_primary = 1
        ");
        $stmt->execute([$counterId]);
        
        $stmt = $conn->prepare("
            UPDATE counter_service_assignments 
            SET is_active = 0 
            WHERE counter_id = ? AND is_primary = 0
        ");
        $stmt->execute([$counterId]);
    }
    
    if ($reassignedCustomers > 0 || $fallbackCounterId) {
        $stmt = $conn->prepare("
            UPDATE redistribution_logs 
            SET affected_services = ?, 
                reassigned_customers = ?, 
                reassigned_to_counter_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            json_encode($affectedServices),
            $reassignedCustomers,
            $fallbackCounterId,
            $logId
        ]);
    }
    
    $conn->commit();
    
    $stmt = $conn->prepare("
        SELECT c.*, 
            (SELECT GROUP_CONCAT(service_type) FROM counter_service_assignments WHERE counter_id = c.id AND is_active = 1) as active_services
        FROM counters c WHERE c.id = ?
    ");
    $stmt->execute([$counterId]);
    $updatedCounter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => $isOnline ? 'Counter is now online' : 'Counter is now offline - services redistributed',
        'data' => [
            'counter_id' => $counterId,
            'is_online' => $isOnline,
            'status_changed' => true,
            'affected_services' => $affectedServices,
            'reassigned_customers' => $reassignedCustomers,
            'fallback_counter_id' => $fallbackCounterId,
            'counter' => $updatedCounter
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