<?php
// db_connect.php
$servername = "localhost";
$username = "live_lgu3_tl"; // Default XAMPP/WAMP user
$password = "adminhost123";     // Default XAMPP/WAMP pass
$dbname = "live_lgu3_tl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
