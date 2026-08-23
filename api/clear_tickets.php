<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireRole(['admin']);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $conn->beginTransaction();

    $conn->exec("UPDATE counters SET current_customer_id = NULL");
    $conn->exec("DELETE FROM customers");
    $conn->exec("UPDATE queue_sequences SET current_value = 0, queue_date = CURDATE()");

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'All tickets have been cleared successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>