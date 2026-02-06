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

$step = isset($_SESSION['signup_step']) ? $_SESSION['signup_step'] : 0;

// STEP 0: Process Terms Acceptance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_terms'])) {
    $_SESSION['signup_step'] = 1;
    header("Location: signup.php");
    exit();
}


// Email Configuration - Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // SSL Port (more reliable on XAMPP)
define('SMTP_USER', 'khentcorpuz71@gmail.com');
define('SMTP_PASS', 'edqj nqsx pvgb ffph');

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
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Changed to SMTPS (SSL)
        $mail->Port       = 465; // Changed to 465
        $mail->Timeout    = 20;
        
        // Fix for Windows/XAMPP SSL issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
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
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $street = $_POST['street'];
    $house_number = trim($_POST['house_number']);
    $password = $_POST['password'];

    // Password Validation: min 8 chars, uppercase, lowercase, number
    $password_valid = preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);

    // Basic Validations
    if (empty($first_name) || empty($last_name) || empty($email) || empty($street) || empty($house_number) || empty($password)) {
        $error = "Please fill in all required fields marked with *.";
    } elseif (strlen($first_name) < 2) {
        $error = "First name must be at least 2 characters.";
    } elseif (!$password_valid) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and a number.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Email is already registered. Please login.";
        } else {
            // Handle File Upload (Optional)
            $valid_id_path = '';
            if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] == 0) {
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                $filename = $_FILES['valid_id']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $size = $_FILES['valid_id']['size'];

                if (!in_array($ext, $allowed)) {
                    $error = "Invalid file type. Only PDF, JPG, and PNG are allowed.";
                } elseif ($size > 5 * 1024 * 1024) {
                    $error = "File size exceeds 5MB limit.";
                } else {
                    $newName = 'ID_' . time() . '_' . uniqid() . '.' . $ext;
                    if (!is_dir('uploads/ids')) mkdir('uploads/ids', 0777, true);
                    $target = 'uploads/ids/' . $newName;
                    if (move_uploaded_file($_FILES['valid_id']['tmp_name'], $target)) {
                        $valid_id_path = $target;
                    }
                }
            }

            if (!$error) {
                // Generate OTP
                $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
                
                // Store detailed data in session
                $_SESSION['signup_data'] = [
                    'first_name' => $first_name,
                    'middle_name' => $middle_name,
                    'last_name' => $last_name,
                    'suffix' => $suffix,
                    'email' => $email,
                    'mobile_number' => $mobile_number,
                    'street' => $street,
                    'house_number' => $house_number,
                    'valid_id_path' => $valid_id_path,
                    'password' => $password,
                    'otp' => $otp,
                    'otp_expiry' => $otp_expiry
                ];
                
                // Send Email
                $fullname = trim("$first_name $last_name");
                if (sendOTPEmail($email, $fullname, $otp)) {
                    $_SESSION['signup_step'] = 2; // Move to Step 2
                    $success = "Verification code sent to your email.";
                    header("Location: signup.php");
                    exit();
                } else {
                    $error = "Failed to send verification email. Please check your internet connection or email address.";
                }
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
            $first_name = $session_data['first_name'];
            $middle_name = $session_data['middle_name'];
            $last_name = $session_data['last_name'];
            $suffix = $session_data['suffix'];
            $fullname = trim("$first_name $middle_name $last_name $suffix");
            $email = $session_data['email'];
            $mobile = $session_data['mobile_number'];
            $street = $session_data['street'];
            $house_number = $session_data['house_number'];
            $vid = $session_data['valid_id_path'];
            $password = $session_data['password'];
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $reference_id = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

            // Insert into DB
            $stmt = $conn->prepare("INSERT INTO users (first_name, middle_name, last_name, suffix, full_name, email, mobile_number, street, house_number, valid_id_path, password, role, is_active, reference_id, barangay) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'user', 1, ?, 'Baranggay Laforteza Holdings 264')");
            $stmt->bind_param("ssssssssssss", $first_name, $middle_name, $last_name, $suffix, $fullname, $email, $mobile, $street, $house_number, $vid, $hashed_password, $reference_id);
            
            if ($stmt->execute()) {
                // Clear signup session only (Do not log in yet)
                unset($_SESSION['signup_data']);
                unset($_SESSION['signup_step']);
                
                // Redirect to Login with Success Message
                header("Location: index.php?verified=1");
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
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(0, 0, 0, 0.08);
            --text-main: #0f172a;
            --text-muted: #475569;
        }

        * { box-sizing: border-box; margin:0; padding:0; }

        body {
            font-family: 'Outfit', sans-serif;
            background: #ffffff;
            color: var(--text-main);
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* --- LEFT SIDE: SLIDER --- */
        .slider-section {
            flex: 1;
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
            background: rgba(255, 255, 255, 0.1); /* Minimal tint instead of heavy fog */
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
            color: #0f172a;
            text-shadow: 0 2px 4px rgba(255,255,255,0.8), 0 0 20px rgba(255,255,255,0.3);
        }

        .slider-desc {
            font-size: 1.2rem;
            color: #0f172a;
            line-height: 1.6;
            margin-bottom: 2rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
        }

        /* --- RIGHT SIDE: LOGIN/SIGNUP --- */
        .login-section {
            flex: 1;
            background: #ffffff;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            border-left: 1px solid var(--glass-border);
        }

        /* Flying Elements */
        .flying-icon {
            position: absolute;
            opacity: 0.1;
            color: var(--primary);
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
            max-width: 850px;
            z-index: 10;
            animation: fadeIn 1s ease-out;
            padding: 1.5rem 2rem;
        }
        
        /* Custom Scrollbar */
        .login-card::-webkit-scrollbar { width: 5px; }
        .login-card::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 4px; }

        .brand-header {
            margin-bottom: 0.8rem;
            text-align: center;
        }

        .brand-logo {
            width: 70px; height: 70px;
            background: #ffffff;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .input-group { position: relative; margin-bottom: 1.1rem; }
        
        .input-field {
            width: 100%;
            background: #f8fafc;
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1rem 1rem 1rem 3rem;
            color: var(--text-main);
            font-size: 0.95rem;
            transition: 0.3s;
            color-scheme: light;
        }

        select.input-field option {
            background-color: #ffffff;
            color: #0f172a;
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
            color: var(--text-main);
        }
        
        .otp-inputs input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .step-indicator {
            display: flex; justify-content: center; gap: 0.5rem; margin-bottom: 2rem;
        }
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%; background: rgba(0,0,0,0.1); transition: 0.3s;
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

        /* Terms Modal-like style */
        .terms-container {
            max-height: 400px;
            overflow-y: auto;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #cbd5e1;
            line-height: 1.6;
        }
        .terms-container::-webkit-scrollbar { width: 5px; }
        .terms-container::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .terms-container h3 { color: #fff; margin: 1.5rem 0 0.8rem; font-size: 1.1rem; }
        .terms-container h3:first-child { margin-top: 0; }
        .terms-container p { margin-bottom: 1rem; }
        .terms-container ul { margin-bottom: 1rem; padding-left: 1.2rem; }
        .terms-container li { margin-bottom: 0.5rem; }

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
                <span style="width:12px; height:12px; background:var(--primary); border-radius:50%; opacity:1;"></span>
                <span style="width:12px; height:12px; background:var(--primary); border-radius:50%; opacity:0.3;"></span>
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
                    <img src="laforteza_logo.jpg" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <h2 style="font-size:1.8rem; font-weight:700; margin-bottom:0.5rem; color:var(--text-main);">
                    <?php 
                        if ($step == 0) echo 'Terms & Policies';
                        else if ($step == 1) echo 'Create Account';
                        else echo 'Verify Email';
                    ?>
                </h2>
                <p style="color:var(--text-muted); font-size:0.9rem;">
                    <?php 
                        if ($step == 0) echo 'Please review our terms before proceeding';
                        else if ($step == 1) echo 'Enter your details below to get started';
                        else echo 'We sent a code to your email';
                    ?>
                </p>
            </div>

            <!-- Steps -->
            <div class="step-indicator">
                <div class="step-dot <?php echo $step >= 0 ? 'active' : ''; ?>"></div>
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

            <?php if($step == 0): ?>
                <!-- STEP 0: TERMS AND CONDITIONS -->
                <div class="terms-container">
                    <h3>Terms and Conditions</h3>
                    <p>These Terms and Conditions govern the use of the Baranggay Laforteza Holdings 264 Public Facilities Reservation System. By accessing the portal, citizens, organizations, and partner agencies agree to observe the policies set by the Municipal Facilities Management Office.</p>
                    
                    <p>Reservations are considered tentative until a confirmation notice is issued by the LGU. The LGU reserves the right to reassign, reschedule, or decline requests to ensure continuity of essential public services, disaster response operations, and official functions.</p>
                    
                    <p>Users shall provide accurate contact information, submit complete supporting documents, and settle applicable fees within the prescribed period. Non-compliance may result in cancellation without prejudice to future bookings.</p>
                    
                    <p>Any unauthorized commercial activity, political gathering without clearance, or activity that jeopardizes public safety is strictly prohibited. Damages to facilities shall be charged to the reserving party and may include administrative sanctions.</p>
                    
                    <p>By proceeding, you acknowledge that you have read and understood these terms and agree to comply with all LGU directives related to facility utilization.</p>

                    <h3>Data Privacy Policy</h3>
                    <h4>1. Data Controller</h4>
                    <p>The Baranggay Laforteza Holdings 264 Public Facilities Reservation System is operated by the Baranggay Laforteza Holdings 264 Facilities Management Office. We are committed to protecting your personal data in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173) and its Implementing Rules and Regulations.</p>

                    <h4>2. Data Protection Officer</h4>
                    <p>For privacy concerns, you may contact our Data Protection Officer:<br>
                    Email: dpo@laforteza.gov.ph<br>
                    Office: Baranggay Laforteza Holdings 264 Facilities Management Office</p>

                    <h4>3. What Data We Collect</h4>
                    <ul>
                        <li>Identity Information: Name, valid ID (optional)</li>
                        <li>Contact Information: Email address, mobile number</li>
                        <li>Address Information: Street, house number (to verify residency in Baranggay Laforteza Holdings 264)</li>
                        <li>Reservation Details: Facility, date, time, purpose, number of attendees</li>
                    </ul>

                    <h4>4. Why We Collect Your Data</h4>
                    <p>We process your personal data based on your consent, legitimate government function, and legal obligations to maintain records as required by government regulations.</p>

                    <h4>5. How We Use Your Data</h4>
                    <p>Your information is used solely for verifying identity, processing reservations, communicating updates, and improving service delivery through anonymized analytics.</p>

                    <h4>6. Data Sharing</h4>
                    <p>We do not sell or share your personal data with third parties, except when required by law, to protect public safety, or with other LGU offices for official coordination.</p>

                    <h4>7. Data Retention</h4>
                    <p>Personal data is retained for 3-5 years after activity/reservation as per COA regulations and audit purposes.</p>

                    <h4>8. Your Rights</h4>
                    <p>Under the Data Privacy Act, you have the right to access, rectify, erase, object, and withdraw consent. Contact our DPO to exercise these rights.</p>
                </div>

                <form method="POST" action="">
                    <label style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 1.5rem; cursor: pointer; color: #cbd5e1;">
                        <input type="checkbox" name="agree" required style="margin-top: 4px;">
                        <span style="font-size: 0.85rem;">I have read and agree to the Terms and Conditions and Data Privacy Policy.</span>
                    </label>
                    <button type="submit" name="accept_terms" class="submit-btn">
                        I Agree & Continue <i data-feather="check-circle" style="width:16px;"></i>
                    </button>
                    <div class="signup-text">
                        Already have an account? <a href="index.php">Sign In</a>
                    </div>
                </form>

            <?php elseif($step == 1): ?>
                <!-- STEP 1 FORM -->
                <form method="POST" action="" enctype="multipart/form-data">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">First Name *</label>
                            <i data-feather="user" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="first_name" class="input-field" placeholder="Juan" required minlength="2">
                            <span style="font-size:0.7rem; color:#fca5a5; display:none;" id="fn-error">⚠️ First name must be at least 2 characters</span>
                        </div>
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Middle Name</label>
                            <i data-feather="user" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="middle_name" class="input-field" placeholder="Santos">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Last Name *</label>
                            <i data-feather="user" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="last_name" class="input-field" placeholder="Dela Cruz" required>
                        </div>
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Suffix</label>
                            <i data-feather="plus-circle" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="suffix" class="input-field" placeholder="Jr., Sr., III">
                        </div>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Email Address *</label>
                            <i data-feather="mail" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="email" name="email" class="input-field" placeholder="official@lgu.gov.ph" required>
                        </div>

                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Mobile Number</label>
                            <i data-feather="phone" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="mobile_number" class="input-field" placeholder="+63 900 000 0000" pattern="[\+]?[0-9\s\-]{10,15}">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Street *</label>
                            <i data-feather="map-pin" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <select name="street" class="input-field" required style="cursor:pointer;">
                                <option value="" style="background:#0f172a;">-- Select Street --</option>
                                <option value="Visayas Avenue">Visayas Avenue</option>
                                <option value="Tandang Sora Avenue">Tandang Sora Avenue</option>
                                <option value="Congress Extension">Congress Extension</option>
                                <option value="Cenacle Street">Cenacle Street</option>
                                <option value="Union Village">Union Village</option>
                                <option value="Tierra Bella">Tierra Bella</option>
                                <option value="Sanville">Sanville</option>
                                <option value="Philand">Philand</option>
                                <option value="Laforteza Main">Laforteza Main Street</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">House Number *</label>
                            <i data-feather="home" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="text" name="house_number" class="input-field" placeholder="123" required style="padding-left:3rem;">
                        </div>
                    </div>
                    <p style="font-size:0.7rem; color:var(--text-muted); margin-top:-0.8rem; margin-bottom:1.5rem;">Registration is limited to residents of Baranggay Laforteza Holdings 264.</p>

                    <div style="display:grid; grid-template-columns:1fr 1.2fr; gap:20px;">
                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Password *</label>
                            <i data-feather="lock" class="input-icon" style="top:calc(50% + 12px);"></i>
                            <input type="password" name="password" class="input-field" placeholder="Create a strong password" required minlength="8" id="pass-field">
                            <p style="font-size:0.65rem; color:var(--text-muted); margin-top:3px;">Min. 8 chars (A-z, 0-9)</p>
                        </div>

                        <div class="input-group">
                            <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">Upload Valid ID (Optional)</label>
                            <input type="file" name="valid_id" class="input-field" style="padding:0.7rem 1rem; height:auto;" accept=".pdf,.jpg,.jpeg,.png">
                            <p style="font-size:0.65rem; color:var(--text-muted); margin-top:3px; line-height:1.2;">
                                Activated immediately. PDF, JPG, PNG (Max 5MB).
                            </p>
                        </div>
                    </div>

                    <button type="submit" name="send_otp" class="submit-btn" style="cursor:pointer; margin-top:0.5rem; padding: 1.1rem;">
                        Continue to Verification <i data-feather="arrow-right" style="width:16px;"></i>
                    </button>
                    
                    <div class="signup-text" style="margin-top: 1rem;">
                        Already have an account? <a href="index.php">Sign In</a>
                    </div>
                </form>

            <?php elseif($step == 2): ?>
                <!-- STEP 2 FORM -->
                <form method="POST" action="">
                    <div style="text-align:center; margin-bottom:1.5rem; color:var(--text-main);">
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