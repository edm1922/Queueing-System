<?php
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check basic stats
    $stmt = $conn->query("
        SELECT status, COUNT(*) as count 
        FROM customers 
        WHERE DATE(created_at) = CURDATE() 
        GROUP BY status
    ");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($results);

    // Check if there are ANY customers at all
    $stmt = $conn->query("SELECT COUNT(*) FROM customers");
    echo "Total customers in DB: " . $stmt->fetchColumn() . "\n";
    
    // Check current date according to PHP and MySQL
    echo "PHP Date: " . date('Y-m-d') . "\n";
    $stmt = $conn->query("SELECT CURDATE()");
    echo "MySQL CURDATE(): " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
