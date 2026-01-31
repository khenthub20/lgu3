<?php
session_start();
include 'db_connect.php';

// Handle restart request
if (isset($_GET['restart'])) {
    unset($_SESSION['signup_data']);
    unset($_SESSION['signup_step']);
    header('Location: signup.php');
    exit();
}

$error = '';
$success = '';
$step = isset($_SESSION['signup_step']) ? $_SESSION['signup_step'] : 1;

// Email Configuration - Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // SSL Port
define('SMTP_USER', 'khentcorpuz71@gmail.com');
define('SMTP_PASS', 'tmyzdqgkxwcjzski');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Send OTP Email using PHPMailer with Gmail SMTP
 */
function sendOTPEmail($to, $fullname, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = trim(SMTP_USER);
        $mail->Password   = trim(SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
        $mail->Port       = SMTP_PORT;
        
        // Fix for Windows/XAMPP SSL issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom(SMTP_USER, 'LGU3 Livelihood Portal');
        $mail->addAddress($to, $fullname);
        $mail->addReplyTo(SMTP_USER, 'LGU3 Support');

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'LGU3 - Email Verification Code';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0; }
                .container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; margin-bottom: 30px; }
                .otp-box { background: #6366f1; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 8px; letter-spacing: 8px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='color: #6366f1; margin: 0;'>LGU3 Livelihood Portal</h2>
                    <p style='color: #666; margin: 5px 0;'>Email Verification</p>
                </div>
                <p>Hello <strong>$fullname</strong>,</p>
                <p>Thank you for registering! Please use the following verification code to complete your registration:</p>
                <div class='otp-box'>$otp</div>
                <p style='margin-top: 20px;'>This code will expire in <strong>10 minutes</strong>.</p>
                <p style='color: #666; font-size: 14px;'>If you didn't request this code, please ignore this email.</p>
                <div class='footer'>
                    <p>© 2026 LGU3 Livelihood Portal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->AltBody = "Hello $fullname,\n\nYour LGU3 verification code is: $otp\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        $_SESSION['email_debug'] = "Mailer Error: " . $mail->ErrorInfo . " | System Error: " . $e->getMessage();
        error_log($_SESSION['email_debug']);
        return false;
    } catch (\Error $err) {
        $_SESSION['email_debug'] = "PHP Error: " . $err->getMessage();
        error_log($_SESSION['email_debug']);
        return false;
    }
}

// Step 1: Send OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // Generate 6-digit OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store in session
            $_SESSION['signup_data'] = [
                'fullname' => $fullname,
                'email' => $email,
                'password' => $password,
                'otp' => $otp,
                'otp_expiry' => $otp_expiry
            ];
            $_SESSION['signup_step'] = 2;

            // Send OTP via email
            $to = $email;
            $subject = "LGU3 - Email Verification Code";
            $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
                    .container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .otp-box { background: #6366f1; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 8px; letter-spacing: 8px; }
                    .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='color: #6366f1;'>LGU3 Livelihood Portal</h2>
                        <p>Email Verification</p>
                    </div>
                    <p>Hello <strong>$fullname</strong>,</p>
                    <p>Thank you for registering! Please use the following verification code to complete your registration:</p>
                    <div class='otp-box'>$otp</div>
                    <p style='margin-top: 20px;'>This code will expire in <strong>10 minutes</strong>.</p>
                    <p>If you didn't request this code, please ignore this email.</p>
                    <div class='footer'>
                        <p>© 2026 LGU3 Livelihood Portal. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: LGU3 Portal <noreply@lgu3.local>" . "\r\n";

            // Send OTP via Gmail SMTP
            $emailSent = sendOTPEmail($email, $fullname, $otp);

            if ($emailSent) {
                $success = "Verification code sent to your email!";
                $step = 2;
            } else {
                $error = "Failed to send verification email. ";
                if(isset($_SESSION['email_debug'])) {
                    $error .= "Error: " . $_SESSION['email_debug'];
                    unset($_SESSION['email_debug']);
                } else {
                    $error .= "Please try again later.";
                }
            }
        }
        $check->close();
    }
}

// Step 2: Verify OTP and Create Account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    if (!isset($_SESSION['signup_data'])) {
        $error = "Session expired. Please start again.";
        $_SESSION['signup_step'] = 1;
        $step = 1;
    } else {
        $stored_otp = $_SESSION['signup_data']['otp'];
        $otp_expiry = $_SESSION['signup_data']['otp_expiry'];

        if (strtotime($otp_expiry) < time()) {
            $error = "OTP has expired. Please request a new one.";
            unset($_SESSION['signup_data']);
            $_SESSION['signup_step'] = 1;
            $step = 1;
        } elseif ($entered_otp != $stored_otp) {
            $error = "Invalid OTP. Please try again.";
        } else {
            // OTP is valid, hash password and create account
            $fullname = $_SESSION['signup_data']['fullname'];
            $email = $_SESSION['signup_data']['email'];
            $password = $_SESSION['signup_data']['password'];
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, 'user', 1)");
            $stmt->bind_param("sss", $fullname, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Account created successfully! <a href='index.php' style='color:#6366f1; font-weight:600;'>Login here</a>";
                unset($_SESSION['signup_data']);
                unset($_SESSION['signup_step']);
                $step = 3; // Success step
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Resend OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    if (isset($_SESSION['signup_data'])) {
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $_SESSION['signup_data']['otp'] = $otp;
        $_SESSION['signup_data']['otp_expiry'] = $otp_expiry;

        $email = $_SESSION['signup_data']['email'];
        $fullname = $_SESSION['signup_data']['fullname'];

        $to = $email;
        $subject = "LGU3 - New Verification Code";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
                .container { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: 0 auto; }
                .otp-box { background: #6366f1; color: white; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 8px; letter-spacing: 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2 style='color: #6366f1;'>New Verification Code</h2>
                <p>Hello <strong>$fullname</strong>,</p>
                <p>Here's your new verification code:</p>
                <div class='otp-box'>$otp</div>
                <p style='margin-top: 20px;'>This code will expire in <strong>10 minutes</strong>.</p>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: LGU3 Portal <noreply@lgu3.local>" . "\r\n";

        if (sendOTPEmail($email, $fullname, $otp)) {
            $success = "New verification code sent!";
        } else {
            $error = "Failed to send email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us | LGU3 Livelihood</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }
        
        body { font-family: 'Outfit', sans-serif; background: #0a0c10; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .login-container {
            max-width: 440px;
            width: 100%;
            padding: 3rem;
            border-radius: 24px;
            background: rgba(15, 17, 21, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
            animation: entrance 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes entrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
        }

        .welcome-msg {
            font-size: 0.9rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            font-weight: 300;
        }

        .input-group { margin-bottom: 1.25rem; position: relative; }
        
        .input-group input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            color: #fff;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
            outline: none;
        }

        .input-group label {
            position: absolute;
            left: 1rem;
            top: 0.85rem;
            font-size: 0.85rem;
            color: #64748b;
            pointer-events: none;
            transition: all 0.3s;
        }

        .input-group input:focus ~ label,
        .input-group input:not(:placeholder-shown) ~ label {
            top: -8px;
            left: 0.8rem;
            font-size: 0.75rem;
            color: #6366f1;
            background: #0a0c10;
            padding: 0 0.4rem;
        }

        .login-btn {
            background: #fff;
            color: #000;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            background: #f8fafc;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.05);
            color: #ef4444;
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            border: 1px solid rgba(239, 68, 68, 0.1);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .success-msg {
            background: rgba(16, 185, 129, 0.05);
            color: #10b981;
            padding: 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            border: 1px solid rgba(16, 185, 129, 0.1);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .otp-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 2rem 0;
        }

        .otp-inputs input {
            width: 50px;
            height: 60px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            border-radius: 12px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff;
        }

        .otp-inputs input:focus {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
            outline: none;
        }

        .resend-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #64748b;
        }

        .resend-link button {
            background: none;
            border: none;
            color: #6366f1;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .step-dot.active {
            background: #6366f1;
            width: 24px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Join Us</h1>
            <p class="welcome-msg">
                <?php if($step == 1): ?>
                    Citizen Registration
                <?php elseif($step == 2): ?>
                    Email Verification
                <?php else: ?>
                    Registration Complete
                <?php endif; ?>

            </p>
        </div>

        <div class="step-indicator">
            <div class="step-dot <?php echo $step >= 1 ? 'active' : ''; ?>"></div>
            <div class="step-dot <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            <div class="step-dot <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
        </div>

        <?php if($error): ?>
            <div class="error-msg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-msg">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($step == 1): ?>
            <!-- Step 1: Registration Form -->
            <form class="login-form" method="POST" action="">
                <div class="input-group">
                    <input type="text" id="fullname" name="fullname" required placeholder=" " autocomplete="name">
                    <label for="fullname">Full Name</label>
                </div>

                <div class="input-group">
                    <input type="email" id="email" name="email" required placeholder=" " autocomplete="email">
                    <label for="email">Email Address</label>
                </div>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="input-group">
                        <input type="password" id="password" name="password" required placeholder=" " autocomplete="new-password">
                        <label for="password">Password</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder=" " autocomplete="new-password">
                        <label for="confirm_password">Confirm</label>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color:#64748b; cursor: pointer;">
                        <input type="checkbox" id="terms" required style="width: auto;">
                        I agree to the <a href="#" style="color:#6366f1;">Terms</a>
                    </label>
                </div>
                
                <button type="submit" name="send_otp" class="login-btn">
                    <span>Send Verification Code</span>
                    <i data-feather="mail" style="width:16px; margin-left:8px; vertical-align:middle;"></i>
                </button>
                
                <p style="text-align: center; font-size: 0.85rem; margin-top: 2rem; color: #64748b;">
                    Member? <a href="index.php" style="font-weight: 600; color: #6366f1; text-decoration: none;">Sign In</a>
                    <span style="margin: 0 0.5rem; color: #334155;">|</span>
                    <a href="landingpage.php" style="color: #94a3b8; text-decoration: none;">Back to Community</a>
                </p>
            </form>

        <?php elseif($step == 2): ?>
            <!-- Step 2: OTP Verification -->
            <form method="POST" action="">
                <p style="text-align: center; color: #94a3b8; margin-bottom: 1rem; font-size: 0.9rem;">
                    We've sent a 6-digit code to<br>
                    <strong style="color: #fff;"><?php echo isset($_SESSION['signup_data']) ? $_SESSION['signup_data']['email'] : ''; ?></strong>
                </p>

                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                    <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" required>
                </div>

                <input type="hidden" name="otp" id="otp-hidden">

                <button type="submit" name="verify_otp" class="login-btn">
                    <span>Verify & Create Account</span>
                    <i data-feather="check-circle" style="width:16px; margin-left:8px; vertical-align:middle;"></i>
                </button>

                <div class="resend-link">
                    Didn't receive code? 
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="resend_otp">Resend</button>
                    </form>
                </div>

                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05);">
                    <a href="?restart=1" style="color: #64748b; font-size: 0.85rem; text-decoration: none; transition: color 0.3s;">
                        <i data-feather="arrow-left" style="width: 14px; vertical-align: middle; margin-right: 4px;"></i>
                        Change Email Address
                    </a>
                </div>
            </form>

            <script>
                const otpInputs = document.querySelectorAll('.otp-digit');
                const otpHidden = document.getElementById('otp-hidden');

                otpInputs.forEach((input, index) => {
                    input.addEventListener('input', (e) => {
                        if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                        updateOTP();
                    });

                    input.addEventListener('keydown', (e) => {
                        if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    });

                    input.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const pastedData = e.clipboardData.getData('text').slice(0, 6);
                        pastedData.split('').forEach((char, i) => {
                            if (otpInputs[i]) otpInputs[i].value = char;
                        });
                        updateOTP();
                    });
                });

                function updateOTP() {
                    const otp = Array.from(otpInputs).map(input => input.value).join('');
                    otpHidden.value = otp;
                }
            </script>
        <?php else: ?>
            <!-- Step 3: Success -->
            <div style="text-align: center; padding: 1rem 0;">
                <div style="width: 64px; height: 64px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i data-feather="user-check" style="width: 32px; height: 32px;"></i>
                </div>
                <p style="color: #94a3b8; margin-bottom: 2rem;">Your account is now active. You can now access the portal and explore upcoming programs.</p>
                <a href="index.php" class="login-btn" style="text-decoration: none; display: block; text-align: center; padding: 1rem; border-radius: 12px; background: #6366f1; color: white; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);">
                    Go to Sign In
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>