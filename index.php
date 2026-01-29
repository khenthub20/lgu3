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
        else if ($password === $user['password']) {
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
    <title>Welcome | LGU3 Livelihood</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }
        
        body { font-family: 'Outfit', sans-serif; background: #0a0c10; height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        
        .login-container {
            max-width: 400px;
            padding: 3rem;
            border-radius: 24px;
            background: rgba(15, 17, 21, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
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
            color: var(--text-muted);
            margin-bottom: 2.5rem;
            font-weight: 300;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-group { margin-bottom: 1.5rem; }
        
        .input-group input {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
        }
        
        .input-group label {
            font-size: 0.85rem;
            font-weight: 400;
            color: #64748b;
        }

        .input-group input:focus {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }

        .input-group input:focus~label,
        .input-group input:not(:placeholder-shown)~label {
            top: -8px;
            color: #6366f1;
            font-weight: 500;
        }

        .login-btn {
            background: #fff;
            color: #000;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            box-shadow: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            background: #f8fafc;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Particle Canvas */
        #particle-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
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

        .divider {
            margin: 2rem 0;
            font-size: 0.75rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }

        /* Crazy Box Loader */
        .loader-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(10, 12, 16, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .crazy-box {
            width: 50px;
            height: 50px;
            background: #fff;
            animation: crazyLoader 1.5s infinite ease-in-out;
            box-shadow: 0 0 20px rgba(255,255,255,0.2);
        }

        @keyframes crazyLoader {
            0% { transform: rotate(0deg); border-radius: 8px; }
            25% { transform: scale(1.2) rotate(90deg); border-radius: 50%; background: #6366f1; }
            50% { transform: scale(0.8) rotate(180deg); border-radius: 8px; background: #a855f7; }
            75% { transform: scale(1.2) rotate(270deg); border-radius: 50%; background: #fff; }
            100% { transform: rotate(360deg); border-radius: 8px; }
        }

        .login-container {
            animation: entrance 1s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes entrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body>
    <div class="loader-overlay" id="loadingScreen">
        <div class="crazy-box"></div>
    </div>

    <canvas id="particle-canvas"></canvas>
    
    <div class="login-container">
        <div class="login-header">
            <h1>LGU3</h1>
            <p class="welcome-msg">Portal Access</p>
        </div>

        <?php if($error): ?>
            <div class="error-msg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" id="loginForm" method="POST" action="">
            <div class="input-group">
                <input type="text" id="email" name="email" required placeholder=" " autocomplete="off">
                <label for="email">Email Address</label>
            </div>
            
            <div class="input-group">
                <input type="password" id="password" name="password" required placeholder=" " autocomplete="off">
                <label for="password">Password</label>
                <button type="button" class="toggle-password" id="togglePassword" style="color:#64748b;">
                    <i data-feather="eye" style="width:16px;"></i>
                </button>
            </div>
            
            <div class="form-actions" style="margin-bottom: 2rem;">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remember">
                    <label for="remember" style="font-size: 0.8rem; color:#64748b;">Keep logged in</label>
                </div>
                <a href="#" class="forgot-password" style="font-size: 0.8rem; color:#64748b; font-weight: 400;">Recovery</a>
            </div>
            
            <button type="submit" class="login-btn">
                <span>Sign In</span>
                <i data-feather="chevron-right" style="width:16px; margin-left:8px; vertical-align:middle;"></i>
            </button>
            
            <div class="divider">
                <span>Authorized Only</span>
            </div>
            
            <p class="signup-link" style="font-size:0.85rem;">New? <a href="signup.php" style="font-weight: 600; color: #6366f1;">Join Now</a></p>
        </form>
    </div>

    <script>
        feather.replace();

        // Password Toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            toggleBtn.innerHTML = `<i data-feather="${type === 'password' ? 'eye' : 'eye-off'}" style="width:16px;"></i>`;
            feather.replace();
        });

        // Form Loader Trigger
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', () => {
            document.getElementById('loadingScreen').style.display = 'flex';
        });

        // Particle System
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        window.addEventListener('resize', resize);
        resize();

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 1.5;
                this.speedX = Math.random() * 0.2 - 0.1;
                this.speedY = Math.random() * 0.2 - 0.1;
                this.opacity = Math.random() * 0.3;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x > canvas.width) this.x = 0;
                if (this.x < 0) this.x = canvas.width;
                if (this.y > canvas.height) this.y = 0;
                if (this.y < 0) this.y = canvas.height;
            }

            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function init() {
            particles = [];
            for (let i = 0; i < 40; i++) particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }

        init();
        animate();
    </script>
</body>
</html>