<?php
session_start();
include 'db_connect.php';

// Email Configuration (Same as signup)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'khentcorpuz71@gmail.com');
define('SMTP_PASS', 'edqj nqsx pvgb ffph');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function sendRefEmail($to, $name, $refId) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = trim(SMTP_USER);
        $mail->Password = trim(SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            ]
        ];

        $mail->setFrom(SMTP_USER, 'LGU3 Support');
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to LGU3 - Your Reference ID';
        $mail->Body = "
        <div style='font-family:Arial,sans-serif; padding:20px; background:#f4f4f4;'>
            <div style='background:white; padding:30px; border-radius:10px; max-width:500px; margin:0 auto;'>
                <h2 style='color:#6366f1; text-align:center;'>Welcome to LGU3 Portal!</h2>
                <p>Hello <strong>$name</strong>,</p>
                <p>Congratulations on your first login! You are now a verified member of our digital livelihood system.</p>
                <p>Please keep your Permanent Reference ID safe:</p>
                <div style='background:#f8fafc; padding:15px; text-align:center; font-size:24px; font-weight:bold; color:#0f172a; border:1px dashed #6366f1; border-radius:8px; margin:20px 0;'>
                    $refId
                </div>
                <p>You will use this ID for official transactions and program applications.</p>
                <p style='color:#64748b; font-size:12px; text-align:center; margin-top:20px;'>Baranggay Laforteza Holdings 264</p>
            </div>
        </div>";
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

$error = '';
$success_msg = '';

if(isset($_GET['verified'])) {
    $success_msg = "Account Verified! Please login to receive your Reference ID.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Secure query including is_first_login and reference_id
    $stmt = $conn->prepare("SELECT id, full_name, password, role, is_active, is_first_login, reference_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['is_active'] == 0) {
            $error = "Your account has been deactivated. Please contact administration.";
        } 
        else if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // First Login Logic
            if ($user['role'] === 'user' && isset($user['is_first_login']) && $user['is_first_login'] == 1) {
                // Send Email
                sendRefEmail($email, $user['full_name'], $user['reference_id']);
                // Update DB
                $conn->query("UPDATE users SET is_first_login = 0 WHERE id = " . $user['id']);
                // Optional: Store flag in session to show 'Welcome' modal in dashboard
                $_SESSION['show_welcome_ref'] = $user['reference_id'];
            }

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU3 Portal Access</title>
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
        
        /* Dark overlay for text readability */
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

        /* --- RIGHT SIDE: LOGIN --- */
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

        /* Flying Elements (Floating Icons) */
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
            max-width: 420px;
            z-index: 10;
            animation: fadeIn 1s ease-out;
            max-height: 90vh; /* Prevent vertical overflow */
            overflow-y: auto;
            padding: 10px;
        }
        .login-card::-webkit-scrollbar { width: 4px; }
        .login-card::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

        .brand-header {
            margin-bottom: 2.5rem;
            text-align: center;
        }

        .brand-logo {
            width: 70px; height: 70px;
            background: #ffffff;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .input-group { position: relative; margin-bottom: 1.5rem; }
        
        .input-field {
            width: 100%;
            background: #f8fafc;
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1.1rem 1rem 1.1rem 3rem;
            color: var(--text-main);
            font-size: 1rem;
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
        }

        .input-field:focus ~ .input-icon { color: var(--primary); }

        .submit-btn {
            width: 100%;
            padding: 1.1rem;
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(99, 102, 241, 0.5);
        }

        .signup-text {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .signup-text a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .explore-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 1.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 99px;
            background: rgba(0,0,0,0.03);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .explore-btn:hover {
            background: rgba(99, 102, 241, 0.08);
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 900px) {
            body { height: auto; }
            .slider-section { display: none; }
            .login-section { flex: 1; border: none; min-height: 100vh; padding: 2rem 1rem; }
            .login-card { max-height: none; overflow: visible; }
        }
        @media (max-width: 400px) {
            .brand-header h2 { font-size: 1.5rem; }
            .brand-logo { width: 50px; height: 50px; margin-bottom: 1rem; }
            .input-field { padding: 0.9rem 1rem 0.9rem 2.8rem; font-size: 0.9rem; }
        }

        @keyframes slideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }
        @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
        
        .error-banner {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(239, 68, 68, 0.2);
            font-size: 0.9rem;
        }
        .success-banner {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            border: 1px solid rgba(16, 185, 129, 0.2);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    <!-- LEFT SIDE: SLIDER -->
    <div class="slider-section">
        <div class="slider-overlay"></div>
        <!-- Generated Images -->
        <div class="slider-bg active" style="background-image: url('barangay_hq.png');"></div>
        <div class="slider-bg" style="background-image: url('training_success.png');"></div>
        <div class="slider-bg" style="background-image: url('livelihood_hero_premium.png');"></div>

        <div class="slider-content">
            <h1 class="slider-title" id="slider-title">Baranggay<br>Laforteza Holdings 264</h1>
            <p class="slider-desc" id="slider-desc">Empowering our community through sustainable livelihood programs and advanced digital training.</p>
            <div style="display:flex; gap:0.5rem;">
                <span style="width:12px; height:12px; background:var(--primary); border-radius:50%; opacity:1;"></span>
                <span style="width:12px; height:12px; background:var(--primary); border-radius:50%; opacity:0.3;"></span>
                <span style="width:12px; height:12px; background:var(--primary); border-radius:50%; opacity:0.3;"></span>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE: LOGIN -->
    <div class="login-section">
        <!-- Floating Elements -->
        <i data-feather="briefcase" class="flying-icon" style="left:10%; width:40px; height:40px; animation-duration:15s;"></i>
        <i data-feather="trending-up" class="flying-icon" style="left:80%; width:30px; height:30px; animation-duration:12s; animation-delay:2s;"></i>
        <i data-feather="award" class="flying-icon" style="left:40%; width:50px; height:50px; animation-duration:18s; animation-delay:5s;"></i>
        <i data-feather="code" class="flying-icon" style="left:70%; width:35px; height:35px; animation-duration:14s; animation-delay:1s;"></i>

        <div class="login-card">
            <div class="brand-header">
                <div class="brand-logo">
                    <img src="laforteza_logo.jpg" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <h2 style="font-size:2rem; font-weight:700; margin-bottom:0.5rem; color:var(--text-main);">Welcome Back</h2>
                <p style="color:var(--text-muted);">Access your professional livelihood portal</p>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div class="success-banner">
                    <i data-feather="check-circle" style="width:16px; vertical-align:middle; margin-right:5px;"></i>
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="error-banner">
                    <i data-feather="alert-circle" style="width:16px; vertical-align:middle; margin-right:5px;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-group">
                    <i data-feather="mail" class="input-icon"></i>
                    <input type="email" name="email" class="input-field" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i data-feather="lock" class="input-icon"></i>
                    <input type="password" name="password" class="input-field" placeholder="Password" required>
                </div>

                <button type="submit" class="submit-btn" id="loginBtn">Sign In</button>
            </form>

            <div class="signup-text">
                Don't have an account? <a href="signup.php">Create Account</a>
                
                <div>
                    <a href="landingpage.php" class="explore-btn">
                        <i data-feather="globe" style="width: 14px; height: 14px;"></i>
                        <span>Explore Landing Page</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        // Slider Logic
        const slides = document.querySelectorAll('.slider-bg');
        const titles = [
            'Baranggay<br>Laforteza Holdings 264',
            'Skills for<br>Success',
            'Community<br>Growth'
        ];
        const descs = [
            'Empowering our community through sustainable livelihood programs and advanced digital training.',
            'Join thousands of citizens learning new skills in tailoring, tech, and agriculture.',
            'Building a stronger future together through innovation and shared opportunity.'
        ];
        
        const titleEl = document.getElementById('slider-title');
        const descEl = document.getElementById('slider-desc');
        let current = 0;

        function nextSlide() {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
            
            // Text Animation
            titleEl.style.opacity = 0;
            descEl.style.opacity = 0;
            setTimeout(() => {
                titleEl.innerHTML = titles[current];
                descEl.innerHTML = descs[current];
                titleEl.style.opacity = 1;
                descEl.style.opacity = 1;
            }, 300);
        }

        setInterval(nextSlide, 5000);
    </script>
</body>
</html>
