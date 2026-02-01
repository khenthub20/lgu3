<?php
// fix_schema_live.php
// URL: http://your-domain.com/fix_schema_live.php

header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

echo "--- STARTING SCHEMA FIX ---\n";

// 1. Fix Announcements Table
echo "Checking 'announcements' table...\n";

// Ensure table exists
$sql = "CREATE TABLE IF NOT EXISTS announcements (
    id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";
if ($conn->query($sql) === TRUE) {
    echo " - Table 'announcements' existence check passed.\n";
} else {
    echo " - Error creating table: " . $conn->error . "\n";
}

// Fix Missing AUTO_INCREMENT on ID
echo "Attempting to fix AUTO_INCREMENT on 'announcements'...\n";
$sql = "ALTER TABLE announcements MODIFY id INT(11) NOT NULL AUTO_INCREMENT";
if ($conn->query($sql) === TRUE) {
    echo " - Success: 'id' column is now AUTO_INCREMENT.\n";
} else {
    echo " - Note: " . $conn->error . " (Ignore if already auto_increment)\n";
}

echo "\n--- DONE. TRY POSTING NOW. ---";
?>
