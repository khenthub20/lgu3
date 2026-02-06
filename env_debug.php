<?php
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
file_put_contents('phpinfo_debug.html', $phpinfo);
echo "phpinfo saved to phpinfo_debug.html\n";

if (extension_loaded('openssl')) {
    echo "OpenSSL is loaded.\n";
    echo "OpenSSL Version: " . OPENSSL_VERSION_TEXT . "\n";
} else {
    echo "OpenSSL is NOT loaded.\n";
}

$stream_transports = stream_get_transports();
echo "Available stream transports: " . implode(', ', $stream_transports) . "\n";

if (in_array('ssl', $stream_transports)) {
    echo "SSL transport is available.\n";
} else {
    echo "SSL transport is NOT available.\n";
}
