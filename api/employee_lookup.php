<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'search';
$q = trim($_GET['q'] ?? '');
$rfid = trim($_GET['rfid'] ?? '');

if ($action === 'search' && strlen($q) >= 1) {
    $url = 'http://192.168.1.200/prlsyst_api.php?action=search&q=' . urlencode($q);
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'External employee server unreachable']);
        exit;
    }
    echo $result;
    exit;
}

if ($action === 'rfid' && $rfid !== '') {
    $url = 'http://192.168.1.200/prlsyst_api.php?action=rfid&rfid=' . urlencode($rfid);
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents($url, false, $ctx);
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'External employee server unreachable']);
        exit;
    }
    echo $result;
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
