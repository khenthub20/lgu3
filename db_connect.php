<?php
// db_connect.php
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

// Check connection
if ($conn->connect_error) {
    if (defined('API_MODE')) {
        // Return null/error but don't die text output
        // The API script will handle the error response
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

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
