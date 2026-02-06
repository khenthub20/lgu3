<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Detailed File System Check</h2>";

function check_path($path) {
    echo "<h3>Checking: $path</h3>";
    if (!file_exists($path)) {
        echo "<b style='color:red;'>DOES NOT EXIST</b><br>";
        return;
    }
    
    $perms = fileperms($path);
    echo "Permissions: " . sprintf('%o', $perms) . "<br>";
    echo "Owner: " . fileowner($path) . "<br>";
    echo "Group: " . filegroup($path) . "<br>";
    
    if (is_dir($path)) {
        echo "Type: Directory<br>";
        $files = scandir($path);
        echo "Contents (" . (count($files) - 2) . " files):<br>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $fpath = $path . '/' . $file;
                $fperms = sprintf('%o', fileperms($fpath));
                echo "<li>$file (Perms: $fperms)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "Type: File<br>";
        echo "Size: " . filesize($path) . " bytes<br>";
    }
}

check_path('uploads');
check_path('uploads/docs');

echo "<h3>Database Content vs File System</h3>";
require_once 'db_connect.php';
$res = $conn->query("SELECT * FROM learning_docs");
if ($res) {
    echo "<table border='1'><tr><th>ID</th><th>Path in DB</th><th>Exists?</th></tr>";
    while ($row = $res->fetch_assoc()) {
        $path = $row['file_path'];
        $exists = file_exists($path) ? "<b style='color:green;'>YES</b>" : "<b style='color:red;'>NO</b>";
        echo "<tr><td>{$row['id']}</td><td>$path</td><td>$exists</td></tr>";
    }
    echo "</table>";
} else {
    echo "DB Error: " . $conn->error;
}
?>
