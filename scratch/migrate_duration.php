<?php
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE display_announcements ADD COLUMN display_duration INT DEFAULT 10 AFTER expires_at;");
    echo "Successfully added display_duration column to display_announcements table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
