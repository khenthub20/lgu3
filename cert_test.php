<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 4;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'khentcorpuz71@gmail.com';
    $mail->Password   = 'tmyzdqgkxwcjzski';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // NO SMTPOptions here to see if certificate is the issue
    
    $mail->setFrom('khentcorpuz71@gmail.com', 'LGU3');
    $mail->addAddress('khentcorpuz71@gmail.com');
    $mail->Subject = 'Cert Test';
    $mail->Body    = 'Test';

    $mail->send();
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "ERROR: " . $mail->ErrorInfo . "\n";
}
