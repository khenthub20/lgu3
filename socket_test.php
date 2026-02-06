<?php
$host = 'smtp.gmail.com';
$port = 587;
$timeout = 10;

echo "Testing connection to $host:$port...\n";
$errno = 0;
$errstr = '';
$fp = fsockopen($host, $port, $errno, $errstr, $timeout);

if (!$fp) {
    echo "Connection failed: $errstr ($errno)\n";
} else {
    echo "Connection successful!\n";
    $response = fgets($fp, 256);
    echo "Server said: $response";
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}

$port = 465;
echo "\nTesting connection to $host:$port...\n";
$fp = fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);

if (!$fp) {
    echo "Connection failed: $errstr ($errno)\n";
} else {
    echo "Connection successful!\n";
    $response = fgets($fp, 256);
    echo "Server said: $response";
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}
