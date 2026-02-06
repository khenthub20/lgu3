<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Live Server Repair Script</h2>";

// 1. Ensure Directories Exist and have Correct Permissions
$dirs = ['uploads', 'uploads/docs', 'uploads/skill_tests', 'uploads/announcements'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir (755)<br>";
        } else {
            echo "<b style='color:red;'>Failed to create: $dir</b><br>";
        }
    } else {
        chmod($dir, 0755);
        echo "Fixed permissions for: $dir (755)<br>";
    }
}

// 2. Add .gitkeep to ensure folders exist in git (optional but good)
foreach ($dirs as $dir) {
    file_put_contents($dir . '/.gitkeep', '');
}

// 3. Check for specific file from screenshot
$test_file = 'uploads/docs/1769872027_Resource 1 - Agriculture.docx';
if (file_exists($test_file)) {
    chmod($test_file, 0644);
    echo "Found and fixed permissions for test file (644)<br>";
} else {
    echo "<b style='color:orange;'>Test file not found on server: $test_file</b><br>";
    echo "<i>Tip: Make sure you uploaded this file ON THE LIVE SERVER admin dashboard. Files in 'uploads/' are NOT synced via GitHub.</i><br>";
}

// 4. Test directory listing
echo "<h3>Current Uploads/Docs Content:</h3><ul>";
$files = scandir('uploads/docs');
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li>$file</li>";
    }
}
echo "</ul>";
?>
