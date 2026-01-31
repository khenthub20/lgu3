<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'khentcorpuz71@gmail.com'); 
define('SMTP_PASS', 'tmyzdqgkxwcjzski'); 

echo "<h2>PHPMailer Diagnostics (Port 465 SSL)</h2>";

if (!extension_loaded('openssl')) {
    echo "<p style='color:red'>ERROR: OpenSSL extension is NOT loaded. Please check php.ini and restart Apache.</p>";
} else {
    echo "<p style='color:green'>SUCCESS: OpenSSL extension is LOADED.</p>";
}

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 3; // High level of debug
    $mail->Debugoutput = 'html';
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = trim(SMTP_USER);
    $mail->Password   = trim(SMTP_PASS);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = SMTP_PORT;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom(SMTP_USER, 'LGU3 Test');
    $mail->addAddress(SMTP_USER, 'Test Me'); // Sending to self

    $mail->Subject = 'PHPMailer Connection Test';
    $mail->Body    = 'This is a test email to verify SMTP settings.';

    echo "<h3>SMTP Debug Log:</h3>";
    if($mail->send()) {
        echo "<h3 style='color:green'>EMAIL SENT SUCCESSFULLY!</h3>";
    }

} catch (Exception $e) {
    echo "<h3 style='color:red'>SEND FAILED!</h3>";
    echo "<b>ErrorInfo:</b> " . $mail->ErrorInfo . "<br>";
    echo "<b>Exception Message:</b> " . $e->getMessage() . "<br>";
}
?>
