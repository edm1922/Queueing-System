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
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $customerId = $data['customer_id'] ?? null;
    
    if (!$customerId) {
        throw new Exception('customer_id is required');
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    // We can only recall customers who are currently being served
    if ($customer['status'] !== 'serving') {
        throw new Exception('Customer is not in serving status');
    }
    
    $now = date('Y-m-d H:i:s');
    
    // Update called_at to trigger a re-announcement on the display
    $stmt = $conn->prepare("UPDATE customers SET called_at = ? WHERE id = ?");
    $stmt->execute([$now, $customerId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Customer recalled successfully',
        'data' => [
            'id' => $customerId,
            'called_at' => $now
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
