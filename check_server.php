<?php
echo "<h2>Domain Email Diagnostic</h2>";

$host = 'smtp.gmail.com';
$ports = [465, 587, 25];

foreach ($ports as $port) {
    echo "Testing Port $port... ";
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if (is_resource($connection)) {
        echo "<b style='color:green;'>OPEN</b><br>";
        fclose($connection);
    } else {
        echo "<b style='color:red;'>CLOSED</b> ($errstr)<br>";
    }
}

echo "<h3>PHP Configuration:</h3>";
echo "OpenSSL Extension: " . (extension_loaded('openssl') ? "Enabled" : "Disabled") . "<br>";
echo "Allow URL Fopen: " . (ini_get('allow_url_fopen') ? "On" : "Off") . "<br>";
?>
