<?php
include 'db_connect.php';

$email = 'admin@lgu3.gov';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin', is_active = 1 WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    if ($stmt->execute()) {
        echo "<h2>SUCCESS!</h2><p>Admin account (admin@lgu3.gov) has been updated with password 'admin123'.</p>";
    } else {
        echo "<h2>ERROR</h2><p>" . $conn->error . "</p>";
    }
} else {
    // Create new admin
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES ('System Admin', ?, ?, 'admin', 1)");
    $stmt->bind_param("ss", $email, $hashed_password);
    if ($stmt->execute()) {
        echo "<h2>SUCCESS!</h2><p>New Admin account (admin@lgu3.gov) has been created with password 'admin123'.</p>";
    } else {
        echo "<h2>ERROR</h2><p>" . $conn->error . "</p>";
    }
}
echo "<br><a href='index.php'>Go to Login</a>";
?>
