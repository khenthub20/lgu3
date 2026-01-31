<?php
include 'db_connect.php';

// Find the admin user
$res = $conn->query("SELECT email, password FROM users WHERE role = 'admin' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $email = $row['email'];
    $current_pass = $row['password'];
    
    // Check if it's already hashed
    if (strpos($current_pass, '$2y$') === 0) {
        echo "Admin ($email) is already using a secure hashed password.";
    } else {
        // It's plaintext, hash it!
        $new_hash = password_hash($current_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
        $stmt->bind_param("ss", $new_hash, $email);
        
        if ($stmt->execute()) {
            echo "SUCCESS: Admin ($email) password has been converted to a secure hash. You can now login!";
        } else {
            echo "ERROR: Could not update password: " . $conn->error;
        }
    }
} else {
    echo "ERROR: No admin user found in the database.";
}
?>
