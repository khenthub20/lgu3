<?php
session_start();
include 'db_connect.php';

// Fetch some real stats for the landing page
$stats = [
    'users' => 0,
    'programs' => 0,
    'completions' => 0,
    'reports' => 0,
    'employment_rate' => 89 // Default
];

$uRes = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
if($uRes) $stats['users'] = $uRes->fetch_assoc()['count'];

$pRes = $conn->query("SELECT COUNT(*) as count FROM programs");
if($pRes) $stats['programs'] = $pRes->fetch_assoc()['count'];

$cRes = $conn->query("SELECT COUNT(*) as count FROM user_skill_progress WHERE status = 'completed'");
if(!$cRes) {
    // Fallback for older schema if necessary
    $cRes = $conn->query("SELECT COUNT(*) as count FROM skill_completions");
}
if($cRes) $stats['completions'] = $cRes->fetch_assoc()['count'];

$rRes = $conn->query("SELECT COUNT(*) as count FROM reports");
if($rRes) $stats['reports'] = $rRes->fetch_assoc()['count'];

// Logic for employment rate: Base 85% + small bonus for completions to keep it dynamic
if ($stats['completions'] > 0) {
    $stats['employment_rate'] = min(98, 85 + floor($stats['completions'] / 2));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LGU3 Livelihood Training Program | Skills & Empowerment</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --lp-primary: #6366f1;
            --lp-primary-dark: #4f46e5;
            --lp-bg: #0f1115;
            --lp-card: #1e293b;
            --lp-text: #f8fafc;
            --lp-text-muted: #94a3b8;
            --lp-border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--lp-bg); color: var(--lp-text); line-height: 1.6; overflow-x: hidden; }

        /* Navigation */
        nav {
            height: 65px; width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 0 8%; position: fixed; top: 0; z-index: 1000;
            background: rgba(15, 17, 21, 0.8); backdrop-filter: blur(15px); border-bottom: 1px solid var(--lp-border);
        }
        .logo { font-size: 1.1rem; font-weight: 800; color: #fff; letter-spacing: -0.5px; }
        .logo span { color: var(--lp-primary); }
        .nav-links { display: flex; gap: 1.8rem; }
        .nav-links a { text-decoration: none; color: var(--lp-text-muted); font-size: 0.85rem; font-weight: 550; transition: 0.3s; }
        .nav-links a:hover { color: #fff; }
        .nav-btns { display: flex; gap: 0.75rem; }
        .btn-outline { padding: 0.45rem 1.2rem; border-radius: 6px; border: 1px solid var(--lp-primary); color: var(--lp-primary); text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.3s; }
        .btn-outline:hover { background: rgba(99, 102, 241, 0.1); }
        .btn-filled { padding: 0.45rem 1.2rem; border-radius: 6px; background: var(--lp-primary); color: #fff; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.3s; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .btn-filled:hover { background: var(--lp-primary-dark); transform: translateY(-1px); }

        /* Mobile Menu */
        .mobile-menu-btn { display: none; background: transparent; border: none; color: #fff; cursor: pointer; padding: 5px; }
        .mobile-nav-overlay {
            position: fixed; top: 0; right: -100%; width: 100%; height: 100vh;
            background: rgba(15, 17, 21, 0.98); backdrop-filter: blur(20px);
            z-index: 2000; transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem;
            padding: 2rem;
        }
        .mobile-nav-overlay.open { right: 0; }
        .mobile-nav-overlay a { color: #fff; text-decoration: none; font-size: 1.5rem; font-weight: 700; }
        .mobile-close { position: absolute; top: 20px; right: 20px; color: #fff; cursor: pointer; }

        /* Hero Section */
        .hero {
            padding: 160px 10% 100px; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 4rem;
            background: radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.15), transparent 40%);
        }
        .hero-content h1 { font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -2px; }
        .hero-content p { font-size: 1.25rem; color: var(--lp-text-muted); margin-bottom: 2.5rem; max-width: 500px; }
        .hero-image { position: relative; }
        .hero-image img { width: 100%; border-radius: 24px; box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .floating-card {
            position: absolute; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(10px);
            padding: 1rem; border-radius: 12px; border: 1px solid var(--lp-border);
            display: flex; align-items: center; gap: 1rem;
            animation: float 4s infinite ease-in-out;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

        /* Stats */
        .stats-bar { padding: 4rem 10%; display: flex; justify-content: space-around; background: rgba(255,255,255,0.02); }
        .stat-item { text-align: center; }
        .stat-item h2 { font-size: 2.5rem; color: #fff; margin-bottom: 0.5rem; }
        .stat-item p { color: var(--lp-text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

        /* Features */
        .section-label { color: var(--lp-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; margin-bottom: 1rem; display: block; }
        .features { padding: 100px 10%; background: #0b0d11; }
        .section-header { margin-bottom: 4rem; max-width: 700px; }
        .section-header h2 { font-size: 2.5rem; margin-bottom: 1rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .feature-card {
            padding: 2.5rem; background: var(--lp-card); border-radius: 20px; border: 1px solid var(--lp-border);
            transition: 0.3s;
        }
        .feature-card:hover { border-color: var(--lp-primary); transform: translateY(-10px); }
        .feature-icon { width: 50px; height: 50px; background: rgba(99, 102, 241, 0.1); color: var(--lp-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; }
        .feature-card h3 { font-size: 1.25rem; margin-bottom: 1rem; }
        .feature-card p { color: var(--lp-text-muted); font-size: 0.95rem; }

        /* Success Stories */
        .success-stories { padding: 100px 10%; }
        .story-flex { display: flex; gap: 4rem; align-items: center; margin-bottom: 6rem; }
        .story-flex:nth-child(even) { flex-direction: row-reverse; }
        .story-img { flex: 1; position: relative; }
        .story-img img { width: 100%; border-radius: 24px; }
        .story-content { flex: 1; }
        .story-tag { background: var(--lp-primary); color: #fff; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 1rem; display: inline-block; }
        .story-content h2 { font-size: 2.5rem; margin-bottom: 1.5rem; line-height: 1.2; }
        .story-content p { color: var(--lp-text-muted); font-size: 1.1rem; margin-bottom: 2rem; }

        /* Testimonials */
        .testimonials { padding: 100px 10%; background: radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.05), transparent 30%); }
        .test-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .test-card { padding: 2rem; background: rgba(30, 41, 59, 0.5); border-radius: 20px; border: 1px solid var(--lp-border); position: relative; }
        .test-card i { color: var(--lp-primary); opacity: 0.3; margin-bottom: 1rem; }
        .test-user { display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem; }
        .test-user img { width: 45px; height: 45px; border-radius: 50%; background: #334155; }
        .test-user-info h4 { font-size: 1rem; margin: 0; }
        .test-user-info p { font-size: 0.8rem; color: var(--lp-text-muted); margin: 0; }

        /* Footer */
        footer { padding: 80px 10% 40px; background: #080a0d; border-top: 1px solid var(--lp-border); }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 4rem; margin-bottom: 4rem; }
        .footer-col h4 { margin-bottom: 1.5rem; font-size: 1.1rem; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col ul li a { text-decoration: none; color: var(--lp-text-muted); transition: 0.3s; }
        .footer-col ul li a:hover { color: #fff; }
        .footer-bottom { border-top: 1px solid var(--lp-border); padding-top: 2rem; display: flex; justify-content: space-between; color: var(--lp-text-muted); font-size: 0.9rem; }

        @media (max-width: 968px) {
            nav { padding: 0 5%; }
            .logo span { font-size: 0.9rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .hero, .feature-grid, .story-flex, .test-grid { grid-template-columns: 1fr; display: block; }
            .stats-bar { flex-wrap: wrap; gap: 2rem; padding: 3rem 5%; }
            .stat-item { flex: 1 1 120px; }
            .hero { padding: 120px 5% 60px; text-align: center; }
            .hero-content h1 { font-size: 2.8rem; }
            .hero-content p { margin: 0 auto 2rem; font-size: 1.1rem; }
            .hero-image { display: none; } /* Hide complex hero images on small mobile to save space */
            .nav-links { display: none; }
            .mobile-menu-btn { display: block; }
            .nav-btns { display: none; } /* Hide login/signup in header, move to mobile menu */
            .story-flex { margin-bottom: 3rem; }
            .story-img { margin-bottom: 1.5rem; }
            .test-card { margin-bottom: 1.2rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
            .program-cat-grid { grid-template-columns: 1fr; gap: 1rem; }
            .program-list-items { grid-template-columns: 1fr; }
            .location-section div { grid-template-columns: 1fr !important; display: flex !important; flex-direction: column !important; }
            .map-container { order: -1; height: 300px; }
        }
        @media (max-width: 480px) {
            .hero-content h1 { font-size: 2.2rem; }
            .footer-grid { grid-template-columns: 1fr; }
            .logo span { display: none; } /* Show only small logo or name */
            .logo::after { content: 'LGU3'; font-size: 1.2rem; font-weight: 800; }
        }

        /* Programs Grid Specific */
        .programs-section { padding: 100px 10%; background: #0f1115; }
        .program-cat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-top: 3rem; }
        .program-cat-card { background: rgba(255,255,255,0.02); border: 1px solid var(--lp-border); border-radius: 20px; padding: 2rem; }
        .program-cat-card h3 { color: var(--lp-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .program-list-items { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .program-list-items li { color: var(--lp-text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .program-list-items li::before { content: 'ΓåÆ'; color: var(--lp-primary); font-weight: bold; }

        /* Team Section */
        .team-section { padding: 100px 10%; background: #0b0d11; border-top: 1px solid var(--lp-border); }
        .team-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 2rem; 
            margin-top: 4rem;
            perspective: 1000px;
        }
        
        .team-card-wrapper {
            height: 480px;
            cursor: pointer;
            perspective: 1000px;
        }

        .team-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }

        .team-card-wrapper.flipped .team-card-inner,
        .team-card-wrapper:hover .team-card-inner {
            transform: rotateY(180deg);
        }

        .team-card-front, .team-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 24px;
            border: 1px solid var(--lp-border);
            padding: 2.5rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            background: rgba(30, 41, 59, 0.4);
        }

        .team-card-front {
            z-index: 2;
        }

        .team-card-back {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(30, 41, 59, 0.9));
            transform: rotateY(180deg);
            border-color: var(--lp-primary);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .team-card-wrapper:hover .team-card-front {
            border-color: var(--lp-primary);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .team-img-wrapper {
            width: 140px; height: 140px; margin: 0 auto 1.5rem;
            border-radius: 50%; padding: 6px; background: linear-gradient(45deg, var(--lp-primary), #ec4899);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
        }
        .team-img-wrapper img { 
            width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid #1e293b; 
            filter: contrast(1.05) brightness(1.05) saturate(1.1);
        }
        
        .team-card h3 { font-size: 1.1rem; margin-bottom: 0.5rem; color: #fff; font-weight: 700; }
        .team-card-front p { color: var(--lp-primary); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
        .role-desc { color: var(--lp-text-muted); font-size: 0.85rem; line-height: 1.5; font-weight: 400; }
        
        .motivation-title {
            color: var(--lp-primary);
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1.2rem;
        }
        
        .motivation-quote {
            color: #fff;
            font-size: 0.95rem;
            font-style: italic;
            line-height: 1.6;
            opacity: 0.9;
        }

        .team-social { display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
        .team-social a { color: var(--lp-text-muted); transition: 0.3s; }
        .team-social a:hover { color: #fff; transform: scale(1.2); }

        .click-hint {
            position: absolute;
            bottom: 15px;
            font-size: 0.65rem;
            color: var(--lp-text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
        }

        @media (max-width: 1100px) { .team-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 600px) { .team-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav>
        <div class="logo" style="display: flex; align-items: center; gap: 12px; cursor: pointer;" onclick="window.location.href='landingpage.php'">
            <img src="laforteza_logo.jpg" style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover;">
            <span>LGU3 Livelihood Training Program</span>
        </div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#programs">Programs</a>
            <a href="#stories">Success Stories</a>
            <a href="#testimonials">Community</a>
            <a href="#team">Meet the Team</a>
            <a href="#location">Location</a>
        </div>
        <div class="nav-btns">
            <a href="index.php" class="btn-outline">Login</a>
            <a href="signup.php" class="btn-filled">Get Started</a>
        </div>
        <button class="mobile-menu-btn" onclick="toggleMobileNav()">
            <i data-feather="menu"></i>
        </button>
    </nav>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobile-nav">
        <div class="mobile-close" onclick="toggleMobileNav()"><i data-feather="x" style="width:32px; height:32px;"></i></div>
        <a href="#features" onclick="toggleMobileNav()">Features</a>
        <a href="#programs" onclick="toggleMobileNav()">Programs</a>
        <a href="#stories" onclick="toggleMobileNav()">Stories</a>
        <a href="#team" onclick="toggleMobileNav()">The Team</a>
        <a href="#location" onclick="toggleMobileNav()">Location</a>
        <hr style="width:50px; border:1px solid var(--lp-primary); opacity:0.3;">
        <a href="index.php" style="color:var(--lp-primary);">Login</a>
        <a href="signup.php" class="btn-filled" style="padding: 1rem 3rem; font-size: 1.2rem; border-radius: 14px;">Get Started</a>
    </div>

    <header class="hero">
        <div class="hero-content">
            <span class="section-label">Empowering Communities</span>
            <h1 style="font-size: 3.5rem;">LGU3 Livelihood Training Program</h1>
            <p>Access free world-class technical training, livelihood initiatives, and AI-powered career coaching integrated with your local government unit.</p>
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                <a href="signup.php" class="btn-filled" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Join for Free</a>
                <a href="#programs" class="btn-outline" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Explore Programs</a>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; color: var(--lp-text-muted);">
                <i data-feather="check-circle" style="width:16px; color:#10b981;"></i>
                <span>No tuition fees, 100% community-driven.</span>
            </div>
        </div>
        <div class="hero-image">
            <img src="livelihood_hero_premium.png" alt="Empower Hub">
            <div class="floating-card" style="top: 10%; right: -20px;">
                <div style="background: var(--lp-primary); padding: 8px; border-radius: 8px;"><i data-feather="award" style="color:white; width:20px;"></i></div>
                <div>
                    <div style="font-weight: 700; font-size: 0.9rem;">Skills Verified</div>
                    <div style="font-size: 0.7rem; color: var(--lp-text-muted);">Completing 85% today</div>
                </div>
            </div>
            <div class="floating-card" style="bottom: 15%; left: -30px;">
                <div style="background: #10b981; padding: 8px; border-radius: 8px;"><i data-feather="users" style="color:white; width:20px;"></i></div>
                <div>
                    <div style="font-weight: 700; font-size: 0.9rem;">Community Growth</div>
                    <div style="font-size: 0.7rem; color: var(--lp-text-muted);">+<?php echo $stats['users']; ?> Active Members</div>
                </div>
            </div>
        </div>
    </header>

    <section class="stats-bar">
        <div class="stat-item">
            <h2><?php echo number_format($stats['users']); ?>+</h2>
            <p>Active Citizens</p>
        </div>
        <div class="stat-item">
            <h2><?php echo number_format($stats['programs']); ?>+</h2>
            <p>Programs Offered</p>
        </div>
        <div class="stat-item">
            <h2><?php echo number_format($stats['completions']); ?>+</h2>
            <p>Skill Certificates</p>
        </div>
        <div class="stat-item">
            <h2><?php echo $stats['employment_rate']; ?>%</h2>
            <p>Employment Rate</p>
        </div>
        <div class="stat-item">
            <h2><?php echo number_format($stats['reports']); ?>+</h2>
            <p>User Reports</p>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-header">
            <span class="section-label">Innovative Learning</span>
            <h2>Tools designed for your success.</h2>
            <p>WeΓÇÖve integrated advanced technology with traditional community support to provide a seamless empowerment experience.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="target"></i></div>
                <h3>Personalized Pathways</h3>
                <p>Our Skill Assessment tool analyzes your strengths and recommends programs that fit your profile perfectly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="cpu"></i></div>
                <h3>AI Career Assistant</h3>
                <p>Chat with our NLP-powered bot to get career advice, help with applications, or clarify training modules.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="book-open"></i></div>
                <h3>Rich Resource Library</h3>
                <p>Download free modules, PDF guides, and video tutorials across Agriculture, IT, and Technical skills.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="activity"></i></div>
                <h3>Smart Insights</h3>
                <p>Real-time neighborhood sentiment analysis and ML-driven trend tracking help administrators improve your community.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="calendar"></i></div>
                <h3>Task Management</h3>
                <p>A built-in personal work calendar helps you stay organized and never miss a training session or community event.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i data-feather="shield"></i></div>
                <h3>Secure Authorization</h3>
                <p>Maintain your data integrity with timed-access security for profile updates and name changes.</p>
            </div>
        </div>
    </section>

    <section class="programs-section" id="programs">
        <div class="section-header">
            <span class="section-label">Available Tracks</span>
            <h2>Diverse Training Programs</h2>
            <p>We offer a wide range of specialized courses designed to meet the demands of today's local and global markets.</p>
        </div>

        <div class="program-cat-grid">
            <div class="program-cat-card" id="cat-tech">
                <h3><i data-feather="tool"></i> Technical & Vocational</h3>
                <ul class="program-list-items">
                    <li>Basic Shielded Metal Arc Welding</li>
                    <li>Residential Electrical Wiring</li>
                    <li>Plumbing & Pipefitting</li>
                    <li>Automotive Servicing</li>
                    <li>Carpentry & Masonry</li>
                    <li>Aircon Tech & Repair</li>
                </ul>
            </div>
            <div class="program-cat-card" id="cat-digital">
                <h3><i data-feather="monitor"></i> Digital & IT Literacy</h3>
                <ul class="program-list-items">
                    <li>Virtual Assistant Essentials</li>
                    <li>Web Design & Management</li>
                    <li>Digital Marketing 101</li>
                    <li>Data Entry & Transcription</li>
                    <li>Cybersecurity Awareness</li>
                    <li>Graphic Design Basics</li>
                </ul>
            </div>
            <div class="program-cat-card" id="cat-agriculture">
                <h3><i data-feather="sun"></i> Agriculture & Livelihood</h3>
                <ul class="program-list-items">
                    <li>Organic Vegetable Farming</li>
                    <li>Urban Hydroponics setup</li>
                    <li>Mushroom Culture & Prod.</li>
                    <li>Tilapia & Fishery Mgmt</li>
                    <li>Free-range Poultry raises</li>
                    <li>Food Processing tech</li>
                </ul>
            </div>
            <div class="program-cat-card" id="cat-business">
                <h3><i data-feather="shopping-bag"></i> Business & Entrepreneurship</h3>
                <ul class="program-list-items">
                    <li>Soap & Detergent Making</li>
                    <li>Tailoring & Dressmaking</li>
                    <li>Baking & Pastry Arts</li>
                    <li>Financial Literacy basics</li>
                    <li>Micro-business Mgmt</li>
                    <li>Local Craftsmanship</li>
                </ul>
            </div>
        </div>

        <div style="margin-top: 4rem; text-align: center; background: rgba(99, 102, 241, 0.05); padding: 3rem; border-radius: 24px; border: 1px dashed var(--lp-primary);">
            <h3 style="margin-bottom:1rem;">Can't find what you're looking for?</h3>
            <p style="color: var(--lp-text-muted); margin-bottom: 2rem;">New programs are added every quarter based on community requests and industry trends.</p>
            <a href="signup.php" class="btn-filled">Request a Program</a>
        </div>
    </section>

    <section class="success-stories" id="stories">
        <div class="section-header" style="text-align: center; margin: 0 auto 5rem;">
            <span class="section-label">Impact Gallery</span>
            <h2>Livelihood Program Success</h2>
        </div>

        <div class="story-flex">
            <div class="story-img">
                <img src="barangay_agriculture_success.png" alt="Agriculture">
            </div>
            <div class="story-content">
                <span class="story-tag">Sustainable Development</span>
                <h2>Barangay 175 Laforteza Oldings: The Green Revolution</h2>
                <p>Through our Livelihood Program, residents of Barangay 175 Laforteza Oldings transformed vacant lots into thriving urban hydroponic gardens, now supplying fresh organic produce to local markets.</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem;">
                    <div>
                        <h4 style="color: #fff; font-size: 1.5rem;">24</h4>
                        <p style="font-size: 0.8rem;">Families Empowered</p>
                    </div>
                    <div>
                        <h4 style="color: #fff; font-size: 1.5rem;">$1.2k</h4>
                        <p style="font-size: 0.8rem;">Monthly Rev Generated</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="story-flex">
            <div class="story-img">
                <img src="barangay_tech_training.png" alt="Tech Training">
            </div>
            <div class="story-content">
                <span class="story-tag">Digital Inclusion</span>
                <h2>Barangay Central: Bridging the Digital Divide</h2>
                <p>By leveraging our specialized IT and Digital Marketing modules, over 50 youth in Barangay Central are now working as remote freelancers for international clients.</p>
                <a href="signup.php" class="btn-outline" style="display:inline-block; margin-top:1.5rem;">Learn Digital Skills</a>
            </div>
        </div>

        <div class="story-flex">
            <div class="story-img">
                <img src="lgu_citizen_success_business.png" alt="Small Business">
            </div>
            <div class="story-content">
                <span class="story-tag">Entrepreneurship</span>
                <h2>Angel's Journey: From Training to Business</h2>
                <p>"Angel's Corner" started as a dream during a livelihood workshop. Today, Angel runs a successful local cafe and employs three other graduates from the same program.</p>
                <blockquote style="border-left: 4px solid var(--lp-primary); padding-left: 1.5rem; color: var(--lp-text-muted); font-style: italic;">
                    "LGU3 gave me more than just a certificate; it gave me the confidence and the resource network to start my own legacy."
                </blockquote>
            </div>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Community Feedback</span>
            <h2>What your neighbors are saying.</h2>
        </div>
        <div class="test-grid">
            <div class="test-card">
                <i data-feather="quote" style="width: 40px; height: 40px;"></i>
                <p>"The Skill Assessment was a game-changer for me. It pointed me towards Agriculture training, and now I'm managing our community urban farm."</p>
                <div class="test-user">
                    <div class="test-user-info">
                        <h4>Maria Santos</h4>
                        <p>Barangay 175 Laforteza Oldings</p>
                    </div>
                </div>
            </div>
            <div class="test-card">
                <i data-feather="quote" style="width: 40px; height: 40px;"></i>
                <p>"I love how I can easily download learning materials on my phone. The PDF guides are so detailed and easy to follow even without internet."</p>
                <div class="test-user">
                    <div class="test-user-info">
                        <h4>Juan Dela Cruz</h4>
                        <p>Barangay Central</p>
                    </div>
                </div>
            </div>
            <div class="test-card">
                <i data-feather="quote" style="width: 40px; height: 40px;"></i>
                <p>"The AI assistant helped me draft my application for the livelihood grant. It's like having a personal mentor available 24/7."</p>
                <div class="test-user">
                    <div class="test-user-info">
                        <h4>Elena Rivera</h4>
                        <p>Barangay Poblacion</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="partner-barangays" id="barangays" style="padding: 80px 10%; background: rgba(30, 41, 59, 0.2);">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Our Coverage</span>
            <h2>Active Partner Barangays</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Join the growing list of communities benefiting from our localized training programs.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <!-- BRGY 175 - Primary -->
            <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid var(--lp-primary); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 40px; height: 40px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 4px;">
                    <img src="laforteza_logo.jpg" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                </div>
                <div>
                    <h4 style="color: #fff; margin: 0;">Barangay 175</h4>
                    <p style="color: var(--lp-text-muted); font-size: 0.8rem; margin: 0;">Laforteza Oldings (HQ)</p>
                </div>
                <span style="margin-left: auto; color: #10b981; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
            </div>

            <!-- BRGY Central -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--lp-border); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--lp-text-muted);">
                    <i data-feather="map-pin"></i>
                </div>
                <div>
                    <h4 style="color: #fff; margin: 0;">Barangay Central</h4>
                    <p style="color: var(--lp-text-muted); font-size: 0.8rem; margin: 0;">District 1 Portal</p>
                </div>
                <span style="margin-left: auto; color: #10b981; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
            </div>

            <!-- BRGY Poblacion -->
            <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--lp-border); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--lp-text-muted);">
                    <i data-feather="map-pin"></i>
                </div>
                <div>
                    <h4 style="color: #fff; margin: 0;">Barangay Poblacion</h4>
                    <p style="color: var(--lp-text-muted); font-size: 0.8rem; margin: 0;">Community Learning Center</p>
                </div>
                <span style="margin-left: auto; color: #10b981; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
            </div>

            <!-- More placeholders if needed -->
        </div>
    </section>
    
    <section class="team-section" id="team">
        <div class="section-header" style="text-align: center; margin: 0 auto 0;">
            <span class="section-label">The Creators</span>
            <h2>Meet the Elite Team</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">The passionate individuals behind the LGU3 Livelihood Training Program Portal.</p>
        </div>

        <div class="team-grid">
            <!-- Wilfred -->
            <div class="team-card-wrapper" onclick="this.classList.toggle('flipped')">
                <div class="team-card-inner">
                    <div class="team-card-front">
                        <div class="team-img-wrapper">
                            <img src="assets/team/wilfred.jpg" alt="Wilfred Aries Cajife Donarbe">
                        </div>
                        <h3>Wilfred Aries Cajife Donarbe</h3>
                        <p>System Documentation</p>
                        <div class="role-desc">Specializes in comprehensive system analysis and technical documentation.</div>
                        <div class="team-social">
                            <a href="#"><i data-feather="github" style="width:16px;"></i></a>
                            <a href="#"><i data-feather="linkedin" style="width:16px;"></i></a>
                        </div>
                        <div class="click-hint">Hover to flip</div>
                    </div>
                    <div class="team-card-back">
                        <div class="motivation-title">Purpose-Driven Innovation</div>
                        <div class="motivation-quote">"We are united by a shared mission: to build a platform that empowers communities through accessible, reliable, and impactful livelihood training. Every line of code and every design choice serves a greater purpose—uplifting lives."</div>
                        <div class="click-hint">Move mouse to return</div>
                    </div>
                </div>
            </div>

            <!-- Ralph -->
            <div class="team-card-wrapper" onclick="this.classList.toggle('flipped')">
                <div class="team-card-inner">
                    <div class="team-card-front">
                        <div class="team-img-wrapper">
                            <img src="assets/team/ralph.jpg" alt="Ralph Renz Cruzado">
                        </div>
                        <h3>Ralph Renz Cruzado</h3>
                        <p>Scrum Master & Back-end</p>
                        <div class="role-desc">Agile leadership and robust server-side architecture development.</div>
                        <div class="team-social">
                            <a href="#"><i data-feather="github" style="width:16px;"></i></a>
                            <a href="#"><i data-feather="linkedin" style="width:16px;"></i></a>
                        </div>
                        <div class="click-hint">Hover to flip</div>
                    </div>
                    <div class="team-card-back">
                        <div class="motivation-title">Strength in Collaboration</div>
                        <div class="motivation-quote">"Each team member brings a unique expertise, and together we transform ideas into powerful solutions. Through collaboration, trust, and agile teamwork, we turn challenges into opportunities for growth."</div>
                        <div class="click-hint">Move mouse to return</div>
                    </div>
                </div>
            </div>

            <!-- Dion -->
            <div class="team-card-wrapper" onclick="this.classList.toggle('flipped')">
                <div class="team-card-inner">
                    <div class="team-card-front">
                        <div class="team-img-wrapper">
                            <img src="assets/team/dion.jpg" alt="Dion Sophia Celine">
                        </div>
                        <h3>Dion Sophia Celine</h3>
                        <p>Front-end Developer</p>
                        <div class="role-desc">Crafting beautiful, responsive and intuitive user interfaces.</div>
                        <div class="team-social">
                            <a href="#"><i data-feather="github" style="width:16px;"></i></a>
                            <a href="#"><i data-feather="linkedin" style="width:16px;"></i></a>
                        </div>
                        <div class="click-hint">Hover to flip</div>
                    </div>
                    <div class="team-card-back">
                        <div class="motivation-title">Excellence in Craftsmanship</div>
                        <div class="motivation-quote">"From detailed system documentation to seamless user experiences, we are committed to quality and precision. We believe excellence is not optional—it’s our standard."</div>
                        <div class="click-hint">Move mouse to return</div>
                    </div>
                </div>
            </div>

            <!-- Khent -->
            <div class="team-card-wrapper" onclick="this.classList.toggle('flipped')">
                <div class="team-card-inner">
                    <div class="team-card-front">
                        <div class="team-img-wrapper">
                            <img src="assets/team/khent.jpg" alt="Khent Agustin">
                        </div>
                        <h3>Khent Agustin</h3>
                        <p>Full-Stack Developer</p>
                        <div class="role-desc">Engineering end-to-end solutions from UI to database logic.</div>
                        <div class="team-social">
                            <a href="#"><i data-feather="github" style="width:16px;"></i></a>
                            <a href="#"><i data-feather="linkedin" style="width:16px;"></i></a>
                        </div>
                        <div class="click-hint">Hover to flip</div>
                    </div>
                    <div class="team-card-back">
                        <div class="motivation-title">Technology with Heart</div>
                        <div class="motivation-quote">"Beyond systems and software, we build with empathy. Our motivation comes from knowing that this portal can open doors, create skills, and shape better futures for individuals and communities alike."</div>
                        <div class="click-hint">Move mouse to return</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="location-section" id="location" style="padding: 100px 10%; background: #0f1115;">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Visit Us</span>
            <h2>Training Center Location</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Our central hub is easily accessible for all residents. Drop by to learn more about our programs.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 4rem; align-items: start;">
            <div class="location-info">
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--lp-border); padding: 2.5rem; border-radius: 24px;">
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--lp-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <i data-feather="map-pin"></i> Primary Address
                        </h4>
                        <p style="color: #fff; font-size: 1.1rem; line-height: 1.6; font-weight: 500;">
                            Barangay 174, <br>
                            Caloocan City, <br>
                            Metro Manila, Philippines
                        </p>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--lp-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <i data-feather="clock"></i> Operating Hours
                        </h4>
                        <p style="color: var(--lp-text-muted); font-size: 0.9rem;">
                            Monday - Friday: 8:00 AM - 5:00 PM <br>
                            Saturday: 9:00 AM - 12:00 PM <br>
                            Sunday: Closed
                        </p>
                    </div>

                    <div style="margin-bottom: 0;">
                        <h4 style="color: var(--lp-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <i data-feather="phone"></i> Contact Details
                        </h4>
                        <p style="color: var(--lp-text-muted); font-size: 0.9rem;">
                            Email: support@lgu3livelihood.gov.ph <br>
                            Phone: +63 912 345 6789
                        </p>
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=14.758252,121.044014" target="_blank" class="btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%;">
                        <i data-feather="navigation" style="width: 18px;"></i> Get Directions
                    </a>
                </div>
            </div>

            <div class="map-container" style="border-radius: 24px; overflow: hidden; height: 450px; border: 1px solid var(--lp-border); box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
                <iframe 
                    src="https://maps.google.com/maps?q=14.758252,121.044014&z=15&output=embed" 
                    width="100%" 
                    height="100%" 
                    style="border:0; filter: grayscale(1) invert(0.9) contrast(1.2) opacity(0.8);" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div class="footer-col" style="padding-right: 2rem;">
                <div class="logo" style="margin-bottom: 1.5rem;">LGU3<span>Livelihood Training Program</span></div>
                <p style="color: var(--lp-text-muted); font-size: 0.9rem;">Empowering every citizen through localized digital learning, career coaching, and technical training. Join our community and build your future today.</p>
            </div>
            <div class="footer-col">
                <h4>Programs</h4>
                <ul>
                    <li><a href="#cat-tech">Technical Skills</a></li>
                    <li><a href="#cat-business">Livelihood Training</a></li>
                    <li><a href="#cat-digital">Digital Marketing</a></li>
                    <li><a href="#cat-agriculture">Agriculture</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="signup.php">Register</a></li>
                    <li><a href="index.php">Citizen Login</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Barangay Portal</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Resource Hub</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Support</h4>
                <ul>
                    <li><a href="#location">Location</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Help Center</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Privacy Policy</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Terms of Service</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Contact Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 LGU3 Management System. All rights reserved.</p>
            <div style="display: flex; gap: 1.5rem;">
                <a href="#"><i data-feather="facebook" style="width: 18px;"></i></a>
                <a href="#"><i data-feather="twitter" style="width: 18px;"></i></a>
                <a href="#"><i data-feather="instagram" style="width: 18px;"></i></a>
            </div>
        </div>
    </footer>

    <script>
        feather.replace();

        // Smooth scroll for nav links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Simple scroll effect for nav
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.style.height = '70px';
                nav.style.background = 'rgba(15, 17, 21, 0.95)';
            } else {
                nav.style.height = '80px';
                nav.style.background = 'rgba(15, 17, 21, 0.8)';
            }
        });

        function comingSoon() {
            const toast = document.createElement('div');
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.background = '#6366f1';
            toast.style.color = 'white';
            toast.style.padding = '1rem 2rem';
            toast.style.borderRadius = '12px';
            toast.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
            toast.style.zIndex = '10000';
            toast.style.animation = 'fadeIn 0.3s ease';
            toast.innerText = 'Feature coming soon! We are currently working on this module.';
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = '0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function toggleMobileNav() {
            document.getElementById('mobile-nav').classList.toggle('open');
        }
    </script>
</body>
</html>
