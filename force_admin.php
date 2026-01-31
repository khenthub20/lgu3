<?php
include 'db_connect.php';

$email = 'admin@lgu3.gov';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin Fix Tool</h2>";
echo "Database: " . $dbname . "<br>";

// 1. Delete any existing admin with this email to be safe
$conn->query("DELETE FROM users WHERE email = '$email'");

// 2. Insert fresh admin
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES ('System Admin', ?, ?, 'admin', 1)");
$stmt->bind_param("ss", $email, $hashed_password);

if ($stmt->execute()) {
    echo "<h3 style='color:green'>SUCCESS! Fresh Admin Created.</h3>";
    echo "<b>Email:</b> " . $email . "<br>";
    echo "<b>Password:</b> " . $password . "<br>";
    echo "<b>Password Hash stored:</b> " . $hashed_password . "<br>";
} else {
    echo "<h3 style='color:red'>FAILED!</h3> Error: " . $conn->error;
}

// 3. Verify it's there
$res = $conn->query("SELECT * FROM users WHERE email = '$email'");
$user = $res->fetch_assoc();
echo "<h3>Verification Check:</h3>";
echo "Found in DB: " . ($user ? "YES" : "NO") . "<br>";
if($user) {
    echo "Role in DB: " . $user['role'] . "<br>";
    echo "Is Active: " . $user['is_active'] . "<br>";
    echo "Check match: " . (password_verify($password, $user['password']) ? "MATCH" : "NO MATCH") . "<br>";
}

echo "<br><a href='index.php'>Go to Login Page</a>";
?>
