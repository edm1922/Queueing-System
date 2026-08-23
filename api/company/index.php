<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->query("SELECT id, name FROM known_companies ORDER BY name ASC");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $companies]);
        exit;
    }

    requireRole(['admin']);
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $method = $input['_method'] ?? 'POST';

    if ($method === 'DELETE') {
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid company ID');
        $stmt = $conn->prepare("DELETE FROM known_companies WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Company deleted']);
        exit;
    }

    $name = trim($input['name'] ?? '');
    if ($name === '') throw new Exception('Company name is required');
    if (strlen($name) > 255) throw new Exception('Company name too long');

    $stmt = $conn->prepare("INSERT INTO known_companies (name) VALUES (?)");
    $stmt->execute([$name]);
    echo json_encode(['success' => true, 'message' => 'Company added', 'id' => $conn->lastInsertId()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
