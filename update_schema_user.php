<?php
include 'db_connect.php';

// 1. Add profile_image to users
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
if ($checkCol->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
    echo "Added 'profile_image' to users.<br>";
}

echo "User schema updated.";
?>
