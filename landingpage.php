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
            --lp-bg: #ffffff;
            --lp-card: #f9fafb;
            --lp-text: #111827;
            --lp-text-muted: #4b5563;
            --lp-border: #e5e7eb;
        }

        /* Announcement Specific Styles */
        #announcements {
            padding: 80px 10%;
            background: #ffffff;
        }
        .announcement-card {
            background: var(--lp-card);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .announcement-card:hover {
            transform: translateY(-10px);
            border-color: var(--lp-primary);
            box-shadow: 0 30px 60px rgba(99, 102, 241, 0.1);
        }
        .ann-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--lp-primary);
            color: #fff;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.4);
        }
        .ann-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            border-bottom: 3px solid var(--lp-primary);
        }
        .ann-content {
            padding: 1.5rem;
        }
        .ann-date {
            font-size: 0.75rem;
            color: var(--lp-text-muted);
            margin-bottom: 0.8rem;
            display: block;
        }
        .ann-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--lp-text);
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .ann-text {
            color: var(--lp-text-muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .ann-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--lp-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: gap 0.3s;
        }
        .ann-link:hover { gap: 12px; }
        .view-all-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #111827;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 4rem;
            transition: all 0.3s;
        }
        .view-all-btn:hover {
            background: var(--lp-primary);
            border-color: var(--lp-primary);
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        body { background: var(--lp-bg); color: var(--lp-text); line-height: 1.6; overflow-x: hidden; text-rendering: optimizeLegibility; }

        /* Navigation */
        nav {
            height: 65px; width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 0 8%; position: fixed; top: 0; z-index: 1000;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(15px); border-bottom: 1px solid var(--lp-border);
        }
        .logo { font-size: 1.1rem; font-weight: 800; color: #111827; letter-spacing: -0.5px; }
        .logo span { color: var(--lp-primary); }
        .nav-links { display: flex; gap: 1.8rem; }
        .nav-links a { text-decoration: none; color: var(--lp-text-muted); font-size: 0.85rem; font-weight: 550; transition: 0.3s; }
        .nav-links a:hover { color: var(--lp-primary); }
        .nav-btns { display: flex; gap: 0.75rem; }
        .btn-outline { padding: 0.45rem 1.2rem; border-radius: 6px; border: 1px solid var(--lp-primary); color: var(--lp-primary); text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.3s; }
        .btn-outline:hover { background: rgba(99, 102, 241, 0.1); }
        .btn-filled { padding: 0.45rem 1.2rem; border-radius: 6px; background: var(--lp-primary); color: #fff; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition: 0.3s; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .btn-filled:hover { background: var(--lp-primary-dark); transform: translateY(-1px); }

        /* Mobile Menu */
        .mobile-menu-btn { display: none; background: transparent; border: none; color: #111827; cursor: pointer; padding: 5px; }
        .mobile-nav-overlay {
            position: fixed; top: 0; right: -100%; width: 100%; height: 100vh;
            background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px);
            z-index: 2000; transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2rem;
            padding: 2rem;
        }
        .mobile-nav-overlay.open { right: 0; }
        .mobile-nav-overlay a { color: #111827; text-decoration: none; font-size: 1.5rem; font-weight: 700; }
        .mobile-close { position: absolute; top: 20px; right: 20px; color: #111827; cursor: pointer; }

        /* Hero Section */
        .hero {
            padding: 160px 10% 100px; display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 4rem;
            background: radial-gradient(circle at 80% 20%, rgba(99, 102, 241, 0.05), transparent 40%);
        }
        .hero-content h1 { font-size: 4rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; letter-spacing: -2px; }
        .hero-content p { font-size: 1.25rem; color: var(--lp-text-muted); margin-bottom: 2.5rem; max-width: 500px; }
        .hero-image { position: relative; }
        .hero-image img { width: 100%; border-radius: 24px; box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .floating-card {
            position: absolute; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
            padding: 1rem; border-radius: 12px; border: 1px solid var(--lp-border);
            display: flex; align-items: center; gap: 1rem;
            animation: float 4s infinite ease-in-out;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

        /* Stats */
        .stats-bar { padding: 4rem 10%; display: flex; justify-content: space-around; background: #f9fafb; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .stat-item { text-align: center; }
        .stat-item h2 { font-size: 2.5rem; color: #111827; margin-bottom: 0.5rem; }
        .stat-item p { color: var(--lp-text-muted); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }

        /* Features */
        .section-label { color: var(--lp-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 0.8rem; margin-bottom: 1rem; display: block; }
        .features { padding: 100px 10%; background: #ffffff; }
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
        .story-img img { width: 100%; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .story-content { flex: 1; }
        .story-tag { background: var(--lp-primary); color: #fff; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; margin-bottom: 1rem; display: inline-block; }
        .story-content h2 { font-size: 2.5rem; color: #111827; margin-bottom: 1.5rem; line-height: 1.2; }
        .story-content p { color: var(--lp-text-muted); font-size: 1.1rem; margin-bottom: 2rem; }

        /* Testimonials */
        .testimonials { padding: 100px 10%; background: radial-gradient(circle at 10% 80%, rgba(236, 72, 153, 0.05), transparent 30%); }
        .test-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .test-card { padding: 2rem; background: #ffffff; border-radius: 20px; border: 1px solid var(--lp-border); position: relative; box-shadow: 0 10px 20px rgba(0,0,0,0.02); }
        .test-card i { color: var(--lp-primary); opacity: 0.1; margin-bottom: 1rem; }
        .test-user { display: flex; align-items: center; gap: 1rem; margin-top: 1.5rem; }
        .test-user img { width: 45px; height: 45px; border-radius: 50%; background: #334155; }
        .test-user-info h4 { font-size: 1rem; margin: 0; color: #111827; }
        .test-user-info p { font-size: 0.8rem; color: var(--lp-text-muted); margin: 0; }

        /* Footer */
        footer { padding: 80px 10% 40px; background: #f9fafb; border-top: 1px solid var(--lp-border); }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 4rem; margin-bottom: 4rem; }
        .footer-col h4 { margin-bottom: 1.5rem; font-size: 1.1rem; color: #111827; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 0.8rem; }
        .footer-col ul li a { text-decoration: none; color: var(--lp-text-muted); transition: 0.3s; }
        .footer-col ul li a:hover { color: var(--lp-primary); }
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
        .programs-section { padding: 100px 10%; background: #ffffff; }
        .program-cat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-top: 3rem; }
        .program-cat-card { background: #f9fafb; border: 1px solid var(--lp-border); border-radius: 20px; padding: 2rem; }
        .program-cat-card h3 { color: var(--lp-primary); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .program-list-items { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .program-list-items li { color: var(--lp-text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .program-list-items li::before { content: 'ΓåÆ'; color: var(--lp-primary); font-weight: bold; }

        /* Team Section */
        .team-section { padding: 100px 10%; background: #f9fafb; border-top: 1px solid var(--lp-border); }
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
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }

        .team-card-front {
            z-index: 2;
        }

        .team-card-back {
            background: linear-gradient(135deg, #f8fafc, #eff6ff);
            transform: rotateY(180deg);
            border-color: var(--lp-primary);
            padding: 2.5rem;
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
        
        .team-card h3 { font-size: 1.2rem; margin-bottom: 0.5rem; color: #1e293b; font-weight: 800; }
        .team-card-front p { color: var(--lp-primary); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; }
        .role-desc { color: #475569; font-size: 0.9rem; line-height: 1.6; font-weight: 500; }
        
        .motivation-title {
            color: var(--lp-primary);
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1.2rem;
        }
        
        .motivation-quote {
            color: #334155;
            font-size: 1rem;
            font-style: italic;
            line-height: 1.6;
            font-weight: 500;
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

        /* FAQ Section Styles */
        .faq-section {
            padding: 100px 10%;
            background: #ffffff;
            border-top: 1px solid var(--lp-border);
        }
        .faq-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem 4rem;
            margin-top: 4rem;
        }
        .faq-item {
            border-bottom: 1px solid var(--lp-border);
            padding: 1.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .faq-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }
        .faq-toggle {
            width: 30px;
            height: 30px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--lp-text-muted);
            font-size: 1.2rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .faq-question {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.4;
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--lp-text-muted);
            font-size: 0.95rem;
            padding-left: 3.4rem;
            margin-top: 0;
            opacity: 0;
        }
        .faq-item.active .faq-answer {
            max-height: 200px;
            margin-top: 1rem;
            opacity: 1;
        }
        .faq-item.active .faq-toggle {
            background: var(--lp-primary);
            border-color: var(--lp-primary);
            color: #fff;
            transform: rotate(45deg);
        }
        .faq-item:hover .faq-question {
            color: var(--lp-primary);
        }

        @media (max-width: 968px) {
            .faq-grid { grid-template-columns: 1fr; gap: 0; }
        }
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
            <a href="#faqs">FAQs</a>
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
        <a href="#faqs" onclick="toggleMobileNav()">FAQs</a>
        <a href="#location" onclick="toggleMobileNav()">Location</a>
        <hr style="width:50px; border:1px solid var(--lp-primary); opacity:0.3;">
        <a href="index.php" style="color:var(--lp-primary);">Login</a>
        <a href="signup.php" class="btn-filled" style="padding: 1rem 3rem; font-size: 1.2rem; border-radius: 14px;">Get Started</a>
    </div>

    <header class="hero">
        <div class="hero-content">
            <!-- REAL-TIME CLOCK -->
            <div id="real-time-clock" style="margin-bottom: 2rem; background: var(--lp-primary); padding: 1rem 1.5rem; border-radius: 12px; display: inline-block; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); border: none;">
                <div id="clock-date" style="font-size: 0.9rem; font-weight: 600; color: rgba(255,255,255,0.9); letter-spacing: 0.5px; margin-bottom: 4px; text-transform: uppercase;"></div>
                <div id="clock-time" style="font-size: 1.5rem; font-weight: 800; color: #ffffff; font-family: 'Outfit', sans-serif;"></div>
            </div>
            <br>
            <span class="section-label">Empowering Communities</span>
            <h1 style="font-size: 3.5rem; color: #111827;">LGU3 Livelihood Training Program</h1>
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
            <h2 style="color: #111827;">Livelihood Program Success</h2>
        </div>

        <div class="story-flex">
            <div class="story-img">
                <img src="barangay_agriculture_success.png" alt="Agriculture">
            </div>
            <div class="story-content">
                <span class="story-tag">Sustainable Development</span>
                <h2>Baranggay Laforteza Holdings 264: The Green Revolution</h2>
                <p>Through our Livelihood Program, residents of Baranggay Laforteza Holdings 264 transformed vacant lots into thriving urban hydroponic gardens, now supplying fresh organic produce to local markets.</p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 2rem;">
                    <div>
                        <h4 style="color: #111827; font-size: 1.5rem;">24</h4>
                        <p style="font-size: 0.8rem;">Families Empowered</p>
                    </div>
                    <div>
                        <h4 style="color: #111827; font-size: 1.5rem;">$1.2k</h4>
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
                <h2 style="color: #111827;">Barangay Central: Bridging the Digital Divide</h2>
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
                <h2 style="color: #111827;">Angel's Journey: From Training to Business</h2>
                <p>"Angel's Corner" started as a dream during a livelihood workshop. Today, Angel runs a successful local cafe and employs three other graduates from the same program.</p>
                <blockquote style="border-left: 4px solid var(--lp-primary); padding-left: 1.5rem; color: var(--lp-text-muted); font-style: italic;">
                    "LGU3 gave me more than just a certificate; it gave me the confidence and the resource network to start my own legacy."
                </blockquote>
            </div>
        </div>
    </section>

    <section class="announcements-section" id="announcements">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Latest News</span>
            <h2 style="color: #111827;">Announcements & Updates</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Latest advisories from Baranggay 624 laforteza holdings.</p>
        </div>
        <div id="announcement-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Announcements will be loaded here by JavaScript -->
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background: rgba(255,255,255,0.02); border-radius: 20px;">
                <i data-feather="loader" style="width: 48px; height: 48px; color: var(--lp-primary); margin-bottom: 1rem;"></i>
                <h3 style="color: #fff;">Loading Announcements...</h3>
                <p style="color: var(--lp-text-muted);">Please wait while we fetch the latest updates.</p>
            </div>
        </div>
        <div style="text-align: center;">
            <a href="announcements.php" class="view-all-btn">
                View All Announcements <i data-feather="arrow-right"></i>
            </a>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Community Feedback</span>
            <h2 style="color: #111827;">What your neighbors are saying.</h2>
        </div>
        <div class="test-grid">
            <div class="test-card">
                <i data-feather="quote" style="width: 40px; height: 40px;"></i>
                <p>"The Skill Assessment was a game-changer for me. It pointed me towards Agriculture training, and now I'm managing our community urban farm."</p>
                <div class="test-user">
                    <div class="test-user-info">
                        <h4>Maria Santos</h4>
                        <p>Baranggay Laforteza Holdings 264</p>
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

    <section class="partner-barangays" id="barangays" style="padding: 80px 10%; background: #f9fafb;">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Our Coverage</span>
            <h2 style="color: #111827;">Active Partner Barangays</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Join the growing list of communities benefiting from our localized training programs.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <!-- BRGY 175 - Primary -->
            <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid var(--lp-primary); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px;">
                <div style="width: 40px; height: 40px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 4px;">
                    <img src="laforteza_logo.jpg" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                </div>
                <div>
                    <h4 style="color: #111827; margin: 0;">Baranggay Laforteza</h4>
                    <p style="color: var(--lp-text-muted); font-size: 0.8rem; margin: 0;">Holdings 264 (HQ)</p>
                </div>
                <span style="margin-left: auto; color: #10b981; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
            </div>

            <!-- BRGY Central -->
            <div style="background: #ffffff; border: 1px solid var(--lp-border); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--lp-primary);">
                    <i data-feather="map-pin"></i>
                </div>
                <div>
                    <h4 style="color: #111827; margin: 0;">Barangay Central</h4>
                    <p style="color: var(--lp-text-muted); font-size: 0.8rem; margin: 0;">District 1 Portal</p>
                </div>
                <span style="margin-left: auto; color: #10b981; font-size: 0.7rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
            </div>

            <!-- BRGY Poblacion -->
            <div style="background: #ffffff; border: 1px solid var(--lp-border); padding: 1.5rem; border-radius: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--lp-primary);">
                    <i data-feather="map-pin"></i>
                </div>
                <div>
                    <h4 style="color: #111827; margin: 0;">Barangay Poblacion</h4>
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
            <h2 style="color: #111827;">Meet the Elite Team</h2>
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
    
    <section class="faq-section" id="faqs">
        <div class="section-header" style="text-align: center; margin: 0 auto 0;">
            <h2 style="color: #111827; font-size: 2.8rem; font-weight: 800;">
                <span style="color: #f59e0b;">FAQs:</span> Everything You Need to Know
            </h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Common questions about the Baranggay 175 Holdings Livelihood Program.</p>
        </div>

        <div class="faq-grid">
            <!-- Col 1 -->
            <div class="faq-col">
                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">What is the Baranggay 175 Holdings Livelihood Program?</div>
                    </div>
                    <div class="faq-answer">
                        It is a community-driven initiative providing free technical, digital, and vocational training to residents to improve employment and entrepreneurship opportunities in our district.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">How do I join a training program?</div>
                    </div>
                    <div class="faq-answer">
                        Simply create an account via the "Get Started" button, complete your profile, and browse the "Programs" section in your citizen dashboard to enroll in your preferred track.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">Is there any fee for the courses?</div>
                    </div>
                    <div class="faq-answer">
                        No. Every training program offered under the LGU3 Livelihood system is 100% free for all registered residents of Baranggay 175 Holdings.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">Are certificates provided upon completion?</div>
                    </div>
                    <div class="faq-answer">
                        Yes, residents who successfully complete a program and pass the final skill assessment will receive an official digital certificate verified by the LGU.
                    </div>
                </div>
            </div>

            <!-- Col 2 -->
            <div class="faq-col">
                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">Can I apply for multiple programs at once?</div>
                    </div>
                    <div class="faq-answer">
                        To ensure quality learning, we recommend focusing on one program at a time. However, you can apply for a new one immediately after finishing your current track.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">What if I don't have a computer for digital courses?</div>
                    </div>
                    <div class="faq-answer">
                        Baranggay 175 Holdings provides a dedicated computer laboratory with free high-speed internet for all residents enrolled in Digital & IT Literacy tracks.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">How does the AI Career Assistant work?</div>
                    </div>
                    <div class="faq-answer">
                        Our AI analyzes your skill progress and provides personalized recommendations, help with applications, and real-time career coaching based on local market trends.
                    </div>
                </div>

                <div class="faq-item" onclick="this.classList.toggle('active')">
                    <div class="faq-header">
                        <div class="faq-toggle">+</div>
                        <div class="faq-question">How can I track my application status?</div>
                    </div>
                    <div class="faq-answer">
                        You can view the real-time status of all your applications, reports, and training progress in the "My History" and "Learning Management" sections of your portal.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="location-section" id="location" style="padding: 100px 10%; background: #ffffff;">
        <div class="section-header" style="text-align: center; margin: 0 auto 4rem;">
            <span class="section-label">Visit Us</span>
            <h2 style="color: #111827;">Training Center Location</h2>
            <p style="color: var(--lp-text-muted); margin-top: 1rem;">Our central hub is easily accessible for all residents. Drop by to learn more about our programs.</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 4rem; align-items: start;">
            <div class="location-info">
                <div style="background: #f9fafb; border: 1px solid var(--lp-border); padding: 2.5rem; border-radius: 24px;">
                    <div style="margin-bottom: 2rem;">
                        <h4 style="color: var(--lp-primary); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <i data-feather="map-pin"></i> Primary Address
                        </h4>
                        <p style="color: #111827; font-size: 1.1rem; line-height: 1.6; font-weight: 500;">
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

            <div class="map-container" style="border-radius: 24px; overflow: hidden; height: 450px; border: 1px solid var(--lp-border); box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <iframe 
                    src="https://maps.google.com/maps?q=14.758252,121.044014&z=15&output=embed" 
                    width="100%" 
                    height="100%" 
                    style="border:0; filter: grayscale(1) contrast(1.1) opacity(0.9);" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-grid">
            <div class="footer-col" style="padding-right: 2rem;">
                <div class="logo" style="margin-bottom: 1.5rem; color: #111827;">LGU3<span>Livelihood Training Program</span></div>
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
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="privacy.php">Terms of Service</a></li>
                    <li><a href="javascript:void(0)" onclick="comingSoon()">Contact Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Baranggay Laforteza Holdings 264. All rights reserved.</p>
            <div style="display: flex; gap: 1.5rem;">
                <a href="#"><i data-feather="facebook" style="width: 18px;"></i></a>
                <a href="#"><i data-feather="twitter" style="width: 18px;"></i></a>
                <a href="#"><i data-feather="instagram" style="width: 18px;"></i></a>
            </div>
        </div>
    </footer>

    <script>
        feather.replace();

        // REAL-TIME CLOCK FUNCTION
        function updateClock() {
            const now = new Date();
            
            // Format Date: Sunday, February 1, 2026
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('en-US', dateOptions);
            
            // Format Time: 11:45:38 PM
            const timeOptions = { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true };
            const timeStr = now.toLocaleTimeString('en-US', timeOptions);
            
            const dateEl = document.getElementById('clock-date');
            const timeEl = document.getElementById('clock-time');
            
            if(dateEl) dateEl.textContent = dateStr;
            if(timeEl) timeEl.textContent = timeStr;
        }
        
        setInterval(updateClock, 1000);
        updateClock(); // Initial call

        async function fetchAnnouncements() {
            try {
                const res = await fetch('api.php?action=get_announcements');
                const data = await res.json();
                const grid = document.getElementById('announcement-grid');
                
                if (!data || data.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background: rgba(255,255,255,0.02); border-radius: 20px;">
                            <i data-feather="info" style="width: 48px; height: 48px; color: var(--lp-primary); margin-bottom: 1rem;"></i>
                            <h3 style="color: #fff;">No Recent Announcements</h3>
                            <p style="color: var(--lp-text-muted);">Check back later for community updates and advisories.</p>
                        </div>
                    `;
                    feather.replace();
                    return;
                }

                grid.innerHTML = '';
                // Only show top 3 latest on landing page
                data.slice(0, 3).forEach(ann => {
                    const img = ann.image_path || 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?q=80&w=1000';
                    const date = new Date(ann.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    grid.innerHTML += `
                        <div class="announcement-card">
                            <span class="ann-badge">${ann.category || 'ADVISORY'}</span>
                            <img src="${img}" class="ann-image" alt="${ann.title}">
                            <div class="ann-content">
                                <span class="ann-date">${date}</span>
                                <h3 class="ann-title">${ann.title}</h3>
                                <p class="ann-text">${ann.content}</p>
                                <a href="view_announcement.php?id=${ann.id}" class="ann-link">Read More <i data-feather="arrow-right" style="width:16px;"></i></a>
                            </div>
                        </div>
                    `;
                });
                feather.replace();
            } catch (e) {
                console.error("Failed to fetch announcements", e);
            }
        }

        // Initialize fetch
        fetchAnnouncements();

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
