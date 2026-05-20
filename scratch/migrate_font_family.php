<?php
include 'config.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE display_announcements ADD COLUMN font_family VARCHAR(50) DEFAULT 'Inter' AFTER type;");
    echo "Successfully added font_family column to display_announcements table.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
