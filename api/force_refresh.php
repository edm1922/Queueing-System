<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user = requireRole(['admin']);

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("UPDATE display_settings SET force_refresh_token = force_refresh_token + 1");
    $stmt->execute();

    $stmt = $conn->query("SELECT force_refresh_token FROM display_settings LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'force_refresh_token' => intval($row['force_refresh_token'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
