<?php
include 'db_connect.php';

echo "<h2>System-Wide Password Security Update</h2>";

$res = $conn->query("SELECT id, email, password FROM users");
$updatedCount = 0;
$skippedCount = 0;

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $id = $row['id'];
        $email = $row['email'];
        $pass = $row['password'];

        // Check if password is already hashed (bcrypt hashes start with $2y$)
        if (strpos($pass, '$2y$') === 0) {
            $skippedCount++;
            continue;
        }

        // It is plaintext, hash it
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $id);
        
        if ($stmt->execute()) {
            $updatedCount++;
            echo "Fixed user: $email<br>";
        }
    }
}

echo "<h3>Update Summary:</h3>";
echo "Users secured: $updatedCount<br>";
echo "Users already secure: $skippedCount<br>";
echo "<br><a href='index.php'>Go to Login</a>";
?>
