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

// Diagnostic check for OpenSSL
if (!extension_loaded('openssl')) {
    $error = "Critical Error: The 'openssl' extension is not enabled in your PHP configuration. Verification emails cannot be sent. Please enable it in php.ini.";
}

$step = isset($_SESSION['signup_step']) ? $_SESSION['signup_step'] : 1;

// Email Configuration - Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // TLS Port
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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
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
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// STEP 1: Process Initial Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validations
    if (empty($fullname) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Email is already registered. Please login.";
        } else {
            // Generate OTP
            $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
            
            // Store simple data in session (Removed Bio/AI Fields)
            $_SESSION['signup_data'] = [
                'fullname' => $fullname,
                'email' => $email,
                'password' => $password,
                'otp' => $otp,
                'otp_expiry' => $otp_expiry
            ];
            
            // Send Email
            if (sendOTPEmail($email, $fullname, $otp)) {
                $_SESSION['signup_step'] = 2; // Move to Step 2
                $success = "Verification code sent to your email.";
                header("Location: signup.php");
                exit();
            } else {
                $error = "Failed to send verification email. Please check your internet connection or email address.";
            }
        }
        $stmt->close();
    }
}

// STEP 2: Process OTP Verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    $input_otp = $_POST['otp'];
    $session_data = $_SESSION['signup_data'];
    
    if ($input_otp === $session_data['otp']) {
        if (date("Y-m-d H:i:s") > $session_data['otp_expiry']) {
            $error = "OTP has expired. Please request a new one.";
        } else {
            // Create Account (Reverted to basic fields)
            $fullname = $session_data['fullname'];
            $email = $session_data['email'];
            $password = $session_data['password'];
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $reference_id = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

            // Insert into DB
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, is_active, reference_id) VALUES (?, ?, ?, 'user', 1, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $reference_id);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'user';
                $_SESSION['full_name'] = $fullname;
                
                // Clear signup session
                unset($_SESSION['signup_data']);
                unset($_SESSION['signup_step']);
                
                // Redirect to Dashboard (Assessment will happen there)
                header("Location: user_dashboard.php?new_user=1");
                exit();
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    } else {
        $error = "Invalid verification code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join LGU3 | Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { box-sizing: border-box; margin:0; padding:0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #020617;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* --- LEFT SIDE: SLIDER --- */
        .slider-section {
            flex: 1.2;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: flex-end;
            padding: 4rem;
        }

        .slider-bg {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center;
            transition: opacity 1.5s ease-in-out, transform 10s ease;
            opacity: 0;
            transform: scale(1.05);
            z-index: 0;
        }

        .slider-bg.active {
            opacity: 1;
            transform: scale(1);
        }
        
        .slider-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to top, #020617 10%, rgba(2,6,23,0.3) 100%);
            z-index: 1;
        }

        .slider-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
            animation: slideUp 1s ease-out;
        }

        .slider-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .slider-desc {
            font-size: 1.2rem;
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* --- RIGHT SIDE: LOGIN/SIGNUP --- */
        .login-section {
            flex: 0.8;
            background: rgba(2, 6, 23, 0.95);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            border-left: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
        }

        /* Flying Elements */
        .flying-icon {
            position: absolute;
            opacity: 0.1;
            color: #fff;
            animation: floatAnim 10s infinite linear;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes floatAnim {
            0% { transform: translateY(110vh) rotate(0deg); opacity: 0; }
            20% { opacity: 0.15; }
            80% { opacity: 0.15; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            z-index: 10;
            animation: fadeIn 1s ease-out;
            max-height: 90vh; /* Prevent overflow on small screens */
            overflow-y: auto;
            padding-right: 5px; /* Scrollbar space */
        }
        
        /* Custom Scrollbar */
        .login-card::-webkit-scrollbar { width: 5px; }
        .login-card::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        .brand-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .brand-logo {
            width: 50px; height: 50px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .input-group { position: relative; margin-bottom: 1.25rem; }
        
        .input-field {
            width: 100%;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1rem 1rem 1rem 3rem;
            color: #fff;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .input-field:focus {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
            outline: none;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem; top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            transition: 0.3s;
            width: 18px;
            height: 18px;
        }

        .input-field:focus ~ .input-icon { color: var(--primary); }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
            margin-top: 1rem;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(99, 102, 241, 0.5);
        }

        .otp-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1.5rem 0;
        }

        .otp-inputs input {
            width: 45px; height: 55px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 700;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            color: #fff;
        }
        
        .otp-inputs input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .step-indicator {
            display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 2rem;
        }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.1); transition: 0.3s;
        }
        .step-dot.active {
            background: var(--primary); width: 24px; border-radius: 10px;
        }

        .error-banner {
            background: rgba(239, 68, 68, 0.1); color: #fca5a5; padding: 0.8rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.2); font-size: 0.85rem;
        }
        .success-banner {
            background: rgba(16, 185, 129, 0.1); color: #86efac; padding: 0.8rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; border: 1px solid rgba(16, 185, 129, 0.2); font-size: 0.85rem;
        }

        .signup-text { text-align: center; margin-top: 2rem; color: var(--text-muted); font-size: 0.9rem; }
        .signup-text a { color: var(--primary); text-decoration: none; font-weight: 600; }

        @media (max-width: 900px) {
            body { height: auto; }
            .slider-section { display: none; }
            .login-section { flex: 1; border: none; min-height: 100vh; padding: 2rem 1rem; }
            .login-card { max-height: none; overflow: visible; }
        }
        @media (max-width: 480px) {
            .brand-header h2 { font-size: 1.5rem; }
            .input-field { padding: 0.9rem 1rem 0.9rem 2.5rem; font-size: 0.9rem; }
            .submit-btn { padding: 0.9rem; font-size: 0.9rem; }
        }
        
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        @keyframes slideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

    <!-- LEFT SIDE: SLIDER -->
    <div class="slider-section">
        <div class="slider-overlay"></div>
        <div class="slider-bg active" style="background-image: url('barangay_hq.png');"></div>
        <div class="slider-bg" style="background-image: url('training_success.png');"></div>
        <div class="slider-bg" style="background-image: url('livelihood_hero_premium.png');"></div>

        <div class="slider-content">
            <h1 class="slider-title" id="slider-title">Join the<br>Movement.</h1>
            <p class="slider-desc" id="slider-desc">Start your journey today. Register to access exclusive livelihood programs and training.</p>
            <div style="display:flex; gap:0.5rem; margin-top:1rem;">
                <span style="width:12px; height:12px; background:#fff; border-radius:50%; opacity:1;"></span>
                <span style="width:12px; height:12px; background:#fff; border-radius:50%; opacity:0.3;"></span>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: FORM -->
    <div class="login-section">
        <!-- Flying Background Icons -->
        <i data-feather="user-plus" class="flying-icon" style="left:15%; width:40px; height:40px; animation-duration:15s;"></i>
        <i data-feather="star" class="flying-icon" style="left:85%; width:30px; height:30px; animation-duration:12s; animation-delay:2s;"></i>
        <i data-feather="monitor" class="flying-icon" style="left:50%; width:45px; height:45px; animation-duration:18s; animation-delay:1s;"></i>

        <div class="login-card">
            <div class="brand-header">
                <div class="brand-logo">
                    <i data-feather="feather" style="color:#fff; width:28px; height:28px;"></i>
                </div>
                <h2 style="font-size:1.8rem; font-weight:700; margin-bottom:0.5rem; color:#fff;">
                    <?php echo $step == 1 ? 'Create Account' : 'Verify Email'; ?>
                </h2>
                <p style="color:var(--text-muted); font-size:0.9rem;">
                    <?php echo $step == 1 ? 'Enter your details below to get started' : 'We sent a code to your email'; ?>
                </p>
            </div>

            <!-- Steps -->
            <div class="step-indicator">
                <div class="step-dot <?php echo $step >= 1 ? 'active' : ''; ?>"></div>
                <div class="step-dot <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            </div>

            <?php if($error): ?>
                <div class="error-banner">
                    <i data-feather="alert-circle" style="width:16px; margin-right:5px; vertical-align:middle;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="success-banner">
                    <i data-feather="check-circle" style="width:16px; margin-right:5px; vertical-align:middle;"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if($step == 1): ?>
                <!-- STEP 1 FORM -->
                <form method="POST" action="">
                    <div class="input-group">
                        <i data-feather="user" class="input-icon"></i>
                        <input type="text" name="fullname" class="input-field" placeholder="Full Name" required autocomplete="name">
                    </div>
                    
                    <div class="input-group">
                        <i data-feather="mail" class="input-icon"></i>
                        <input type="email" name="email" class="input-field" placeholder="Email Address" required autocomplete="email">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div class="input-group">
                            <i data-feather="lock" class="input-icon"></i>
                            <input type="password" name="password" class="input-field" placeholder="Password" required autocomplete="new-password">
                        </div>
                        <div class="input-group">
                            <i data-feather="check" class="input-icon"></i>
                            <input type="password" name="confirm_password" class="input-field" placeholder="Confirm" required autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" name="send_otp" class="submit-btn" style="cursor:pointer;">
                        Continue <i data-feather="arrow-right" style="width:16px;"></i>
                    </button>
                    
                    <div class="signup-text">
                        Already have an account? <a href="index.php">Sign In</a>
                    </div>
                </form>

            <?php elseif($step == 2): ?>
                <!-- STEP 2 FORM -->
                <form method="POST" action="">
                    <div style="text-align:center; margin-bottom:1.5rem; color:#fff;">
                        Enter the 6-digit code sent to<br>
                        <strong style="color:var(--primary);"><?php echo htmlspecialchars($_SESSION['signup_data']['email']); ?></strong>
                    </div>

                    <div class="otp-inputs">
                        <input type="text" id="otp-input" maxlength="6" style="width:200px; letter-spacing:8px;" pattern="[0-9]*" required autocomplete="one-time-code">
                    </div>
                    <input type="hidden" name="otp" id="otp-hidden">

                    <button type="submit" name="verify_otp" class="submit-btn">
                        Verify & Join <i data-feather="check-circle" style="width:16px;"></i>
                    </button>
                    
                    <div class="signup-text">
                        <a href="?restart=1" style="color:#64748b; font-size:0.85rem;">Change Email / Restart</a>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
        feather.replace();

        // Slider Logic
        const slides = document.querySelectorAll('.slider-bg');
        let current = 0;
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
        }, 5000);

        // OTP Input handling
        const otpInput = document.getElementById('otp-input');
        if(otpInput) {
            otpInput.addEventListener('input', (e) => {
                document.getElementById('otp-hidden').value = e.target.value;
            });
        }
    </script>
</body>
</html>