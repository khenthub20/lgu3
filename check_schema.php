<?php
require_once 'db_connect.php';

echo "<h2>Database Schema Check</h2>";
echo "<h3>Checking table 'learning_docs'...</h3>";
$result = $conn->query("DESCRIBE learning_docs");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . ($val ?: '<i>null</i>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else { echo "Error: " . $conn->error . "<br>"; }

echo "<h3>Checking table 'program_applications'...</h3>";
$result = $conn->query("DESCRIBE program_applications");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) echo "<td>" . ($val ?: '<i>null</i>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else { echo "Error: " . $conn->error . "<br>"; }

echo "<h3>Sample Data: learning_docs</h3>";
$resData = $conn->query("SELECT * FROM learning_docs LIMIT 5");
if ($resData && $resData->num_rows > 0) {
    echo "<table border='1'>";
    while($row = $resData->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $k=>$v) echo "<td><b>$k:</b> $v</td>";
        echo "</tr>";
    }
    echo "</table>";
} else { echo "No data in learning_docs.<br>"; }

echo "<h3>Sample Data: program_applications</h3>";
$resData = $conn->query("SELECT * FROM program_applications WHERE status = 'approved' LIMIT 5");
if ($resData && $resData->num_rows > 0) {
    echo "<table border='1'>";
    while($row = $resData->fetch_assoc()) {
        echo "<tr>";
        foreach($row as $k=>$v) echo "<td><b>$k:</b> $v</td>";
        echo "</tr>";
    }
    echo "</table>";
} else { echo "No approved applications found.<br>"; }
?>
