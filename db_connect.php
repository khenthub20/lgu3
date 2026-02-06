<?php
// db_connect.php
$servername = "localhost";

// Check if running on localhost
if (php_sapi_name() === 'cli' || $_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
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

// --- SCHEMA AUTO-HEAL: Ensure all columns and tables exist ---
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

// 1. Learning Docs Table
$conn->query("CREATE TABLE IF NOT EXISTS learning_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Fix uploaded_at vs created_at for learning_docs
$resL = $conn->query("SHOW COLUMNS FROM learning_docs LIKE 'uploaded_at'");
if($resL && $resL->num_rows > 0) {
    $conn->query("ALTER TABLE learning_docs CHANGE uploaded_at created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

// 2. Program Applications Table
$conn->query("CREATE TABLE IF NOT EXISTS program_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    program_id INT,
    status VARCHAR(50) DEFAULT 'pending',
    material_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 3. Programs Table
$conn->query("CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    category VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 4. Maintenance Schedules Table
$conn->query("CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maint_id VARCHAR(50) UNIQUE,
    facility VARCHAR(200),
    maint_type VARCHAR(100),
    scheduled_date DATETIME,
    duration VARCHAR(50),
    priority VARCHAR(50),
    status VARCHAR(50),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 5. Ensure Upload Directories Exist
$uploadDirs = ['uploads', 'uploads/docs', 'uploads/skill_tests', 'uploads/announcements'];
foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Standardize Reference IDs for existing users
$res = $conn->query("SELECT id, reference_id FROM users");
if ($res) {
    while($row = $res->fetch_assoc()) {
        if (empty($row['reference_id']) || !preg_match('/^REF-\d{8}$/', $row['reference_id'])) {
            $is_unique = false;
            $tries = 0;
            $ref = '';
            while(!$is_unique && $tries < 10) {
                $ref = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
                if($conn->query("SELECT id FROM users WHERE reference_id = '$ref'")->num_rows == 0) $is_unique = true;
                $tries++;
            }
            if($is_unique) {
                $uid_row = $row['id'];
                $conn->query("UPDATE users SET reference_id = '$ref' WHERE id = $uid_row");
            }
        }
    }
}
?>
