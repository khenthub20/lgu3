<?php
echo "OpenSSL extension: " . (extension_loaded('openssl') ? "LOADED" : "NOT LOADED") . "<br>";
echo "Allow URL Fopen: " . (ini_get('allow_url_fopen') ? "ON" : "OFF") . "<br>";
echo "SMTP: " . ini_get('SMTP') . "<br>";
echo "smtp_port: " . ini_get('smtp_port') . "<br>";
?>
