<?php
include 'config.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT * FROM counters");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
