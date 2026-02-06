<?php
require_once 'db_connect.php';

echo "<h2>Database Schema Check</h2>";
echo "Checking table 'users'...<br>";

$result = $conn->query("DESCRIBE users");

if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . ($val ?: '<i>null</i>') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing users table: " . $conn->error;
}
?>
