<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

define('SMTP_USER', 'khentcorpuz71@gmail.com');
define('SMTP_PASS', 'tmyzdqgkxwcjzski');

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 4; // Max debug
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = trim(SMTP_USER);
    $mail->Password   = trim(SMTP_PASS);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    $mail->Port       = 465;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom(SMTP_USER, 'Test');
    $mail->addAddress(SMTP_USER);

    $mail->isHTML(true);
    $mail->Subject = 'Test Email (Port 465)';
    $mail->Body    = 'This is a test email using port 465.';

    $mail->send();
    echo "Message has been sent\n";
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
}
