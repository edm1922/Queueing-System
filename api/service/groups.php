<?php
header('Content-Type: application/json');
include '../../config.php';

$user = requireRole(['admin', 'supervisor', 'staff']);

try {
    $db = new Database();
    $conn = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $groups = $conn->query("
            SELECT sg.*, 
                   (SELECT COUNT(*) FROM service_types st WHERE st.group_id = sg.id) as service_count
            FROM service_groups sg 
            ORDER BY sg.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($groups as &$g) {
            $stmt = $conn->prepare("SELECT id, code, name, queue_prefix, description FROM service_types WHERE group_id = ? ORDER BY name ASC");
            $stmt->execute([$g['id']]);
            $g['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $ungrouped = $conn->query("
            SELECT id, code, name, queue_prefix, description FROM service_types WHERE (group_id IS NULL OR group_id = 0) AND is_active = 1 ORDER BY name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $allServices = $conn->query("SELECT id, code, name, queue_prefix, description FROM service_types WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => ['groups' => $groups, 'ungrouped' => $ungrouped, 'all_services' => $allServices]]);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $action = $data['action'] ?? '';

    // CREATE group
    if ($action === 'create') {
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        if (!$name) throw new Exception('Group name is required');
        $stmt = $conn->prepare("INSERT INTO service_groups (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        echo json_encode(['success' => true, 'message' => 'Group created', 'data' => ['id' => $conn->lastInsertId(), 'name' => $name]]);
        exit;
    }

    // UPDATE group (rename)
    if ($action === 'update') {
        $id = intval($data['id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        if (!$id || !$name) throw new Exception('Group ID and name are required');
        $stmt = $conn->prepare("UPDATE service_groups SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        echo json_encode(['success' => true, 'message' => 'Group updated']);
        exit;
    }

    // DELETE group
    if ($action === 'delete') {
        $id = intval($data['id'] ?? 0);
        if (!$id) throw new Exception('Group ID is required');
        $conn->beginTransaction();
        $conn->prepare("UPDATE service_types SET group_id = NULL WHERE group_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM service_groups WHERE id = ?")->execute([$id]);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Group deleted']);
        exit;
    }

    // ASSIGN service to group
    if ($action === 'assign') {
        $serviceId = intval($data['service_id'] ?? 0);
        $groupId = intval($data['group_id'] ?? 0);
        if (!$serviceId || !$groupId) throw new Exception('Service ID and Group ID are required');
        $stmt = $conn->prepare("UPDATE service_types SET group_id = ? WHERE id = ?");
        $stmt->execute([$groupId, $serviceId]);
        echo json_encode(['success' => true, 'message' => 'Service assigned to group']);
        exit;
    }

    // REMOVE service from group
    if ($action === 'remove') {
        $serviceId = intval($data['service_id'] ?? 0);
        if (!$serviceId) throw new Exception('Service ID is required');
        $stmt = $conn->prepare("UPDATE service_types SET group_id = NULL WHERE id = ?");
        $stmt->execute([$serviceId]);
        echo json_encode(['success' => true, 'message' => 'Service removed from group']);
        exit;
    }

    // CREATE service
    if ($action === 'create_service') {
        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $prefix = trim($data['prefix'] ?? '');
        if (!$name || !$code || !$prefix) throw new Exception('Name, code, and prefix are required');

        $stmt = $conn->prepare("SELECT id FROM service_types WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) throw new Exception('Service code already exists');

        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO service_types (name, code, description, queue_prefix, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$name, $code, '', $prefix]);
        $stmt = $conn->prepare("INSERT INTO queue_sequences (prefix, current_value, queue_date) VALUES (?, 0, CURDATE()) ON DUPLICATE KEY UPDATE prefix = prefix");
        $stmt->execute([$prefix]);
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Service created', 'data' => ['id' => $conn->lastInsertId()]]);
        exit;
    }

    // EDIT service (name, code, prefix, description)
    if ($action === 'edit_service') {
        $serviceId = intval($data['service_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $newCode = trim($data['code'] ?? '');
        $prefix = trim($data['prefix'] ?? '');
        $description = trim($data['description'] ?? '');
        if (!$serviceId) throw new Exception('Service ID is required');
        if (!$name) throw new Exception('Service name is required');
        if (!$newCode) throw new Exception('Service code is required');
        if (!$prefix) throw new Exception('Queue prefix is required');

        // Get current code
        $stmt = $conn->prepare("SELECT code FROM service_types WHERE id = ?");
        $stmt->execute([$serviceId]);
        $oldCode = $stmt->fetchColumn();
        if (!$oldCode) throw new Exception('Service not found');
        if ($oldCode === 'custom') throw new Exception('Cannot edit the Custom service');

        // Check uniqueness if code changed
        if ($newCode !== $oldCode) {
            $stmt = $conn->prepare("SELECT id FROM service_types WHERE code = ? AND id != ?");
            $stmt->execute([$newCode, $serviceId]);
            if ($stmt->fetch()) throw new Exception('Service code already exists');
        }

        $conn->beginTransaction();
        $stmt = $conn->prepare("UPDATE service_types SET name = ?, code = ?, queue_prefix = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $newCode, $prefix, $description, $serviceId]);

        if ($newCode !== $oldCode) {
            // Cascade to counter_service_assignments
            $stmt = $conn->prepare("UPDATE counter_service_assignments SET service_type = ? WHERE service_type = ?");
            $stmt->execute([$newCode, $oldCode]);
            // Cascade to customers (waiting/serving only — keep old code on completed for historical integrity)
            $stmt = $conn->prepare("UPDATE customers SET service_type = ? WHERE service_type = ? AND status IN ('waiting', 'serving')");
            $stmt->execute([$newCode, $oldCode]);
        }

        $stmt = $conn->prepare("INSERT INTO queue_sequences (prefix, current_value, queue_date) VALUES (?, 0, CURDATE()) ON DUPLICATE KEY UPDATE prefix = prefix");
        $stmt->execute([$prefix]);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Service updated']);
        exit;
    }

    // DELETE service (soft delete: set inactive)
    if ($action === 'delete_service') {
        $serviceId = intval($data['service_id'] ?? 0);
        if (!$serviceId) throw new Exception('Service ID is required');
        $stmt = $conn->prepare("SELECT code FROM service_types WHERE id = ?");
        $stmt->execute([$serviceId]);
        $svcCode = $stmt->fetchColumn();
        if (!$svcCode) throw new Exception('Service not found');
        if ($svcCode === 'custom') throw new Exception('Cannot delete the Custom service');
        $conn->beginTransaction();
        $conn->prepare("UPDATE counter_service_assignments SET is_active = 0 WHERE service_type = (SELECT code FROM service_types WHERE id = ?)")->execute([$serviceId]);
        $conn->prepare("UPDATE service_types SET is_active = 0, group_id = NULL WHERE id = ?")->execute([$serviceId]);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Service deleted']);
        exit;
    }

    throw new Exception('Unknown action: ' . $action);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
