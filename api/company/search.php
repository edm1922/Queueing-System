<?php
header('Content-Type: application/json');
include '../../config.php';

try {
    $q = trim($_GET['q'] ?? '');
    $db = new Database();
    $conn = $db->getConnection();

    if (strlen($q) > 0) {
        $stmt = $conn->prepare("SELECT id, name FROM known_companies WHERE name LIKE ? ORDER BY name ASC LIMIT 20");
        $stmt->execute(['%' . $q . '%']);
    } else {
        $stmt = $conn->query("SELECT id, name FROM known_companies ORDER BY name ASC LIMIT 20");
    }
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
