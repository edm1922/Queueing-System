<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new Exception('Invalid JSON input');

    $customerId = $data['customer_id'] ?? null;
    $counterId = $data['counter_id'] ?? null;

    if (!$customerId) throw new Exception('customer_id is required');

    $db = new Database();
    $conn = $db->getConnection();

    // Find the customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) throw new Exception('Customer not found');

    // If no counter specified, auto-assign based on service or any online counter
    if (!$counterId) {
        $stmt = $conn->prepare("
            SELECT c.id FROM counters c
            JOIN counter_service_assignments csa ON csa.counter_id = c.id
            WHERE csa.service_type = ? AND csa.is_active = 1 AND c.is_online = 1
            LIMIT 1
        ");
        $stmt->execute([$customer['service_type']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $counterId = $result ? $result['id'] : null;
    }
    if (!$counterId) {
        $stmt = $conn->query("SELECT id FROM counters WHERE is_online = 1 LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $counterId = $result ? $result['id'] : null;
    }
    if (!$counterId) throw new Exception('No counter is currently online');

    // Check counter is not busy
    $stmt = $conn->prepare("SELECT current_customer_id FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    $busyId = $stmt->fetchColumn();
    if ($busyId && $busyId != $customerId) {
        $stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
        $stmt->execute([$busyId]);
        if ($stmt->fetchColumn() === 'serving') {
            throw new Exception('Window is currently serving another customer. Complete their ticket first.');
        }
    }

    $conn->beginTransaction();

    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE customers
        SET status = 'serving', called_at = ?, served_at = ?, counter_id = ?, is_follow_up = 0
        WHERE id = ?
    ");
    $stmt->execute([$now, $now, $counterId, $customerId]);

    $stmt = $conn->prepare("
        UPDATE customers
        SET wait_duration = TIMESTAMPDIFF(SECOND, created_at, ?)
        WHERE id = ?
    ");
    $stmt->execute([$now, $customerId]);

    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $counterId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Follow-up ticket served',
        'data' => ['counter_id' => $counterId, 'customer_id' => $customerId]
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
