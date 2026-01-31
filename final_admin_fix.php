<?php
include 'db_connect.php';

$email = 'admin@lgu3.gov';
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin Force Update Tool</h2>";

// 1. Force UPDATE the existing record - this avoids the 'Duplicate entry' error
$stmt = $conn->prepare("UPDATE users SET password = ?, full_name = 'System Admin', role = 'admin', is_active = 1 WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute() && $conn->affected_rows > 0) {
    echo "<h3 style='color:green'>SUCCESS! Admin password forced to 'admin123'.</h3>";
} else {
    // If it didn't affect any rows, maybe the email doesn't exist? (Unlikely given previous log, but let's be safe)
    $stmt_insert = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES ('System Admin', ?, ?, 'admin', 1) ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $stmt_insert->bind_param("ss", $email, $hashed_password);
    if($stmt_insert->execute()) {
        echo "<h3 style='color:green'>SUCCESS! Admin record synchronized.</h3>";
    } else {
        echo "<h3 style='color:red'>FAILED!</h3> Error: " . $conn->error;
    }
}

// 2. Final Verification
$res = $conn->query("SELECT * FROM users WHERE email = '$email'");
$user = $res->fetch_assoc();
echo "<h3>Final Verification Check:</h3>";
if($user) {
    $match = password_verify($password, $user['password']);
    echo "Check match for 'admin123': " . ($match ? "<span style='color:green'>MATCH (LOGIN WILL WORK)</span>" : "<span style='color:red'>NO MATCH</span>") . "<br>";
}

echo "<br><a href='index.php'>Go to Login Page</a>";
?>
