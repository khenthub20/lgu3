<?php
// db_connect_safe.php - Safe database connection for public pages
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lgu3_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Don't die on error - let the calling page handle it
if ($conn->connect_error) {
    $conn = null; // Set to null so pages can check if connection failed
}
?>
