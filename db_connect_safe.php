<?php
// db_connect_safe.php - Safe database connection for public pages
$servername = "localhost";

// Check if running on localhost
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    // Local Credentials
    $username = "root";
    $password = "";
    $dbname = "lgu3_db";
} else {
    // Live/Production Credentials
    $username = "live_lgu3_tl";
    $password = "adminhost123";
    $dbname = "live_lgu3_tl";
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Don't die on error - let the calling page handle it
if ($conn->connect_error) {
    $conn = null; // Set to null so pages can check if connection failed
}
?>
