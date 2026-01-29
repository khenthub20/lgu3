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
// Try fetching with profile_image first
$resUser = $conn->query("SELECT full_name, email, profile_image FROM users WHERE id = $uid");

// If query failed (likely missing column), fallback to basic fetch
if (!$resUser) {
    $resUser = $conn->query("SELECT full_name, email FROM users WHERE id = $uid");
    $userData = $resUser->fetch_assoc();
    $userData['profile_image'] = null; // Default to null
} else {
    $userData = $resUser->fetch_assoc();
}

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
        .profile-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--card-bg); box-shadow: 0 0 0 2px var(--primary); background: #334155; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #fff; overflow:hidden;}
        .profile-preview img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Form Styles */
        .form-control { width: 100%; padding: 0.8rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; margin-bottom: 1rem; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem; }
        textarea.form-control { resize: vertical; min-height: 120px; }

        /* Mobile Nav Toggle */
        .mobile-toggle { display: none; }
        @media(max-width:768px){
            .mobile-toggle { display: block; font-size: 1.5rem; background:none; border:none; color:white; cursor:pointer;}
        }
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
        .app-info h4 { margin: 0; color: #fff; font-size: 1.05rem; }
        .app-info p { margin: 0.25rem 0 0; color: var(--text-muted); font-size: 0.85rem; }
        
        .download-btn {
            background: var(--primary);
            color: white;
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
            background: #ef4444; color: white; width: 18px; height: 18px; 
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
        .notif-item h5 { margin: 0 0 0.25rem 0; font-size: 0.9rem; color: #fff; }
        .notif-item p { margin: 0; font-size: 0.8rem; color: #94a3b8; line-height: 1.4; }
        .notif-item small { color: #64748b; font-size: 0.7rem; margin-top: 0.5rem; display: block; }
        .notif-empty { padding: 2rem; text-align: center; color: #64748b; font-size: 0.9rem; }

        /* Learning Center Premium Cards */
        .doc-card {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.4), rgba(15, 23, 42, 0.6));
            border: 1px solid rgba(255, 255, 255, 0.05);
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
            color: #fff;
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
            color: white;
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
            border: 1px solid rgba(255, 255, 255, 0.05);
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
            color: white;
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
            color: white;
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
            color: white;
            font-family: inherit;
        }
        .ai-input:focus { outline: none; border-color: var(--primary); }
        .ai-send-btn {
            background: var(--primary);
            color: white;
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
    </style>
</head>
<body class="dashboard-body">
    <!-- ... body content ... -->

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">LGU3<span>User</span></div>
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
                <a href="#" class="nav-item" onclick="showSection('ai-chat', this)">
                    <i data-feather="message-circle"></i>
                    <span>AI Assistant</span>
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
        <main class="main-content">
            <header class="top-bar">
                <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
                <div class="welcome-text">
                    <span>Good Morning, <span id="header-name"><?php echo htmlspecialchars($user_name); ?></span>!</span>
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

                    <?php if($user_image): ?>
                        <img src="<?php echo $user_image; ?>" class="avatar" style="object-fit:cover;">
                    <?php else: ?>
                        <div class="avatar"><?php echo $user_initials; ?></div>
                    <?php endif; ?>
                </div>
            </header>

            <div class="content-wrapper">
                
                <!-- HOME SECTION -->
                <div id="home" class="section-view active">
                    <div style="margin-bottom: 2.5rem;">
                        <h3 style="margin-bottom:1.25rem; font-size:1.1rem; color:#fff; display:flex; align-items:center; gap:0.5rem;">
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
                            <h3 style="font-size:1.1rem; color:#fff;">My Learning Programs</h3>
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
                    <div class="content-section" style="padding: 2rem; max-width: 500px; margin: 0 auto; text-align: left;">
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
                                <button type="button" class="action-btn" onclick="document.getElementById('profile_image').click()">Change Picture</button>
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
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" readonly style="background:rgba(255,255,255,0.02); color:#64748b;">
                            
                            <div id="profile-actions" style="margin-top:2rem;">
                                <button type="button" id="request-perm-btn" onclick="requestEditPermission()" class="action-btn" style="width:100%; border:1px dashed var(--primary); background:transparent; display:block;">
                                    Request Admin Access to Change Name
                                </button>
                                <button type="submit" id="save-profile-btn" class="primary-action-btn" style="width:100%; display:none;">
                                    Confirm Name Change
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CREATE REPORT SECTION -->
                <div id="create-report" class="section-view">
                    <div class="page-header" style="text-align: center;">
                        <h2>Create New Report</h2>
                        <p>Submit a concern or incident to the administration.</p>
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

                    <div style="display:grid; grid-template-columns: 1fr 350px; gap:1.5rem;">
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
                                <h3 style="font-size:1.1rem; color:#fff;">Upcoming Activities</h3>
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
                    <div id="user-docs-list" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem; margin-top:1.5rem;">
                        <!-- Populated by JS -->
                        <p style="color:#aaa;">Loading resources...</p>
                    </div>
                </div>

                <!-- AI CHATBOT SECTION -->
                <div id="ai-chat" class="section-view">
                    <div class="page-header">
                        <h2>Barangay AI Assistant</h2>
                        <p>Ask anything about barangay processes, requirements, or programs.</p>
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

            </div>
        </main>
    </div>
    <script>
        feather.replace();

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
                
                container.innerHTML = '';
                data.forEach(p => {
                    container.innerHTML += `
                        <div class="rec-card">
                            <span class="rec-tag">${p.category}</span>
                            <span class="rec-score">${p.score ? Math.round(p.score * 10) + '% Match' : 'Featured'}</span>
                            <h3 style="color:#fff; margin-bottom:0.5rem;">${p.title}</h3>
                            <p style="color:var(--text-muted); font-size:0.9rem;">${p.description || 'Program description unavailable.'}</p>
                            <button class="action-btn" onclick="applyFor(${p.id})" style="margin-top:1rem; width:100%;">Free to Apply</button>
                        </div>
                    `;
                });
            } catch(e) {
                 console.error(e);
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
                    container.innerHTML = '<div style="color:#aaa; text-align:center; padding:3rem; background:rgba(30,41,59,0.3); border-radius:16px; border:1px dashed #475569;">You haven\'t applied to any programs yet. Start learning today!</div>';
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
                            <div style="font-size:0.85rem; color: #94a3b8; margin-top:4px;">${row.description.substring(0,50)}...</div>
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

             reader.addEventListener("load", function () {
                preview.src = reader.result;
                preview.style.display = 'block';
                if(text) text.style.display = 'none';
             }, false);

             if (file) {
                reader.readAsDataURL(file);
             }
        }

        document.getElementById('profileForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            const fileInput = document.getElementById('profile_image');
            
            if(fileInput.files.length > 0) {
                formData.append('profile_image', fileInput.files[0]);
                
                // Since our API currently separates JSON and Files a bit awkwardly in strict REST,
                // but PHP $_FILES works fine with FormData.
                // We just need to make sure the endpoint is correct.
                // Our api.php?action=update_profile handles $_FILES.
                
                try {
                    const res = await fetch('api.php?action=update_profile', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if(data.success) {
                        alert('Profile picture updated!');
                        location.reload(); // To update header avatar
                    } else {
                        alert('Error: ' + data.error);
                    }
                } catch(err) {
                    alert('Upload failed.');
                }
            } else {
                 alert('Please select an image first.');
            }
        });

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

        // Logout
        document.querySelector('.logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

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

        async function markAsRead(id) {
            await fetch('api.php?action=mark_read', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            });
            fetchNotifs();
        }

        async function markAllRead() {
            // Simplify for demo: just call mark read for all unread or refresh
            const unread = document.querySelectorAll('.notif-item.unread');
            // Normally a single API call is better
            fetchNotifs();
        }

        async function fetchUserDocs() {
            try {
                const res = await fetch('api.php?action=get_docs');
                const data = await res.json();
                const container = document.getElementById('user-docs-list');
                container.innerHTML = '';
                
                if(data.length === 0) {
                    container.innerHTML = '<p style="color:#aaa;">No resources available yet.</p>';
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
                        <h4 style="margin:0 0 5px 0; font-size:1rem; color:#fff;">${ev.title}</h4>
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
    </script>
</body>
</html>