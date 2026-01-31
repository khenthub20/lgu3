<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Secure query
    $stmt = $conn->prepare("SELECT id, full_name, password, role, is_active FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if account is deactivated
        if ($user['is_active'] == 0) {
            $error = "Your account has been deactivated. Please contact administration.";
        } 
        // In a real app, use password_verify($password, $user['password'])
        else if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

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
            height: 100vh;
            display: flex;
            overflow: hidden;
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
        
        /* Dark overlay for text readability */
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

        /* --- RIGHT SIDE: LOGIN --- */
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

        /* Flying Elements (Floating Icons) */
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
            max-width: 420px;
            z-index: 10;
            animation: fadeIn 1s ease-out;
        }

        .brand-header {
            margin-bottom: 2.5rem;
            text-align: center;
        }

        .brand-logo {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .input-group { position: relative; margin-bottom: 1.5rem; }
        
        .input-field {
            width: 100%;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1.1rem 1rem 1.1rem 3rem;
            color: #fff;
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

        /* Responsive */
        @media (max-width: 900px) {
            .slider-section { display: none; }
            .login-section { flex: 1; border: none; }
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
            <h1 class="slider-title" id="slider-title">Barangay 175<br>Holdings Inc.</h1>
            <p class="slider-desc" id="slider-desc">Empowering our community through sustainable livelihood programs and advanced digital training.</p>
            <div style="display:flex; gap:0.5rem;">
                <span style="width:12px; height:12px; background:#fff; border-radius:50%; opacity:1;"></span>
                <span style="width:12px; height:12px; background:#fff; border-radius:50%; opacity:0.3;"></span>
                <span style="width:12px; height:12px; background:#fff; border-radius:50%; opacity:0.3;"></span>
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
                    <i data-feather="shield" style="color:#fff; width:32px; height:32px;"></i>
                </div>
                <h2 style="font-size:2rem; font-weight:700; margin-bottom:0.5rem; color:#fff;">Welcome Back</h2>
                <p style="color:var(--text-muted);">Access your professional livelihood portal</p>
            </div>

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
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        // Slider Logic
        const slides = document.querySelectorAll('.slider-bg');
        const titles = [
            'Barangay 175<br>Holdings Inc.',
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
