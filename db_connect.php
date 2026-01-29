<?php
// db_connect.php
$servername = "localhost";
$username = "root"; // Default XAMPP/WAMP user
$password = "";     // Default XAMPP/WAMP pass
$dbname = "lgu3_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
