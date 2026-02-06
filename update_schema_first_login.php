<?php
include 'db_connect.php';

// Add is_first_login column
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'is_first_login'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN is_first_login TINYINT(1) DEFAULT 1 AFTER is_active");
    echo "Added is_first_login column.\n";
} else {
    echo "Column is_first_login already exists.\n";
}

// Reset admin to NOT first login (optional, safe)
$conn->query("UPDATE users SET is_first_login = 0 WHERE role = 'admin'");
echo "Updated admin status.\n";
?>
