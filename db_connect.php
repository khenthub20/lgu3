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

// --- SCHEMA AUTO-HEAL: Ensure all columns exist ---
$required_columns = [
    'first_name' => "VARCHAR(100) AFTER full_name",
    'middle_name' => "VARCHAR(100) AFTER first_name",
    'last_name' => "VARCHAR(100) AFTER middle_name",
    'suffix' => "VARCHAR(20) AFTER last_name",
    'mobile_number' => "VARCHAR(20) AFTER email",
    'street' => "VARCHAR(255) AFTER mobile_number",
    'house_number' => "VARCHAR(50) AFTER street",
    'valid_id_path' => "VARCHAR(255) AFTER house_number",
    'is_active' => "INT(1) DEFAULT 1",
    'is_first_login' => "INT(1) DEFAULT 1",
    'reference_id' => "VARCHAR(20) UNIQUE",
    'barangay' => "VARCHAR(255) DEFAULT 'Baranggay Laforteza Holdings 264'"
];

foreach ($required_columns as $col => $definition) {
    if (!$conn->query("SHOW COLUMNS FROM users LIKE '$col'")->num_rows) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $definition");
    }
}

// Standardize Reference IDs for existing users
$res = $conn->query("SELECT id, reference_id FROM users");
if ($res) {
    while($row = $res->fetch_assoc()) {
        if (empty($row['reference_id']) || !preg_match('/^REF-\d{8}$/', $row['reference_id'])) {
            $is_unique = false;
            $tries = 0;
            while(!$is_unique && $tries < 10) {
                $ref = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                if($conn->query("SELECT id FROM users WHERE reference_id = '$ref'")->num_rows == 0) $is_unique = true;
                $tries++;
            }
            $uid_row = $row['id'];
            $conn->query("UPDATE users SET reference_id = '$ref' WHERE id = $uid_row");
        }
    }
}
?>
