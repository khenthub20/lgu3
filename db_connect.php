<?php
// db_connect.php
// For most shared hosting (cPanel, Hostinger, etc.), use 'localhost'
// For some hosts, you might need to use the actual database server hostname
$servername = "localhost"; // Try "localhost" first, if it fails, use your hosting provider's DB host
$username = "live_lgu3_tl";
$password = "adminhost123";
$dbname = "live_lgu3_tl";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection with detailed error
if ($conn->connect_error) {
    // For debugging - remove this in production for security
    error_log("Database Connection Error: " . $conn->connect_error);
    die("Connection failed. Please check your database credentials and ensure the database exists. Error: " . $conn->connect_error);
}

// Set charset to UTF-8 for proper character handling
$conn->set_charset("utf8mb4");

// --- SCHEMA AUTO-HEAL: Reference ID ---
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'reference_id'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN reference_id VARCHAR(20) UNIQUE AFTER id");
}

// Ensure all users have the standardized 8-digit numeric ID format (e.g., REF-12345678)
$res = $conn->query("SELECT id, reference_id FROM users");
if ($res) {
    while($row = $res->fetch_assoc()) {
        // If it's empty OR NOT exactly 8 digits after 'REF-' (standardizes old alphanumeric ones)
        if (empty($row['reference_id']) || !preg_match('/^REF-\d{8}$/', $row['reference_id'])) {
            $is_unique = false;
            $max_tries = 10;
            $tries = 0;
            
            while(!$is_unique && $tries < $max_tries) {
                $ref = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                $dupCheck = $conn->query("SELECT id FROM users WHERE reference_id = '$ref'");
                if($dupCheck->num_rows == 0) $is_unique = true;
                $tries++;
            }
            
            $uid_row = $row['id'];
            $conn->query("UPDATE users SET reference_id = '$ref' WHERE id = $uid_row");
        }
    }
}
