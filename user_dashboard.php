<?php
session_start();
include 'db_connect.php';

// Check if logged in and is user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    }
    header("Location: index.php");
    exit();
}

// Fetch current user data for profile display
$uid = $_SESSION['user_id'];

// Robust fetch: try all columns, fallback if any missing (auto-heal handles creation)
$resUser = $conn->query("SELECT full_name, email, profile_image, is_active, reference_id FROM users WHERE id = $uid");

if (!$resUser) {
    // If the above failed (likely some columns missing), fetch what we definitely have
    $baseData = $conn->query("SELECT full_name, email FROM users WHERE id = $uid")->fetch_assoc();
    
    // Check for other columns individually to be safe
    $checkRef = $conn->query("SHOW COLUMNS FROM users LIKE 'reference_id'");
    $refId = ($checkRef->num_rows > 0) ? $conn->query("SELECT reference_id FROM users WHERE id = $uid")->fetch_assoc()['reference_id'] : null;
    
    $checkActive = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $activeVal = ($checkActive->num_rows > 0) ? $conn->query("SELECT is_active FROM users WHERE id = $uid")->fetch_assoc()['is_active'] : 1;

    $userData = [
        'full_name' => $baseData['full_name'],
        'email' => $baseData['email'],
        'profile_image' => null,
        'is_active' => $activeVal,
        'reference_id' => $refId
    ];
} else {
    $userData = $resUser->fetch_assoc();
}

// FINAL SAFETY: If reference_id is still empty, generate it now
if (empty($userData['reference_id']) || !preg_match('/^REF-\d{8}$/', $userData['reference_id'])) {
    $newRef = 'REF-' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $conn->query("UPDATE users SET reference_id = '$newRef' WHERE id = $uid");
    $userData['reference_id'] = $newRef;
}

$is_active = isset($userData['is_active']) ? (int)$userData['is_active'] : 1;

$user_name = $userData['full_name'];
$user_email = $userData['email'];
$user_image = $userData['profile_image'] ?? null; // Handle if key exists but is null or key doesn't exist
$user_initials = strtoupper(substr($user_name, 0, 2));

// Update Last Activity
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
if ($checkCol->num_rows > 0) $conn->query("UPDATE users SET last_activity = NOW() WHERE id = $uid");

// Check Assessment Status
$checkSkill = $conn->query("SELECT skills FROM users WHERE id = $uid");
$skillRow = $checkSkill->fetch_assoc();
if (empty($skillRow['skills'])) {
    header("Location: assessment.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... head content ... -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | LGU3 Livelihood</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .section-view { display: none; animation: fadeIn 0.3s ease; }
        .section-view.active { display: block; }
        @keyframes fadeIn { from { opacity:0; transform: translateY(5px); } to { opacity:1; transform: translateY(0); } }
        
        /* Theme active state */
        .action-btn.active { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
        
        /* Recommendation Cards */
        .rec-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .rec-card {
            background: var(--card-bg); border: 1px solid var(--border-color);
            padding: 1.5rem; border-radius: 16px; transition: transform 0.2s;
        }
        .rec-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .rec-tag {
            display: inline-block; padding: 0.25rem 0.75rem; 
            border-radius: 20px; font-size: 0.75rem; 
            background: rgba(99, 102, 241, 0.1); color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .rec-score { float: right; color: #10b981; font-size: 0.8rem; font-weight: 600; }
        
        /* Profile Image Styles */
        .profile-upload-container { display: flex; flex-direction: column; align-items: center; gap: 1rem; margin-bottom: 2rem; }
        .profile-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--card-bg); box-shadow: 0 0 0 2px var(--primary); background: #334155; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--text-main); overflow:hidden;}
        .profile-preview img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Form Styles */
        .form-control { width: 100%; padding: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); margin-bottom: 1rem; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; }
        textarea.form-control { resize: vertical; min-height: 120px; }

        /* Modals and Animations */
        @keyframes popupScale { 0% { transform:scale(0.8); opacity:0; } 100% { transform:scale(1); opacity:1; } }
        #general-success-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10001; backdrop-filter:blur(8px); align-items:center; justify-content:center; }
        .modal-content-premium { background:#0f172a; padding:2.5rem; border-radius:24px; border:1px solid rgba(16, 185, 129, 0.2); width:90%; max-width:400px; text-align:center; box-shadow:0 25px 60px -12px rgba(0,0,0,0.6); animation: popupScale 0.3s ease-out; }

        /* Mobile Nav Toggle */
        .mobile-toggle { display: none; }
        @media(max-width:768px){
            .mobile-toggle { display: block; font-size: 1.5rem; background:none; border:none; color:white; cursor:pointer;}
        }

        /* Sidebar Minimization */
        .sidebar { transition: all 0.3s ease; overflow: hidden; display: flex; flex-direction: column; }
        .sidebar-nav { overflow-y: auto; flex: 1; padding-right: 5px; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.1) transparent; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        
        /* Account Locked State */
        .sidebar.account-suspended .nav-item:not(.logout-btn) { 
            opacity: 0.5; 
            pointer-events: none; 
            cursor: not-allowed;
            filter: grayscale(1);
        }
        .account-lock-overlay {
            position: fixed;
            top: 15px;
            left: 280px;
            right: 20px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-radius: 12px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-main);
            animation: slideDown 0.5s ease;
        }
        @keyframes slideDown { from { transform: translateY(-100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .main-content.locked-view {
            filter: blur(2px);
            pointer-events: none;
            user-select: none;
        }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        
        .main-content { transition: all 0.3s ease; }
        .sidebar.minimized { width: 80px; padding: 1.5rem 0.5rem; }
        .sidebar.minimized .sidebar-header { justify-content: center; flex-direction: column; gap: 1rem; }
        .sidebar.minimized .logo { font-size: 0.9rem; display: flex; flex-direction: column; align-items: center; }
        .sidebar.minimized .logo span, .sidebar.minimized .nav-item span, .sidebar.minimized .sidebar-footer span { display: none; }
        .sidebar.minimized .nav-item { justify-content: center; padding: 0.75rem; width: 100%; }
        .sidebar.minimized .nav-item i { margin: 0; }
        .main-content.sidebar-collapsed { margin-left: 80px; }
        
        .sidebar-toggle {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            border-radius: 8px;
            min-width: 32px;
        }
        .sidebar-toggle:hover { background: var(--primary); color: var(--text-main); }
        .sidebar.minimized .sidebar-toggle { width: 40px; height: 40px; margin: 0 auto; }

        /* AI Chat Minimization */
        .chat-section-container { transition: height 0.3s ease, transform 0.3s ease; }
        .chat-section-container.minimized { height: 65px; overflow: hidden; transform: translateY(0); max-width: 400px; margin: 0 auto; }
        .chat-body-ai, .chat-footer-ai { transition: opacity 0.2s; }
        .chat-section-container.minimized .chat-body-ai, 
        .chat-section-container.minimized .chat-footer-ai { opacity: 0; pointer-events: none; }
        /* Application Cards */
        .app-list { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        .app-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .app-card:hover { 
            background: rgba(30, 41, 59, 0.8); 
            border-color: rgba(99, 102, 241, 0.5); 
            transform: scale(1.01);
            box-shadow: 0 10px 20px -10px rgba(0,0,0,0.3);
        }
        .app-info h4 { margin: 0; color: var(--text-main); font-size: 1.05rem; }
        .app-info p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.85rem; }
        
        .download-btn {
            background: var(--primary);
            color: var(--text-main);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .download-btn:hover { background: #4f46e5; transform: translateY(-2px); }
        
        .pending-tag {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Notifications UI */
        .notif-container { position: relative; margin-right: 1.5rem; cursor: pointer; }
        .notif-badge { 
            position: absolute; top: -5px; right: -5px; 
            background: #ef4444; color: var(--text-main); width: 18px; height: 18px; 
            border-radius: 50%; font-size: 0.7rem; display: flex; 
            align-items: center; justify-content: center; font-weight: bold;
            display: none; /* Shown via JS */
        }
        .notif-dropdown {
            position: absolute; top: 40px; right: 0; width: 300px;
            background: #1e293b; border: 1px solid #334155; border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
            display: none; z-index: 1000;
            overflow: hidden;
        }
        .notif-dropdown.show { display: block; animation: fadeIn 0.2s ease; }
        .notif-header { padding: 1rem; border-bottom: 1px solid #334155; font-weight: 600; display: flex; justify-content: space-between; }
        .notif-list { max-height: 350px; overflow-y: auto; }
        .notif-item { padding: 1rem; border-bottom: 1px solid #334155; transition: 0.2s; }
        .notif-item:hover { background: rgba(255,255,255,0.05); }
        .notif-item.unread { background: rgba(99, 102, 241, 0.05); border-left: 3px solid var(--primary); }
        .notif-item h5 { margin: 0 0 0.25rem 0; font-size: 0.9rem; color: var(--text-main); }
        .notif-item p { margin: 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4; }
        .notif-item small { color: #64748b; font-size: 0.7rem; margin-top: 0.5rem; display: block; }
        .notif-empty { padding: 2rem; text-align: center; color: #64748b; font-size: 0.9rem; }

        /* Learning Center Premium Cards */
        .doc-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.4), rgba(15, 23, 42, 0.6));
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.75rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            backdrop-filter: blur(12px);
        }
        .doc-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
        }
        .doc-icon-wrapper {
            width: 50px;
            height: 50px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }
        .doc-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
            line-height: 1.4;
        }
        .doc-cat-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.85rem;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
            width: fit-content;
        }
        .doc-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .download-btn-premium {
            background: var(--primary);
            color: var(--text-main);
            width: 100%;
            padding: 0.9rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .download-btn-premium:hover {
            background: #4f46e5;
            transform: scale(1.02);
        }

        /* --- AI Chatbot Interface --- */
        .chat-section-container {
            display: flex;
            flex-direction: column;
            height: 550px;
            max-width: 700px;
            margin: 0 auto;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .chat-header-ai {
            padding: 1rem 1.5rem;
            background: rgba(99, 102, 241, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .ai-avatar {
            width: 35px;
            height: 35px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
        }
        .chat-body-ai {
            flex: 1;
            padding: 1.25rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .chat-message {
            max-width: 85%;
            padding: 0.8rem 1rem;
            border-radius: 14px;
            font-size: 0.85rem;
            line-height: 1.5;
            position: relative;
        }
        .chat-message.ai {
            align-self: flex-start;
            background: #1e293b;
            color: #d1d5db;
            border-bottom-left-radius: 2px;
        }
        .chat-message.user {
            align-self: flex-end;
            background: var(--primary);
            color: var(--text-main);
            border-bottom-right-radius: 2px;
        }
        .chat-footer-ai {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            gap: 0.75rem;
        }
        .ai-input {
            flex: 1;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            color: var(--text-main);
            font-family: inherit;
        }
        .ai-input:focus { outline: none; border-color: var(--primary); }
        .ai-send-btn {
            background: var(--primary);
            color: var(--text-main);
            border: none;
            width: 50px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }
        .ai-send-btn:hover { background: #4f46e5; transform: scale(1.05); }
        
        /* Pulse for active AI */
        .status-dot { width: 10px; height: 10px; background: #10b981; border-radius: 50%; border: 2px solid #0f172a; }
        .ai-typing { font-size: 0.8rem; color: #64748b; margin-left: 0.5rem; display: none; }

        /* --- Skill Test Styles --- */
        .skill-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }
        .skill-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .skill-thumb { height: 160px; width: 100%; object-fit: cover; }
        .skill-body { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .skill-title { font-size: 1.2rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem; }
        .skill-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem; flex: 1; }
        
        .progress-container { width: 100%; background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 1rem; }
        .progress-bar { height: 100%; background: #10b981; transition: width 0.5s ease; width: 0%; }
        
        .stage-list { display: flex; flex-direction: column; gap: 1rem; }
        .stage-item { 
            background: rgba(15, 23, 42, 0.5); padding: 1rem; border-radius: 12px; 
            border: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;
        }
        .stage-item.locked { opacity: 0.5; pointer-events: none; }
        .stage-item.active { border-color: var(--primary); background: rgba(99, 102, 241, 0.1); }
        .stage-item.completed { border-color: #10b981; }
        
        .stage-check { 
            width: 24px; height: 24px; border-radius: 50%; border: 2px solid #64748b; 
            display: flex; align-items: center; justify-content: center; margin-right: 1rem;
        }
        .stage-item.completed .stage-check { background: #10b981; border-color: #10b981; color: var(--text-main); }
        .stage-item.active .stage-check { border-color: var(--primary); color: var(--primary); }

        /* Stage Completion Animations */
        #stage-loader-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(8px);
            display: none; flex-direction: column; align-items: center; justify-content: center;
            z-index: 10001; animation: fadeIn 0.3s ease;
        }
        .premium-loader {
            width: 60px; height: 60px; border: 4px solid rgba(99, 102, 241, 0.2);
            border-top: 4px solid var(--primary); border-radius: 50%;
            animation: spin 1s linear infinite; margin-bottom: 1.5rem;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        #stage-success-popup {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #1e293b; border: 1px solid var(--primary); border-radius: 20px;
            padding: 2.5rem; text-align: center; z-index: 10002; display: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); width: 90%; max-width: 400px;
            animation: popupScale 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .success-icon-animated {
            width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1);
            color: #10b981; border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 1.5rem auto; font-size: 2.5rem;
        }

        @keyframes popupScale { 
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; } 
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; } 
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* Comprehensive Light Theme Overrides */
        body.light-theme {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
        }

        body.light-theme .main-content {
            background-color: #f1f5f9 !important;
        }

        body.light-theme .top-bar {
            background: rgba(255, 255, 255, 0.8) !important;
            border-bottom: 1px solid #cbd5e1 !important;
            backdrop-filter: blur(10px);
        }

        body.light-theme .sidebar {
            background: #ffffff !important;
            border-right: 1px solid #cbd5e1 !important;
        }

        body.light-theme .doc-card,
        body.light-theme .skill-card,
        body.light-theme .stat-card,
        body.light-theme .app-card,
        body.light-theme .content-section,
        body.light-theme .chat-section-container,
        body.light-theme .notif-dropdown,
        body.light-theme .stage-item {
            background: #ffffff !important;
            border-color: #cbd5e1 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
        }

        body.light-theme h1, 
        body.light-theme h2, 
        body.light-theme h3, 
        body.light-theme h4,
        body.light-theme .stat-value,
        body.light-theme .doc-title,
        body.light-theme .skill-title,
        body.light-theme .val-name,
        body.light-theme #header-name,
        body.light-theme .nav-item.active {
            color: #0f172a !important;
        }

        body.light-theme p, 
        body.light-theme span:not(.notif-badge):not(.badge), 
        body.light-theme label,
        body.light-theme .nav-item,
        body.light-theme .stat-info h3,
        body.light-theme .app-info p {
            color: #475569 !important;
        }

        body.light-theme .nav-item:hover,
        body.light-theme .nav-item.active {
            background: rgba(99, 102, 241, 0.08) !important;
            color: var(--primary) !important;
        }

        body.light-theme .ai-input {
            background: #f8fafc !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }

        body.light-theme .chat-message.ai {
            background: #f1f5f9 !important;
            color: #1e293b !important;
        }

        body.light-theme .form-control {
            background: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }

        body.light-theme .sidebar-header .logo {
            color: #0f172a !important;
        }

        body.light-theme .notif-item:hover {
            background: #f8fafc !important;
        }

        body.light-theme .notif-header {
            border-bottom: 1px solid #cbd5e1 !important;
            color: #0f172a !important;
        }

        body.light-theme .avatar {
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 1px #cbd5e1;
        }

        body.light-theme td {
            color: #0f172a !important;
        }

        body.light-theme th {
            color: #475569 !important;
        }

        body.light-theme .badge.warning {
            color: #b45309 !important;
            background: rgba(180, 83, 9, 0.1) !important;
        }
    </style>
</head>
<body class="dashboard-body">
    <!-- ... body content ... -->

    <div class="app-container">
        <?php if($is_active === 0): ?>
            <div class="account-lock-overlay">
                <div style="background: #ef4444; color: var(--text-main); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-feather="lock"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0; font-size: 1rem;">Account Temporarily Suspended</h4>
                    <p style="margin: 0; font-size: 0.8rem; opacity: 0.8;">Your account is deactivated. Reference ID: <strong style="color: var(--text-main); background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px;"><?php echo $userData['reference_id'] ?? 'N/A'; ?></strong></p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="openReactivationModal()" style="background: var(--primary); color: var(--text-main); border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer;">Request Reactivation</button>
                    <a href="logout.php" style="color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 0.8rem; background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px;">Logout</a>
                </div>
            </div>

            <!-- Reactivation Request Modal -->
            <div id="reactivation-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 10005;">
                <div style="background: #1e293b; border: 1px solid var(--border-color); padding: 2.5rem; border-radius: 20px; width: 450px; position: relative;">
                    <h3 style="margin-bottom: 0.5rem; color: var(--text-main);">Request Reactivation</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Please provide a reason why your account should be reactivated.</p>
                    
                    <div style="margin-bottom: 1.2rem;">
                        <label style="font-size: 0.85rem; margin-bottom: 5px;">Reference ID</label>
                        <input type="text" value="<?php echo $userData['reference_id'] ?? ''; ?>" readonly style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 8px; color: var(--primary); font-family: monospace; font-weight: 700;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-size: 0.85rem; margin-bottom: 5px;">Reason for Reactivation</label>
                        <textarea id="reactivation-reason" placeholder="Explain your situation..." style="width: 100%; padding: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); min-height: 120px;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button onclick="closeReactivationModal()" style="flex: 1; padding: 0.8rem; background: rgba(255,255,255,0.05); color: var(--text-main); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                        <button onclick="submitReactivationRequest()" style="flex: 2; padding: 0.8rem; background: var(--primary); color: var(--text-main); border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Send Request</button>
                    </div>
                </div>
            </div>
            
            <script>
                function openReactivationModal() {
                    document.getElementById('reactivation-modal').style.display = 'flex';
                }
                function closeReactivationModal() {
                    document.getElementById('reactivation-modal').style.display = 'none';
                }
                async function submitReactivationRequest() {
                    const reason = document.getElementById('reactivation-reason').value;
                    const refId = "<?php echo $userData['reference_id']; ?>";
                    
                    if(!reason.trim()) {
                        alert("Please enter a reason.");
                        return;
                    }

                    try {
                        const res = await fetch('api.php?action=request_reactivation', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ reason: reason, reference_id: refId })
                        });
                        const data = await res.json();
                        if(data.success) {
                            alert("Request sent successfully! Our team will review it.");
                            closeReactivationModal();
                        } else {
                            alert("Error: " + data.error);
                        }
                    } catch (e) {
                        alert("Network error. Please try again.");
                    }
                }
            </script>
        <?php endif; ?>
        <!-- Sidebar -->
        <aside class="sidebar <?php echo ($is_active === 0) ? 'account-suspended' : ''; ?>" id="sidebar">
            <div class="sidebar-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div class="logo" style="display: flex; align-items: center; gap: 10px;">
                    <img src="laforteza_logo.jpg" style="width: 30px; height: 30px; border-radius: 6px; object-fit: cover;">
                    <div style="font-size: 1.1rem; line-height: 1;">LGU3<span style="display: block; font-size: 0.65rem; font-weight: 500; opacity: 0.7;">BARANGAY 175</span></div>
                </div>
                <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebar-btn" title="Toggle Sidebar">
                    <i data-feather="chevron-left"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="showSection('home', this)">
                    <i data-feather="home"></i>
                    <span>Home</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('profile', this)">
                    <i data-feather="user"></i>
                    <span>My Profile</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('create-report', this)">
                    <i data-feather="file-plus"></i>
                    <span>Create Report</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('history', this)">
                    <i data-feather="clock"></i>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('schedule', this)">
                    <i data-feather="calendar"></i>
                    <span>My Schedule</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('learning-docs', this)">
                    <i data-feather="book-open"></i>
                    <span>Learning Center</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('skill-test', this)">
                    <i data-feather="award"></i>
                    <span>Skill Test</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('ai-chat', this)">
                    <i data-feather="message-circle"></i>
                    <span>AI Assistant</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('generate-id', this)">
                    <i data-feather="credit-card"></i>
                    <span>Generate ID</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="nav-item logout-btn">
                    <i data-feather="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content <?php echo ($is_active === 0) ? 'locked-view' : ''; ?>" id="main-content">
            <header class="top-bar">
                <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i data-feather="menu"></i></button>
                <div class="welcome-text">
                    <span id="header-welcome-main" style="font-weight: 600;">Good Morning, <span id="header-name"><?php echo htmlspecialchars($user_name); ?></span>!</span>
                    <span id="header-ref-id" style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px;">
                        <i data-feather="hash" style="width: 12px; height: 12px;"></i>
                        Reference: <b style="color: var(--primary); font-family: monospace; letter-spacing: 1px;"><?php echo htmlspecialchars($userData['reference_id'] ?? 'REF-N/A'); ?></b>
                    </span>
                </div>
                <div class="user-profile" style="display:flex; align-items:center;">
                    <!-- Notification Bell -->
                    <div class="notif-container" onclick="toggleNotifs(event)">
                        <i data-feather="bell" style="color:#94a3b8;"></i>
                        <div id="notif-count" class="notif-badge">0</div>
                        <div id="notif-dropdown" class="notif-dropdown" onclick="event.stopPropagation()">
                            <div class="notif-header">
                                <span>Notifications</span>
                                <span style="font-size:0.75rem; color:var(--primary); cursor:pointer;" onclick="markAllRead()">Mark all read</span>
                            </div>
                            <div id="notif-list" class="notif-list">
                                <div class="notif-empty">No new notifications</div>
                            </div>
                        </div>
                    </div>

                    <div id="header-avatar-container">
                        <?php if($user_image): ?>
                            <img src="<?php echo $user_image; ?>" class="avatar" id="header-avatar" style="object-fit:cover;">
                        <?php else: ?>
                            <div class="avatar" id="header-avatar"><?php echo $user_initials; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                
                <!-- HOME SECTION -->
                <div id="home" class="section-view active">
                    <div style="margin-bottom: 2.5rem;">
                        <h3 style="margin-bottom:1.25rem; font-size:1.1rem; color: var(--text-main); display:flex; align-items:center; gap:0.5rem;">
                            <i data-feather="activity" style="width:18px; color:var(--primary);"></i> Your Activity
                        </h3>
                        <div class="stats-grid" style="margin-bottom:0;">
                            <div class="stat-card">
                                <div class="stat-icon user-color"><i data-feather="file-text"></i></div>
                                <div class="stat-info">
                                    <h3>Total Reports</h3>
                                    <p class="stat-value" id="stat-total">--</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon doc-color"><i data-feather="clock"></i></div>
                                <div class="stat-info">
                                    <h3>Pending</h3>
                                    <p class="stat-value" id="stat-pending">--</p>
                                </div>
                            </div>
                            <!-- Reference ID Card -->
                            <div class="stat-card">
                                <div class="stat-icon activity-color"><i data-feather="hash"></i></div>
                                <div class="stat-info">
                                    <h3>Reference ID</h3>
                                    <p class="stat-value" style="font-family: monospace; font-size: 1rem; color: var(--text-main);"><?php echo htmlspecialchars($userData['reference_id'] ?? 'REF-N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="welcome-banner">
                        <div class="banner-content">
                            <h2>AI-Powered Recommendations</h2>
                            <p>Based on your community analysis, here are the best programs for you.</p>
                        </div>
                    </div>

                    <div id="ai-recommendations" class="rec-grid">
                        <!-- Populated by AI -->
                        <div style="grid-column: 1/-1; text-align:center; padding:2rem; color:#aaa;">Loading recommendations...</div>
                    </div>
                    
                    <div style="margin-top: 3rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <h3 style="font-size:1.1rem; color: var(--text-main);">My Learning Programs</h3>
                            <span style="font-size:0.8rem; color:var(--text-muted);" id="app-count">0 Programs</span>
                        </div>
                        <div id="my-applications-list" class="app-list">
                            <!-- Populated by JS -->
                            <div style="color:#aaa; text-align:center; padding:2rem; background:rgba(30,41,59,0.3); border-radius:16px;">Loading your applications...</div>
                        </div>
                    </div>
                </div>

                <!-- PROFILE SECTION -->
                <div id="profile" class="section-view">
                    <div class="page-header" style="text-align: center;">
                        <h2>My Profile</h2>
                        <p>Manage your account settings</p>
                    </div>
                    <div class="content-section" style="padding: 2.5rem; max-width: 1000px; margin: 0 auto; text-align: left;">
                        <div style="display: flex; gap: 3rem; flex-wrap: wrap;">
                            <!-- Account Settings Column -->
                            <div style="flex: 1.2; min-width: 320px;">
                                <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.2rem; display: flex; align-items: center; gap: 10px;">
                                    <i data-feather="settings" style="width: 20px; color: var(--primary);"></i> Account Settings
                                </h3>
                                <form id="profileForm">
                                    <div class="profile-upload-container">
                                        <div class="profile-preview" id="preview-container">
                                            <?php if($user_image): ?>
                                                <img src="<?php echo $user_image; ?>" id="preview-img">
                                            <?php else: ?>
                                                <span id="preview-text"><?php echo $user_initials; ?></span>
                                                <img src="" id="preview-img" style="display:none;">
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display:none;" onchange="previewFile()">
                                        <div style="display:flex; gap:0.5rem; margin-top:1rem; justify-content:center; width:100%;">
                                            <button type="button" class="action-btn" onclick="document.getElementById('profile_image').click()" style="padding:0.6rem 1rem; font-size:0.85rem; flex:1;">Change Picture</button>
                                            <button type="button" id="save-photo-btn" class="primary-action-btn" style="display:none; padding:1.1rem 1.5rem; font-size:0.85rem; width:auto; flex:1; margin:0;" onclick="uploadProfilePicture()">Save Photo</button>
                                        </div>
                                    </div>
                                    
                                    <div id="edit-auth-notice" style="display:none; background:rgba(16, 185, 129, 0.1); border:1px solid #10b981; padding:0.75rem; border-radius:10px; margin-bottom:1.5rem; color:#10b981; font-size:0.85rem; text-align:center;">
                                        <i data-feather="check-circle" style="width:14px; vertical-align:middle; margin-right:5px;"></i>
                                        Authorized! You have <span id="auth-timer" style="font-weight:700;">25:00</span> remaining to change your name.
                                    </div>

                                    <label>Full Name</label>
                                    <div style="position:relative; margin-bottom:1.5rem;">
                                        <input type="text" id="profile-name-input" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" readonly style="margin-bottom:0; padding-right:45px;">
                                        <div id="lock-icon" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:#64748b;">
                                            <i data-feather="lock" style="width:18px;"></i>
                                        </div>
                                    </div>
                                    
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background: var(--input-bg); color:#64748b;">
                                    
                                    <div id="profile-actions" style="margin-top:2rem;">
                                        <button type="button" id="request-perm-btn" onclick="requestEditPermission()" class="action-btn" style="width:100%; border:1px dashed var(--primary); background:transparent; display:block;">
                                            Request Permission to Change Name
                                        </button>
                                        <button type="submit" id="save-profile-btn" class="primary-action-btn" style="width:100%; display:none;">
                                            Confirm Name Change
                                        </button>
                                    </div>

                                    <!-- Theme Settings -->
                                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05);">
                                       <label>Interface Theme</label>
                                       <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                           <button type="button" class="action-btn" id="btn-dark" onclick="setTheme('dark')" style="flex:1;">
                                               <i data-feather="moon" style="width:14px; margin-right:5px; vertical-align:middle;"></i> Dark
                                           </button>
                                           <button type="button" class="action-btn" id="btn-light" onclick="setTheme('light')" style="flex:1;">
                                               <i data-feather="sun" style="width:14px; margin-right:5px; vertical-align:middle;"></i> Light
                                           </button>
                                       </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Badges Column -->
                            <div style="flex: 1; min-width: 320px; border-left: 1px solid rgba(255,255,255,0.05); padding-left: 3rem;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                                    <h3 style="margin:0; color: var(--text-main); font-size:1.2rem;">
                                        <i data-feather="award" style="width:20px; vertical-align:middle; margin-right:8px; color:#fbbf24;"></i>
                                        Earned Badges
                                    </h3>
                                    <span id="badge-count" style="background:rgba(251, 191, 36, 0.1); color:#fbbf24; padding:0.3rem 0.8rem; border-radius:20px; font-size:0.85rem; font-weight:600;">0</span>
                                </div>
                                <div id="badges-container" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:1rem;">
                                    <div style="text-align:center; padding:2rem; color:#64748b; grid-column:1/-1;">
                                        <i data-feather="award" style="width:40px; opacity:0.3; margin-bottom:0.5rem;"></i>
                                        <div style="font-size:0.9rem;">Complete skill tests to earn badges!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CREATE REPORT SECTION -->
                <div id="create-report" class="section-view">
                    <div class="page-header" style="text-align: center;">
                        <h2>Create New Report</h2>
                        <p>Submit incident reports or community feedback.</p>
                        <div style="font-size:0.8rem; color:#10b981; background:rgba(16, 185, 129, 0.1); display:inline-block; padding:0.4rem 1rem; border-radius:30px; margin-top:0.8rem;">
                            <i data-feather="cpu" style="width:12px; vertical-align:middle; margin-right:4px;"></i>
                            <strong>AI Powered:</strong> Your feedback is analyzed by NLP for sentiment & trends!
                        </div>
                    </div>
                    <div class="content-section" style="padding: 2rem; max-width: 600px; margin: 0 auto; text-align: left;">
                        <form id="reportForm">
                            <label>Subject / Title</label>
                            <input type="text" id="report-title" class="form-control" placeholder="e.g., Broken Street Light" required>
                            
                            <label>Description</label>
                            <textarea id="report-desc" class="form-control" placeholder="Describe the issue in detail..." required></textarea>
                            
                            <button type="submit" class="primary-action-btn">Submit Report</button>
                        </form>
                    </div>
                </div>

                <!-- HISTORY SECTION -->
                <div id="history" class="section-view">
                    <div class="page-header">
                        <h2>My History</h2>
                        <p>Track the status of your submitted reports.</p>
                    </div>
                    <div class="content-section">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Date Submitted</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="history-table">
                                    <tr><td colspan="3">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- MY SCHEDULE SECTION -->
                <div id="schedule" class="section-view">
                    <div class="page-header">
                        <h2>My Schedule</h2>
                        <p>View your training sessions and assigned tasks from the administration.</p>
                    </div>

                    <div class="schedule-grid">
                        <!-- Calendar Graphic -->
                        <div class="content-section" style="padding:1.5rem; background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                                <h3 id="calendar-month-year" style="margin:0;">Month Year</h3>
                                <div style="display:flex; gap:0.5rem;">
                                    <button class="icon-btn" onclick="prevMonth()" style="background:none; border:none; color:white; cursor:pointer;"><i data-feather="chevron-left"></i></button>
                                    <button class="icon-btn" onclick="nextMonth()" style="background:none; border:none; color:white; cursor:pointer;"><i data-feather="chevron-right"></i></button>
                                </div>
                            </div>
                            <div id="calendar-grid" style="display:grid; grid-template-columns: repeat(7, 1fr); gap:2px; background:var(--border-color); border:1px solid var(--border-color); border-radius:8px; overflow:hidden;">
                                <!-- Header -->
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">SUN</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">MON</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">TUE</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">WED</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">THU</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">FRI</div>
                                <div style="background:rgba(15, 17, 21, 0.5); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">SAT</div>
                                <!-- Days populated by JS -->
                            </div>
                        </div>

                        <!-- Assigned Events List -->
                        <div class="content-section" style="background:var(--card-bg); border-radius:16px; border:1px solid var(--border-color);">
                            <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color);">
                                <h3 style="font-size:1.1rem; color: var(--text-main);">Upcoming Activities</h3>
                            </div>
                            <div id="event-list" style="padding:1rem; display:flex; flex-direction:column; gap:1rem;">
                                <div style="text-align:center; padding:2rem; color:#64748b;">Loading schedule...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LEARNING DOCS SECTION -->
                <div id="learning-docs" class="section-view">
                    <div class="page-header">
                        <h2>Free Learning Resources</h2>
                        <p>Download modules and certificates for your professional growth.</p>
                    </div>
                    <div id="user-docs-list" class="resources-grid">
                        <!-- Populated by JS -->
                        <p style="color:#aaa;">Loading resources...</p>
                    </div>
                </div>

                <!-- SKILL TEST SECTION -->
                <div id="skill-test" class="section-view">
                    <div class="page-header">
                        <h2>Professional Skill Tests</h2>
                        <p>Boost your career by enrolling in free skill assessments. Get certified instantly.</p>
                    </div>

                    <!-- Catalog View -->
                    <div id="skill-catalog" style="display: block;">
                        <div id="skill-list" class="rec-grid"> <!-- reuse grid -->
                            <div style="color:#aaa; text-align:center; padding:2rem;">Loading skill tests...</div>
                        </div>
                    </div>

                    <!-- Active Test View (Hidden by default) -->
                    <div id="skill-detail-view" style="display: none;">
                        <button onclick="showSkillCatalog()" class="action-btn" style="background:transparent; border:1px solid #64748b; margin-bottom:1rem;">
                            <i data-feather="arrow-left" style="width:16px; margin-right:5px; vertical-align:middle;"></i> Back to Catalog
                        </button>
                        
                        <div class="doc-card" style="border:1px solid var(--primary); background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));">
                            <h2 id="active-test-title" style="color: var(--text-main); margin-bottom:0.5rem;">Test Title</h2>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                                 <span style="color:#94a3b8; font-size:0.9rem;" id="active-test-stages-count">5 Stages</span>
                                 <span class="badgex" style="background:rgba(16,185,129,0.2); color:#10b981; padding:2px 8px; border-radius:4px; font-size:0.8rem;" id="active-test-status">In Progress</span>
                            </div>
                            
                            <div style="display:flex; justify-content:space-between; color:#cbd5e1; font-size:0.85rem; margin-bottom:5px;">
                                <span>Progress</span>
                                <span id="active-progress-text">0%</span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar" id="active-progress-bar"></div>
                            </div>
                            
                            <div id="stages-container" class="stage-list">
                                <!-- Stages go here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI CHATBOT SECTION -->
                <div id="ai-chat" class="section-view">
                     <div class="page-header" style="max-width:700px; margin:0 auto 1.5rem auto;">
                        <h2>LGU3 AI Assistant</h2>
                        <p>Ask questions about livelihood programs, certifications, or the system.</p>
                         <div style="font-size:0.8rem; color:#94a3b8; background:rgba(255,255,255,0.05); padding:0.8rem; border-radius:12px; margin-top:1rem; border:1px solid rgba(255,255,255,0.1);">
                            <i data-feather="info" style="width:14px; vertical-align:middle; color:#3b82f6;"></i>
                            <strong>Did you know?</strong> Our Admin uses <em>Natural Language Processing (NLP)</em> to analyze the sentiment of your reports and <em>Machine Learning (ML)</em> to predict future program demand. Your voice matters!
                        </div>
                    </div>
                    
                    <div class="chat-section-container">
                        <div class="chat-header-ai">
                            <div class="ai-avatar">
                                <i data-feather="cpu"></i>
                            </div>
                            <div>
                                <h4 style="color:white; margin:0;">Smart LGU Assistant</h4>
                                <div style="display:flex; align-items:center;">
                                     <div class="status-dot"></div>
                                     <span style="font-size:0.75rem; color:#64748b; margin-left:0.5rem;">Always Online</span>
                                     <span class="ai-typing" id="ai-typing">Typing...</span>
                                 </div>
                             </div>
                             <button class="sidebar-toggle" onclick="toggleChatMini()" style="margin-left:auto;">
                                <i data-feather="minus" id="chat-mini-icon"></i>
                             </button>
                         </div>
                         
                         <div class="chat-body-ai" id="chat-messages">
                            <div class="chat-message ai">
                                Hello! I am your Barangay AI Assistant. How can I help you today? You can ask me about Barangay 175 requirements, livelihood programs, or how to report an incident.
                            </div>
                        </div>
                        
                        <form class="chat-footer-ai" onsubmit="event.preventDefault(); sendChatMessage();">
                            <input type="text" id="chat-input" class="ai-input" placeholder="Type your question here..." autocomplete="off">
                            <button type="submit" class="ai-send-btn">
                                <i data-feather="send" style="width:20px;"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- GENERATE ID SECTION -->
                <div id="generate-id" class="section-view">
                    <div class="page-header" style="text-align: center;">
                        <h2>Community Identity</h2>
                        <p>Generate and download your official Barangay ID</p>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 3rem; margin-top: 2rem;">
                        <div id="id-card-view" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem;">
                            <!-- Front View -->
                            <div class="id-card-item front-side" id="id-front">
                                <div class="id-header-premium">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 42px; height: 42px; background: #fff; border-radius: 8px; padding: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); overflow: hidden;">
                                            <img src="laforteza_logo.jpg" style="width:100%; height:100%; object-fit: cover;" alt="Laforteza Logo">
                                        </div>
                                        <div style="line-height: 1.2;">
                                            <div style="font-size: 0.6rem; font-weight: 700; color: var(--text-main); opacity: 0.8; letter-spacing: 0.5px;">REPUBLIC OF THE PHILIPPINES</div>
                                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.2px;">BRGY. 175 LAFORTEZA</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="id-body-premium">
                                    <div class="id-photo-premium">
                                        <?php if($user_image): ?>
                                            <img src="<?php echo $user_image; ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1.5rem; font-weight:700;">
                                                <?php echo $user_initials; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="id-info-premium">
                                        <div class="label-tiny">FULL NAME</div>
                                        <div class="val-name"><?php echo htmlspecialchars($user_name); ?></div>
                                        <div style="display: flex; gap: 1rem; margin-top: 0.8rem;">
                                            <div>
                                                <div class="label-tiny">STATUS</div>
                                                <div class="val-mini">CITIZEN</div>
                                            </div>
                                            <div>
                                                <div class="label-tiny">ISSUE DATE</div>
                                                <div class="val-mini"><?php echo date('M d, Y'); ?></div>
                                            </div>
                                        </div>
                                        <div style="margin-top: 0.8rem;">
                                            <div class="label-tiny">ID NUMBER</div>
                                            <div class="val-mini" style="font-family: 'Courier New', monospace; letter-spacing: 1px;">LG-<?php echo str_pad($_SESSION['user_id'], 6, '0', STR_PAD_LEFT); ?>-CP</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="id-footer-accent"></div>
                            </div>

                            <!-- Back View -->
                            <div class="id-card-item back-side" id="id-back">
                                <div style="height: 40px; background: #111; margin-bottom: 1.5rem;"></div>
                                <div style="padding: 0 1.5rem;">
                                    <div class="label-tiny" style="color: #94a3b8; font-size: 0.6rem;">PERMANENT RESIDENT OF</div>
                                    <div style="color: var(--text-main); font-size: 0.9rem; margin-bottom: 1rem; font-weight: 600;">Laforteza Oldings, Barangay 175, Caloocan City, PH.</div>
                                    
                                    <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                                        <div style="font-size: 0.6rem; color: #64748b; margin-bottom: 0.5rem; line-height: 1.4;">
                                            THIS CARD IS NON-TRANSFERABLE AND IS ISSUED PURSUANT TO THE CITIZENSHIP REGISTRATION ACT. IN CASE OF LOSS, PLEASE REPORT IMMEDIATELY TO THE BARANGAY SECRETARY.
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 0.75rem;">
                                            <div style="padding: 4px; background: white; border-radius: 4px; display: inline-flex;">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=50x50&data=USER-<?php echo $_SESSION['user_id']; ?>" style="width:45px; height:45px;" alt="QR">
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="width: 100px; height: 1px; background: rgba(255,255,255,0.2); margin-bottom: 5px;"></div>
                                                <div style="font-size: 0.5rem; color: #94a3b8;">AUTHORIZED SIGNATURE</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                            <button onclick="printID()" class="primary-action-btn">
                                <i data-feather="printer" style="width:16px; margin-right:8px; vertical-align:middle;"></i>
                                Print ID Card
                            </button>
                            <button onclick="downloadID()" class="action-btn" style="background: transparent; border: 1px solid var(--primary); color: var(--primary);">
                                <i data-feather="download" style="width:16px; margin-right:8px; vertical-align:middle;"></i>
                                Download Digital Copy
                            </button>
                        </div>
                    </div>

                    <style>
                        .id-card-item {
                            width: 340px;
                            height: 215px;
                            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                            border-radius: 16px;
                            position: relative;
                            overflow: hidden;
                            border: 1px solid rgba(255, 255, 255, 0.1);
                            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
                            color: var(--text-main);
                        }
                        .id-card-item.back-side { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); }
                        
                        .id-header-premium {
                            padding: 1.25rem;
                            background: rgba(99, 102, 241, 0.2);
                            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                        }
                        
                        .id-body-premium {
                            padding: 1.25rem;
                            display: flex;
                            gap: 1.25rem;
                            align-items: center;
                        }
                        
                        .id-photo-premium {
                            width: 100px;
                            height: 100px;
                            background: rgba(30, 41, 59, 0.5);
                            border-radius: 8px;
                            overflow: hidden;
                            border: 2px solid rgba(99, 102, 241, 0.3);
                        }
                        
                        .id-info-premium { flex: 1; }
                        
                        .label-tiny {
                            font-size: 0.55rem;
                            font-weight: 700;
                            color: var(--primary);
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            margin-bottom: 2px;
                        }
                        
                        .val-name {
                            font-size: 0.9rem;
                            font-weight: 800;
                            color: var(--text-main);
                            letter-spacing: 0.5px;
                        }
                        
                        .val-mini {
                            font-size: 0.75rem;
                            font-weight: 600;
                            color: #cbd5e1;
                        }
                        
                        .id-footer-accent {
                            position: absolute;
                            bottom: 0;
                            left: 0;
                            width: 100%;
                            height: 8px;
                            background: linear-gradient(90deg, var(--primary), #a855f7);
                        }

                        @media print {
                            body * { visibility: hidden !important; }
                            #id-card-view, #id-card-view * { visibility: visible !important; }
                            #id-card-view { position: fixed; left: 0; top: 0; width: 100%; display: flex; flex-direction: column; align-items: center; gap: 20px; background: white; padding: 20px; }
                            .id-card-item { border: 1px solid #111 !important; color: black !important; background: white !important; }
                            .id-header-premium { background: #eee !important; color: black !important; }
                            .val-name, .val-mini { color: black !important; }
                            .id-footer-accent { display: none; }
                        }
                    </style>
                </div>

            </div>
        </main>
    </div>
    <div id="stage-loader-overlay">
        <div class="premium-loader"></div>
        <p style="color:white; font-weight:600; letter-spacing:1px;">PROCESSING STAGE...</p>
    </div>

    <div id="stage-success-popup">
        <div class="success-icon-animated">
            <i data-feather="check-circle" style="width:48px; height:48px;"></i>
        </div>
        <h2 style="color:white; margin-bottom:0.5rem;">Congrats!</h2>
        <p style="color:#94a3b8; font-size:1.1rem; font-weight:500;">Good work! Stage Complete.</p>
    </div>

    <script>
        feather.replace();

        // Theme System
        function setTheme(theme) {
            if (theme === 'light') {
                document.body.classList.add('light-theme');
                if(document.getElementById('btn-light')) document.getElementById('btn-light').classList.add('active');
                if(document.getElementById('btn-dark')) document.getElementById('btn-dark').classList.remove('active');
            } else {
                document.body.classList.remove('light-theme');
                if(document.getElementById('btn-dark')) document.getElementById('btn-dark').classList.add('active');
                if(document.getElementById('btn-light')) document.getElementById('btn-light').classList.remove('active');
            }
            localStorage.setItem('theme', theme);
        }

        // Initialize theme on load
        const savedTheme = localStorage.getItem('theme') || 'dark';
        setTheme(savedTheme);

        // Sidebar System
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const toggleBtn = document.getElementById('sidebar-btn');
            
            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('sidebar-collapsed');
            
            if(sidebar.classList.contains('minimized')) {
                toggleBtn.innerHTML = '<i data-feather="chevron-right"></i>';
            } else {
                toggleBtn.innerHTML = '<i data-feather="chevron-left"></i>';
            }
            feather.replace();
            localStorage.setItem('sidebarMinimized', sidebar.classList.contains('minimized'));
        }

        function toggleChatMini() {
            const chat = document.querySelector('.chat-section-container');
            const icon = document.getElementById('chat-mini-icon');
            chat.classList.toggle('minimized');
            
            if(chat.classList.contains('minimized')) {
                icon.setAttribute('data-feather', 'maximize-2');
            } else {
                icon.setAttribute('data-feather', 'minus');
            }
            feather.replace();
        }

        // Apply saved sidebar state
        window.addEventListener('load', () => {
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
            if(isMinimized) {
                document.getElementById('sidebar').classList.add('minimized');
                document.getElementById('main-content').classList.add('sidebar-collapsed');
                if(document.getElementById('sidebar-btn')) document.getElementById('sidebar-btn').innerHTML = '<i data-feather="chevron-right"></i>';
                feather.replace();
            }
        });

        // --- Navigation ---
        function showSection(id, element) {
            document.querySelectorAll('.section-view').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            document.getElementById(id).classList.add('active');
            if(element) element.classList.add('active');
            
            // Refresh data
            if(id === 'home') fetchStats();
            if(id === 'history') fetchHistory();
            if(id === 'schedule') fetchCalendar();
            if(id === 'learning-docs') fetchUserDocs();
            if(id === 'skill-test') fetchSkillTests();
            if(id === 'ai-chat') {
                const cb = document.getElementById('chat-messages');
                cb.scrollTop = cb.scrollHeight;
            }
        }

        // --- API Calls ---
        
        async function fetchRecommended() {
            try {
                const res = await fetch('api.php?action=get_recommendations');
                const data = await res.json();
                const container = document.getElementById('ai-recommendations');
                
                if(data.length === 0) {
                     container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:2rem; color:#aaa;">No specific recommendations found. Update your profile!</div>';
                     return;
                }
                
                container.innerHTML = ''; // Clear loading text
                data.forEach(p => {
                    // Score handling: if score is null/undefined it's a generic fallback
                    // If score is 0 it was a 0% match (which we want to avoid)
                    let scoreHtml = '';
                    if (p.score !== null && p.score !== undefined) {
                        const pct = Math.round(p.score * 10);
                        scoreHtml = `<span class="rec-score">${pct}% Match</span>`;
                    } else {
                        scoreHtml = `<span class="rec-score" style="color:#6366f1;">AI Choice</span>`;
                    }

                    container.innerHTML += `
                        <div class="rec-card">
                            <span class="rec-tag">${p.category}</span>
                            ${scoreHtml}
                            <h3 style="color: var(--text-main); margin-bottom:0.5rem; font-size:1.1rem;">${p.title}</h3>
                            <p style="color:var(--text-muted); font-size:0.85rem; line-height:1.4;">${p.description || 'Program description unavailable.'}</p>
                            <button class="action-btn" onclick="applyFor(${p.id})" style="margin-top:1.25rem; width:100%; font-weight:600; background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.2);">Enroll for Free</button>
                        </div>
                    `;
                });
            } catch(e) {
                 console.error(e);
                 document.getElementById('ai-recommendations').innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:#ef4444;">Unable to load recommendations. Please try refreshing.</div>';
            }
        }
        
        async function applyFor(id) {
            if(!confirm('Apply for this program for free?')) return;
            try {
                const res = await fetch('api.php?action=apply_program', {
                    method: 'POST', body: JSON.stringify({program_id: id})
                });
                const data = await res.json();
                if(data.success) {
                    alert('Application sent! Admin will review and send learning materials.');
                    fetchMyApplications();
                } else {
                    alert(data.error);
                }
            } catch(e) { alert('Error applying'); }
        }
        
        async function fetchMyApplications() {
             try {
                const res = await fetch('api.php?action=get_my_applications');
                const data = await res.json();
                const container = document.getElementById('my-applications-list');
                if(!container) return; // if header not added yet
                
                document.getElementById('app-count').innerText = `${data.length} Program${data.length !== 1 ? 's' : ''}`;
                container.innerHTML = '';
                
                if(data.length === 0) {
                    container.innerHTML = '<div style="color:var(--text-muted); text-align:center; padding:3rem; background:rgba(30,41,59,0.3); border-radius:16px; border:1px dashed #475569;">You haven\'t applied to any programs yet. Start learning today!</div>';
                    return;
                }
                
                data.forEach(app => {
                    let actionHtml = '';
                    if(app.status === 'approved') {
                        actionHtml = `
                            <a href="${app.material_link}" target="_blank" class="download-btn">
                                <i data-feather="download"></i>
                                Get Study Module
                            </a>
                        `;
                    } else {
                        actionHtml = `
                            <div class="pending-tag">
                                <i data-feather="clock" style="width:14px; margin-right:4px; vertical-align:middle;"></i>
                                Processing
                            </div>
                        `;
                    }
                    
                    container.innerHTML += `
                        <div class="app-card">
                            <div class="app-info">
                                <h4>${app.title}</h4>
                                <p>${app.category} • Applied on ${app.created_at.split(' ')[0]}</p>
                            </div>
                            <div class="app-actions">
                                ${actionHtml}
                            </div>
                        </div>
                    `;
                });
                feather.replace();
             } catch(e) {}
        }

        async function fetchStats() {
            try {
                const res = await fetch('api.php?action=user_stats');
                const data = await res.json();
                document.getElementById('stat-total').innerText = data.total_reports;
                document.getElementById('stat-pending').innerText = data.pending_reports;
            } catch(e) {}
        }

        async function fetchHistory() {
            try {
                const res = await fetch('api.php?action=my_reports');
                const data = await res.json();
                const tbody = document.getElementById('history-table');
                
                if(data.length === 0){
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No reports found.</td></tr>';
                    return;
                }
                
                tbody.innerHTML = '';
                data.forEach(row => {
                    let badgeClass = 'pending';
                    if(row.status === 'approved') badgeClass = 'active';
                    else if(row.status === 'rejected') badgeClass = 'warning';

                    tbody.innerHTML += `<tr>
                        <td>
                            <div style="font-weight:500;">${row.title}</div>
                            <div style="font-size:0.85rem; color:var(--text-muted); margin-top:4px;">${row.description.substring(0,50)}...</div>
                        </td>
                        <td>${row.created_at}</td>
                        <td><span class="badge ${badgeClass}">${row.status}</span></td>
                    </tr>`;
                });
            } catch(e) {}
        }

        // --- Forms ---

        // Profile Upload
        function previewFile() {
             const preview = document.getElementById('preview-img');
             const file = document.querySelector('input[type=file]').files[0];
             const reader = new FileReader();
             const text = document.getElementById('preview-text');
             const saveBtn = document.getElementById('save-photo-btn');

             reader.addEventListener("load", function () {
                preview.src = reader.result;
                preview.style.display = 'block';
                if(text) text.style.display = 'none';
                if(saveBtn) saveBtn.style.display = 'block';
             }, false);

             if (file) {
                reader.readAsDataURL(file);
             }
        }

        async function uploadProfilePicture() {
            const fileInput = document.getElementById('profile_image');
            if(fileInput.files.length === 0) return;

            const formData = new FormData();
            formData.append('profile_image', fileInput.files[0]);

            const saveBtn = document.getElementById('save-photo-btn');
            const originalText = saveBtn.innerText;
            saveBtn.innerText = 'Updating...';
            saveBtn.disabled = true;

            try {
                const res = await fetch('api.php?action=update_profile', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if(data.success) {
                    // Update Header Avatar Instantly
                    const headerContainer = document.getElementById('header-avatar-container');
                    if(headerContainer) {
                        headerContainer.innerHTML = `<img src="${data.image_url}" class="avatar" id="header-avatar" style="object-fit:cover;">`;
                    }
                    
                    showSuccessModal("You are so pogi and maganda! Your profile picture is updated.");
                    saveBtn.style.display = 'none';
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(err) {
                alert('Upload failed.');
            } finally {
                saveBtn.innerText = originalText;
                saveBtn.disabled = false;
            }
        }

        function showSuccessModal(message) {
            const modal = document.getElementById('general-success-modal');
            const msgEl = document.getElementById('success-modal-msg');
            if(msgEl) msgEl.innerText = message;
            if(modal) modal.style.display = 'flex';
            feather.replace();
        }

        // Create Report
        document.getElementById('reportForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = document.getElementById('report-title').value;
            const desc = document.getElementById('report-desc').value;

            try {
                const res = await fetch('api.php?action=create_report', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ title: title, description: desc })
                });
                
                // Read as text first to avoid JSON parse error on non-JSON response
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch(e) {
                    console.error("Server API Error (Raw):", text);
                    alert("Server Error. Check console for details.");
                    return;
                }
                
                if(data.success) {
                    alert('Report submitted successfully!');
                    document.getElementById('reportForm').reset();
                    showSection('history', document.querySelectorAll('.nav-item')[3]);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(err) {
                console.error(err);
                alert('Submission failed. Check network');
            }
        });

        // Logout handler is defined in the main init section below (uses modal)

        // Load badges on profile view
        fetchBadges();

        // --- Notifications Logic ---
        function toggleNotifs(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.classList.toggle('show');
            if(dropdown.classList.contains('show')) fetchNotifs();
        }

        async function fetchNotifs() {
            try {
                const res = await fetch('api.php?action=get_notifications');
                const data = await res.json();
                const list = document.getElementById('notif-list');
                const badge = document.getElementById('notif-count');
                
                let unreadCount = data.filter(n => n.is_read == 0).length;
                if(unreadCount > 0) {
                    badge.innerText = unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }

                if(data.length === 0) {
                    list.innerHTML = '<div class="notif-empty">No new notifications</div>';
                    return;
                }

                list.innerHTML = '';
                data.forEach(n => {
                    const item = document.createElement('div');
                    item.className = `notif-item ${n.is_read == 0 ? 'unread' : ''}`;
                    item.innerHTML = `
                        <h5>${n.title}</h5>
                        <p>${n.message}</p>
                        <small>${n.created_at}</small>
                    `;
                    item.onclick = () => markAsRead(n.id);
                    list.appendChild(item);
                });
            } catch(e) {}
        }

        async function fetchBadges() {
            try {
                const res = await fetch('api.php?action=get_skill_progress');
                const data = await res.json();
                const container = document.getElementById('badges-container');
                const countBadge = document.getElementById('badge-count');
                
                const completed = data.filter(t => t.status === 'completed' || t.user_status === 'completed');
                countBadge.innerText = completed.length;
                
                if(completed.length === 0) {
                    container.innerHTML = `
                        <div style="text-align:center; padding:2rem; color:var(--text-muted); grid-column:1/-1;">
                            <i data-feather="award" style="width:40px; opacity:0.3; margin-bottom:0.5rem;"></i>
                            <div style="font-size:0.9rem;">Complete skill tests to earn badges!</div>
                        </div>
                    `;
                    feather.replace();
                    return;
                }
                
                container.innerHTML = '';
                completed.forEach(test => {
                    container.innerHTML += `
                        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(251, 191, 36, 0.2); border-radius:12px; padding:1rem; text-align:center; transition:transform 0.2s; cursor:pointer;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div style="width:60px; height:60px; background:rgba(251, 191, 36, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 0.8rem auto;">
                                <i data-feather="award" style="width:30px; height:30px; color:#fbbf24;"></i>
                            </div>
                            <div style="color: var(--text-main); font-size:0.85rem; font-weight:600; margin-bottom:0.3rem;">${test.title}</div>
                            <div style="color:#fbbf24; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.5px;">Certified</div>
                        </div>
                    `;
                });
                feather.replace();
            } catch(e) { console.error(e); }
        }

        async function markAsRead(id) {
            await fetch('api.php?action=mark_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });
            fetchNotifs();
        }

        async function markAllRead() {
            try {
                const res = await fetch('api.php?action=mark_all_read', {
                    method: 'POST'
                });
                const data = await res.json();
                if(data.success) {
                    fetchNotifs(); // Refresh to show updated state
                }
            } catch(e) {
                console.error('Failed to mark all as read:', e);
            }
        }

        async function fetchUserDocs() {
            try {
                const res = await fetch('api.php?action=get_docs');
                const data = await res.json();
                const container = document.getElementById('user-docs-list');
                container.innerHTML = '';
                
                if(data.length === 0) {
                    container.innerHTML = '<p style="color:var(--text-muted);">No resources available yet.</p>';
                    return;
                }
                
                data.forEach(d => {
                    container.innerHTML += `
                        <div class="doc-card">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div class="doc-icon-wrapper">
                                    <i data-feather="file-text" style="width:24px; height:24px;"></i>
                                </div>
                                <span class="doc-cat-badge">${d.category}</span>
                            </div>
                            <div>
                                <h4 class="doc-title">${d.title}</h4>
                                <p style="color:#94a3b8; font-size:0.85rem; margin-top:0.5rem; line-height:1.5;">Official livelihood resource for professional distribution and learning.</p>
                            </div>
                            <div class="doc-footer">
                                <a href="${d.file_path}" target="_blank" class="download-btn-premium">
                                    <i data-feather="download" style="width:18px;"></i>
                                    Access Material
                                </a>
                            </div>
                        </div>
                    `;
                });
                feather.replace();
            } catch(e) {}
        }

        async function sendChatMessage() {
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if(!message) return;
            
            const chatBody = document.getElementById('chat-messages');
            const typing = document.getElementById('ai-typing');
            
            // Add user message
            addMessageToChat('user', message);
            input.value = '';
            
            // Show typing
            typing.style.display = 'block';
            chatBody.scrollTop = chatBody.scrollHeight;
            
            try {
                const res = await fetch('api.php?action=chatbot', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({message: message})
                });
                const data = await res.json();
                
                // Simulate delay for "AI thinking"
                setTimeout(() => {
                    typing.style.display = 'none';
                    addMessageToChat('ai', data.response);
                }, 800);
                
            } catch(e) {
                typing.style.display = 'none';
                addMessageToChat('ai', "I'm having trouble connecting to my brain right now. Please try again later.");
            }
        }

        function addMessageToChat(role, text) {
            const chatBody = document.getElementById('chat-messages');
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-message ${role}`;
            msgDiv.innerText = text;
            chatBody.appendChild(msgDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', () => {
            document.getElementById('notif-dropdown').classList.remove('show');
        });

        async function requestEditPermission() {
            try {
                const res = await fetch('api.php?action=request_edit');
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if(data.success) {
                        showSuccessModal(data.message);
                    } else {
                        alert('Request refused: ' + (data.error || 'Unknown error'));
                    }
                } catch(e) {
                    console.error('Server response was not JSON:', text);
                    alert('Server Error: ' + text.substring(0, 200));
                }
            } catch(e) { 
                alert('Connection failure. Local server might be down or not responding.');
            }
        }

        async function checkEditAuth() {
            try {
                const res = await fetch('api.php?action=check_edit_auth');
                const data = await res.json();
                const notice = document.getElementById('edit-auth-notice');
                const input = document.getElementById('profile-name-input');
                const lockIcon = document.getElementById('lock-icon');
                const requestBtn = document.getElementById('request-perm-btn');
                const saveBtn = document.getElementById('save-profile-btn');
                const timerSpan = document.getElementById('auth-timer');

                if (data.authorized) {
                    notice.style.display = 'block';
                    input.readOnly = false;
                    input.style.border = '1px solid #10b981';
                    lockIcon.innerHTML = '<i data-feather="edit-3" style="width:18px; color:#10b981;"></i>';
                    requestBtn.style.display = 'none';
                    saveBtn.style.display = 'block';
                    
                    // Update timer
                    const mins = Math.floor(data.remaining / 60);
                    const secs = data.remaining % 60;
                    timerSpan.innerText = `${mins}:${secs.toString().padStart(2, '0')}`;
                    feather.replace();
                } else {
                    notice.style.display = 'none';
                    input.readOnly = true;
                    input.style.border = '';
                    lockIcon.innerHTML = '<i data-feather="lock" style="width:18px;"></i>';
                    requestBtn.style.display = 'block';
                    saveBtn.style.display = 'none';
                    feather.replace();
                }
            } catch(e) {}
        }

        // Handle Profile Form Submission (Update Name)
        document.getElementById('profileForm').onsubmit = async (e) => {
            e.preventDefault();
            const newName = document.getElementById('profile-name-input').value;
            if(!newName) return;

            try {
                const res = await fetch('api.php?action=update_name', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({full_name: newName})
                });
                const data = await res.json();
                if(data.success) {
                    showSuccessModal('Profile name updated successfully!');
                    document.getElementById('header-name').innerText = newName;
                    checkEditAuth();
                } else alert(data.error);
            } catch(e) { alert('Update failed'); }
        };

        // Init
        fetchRecommended();
        fetchMyApplications();
        fetchStats();
        fetchNotifs();
        checkEditAuth();
        setInterval(fetchNotifs, 10000); // Poll every 10s
        setInterval(checkEditAuth, 5000); // Check auth every 5s

        // --- Calendar Logic ---
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let calendarEvents = [];

        async function fetchCalendar() {
            try {
                const res = await fetch('api.php?action=get_calendar');
                calendarEvents = await res.json();
                renderCalendar();
                renderEventList();
            } catch(e) { console.error("Cal Load Error", e); }
        }

        function renderCalendar() {
            const grid = document.getElementById('calendar-grid');
            const header = document.getElementById('calendar-month-year');
            if(!grid) return;

            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            header.innerText = `${monthNames[currentMonth]} ${currentYear}`;

            const headersCount = 7;
            while(grid.children.length > headersCount) grid.removeChild(grid.lastChild);

            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            for(let i=0; i<firstDay; i++) {
                const dev = document.createElement('div');
                dev.style.cssText = "background:rgba(15,17,21,0.2); height:100px; padding:10px; opacity:0.3;";
                grid.appendChild(dev);
            }

            for(let d=1; d<=daysInMonth; d++) {
                const dayBox = document.createElement('div');
                dayBox.style.cssText = "background:rgba(30,41,59,0.3); height:100px; padding:10px; font-size:0.9rem; border:1px solid rgba(255,255,255,0.02); display:flex; flex-direction:column; gap:5px; overflow:hidden;";
                
                const dayNum = document.createElement('span');
                dayNum.innerText = d;
                dayNum.style.fontWeight = "600";
                dayBox.appendChild(dayNum);

                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                
                const dayEvents = calendarEvents.filter(e => e.event_date === dateStr);
                dayEvents.forEach(ev => {
                    const dot = document.createElement('div');
                    let color = "var(--primary)";
                    if(ev.type === 'training') color = "#10b981";
                    if(ev.type === 'work') color = "#f59e0b";
                    
                    dot.style.cssText = `background:${color}; padding:2px 6px; border-radius:4px; font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:white;`;
                    dot.innerText = ev.title;
                    dayBox.appendChild(dot);
                });

                grid.appendChild(dayBox);
            }
            feather.replace();
        }

        function renderEventList() {
            const list = document.getElementById('event-list');
            list.innerHTML = '';
            
            if(calendarEvents.length === 0) {
                list.innerHTML = '<div style="text-align:center; padding:2rem; color:#64748b;">No activities scheduled for you.</div>';
                return;
            }

            calendarEvents.forEach(ev => {
                const timeStr = ev.event_time.substring(0,5);
                let actionsHtml = '';
                
                if(ev.user_status === 'pending') {
                    actionsHtml = `
                        <div style="display:flex; gap:0.5rem; margin-top:0.8rem;">
                            <button onclick="respondToEvent(${ev.id}, 'joined')" style="flex:1; padding:0.4rem; background:#10b981; border:none; color:white; border-radius:6px; cursor:pointer; font-size:0.75rem;">Join</button>
                            <button onclick="respondToEvent(${ev.id}, 'declined')" style="flex:1; padding:0.4rem; background:transparent; border:1px solid #ef4444; color:#ef4444; border-radius:6px; cursor:pointer; font-size:0.75rem;">Decline</button>
                        </div>
                    `;
                } else if(ev.user_status) {
                    const statusColor = ev.user_status === 'joined' ? '#10b981' : '#ef4444';
                    actionsHtml = `<div style="margin-top:0.8rem; font-size:0.75rem; color:${statusColor}; font-weight:600; text-transform:uppercase;">
                        ${ev.user_status === 'joined' ? '<i data-feather="check-circle" style="width:12px; vertical-align:middle;"></i> Joined' : '<i data-feather="x-circle" style="width:12px; vertical-align:middle;"></i> Declined'}
                    </div>`;
                }

                list.innerHTML += `
                    <div style="background:rgba(255,255,255,0.03); border-radius:12px; padding:1rem; border:1px solid var(--border-color);">
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                            <span class="badge" style="font-size:0.7rem; background:rgba(99, 102, 241, 0.2); color:var(--primary); ">${ev.type.toUpperCase()}</span>
                            <span style="font-size:0.8rem; color:#64748b;"><i data-feather="clock" style="width:12px; vertical-align:middle; margin-right:4px;"></i>${ev.event_date} @ ${timeStr}</span>
                        </div>
                        <h4 style="margin:0 0 5px 0; font-size:1rem; color: var(--text-main);">${ev.title}</h4>
                        <p style="margin:0; font-size:0.85rem; color:#94a3b8;">${ev.description || 'No description'}</p>
                        ${actionsHtml}
                    </div>
                `;
            });
            feather.replace();
        }

        async function respondToEvent(id, status) {
            try {
                const res = await fetch('api.php?action=respond_to_event', {
                    method: 'POST',
                    body: JSON.stringify({ id, status })
                });
                const data = await res.json();
                if(data.success) {
                    fetchCalendar();
                } else {
                    alert(data.error);
                }
            } catch(e) { console.error(e); }
        }

        function nextMonth() {
            currentMonth++;
            if(currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderCalendar();
        }

        function prevMonth() {
            currentMonth--;
            if(currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar();
        }

        // --- SKILL TEST FUNCTIONS ---
        let currentTestId = null;

        async function fetchSkillTests() {
            try {
                const res = await fetch('api.php?action=get_skill_progress');
                const data = await res.json();
                const container = document.getElementById('skill-list');
                
                if(data.length === 0) {
                    container.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:2rem; color:#aaa;">No skill tests available at the moment.</div>';
                    return;
                }

                container.innerHTML = '';
                data.forEach(test => {
                    const isEnrolled = test.current_stage > 0;
                    // Use user_status for reliability. Fallback to status if aliased.
                    const status = test.user_status || test.status; 
                    const isCompleted = status === 'completed';
                    
                    // Calc progress: if completed force 100, else calc based on stage
                    let progress = 0;
                    if(isEnrolled) {
                        if(isCompleted) progress = 100;
                        else progress = test.total_stages > 0 ? Math.round(((test.current_stage - 1) / test.total_stages) * 100) : 0;
                    }

                    const btnText = isEnrolled ? (isCompleted ? 'View Certificate' : 'Continue Learning') : 'Enroll Free';
                    const btnAction = isEnrolled ? `openSkillTest(${test.id}, '${test.title.replace(/'/g, "\\'")}', ${test.total_stages})` : `enrollSkill(${test.id})`;
                    
                    container.innerHTML += `
                        <div class="skill-card">
                            <img src="${test.thumbnail}" class="skill-thumb" onerror="this.src='https://via.placeholder.com/600x400?text=Skill+Test'">
                            <div class="skill-body">
                                <h3 class="skill-title">${test.title}</h3>
                                <p class="skill-desc">${test.description}</p>
                                ${isEnrolled ? `
                                    <div style="margin-bottom:10px;">
                                        <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:#cbd5e1; margin-bottom:4px;">
                                            <span>${isCompleted ? 'Completed' : 'In Progress'}</span>
                                            <span>${progress}%</span>
                                        </div>
                                        <div class="progress-container" style="height:4px; margin-bottom:0;"><div class="progress-bar" style="width:${progress}%"></div></div>
                                    </div>
                                ` : ''}
                                <button class="primary-action-btn" onclick="${btnAction}" style="width:100%; margin-top:auto;">${btnText}</button>
                            </div>
                        </div>
                    `;
                });
            } catch(e) { console.error(e); }
        }

        async function enrollSkill(tid) {
            if(!confirm('Enroll in this skill test for free?')) return;
            try {
                const res = await fetch('api.php?action=enroll_skill', {
                    method: 'POST', body: JSON.stringify({test_id: tid})
                });
                const data = await res.json();
                if(data.success) {
                    fetchSkillTests(); 
                    // Automatically open it?
                    // Let's just refresh list for now as per "attach and start" could mean auto-open but list view needs update first.
                    // Actually let's just refresh.
                }
            } catch(e) { alert('Error enrolling'); }
        }

        async function openSkillTest(tid, title, totalStages) {
            currentTestId = tid;
            document.getElementById('skill-catalog').style.display = 'none';
            document.getElementById('skill-detail-view').style.display = 'block';
            document.getElementById('active-test-title').innerText = title;
            
            try {
                // Fetch latest progress
                const progressRes = await fetch('api.php?action=get_skill_progress');
                const progressData = await progressRes.json();
                const myTest = progressData.find(t => t.id == tid);
                const currentStage = myTest.current_stage;
                const status = myTest.user_status;
                
                document.getElementById('active-test-status').innerText = status === 'completed' ? 'Completed' : 'Stage ' + currentStage + ' of ' + totalStages;
                const pct = status === 'completed' ? 100 : Math.round(((currentStage - 1) / totalStages) * 100);
                document.getElementById('active-progress-text').innerText = pct + '%';
                document.getElementById('active-progress-bar').style.width = pct + '%';

                // Fetch Stages
                const res = await fetch('api.php?action=get_test_stages&test_id=' + tid);
                const stages = await res.json();
                const container = document.getElementById('stages-container');
                container.innerHTML = '';
                
                stages.forEach(stage => {
                    const num = parseInt(stage.stage_number);
                    let stateClass = '';
                    let icon = '<span style="color:#64748b; font-size:0.9rem;">' + num + '</span>';
                    let btn = '';

                    if (status === 'completed') {
                        stateClass = 'completed';
                        icon = '<i data-feather="check" style="width:14px;"></i>';
                        btn = '<span style="color:#10b981; font-size:0.85rem;">Completed</span>';
                    } else {
                        if(num < currentStage) {
                            stateClass = 'completed';
                            icon = '<i data-feather="check" style="width:14px;"></i>';
                            btn = '<span style="color:#10b981; font-size:0.85rem;">Completed</span>';
                        } else if (num == currentStage) {
                            stateClass = 'active';
                            icon = '<span style="color:var(--primary); font-weight:bold;">' + num + '</span>';
                            btn = `<button class="action-btn" onclick="viewStageContent('${stage.title.replace(/'/g, "\\'")}', '${stage.video_url}', ${num}, ${totalStages})" style="padding:0.4rem 0.8rem; font-size:0.8rem;">Start</button>`;
                        } else {
                            stateClass = 'locked';
                            icon = '<i data-feather="lock" style="width:14px;"></i>';
                        }
                    }
                    
                    container.innerHTML += `
                        <div class="stage-item ${stateClass}">
                            <div style="display:flex; align-items:center;">
                                <div class="stage-check">${icon}</div>
                                <div>
                                    <div style="color: var(--text-main); font-weight:500;">${stage.title}</div>
                                    <div style="color:#94a3b8; font-size:0.85rem;">${stage.content.substring(0, 50)}...</div>
                                </div>
                            </div>
                            <div>${btn}</div>
                        </div>
                    `;
                });
                feather.replace();
            } catch(e) {}
        }

        function showSkillCatalog() {
            document.getElementById('skill-catalog').style.display = 'block';
            document.getElementById('skill-detail-view').style.display = 'none';
            fetchSkillTests();
        }

        function viewStageContent(title, url, stageNum, totalStages) {
            // Check for modal
            if(!document.getElementById('video-modal')) {
                const modal = document.createElement('div');
                modal.id = 'video-modal';
                modal.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center; display:none;';
                modal.innerHTML = `
                    <div style="background:#1e293b; padding:1.5rem; border-radius:16px; width:90%; max-width:600px; border:1px solid #334155;">
                        <h3 id="vm-title" style="color:white; margin-bottom:1rem;"></h3>
                        <div style="aspect-ratio:16/9; background:#000; margin-bottom:1rem; border-radius:8px; overflow:hidden;">
                            <iframe id="vm-frame" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <div style="display:flex; justify-content:end; gap:1rem;">
                            <button onclick="document.getElementById('video-modal').style.display='none'" class="action-btn" style="background:transparent; border:1px solid #64748b;">Close</button>
                            <button id="vm-complete-btn" class="primary-action-btn">Complete & Continue</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            document.getElementById('vm-title').innerText = title;
            document.getElementById('vm-frame').src = url;
            document.getElementById('video-modal').style.display = 'flex';
            document.getElementById('vm-complete-btn').onclick = function() {
                document.getElementById('video-modal').style.display = 'none';
                finishStage(stageNum, totalStages);
                document.getElementById('vm-frame').src = ''; // stop video
            };
        }

        async function finishStage(num, totalStages) {
            const loader = document.getElementById('stage-loader-overlay');
            const popup = document.getElementById('stage-success-popup');
            
            // Show Loader
            loader.style.display = 'flex';
            
            try {
                const res = await fetch('api.php?action=complete_stage', {
                    method: 'POST', body: JSON.stringify({test_id: currentTestId, stage_number: num})
                });
                const data = await res.json();
                
                if(data.success) {
                    // Slight delay for premium feel
                    setTimeout(() => {
                        loader.style.display = 'none';
                        popup.style.display = 'block';
                        feather.replace();
                        
                        setTimeout(() => {
                            popup.style.display = 'none';
                            
                            // Update list view background
                            fetchSkillTests(); 
                            
                            if(data.test_completed) {
                                const title = document.getElementById('active-test-title').innerText;
                                openSkillTest(currentTestId, title, totalStages); // Update to completed view
                                showCompletionModal();
                            } else {
                                const title = document.getElementById('active-test-title').innerText;
                                openSkillTest(currentTestId, title, totalStages);
                            }
                        }, 2000); // Show success for 2 seconds
                    }, 1000); // Loading for 1 second
                } else {
                    loader.style.display = 'none';
                    alert(data.error || 'Failed to complete stage');
                }
            } catch(e) {
                loader.style.display = 'none';
                alert('Connection error');
            }
        }

        function showCompletionModal() {
            if(!document.getElementById('completion-styles')) {
                const style = document.createElement('style');
                style.id = 'completion-styles';
                style.innerHTML = `
                    @keyframes popupScale { 0% { transform:scale(0.8); opacity:0; } 100% { transform:scale(1); opacity:1; } }
                    @keyframes badgePulse { 0% { transform:scale(1); box-shadow:0 0 0 0 rgba(251, 191, 36, 0.4); } 70% { transform:scale(1.1); box-shadow:0 0 0 20px rgba(251, 191, 36, 0); } 100% { transform:scale(1); box-shadow:0 0 0 0 rgba(251, 191, 36, 0); } }
                    .confetti { position: absolute; width: 10px; height: 10px; animation: confetti-fall 4s linear infinite; opacity:0.8; }
                    @keyframes confetti-fall { 0% { transform: translateY(-10vh) rotate(0deg); opacity:1; } 100% { transform: translateY(110vh) rotate(720deg); opacity:0; } }
                `;
                document.head.appendChild(style);
            }

            const modal = document.createElement('div');
            modal.style.cssText = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(5px);";
            
            modal.innerHTML = `
                <div style="background:#1e293b; padding:2.5rem; border-radius:24px; border:1px solid rgba(251, 191, 36, 0.3); text-align:center; max-width:450px; width:90%; position:relative; overflow:hidden; animation: popupScale 0.4s ease-out; box-shadow:0 20px 50px rgba(0,0,0,0.5);">
                    <div style="position:absolute; top:0; left:0; width:100%; height:6px; background:linear-gradient(90deg, #fbbf24, #d97706);"></div>
                    <div style="width:100px; height:100px; background:rgba(251, 191, 36, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem auto; animation: badgePulse 2s infinite;">
                        <i data-feather="award" style="width:50px; height:50px; color:#fbbf24;"></i>
                    </div>
                    <h2 style="color: var(--text-main); margin-bottom:0.5rem; font-family:'Inter', sans-serif; font-size:1.8rem;">Congratulations!</h2>
                    <p style="color:#94a3b8; margin-bottom:2rem; line-height:1.5;">You have successfully completed this skill assessment. Your progress has been recorded!</p>
                    <div style="background:rgba(255,255,255,0.03); padding:1rem; border-radius:12px; margin-bottom:2rem; border: 1px solid var(--border-color);">
                        <div style="color:#fbbf24; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:1px; margin-bottom:0.2rem;">Achievement Unlocked</div>
                        <div style="color:white; font-size:1.1rem; font-weight:600;">Skill Verified</div>
                    </div>
                    <button onclick="this.closest('div').parentElement.remove();" style="background:linear-gradient(135deg, #fbbf24, #d97706); color:#1e293b; border:none; padding:1rem 2.5rem; border-radius:12px; font-weight:700; cursor:pointer; font-size:1rem; width:100%; box-shadow:0 4px 15px rgba(251, 191, 36, 0.3); transition:transform 0.2s;">View Certificate</button>
                </div>
            `;

            const colors = ['#fbbf24', '#3b82f6', '#10b981', '#ef4444', '#a855f7'];
            for(let i=0; i<40; i++) {
                const c = document.createElement('div');
                c.className = 'confetti';
                c.style.left = Math.random() * 100 + '%';
                c.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                c.style.animationDuration = (Math.random() * 3 + 2) + 's';
                c.style.animationDelay = (Math.random() * 2) + 's';
                modal.appendChild(c);
            }

            document.body.appendChild(modal);
            feather.replace();
        }

        function printID() {
            window.print();
        }

        function downloadID() {
            alert('Digital Copy Generated! In a real system, this would trigger a PDF/Image generation. You can now use the Print feature to save as PDF.');
        }

        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Dashboard Init');
            
            // Logout Logic
            const logoutBtn = document.querySelector('.logout-btn');
            if(logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const modal = document.getElementById('logout-modal');
                    modal.style.display = 'flex'; // Use flex to center
                });
            }

            fetchRecommended();
            // Other inits if needed:
            if(typeof fetchMyApplications === 'function') fetchMyApplications();
            
            // Notification poller
            setInterval(() => {
                if(typeof fetchNotifications === 'function') fetchNotifications();
            }, 60000); // Pulse every min
            if(typeof fetchNotifications === 'function') fetchNotifications();
        });
    </script>
    <!-- Logout Modal -->
    <div id="logout-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; backdrop-filter:blur(8px); align-items:center; justify-content:center;">
        <div style="background:#0f172a; padding:2.5rem; border-radius:24px; border:1px solid rgba(255,255,255,0.08); width:90%; max-width:400px; text-align:center; box-shadow:0 25px 60px -12px rgba(0,0,0,0.6); animation: popupScale 0.3s ease-out;">
            <div style="width:80px; height:80px; background:rgba(239, 68, 68, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <i data-feather="log-out" style="color:#ef4444; width:36px; height:36px;"></i>
            </div>
            <h3 style="color: var(--text-main); margin-bottom:0.75rem; font-size:1.5rem; font-weight:700;">Signing Out?</h3>
            <p style="color:#94a3b8; font-size:1rem; margin-bottom:2.5rem; line-height:1.5;">Are you sure you want to end your session?</p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <button onclick="document.getElementById('logout-modal').style.display='none'" style="padding:1rem; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color: var(--text-main); border-radius:14px; cursor:pointer; font-weight:600; font-size:1rem; transition:0.2s;">Cancel</button>
                <button onclick="window.location.href='logout.php'" style="padding:1rem; background:#ef4444; border:none; color: var(--text-main); border-radius:14px; cursor:pointer; font-weight:600; font-size:1rem; box-shadow:0 10px 20px -5px rgba(239, 68, 68, 0.4); transition:0.2s;">Yes, Logout</button>
            </div>
        </div>
    </div>

    <!-- General Success Modal -->
    <div id="general-success-modal">
        <div class="modal-content-premium">
            <div style="width:80px; height:80px; background:rgba(16, 185, 129, 0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <i data-feather="check-circle" style="color:#10b981; width:36px; height:36px;"></i>
            </div>
            <h3 style="color: var(--text-main); margin-bottom:0.75rem; font-size:1.5rem; font-weight:700;">Success!</h3>
            <p id="success-modal-msg" style="color:#94a3b8; font-size:1.1rem; margin-bottom:2.5rem; line-height:1.5;"></p>
            <button onclick="location.reload()" style="padding:1rem 2rem; background:#10b981; border:none; color: var(--text-main); border-radius:14px; cursor:pointer; font-weight:600; font-size:1rem; box-shadow:0 10px 20px -5px rgba(16, 185, 129, 0.4); transition:0.2s; width:100%;">Awesome!</button>
        </div>
    </div>
</body>
</html>