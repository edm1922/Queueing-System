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
    
    if ($counterId === null || !is_numeric($counterId)) {
        throw new Exception('Valid counter_id is required');
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
    
    // Release any customers assigned to this counter
    $stmt = $conn->prepare("
        UPDATE customers 
        SET counter_id = NULL, status = 'waiting' 
        WHERE counter_id = ? AND status = 'serving'
    ");
    $stmt->execute([$counterId]);
    
    $stmt = $conn->prepare("
        UPDATE customers 
        SET counter_id = NULL 
        WHERE counter_id = ? AND status = 'waiting'
    ");
    $stmt->execute([$counterId]);
    
    // Delete service assignments
    $stmt = $conn->prepare("DELETE FROM counter_service_assignments WHERE counter_id = ?");
    $stmt->execute([$counterId]);
    
    // Delete redistribution logs
    $stmt = $conn->prepare("DELETE FROM redistribution_logs WHERE counter_id = ? OR reassigned_to_counter_id = ?");
    $stmt->execute([$counterId, $counterId]);
    
    // Delete the counter
    $stmt = $conn->prepare("DELETE FROM counters WHERE id = ?");
    $stmt->execute([$counterId]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Window deleted successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
