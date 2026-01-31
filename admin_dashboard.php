<?php
session_start();
include 'db_connect.php';

// Check if logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
        header("Location: user_dashboard.php");
        exit();
    }
    header("Location: index.php");
    exit();
}

$admin_name = $_SESSION['full_name'] ?? 'System Admin';

// Update Last Activity
$uid = $_SESSION['user_id'];
// Check if column exists first to avoid fatal error on first run without migration
$checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
if ($checkCol && $checkCol->num_rows > 0) {
    $conn->query("UPDATE users SET last_activity = NOW() WHERE id = $uid");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Minimalist</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* Extra styles for SPA feel */
        .section-view { display: none; animation: fadeIn 0.3s ease; }
        .section-view.active { display: block; }
        @keyframes fadeIn { from { opacity:0; transform: translateY(5px); } to { opacity:1; transform: translateY(0); } }
        
        /* Settings Form */
        .settings-form { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 0.75rem; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none; }
        .form-control:focus { border-color: var(--primary); }
        
        /* Deactivation Buttons */
        .danger-btn { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2) !important; transition: all 0.2s; }
        .danger-btn:hover { background: #ef4444; color: #fff; }
        .success-btn { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2) !important; transition: all 0.2s; }
        .success-btn:hover { background: #10b981; color: #fff; }
        
        /* Theme button active state */
        .action-btn.active { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }

        /* Sidebar Minimization & Scrolling */
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; display: flex; flex-direction: column; background: var(--sidebar-bg); }
        .sidebar-nav { overflow-y: auto; flex: 1; padding: 0 0.75rem; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.05) transparent; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
        
        .nav-item { position: relative; transition: all 0.2s; margin-bottom: 2px; }
        .nav-item.active::before { content: ''; position: absolute; left: -12px; top: 20%; height: 60%; width: 4px; background: var(--primary); border-radius: 0 4px 4px 0; box-shadow: 2px 0 10px var(--primary); }
        .sidebar.minimized .nav-item.active::before { left: -8px; }

        .main-content { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.minimized { width: 80px; padding: 1.5rem 0; }
        .sidebar.minimized .sidebar-header { justify-content: center; padding: 0; }
        .sidebar.minimized .logo { font-size: 1rem; letter-spacing: 0; }
        .sidebar.minimized .logo span, .sidebar.minimized .nav-item span, .sidebar.minimized .sidebar-footer span { display: none; }
        .sidebar.minimized .nav-item { justify-content: center; padding: 0.8rem; border-radius: 12px; margin: 0 10px 5px 10px; }
        .sidebar.minimized .nav-item i { margin: 0; }
        .main-content.sidebar-collapsed { margin-left: 80px; }
        
        .sidebar-toggle {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            border-radius: 10px;
        }
        .sidebar-toggle:hover { background: var(--primary); color: #fff; transform: scale(1.05); }
        
        /* Mobile Menu Toggle */
        .mobile-toggle { display: none; background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 8px; margin-right: 1rem; }
        
        @media (max-width: 768px) {
            .mobile-toggle { display: block; }
            .sidebar { transform: translateX(-100%); z-index: 1001; }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0 !important; }
            .sidebar-overlay { 
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); 
                z-index: 1000; display: none; 
            }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header" style="margin-bottom: 2rem;">
                <div class="logo">LGU3<span>Admin</span></div>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="showSection('overview', this)" title="Overview">
                    <i data-feather="grid"></i>
                    <span>Overview</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('users', this)" title="Users">
                    <i data-feather="users"></i>
                    <span>Users</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('reports', this)" title="Reports">
                    <i data-feather="file-text"></i>
                    <span>Reports</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('programs', this)" title="Programs">
                    <i data-feather="briefcase"></i>
                    <span>Programs</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('applications', this)" title="Applications">
                    <i data-feather="folder"></i>
                    <span>Applications</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('learning-docs', this)" title="Learning Docs">
                    <i data-feather="book-open"></i>
                    <span>Learning Docs</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('requests', this)" title="Pending Requests">
                    <i data-feather="bell"></i>
                    <span>Pending Requests</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('calendar', this)" title="My Calendar">
                    <i data-feather="calendar"></i>
                    <span>My Calendar</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('skill-analytics', this)" title="Skill Analytics">
                    <i data-feather="bar-chart-2"></i>
                    <span>Skill Analytics</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('skill-mgmt', this)" title="Manage Skills">
                    <i data-feather="monitor"></i>
                    <span>Manage Skills</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('smart-insights', this)" title="Smart Insights">
                    <i data-feather="cpu"></i>
                    <span>Smart Insights (AI)</span>
                </a>
                <a href="#" class="nav-item" onclick="showSection('settings', this)" title="Settings">
                    <i data-feather="settings"></i>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="#" class="nav-item logout-btn" style="color: #ef4444;" title="Logout">
                    <i data-feather="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleMobileMenu()"></div>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <header class="top-bar">
                <div style="display:flex; align-items:center;">
                    <button class="mobile-toggle" onclick="toggleMobileMenu()">
                        <i data-feather="menu"></i>
                    </button>
                    <div class="search-bar">
                        <i data-feather="search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                </div>
                 <div class="user-profile" style="display:flex; align-items:center;">
                    <!-- Notification Bell -->
                    <div class="notif-container" onclick="toggleNotifs(event)" style="margin-right:1rem; position:relative; cursor:pointer;">
                        <i data-feather="bell" style="color:#94a3b8;"></i>
                        <div id="notif-count" class="notif-badge" style="position:absolute; top:-5px; right:-5px; background:var(--primary); color:white; font-size:10px; padding:2px 5px; border-radius:10px; display:none;">0</div>
                        <div id="notif-dropdown" class="notif-dropdown" onclick="event.stopPropagation()" style="position:absolute; top:40px; right:0; width:300px; background:#1e293b; border:1px solid #334155; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.5); display:none; z-index:1000; overflow:hidden;">
                            <div class="notif-header" style="padding:1rem; border-bottom:1px solid #334155; font-weight:600; display:flex; justify-content:space-between;">
                                <span>Requests</span>
                            </div>
                            <div id="notif-list" style="max-height:350px; overflow-y:auto;">
                                <div style="padding:2rem; text-align:center; color:#64748b; font-size:0.9rem;">No new requests</div>
                            </div>
                        </div>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <div class="avatar">DA</div>
                </div>
            </header>

            <div class="content-wrapper">
                
                <!-- SECTION: OVERVIEW -->
                <div id="overview" class="section-view active">
                    <div class="page-header">
                        <h2>Dashboard Overview</h2>
                        <p>Welcome back, Admin. Here's what's happening today.</p>
                    </div>

                    <!-- Stats Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon user-color"><i data-feather="users"></i></div>
                            <div class="stat-info">
                                <h3>Total Users</h3>
                                <p class="stat-value" id="stat-total-users">--</p>
                                <span class="stat-trend positive">Realtime</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon doc-color"><i data-feather="file-text"></i></div>
                            <div class="stat-info">
                                <h3>Pending Reports</h3>
                                <p class="stat-value" id="stat-pending-reports">--</p>
                                <span class="stat-trend warning">Check Reports</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon activity-color"><i data-feather="activity"></i></div>
                            <div class="stat-info">
                                <h3>Active Sessions</h3>
                                <p class="stat-value" id="stat-active-sessions">--</p>
                                <span class="stat-trend positive">Online (30m)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Participation Analysis Widget (Overview version) -->
                    <div class="content-section" style="padding:1.5rem; margin-bottom: 2rem;">
                        <div class="section-header">
                             <h3>Engagement & Participation</h3>
                        </div>
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem;">
                            <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px; border:1px solid var(--border-color);">
                                <p style="color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem;">Citizen Join Rate</p>
                                <div style="display:flex; align-items:end; gap:0.5rem;">
                                    <h4 style="font-size:1.5rem; margin:0; color:#10b981;" id="ov-join-rate">0%</h4>
                                    <span style="color:#64748b; font-size:0.75rem; margin-bottom:3px;" id="ov-join-count">0</span>
                                </div>
                                <div style="width:100%; height:6px; background:rgba(255,255,255,0.05); border-radius:3px; margin-top:0.8rem; overflow:hidden;">
                                    <div id="ov-join-bar" style="width:0%; height:100%; background:#10b981; transition: width 1s ease;"></div>
                                </div>
                            </div>

                            <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px; border:1px solid var(--border-color);">
                                <p style="color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem;">Decline Rate</p>
                                <div style="display:flex; align-items:end; gap:0.5rem;">
                                    <h4 style="font-size:1.5rem; margin:0; color:#ef4444;" id="ov-decline-rate">0%</h4>
                                    <span style="color:#64748b; font-size:0.75rem; margin-bottom:3px;" id="ov-decline-count">0</span>
                                </div>
                                <div style="width:100%; height:6px; background:rgba(255,255,255,0.05); border-radius:3px; margin-top:0.8rem; overflow:hidden;">
                                    <div id="ov-decline-bar" style="width:0%; height:100%; background:#ef4444; transition: width 1s ease;"></div>
                                </div>
                            </div>

                            <div style="background:rgba(255,255,255,0.02); padding:1rem; border-radius:12px; border:1px solid var(--border-color);">
                                <p style="color:#94a3b8; font-size:0.8rem; margin-bottom:0.5rem;">Target Engagement</p>
                                <div style="display:flex; align-items:end; gap:0.5rem;">
                                    <h4 style="font-size:1.5rem; margin:0; color:var(--primary);" id="ov-pending-count">0</h4>
                                    <span style="color:#64748b; font-size:0.75rem; margin-bottom:3px;">Pending</span>
                                </div>
                                <p style="font-size:0.7rem; color:#475569; margin-top:0.8rem;">Total events tagged: <span id="ov-total-tagged" style="color:#fff;">0</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users Table Widget -->
                    <div class="content-section">
                        <div class="section-header">
                            <h3>Recent Signups</h3>
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-users-table">
                                    <!-- Populated by JS -->
                                    <tr><td colspan="4" style="text-align:center; color:#666;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: USERS -->
                <div id="users" class="section-view">
                     <div class="page-header">
                        <h2>User Management</h2>
                        <p>Manage all registered citizens.</p>
                    </div>
                    <div class="content-section">
                         <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined At</th>
                                        <th>Edit Auth</th>
                                    </tr>
                                </thead>
                                <tbody id="all-users-table">
                                    <tr><td colspan="5" style="text-align:center; padding:2rem; color:#64748b;">Loading citizens...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: REPORTS -->
                <div id="reports" class="section-view">
                     <div class="page-header">
                        <h2>Citizen Reports</h2>
                        <p>Review and manage submitted incidents.</p>
                    </div>
                    <div class="content-section">
                         <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Submitted By</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="reports-table">
                                    <tr><td colspan="5">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: PROGRAMS -->
                <div id="programs" class="section-view">
                     <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2>Livelihood Programs</h2>
                            <p>Manage training and support programs.</p>
                        </div>
                        <button class="primary-action-btn" onclick="openProgramModal()">+ Add Program</button>
                    </div>
                     <div class="content-section">
                         <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="programs-table">
                                    <tr><td colspan="3">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: APPLICATIONS -->
                <div id="applications" class="section-view">
                     <div class="page-header">
                        <h2>Program Applications</h2>
                        <p>Manage citizen applications and send materials.</p>
                    </div>
                     <div class="content-section">
                         <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Program</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="applications-table">
                                    <tr><td colspan="5">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: LEARNING DOCS -->
                <div id="learning-docs" class="section-view">
                     <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2>Free Learning Resources</h2>
                            <p>Upload PDFs and materials for all citizens.</p>
                        </div>
                        <button class="primary-action-btn" onclick="openDocModal()">+ Upload Doc</button>
                    </div>
                     <div class="content-section">
                          <div class="table-container">
                             <table>
                                 <thead>
                                     <tr>
                                         <th>Title</th>
                                         <th>Category</th>
                                         <th>File</th>
                                         <th>Uploaded At</th>
                                         <th>Action</th>
                                     </tr>
                                 </thead>
                                 <tbody id="docs-table">
                                     <tr><td colspan="5">Loading...</td></tr>
                                 </tbody>
                             </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: REQUESTS -->
                <div id="requests" class="section-view">
                     <div class="page-header">
                        <h2>Pending Authorization Requests</h2>
                        <p>Citizens requiring special access to update account data (Name Change).</p>
                    </div>
                    <div class="content-section">
                         <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Citizen Name</th>
                                        <th>Request Type</th>
                                        <th>Date Requested</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="requests-table">
                                    <tr><td colspan="4">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION: CALENDAR -->
                <div id="calendar" class="section-view">
                    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2>Work Calendar</h2>
                            <p>Manage your tasks, schedules, and training sessions.</p>
                        </div>
                        <button class="primary-action-btn" onclick="openEventModal()">+ Add Event</button>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 350px; gap:1.5rem; margin-bottom: 1.5rem;">
                        <!-- Calendar Graphic -->
                        <div class="content-section" style="padding:1.5rem;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                                <h3 id="calendar-month-year" style="margin:0;">Month Year</h3>
                                <div style="display:flex; gap:0.5rem;">
                                    <button class="icon-btn" onclick="prevMonth()"><i data-feather="chevron-left"></i></button>
                                    <button class="icon-btn" onclick="nextMonth()"><i data-feather="chevron-right"></i></button>
                                </div>
                            </div>
                            <div id="calendar-grid" style="display:grid; grid-template-columns: repeat(7, 1fr); gap:2px; background:var(--border-color); border:1px solid var(--border-color); border-radius:8px; overflow:hidden;">
                                <!-- Header -->
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">SUN</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">MON</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">TUE</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">WED</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">THU</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">FRI</div>
                                <div style="background:var(--sidebar-bg); padding:0.75rem; text-align:center; font-size:0.8rem; color:var(--text-muted); font-weight:600;">SAT</div>
                                <!-- Days populated by JS -->
                            </div>
                        </div>

                        <!-- Upcoming Events List -->
                        <div class="content-section">
                            <div class="section-header">
                                <h3>Scheduled Activities</h3>
                            </div>
                            <div id="event-list" style="padding:1rem; display:flex; flex-direction:column; gap:1rem;">
                                <div style="text-align:center; padding:2rem; color:#64748b;">Loading schedule...</div>
                            </div>
                        </div>
                    </div>

                    </div>



                <!-- SECTION: SKILL ANALYTICS -->
                <div id="skill-analytics" class="section-view">
                    <div class="page-header">
                        <h2>Skill Test Analytics</h2>
                        <p>Monitor enrollment and completion rates.</p>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon user-color"><i data-feather="users"></i></div>
                            <div class="stat-info">
                                <h3>Total Enrollments</h3>
                                <p class="stat-value" id="sa-total">--</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon success-color"><i data-feather="award"></i></div>
                            <div class="stat-info">
                                <h3>Completions</h3>
                                <p class="stat-value" id="sa-completed">--</p>
                            </div>
                        </div>
                    </div>

                    <div id="sa-test-list" class="content-section" style="padding:1.5rem; display:grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap:1.5rem;">
                        <div style="text-align:center; padding:2rem; color:#aaa; grid-column:1/-1;">Loading analytics...</div>
                    </div>
                </div>




                <!-- SECTION: MANAGE SKILLS (CRUD) -->
                <div id="skill-mgmt" class="section-view">
                    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2>Manage Skill Tests</h2>
                            <p>Create, edit, and organize skill assessments.</p>
                        </div>
                        <button class="primary-action-btn" onclick="openSkillModal()">+ New Skill Test</button>
                    </div>
                    
                    <div id="sm-test-list" class="content-section" style="padding:1.5rem; display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
                        <div style="text-align:center; padding:2rem; color:#aaa; grid-column:1/-1;">Loading tests...</div>
                    </div>
                </div>

                <!-- SECTION: SMART INSIGHTS (AI) -->
                <div id="smart-insights" class="section-view">
                     <div class="page-header">
                        <h2>LGU Intelligence Hub</h2>
                        <p>AI-powered analytics using NLP for sentiment and ML for trend prediction.</p>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap:1.5rem;">
                        
                        <!-- NLP: Sentiment Analysis -->
                        <div class="content-section" style="padding:1.5rem;">
                            <div class="section-header" style="margin-bottom:1.5rem;">
                                <div>
                                    <h3 style="margin-bottom:0.2rem;">Citizen Sentiment (NLP)</h3>
                                    <p style="font-size:0.8rem; color:#94a3b8;">Real-time analysis of citizen feedback.</p>
                                </div>
                                <div class="icon-box" style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; padding:0.5rem; border-radius:8px;"><i data-feather="message-circle"></i></div>
                            </div>
                            
                            <div style="display:flex; align-items:center; gap:2rem; margin-bottom:2rem;">
                                <div style="position:relative; width:120px; height:120px; display:flex; align-items:center; justify-content:center;">
                                    <svg viewBox="0 0 36 36" style="width:100%; height:100%; transform:rotate(-90deg);">
                                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#334155" stroke-width="3" />
                                        <path id="sentiment-circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831" fill="none" stroke="#10b981" stroke-width="3" stroke-dasharray="0, 100" />
                                    </svg>
                                    <div style="position:absolute; text-align:center;">
                                        <div id="sent-score" style="font-size:1.5rem; font-weight:700; color:#fff;">--%</div>
                                        <div style="font-size:0.6rem; color:#94a3b8;">POSITIVE</div>
                                    </div>
                                </div>
                                <div style="flex:1;">
                                    <div style="margin-bottom:0.8rem;">
                                        <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.3rem;">
                                            <span style="color:#10b981;">Positive</span>
                                            <span id="sent-pos-val">0</span>
                                        </div>
                                        <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:3px; overflow:hidden;"><div id="sent-pos-bar" style="width:0; height:100%; background:#10b981;"></div></div>
                                    </div>
                                    <div style="margin-bottom:0.8rem;">
                                        <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.3rem;">
                                            <span style="color:#fbbf24;">Neutral</span>
                                            <span id="sent-neu-val">0</span>
                                        </div>
                                        <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:3px; overflow:hidden;"><div id="sent-neu-bar" style="width:0; height:100%; background:#fbbf24;"></div></div>
                                    </div>
                                    <div>
                                        <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.3rem;">
                                            <span style="color:#ef4444;">Negative</span>
                                            <span id="sent-neg-val">0</span>
                                        </div>
                                        <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:3px; overflow:hidden;"><div id="sent-neg-bar" style="width:0; height:100%; background:#ef4444;"></div></div>
                                    </div>
                                </div>
                            </div>
                            
                            <h4 style="font-size:0.9rem; color:#e2e8f0; margin-bottom:1rem; border-bottom:1px solid #334155; padding-bottom:0.5rem;">Recent Analyzed Feedback</h4>
                            <div id="urgent-issues-list" style="display:flex; flex-direction:column; gap:0.8rem;">
                                <div style="color:#64748b; font-size:0.8rem; text-align:center;">No urgent issues detected.</div>
                            </div>
                        </div>

                        <!-- ML: Trend Prediction -->
                        <div class="content-section" style="padding:1.5rem;">
                             <div class="section-header" style="margin-bottom:1.5rem;">
                                <div>
                                    <h3 style="margin-bottom:0.2rem;">Activity Prediction (ML)</h3>
                                    <p style="font-size:0.8rem; color:#94a3b8;">Forecast of next month's report volume</p>
                                </div>
                                <div class="icon-box" style="background:rgba(59, 130, 246, 0.1); color:#3b82f6; padding:0.5rem; border-radius:8px;"><i data-feather="trending-up"></i></div>
                            </div>

                            <div style="background:rgba(0,0,0,0.2); border-radius:12px; padding:1.5rem; text-align:center; margin-bottom:2rem;">
                                <div style="font-size:0.9rem; color:#94a3b8; margin-bottom:0.5rem;">Forecasted Volume (Next Month)</div>
                                <div id="pred-val" style="font-size:2.5rem; font-weight:800; color:#3b82f6;">--</div>
                                <div style="font-size:0.8rem; color:#10b981; margin-top:0.5rem;">Based on 6-month moving average</div>
                            </div>

                            <h4 style="font-size:0.9rem; color:#e2e8f0; margin-bottom:1rem;">Growth Trend</h4>
                            <div id="trend-chart" style="height:200px; display:flex; align-items:flex-end; gap:1rem; padding-bottom:1rem; border-bottom:1px solid #334155; margin-bottom:1.5rem;">
                                <!-- Bars populated by JS -->
                                <div style="flex:1; text-align:center; color:#64748b; align-self:center;">Insufficient data for trend graph</div>
                            </div>

                            <div style="background:rgba(255,255,255,0.02); border-radius:8px; padding:1rem;">
                                <h5 style="color:#94a3b8; font-size:0.8rem; margin:0 0 0.5rem 0;">Model Data Source (Last 6 Months)</h5>
                                <div id="ml-data-table" style="display:grid; grid-template-columns: 1fr 1fr; gap:0.5rem; font-size:0.8rem; color:#e2e8f0;">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <!-- SECTION: SETTINGS -->
            <div id="settings" class="section-view">

                     <div class="page-header" style="text-align: center;">
                        <h2>Settings</h2>
                        <p>Update system preferences.</p>
                    </div>
                    <div class="content-section" style="padding: 2rem;">
                        <form class="settings-form" onsubmit="event.preventDefault(); alert('Settings saved (Demo)!');">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label>System Name</label>
                                    <input type="text" class="form-control" value="LGU3 Management System">
                                </div>
                                <div class="form-group">
                                    <label>Admin Email</label>
                                    <input type="email" class="form-control" value="admin@lgu3.gov" readonly style="opacity:0.7">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                             <div class="form-group" style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-color);">
                                 <label style="margin-bottom: 0.8rem; font-weight: 600; color: #fff;">System Controls</label>
                                 <div class="checkbox-wrapper" style="margin-bottom: 1rem;">
                                     <input type="checkbox" id="maint">
                                     <label for="maint" style="display:inline; margin-left: 0.5rem; color: #94a3b8;">Enable System Maintenance Mode</label>
                                 </div>
                                 <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                                     <label style="margin-bottom: 0.5rem; display: block;">Interface Appearance</label>
                                     <div style="display: flex; gap: 1rem;">
                                         <button type="button" class="action-btn" id="btn-dark" onclick="setTheme('dark')" style="flex:1; justify-content:center;">
                                             <i data-feather="moon" style="width:14px; margin-right:8px;"></i> Dark Mode
                                         </button>
                                         <button type="button" class="action-btn" id="btn-light" onclick="setTheme('light')" style="flex:1; justify-content:center;">
                                             <i data-feather="sun" style="width:14px; margin-right:8px;"></i> Light Mode
                                         </button>
                                     </div>
                                 </div>
                             </div>
                             <button type="submit" class="primary-action-btn" style="width: 100%; padding: 1rem; margin-top: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                                 <i data-feather="save" style="width:18px;"></i> Save Changes
                             </button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        
        .success-modal {
            background: rgba(23, 25, 30, 0.95);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 2.5rem;
            border-radius: 24px;
            text-align: center;
            transform: scale(0.9);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 320px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .modal-overlay.show .success-modal { transform: scale(1); }

        /* Rejected Variant */
        .success-modal.rejected {
            border-color: rgba(239, 68, 68, 0.3);
        }
        .success-modal.rejected .success-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .success-modal.rejected .close-modal-btn {
            background: #ef4444;
        }
        .success-modal.rejected .close-modal-btn:hover {
            background: #dc2626;
        }
        
        .success-icon {
            width: 80px; height: 80px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes popIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        
        .success-icon svg { width: 40px; height: 40px; }
        
        .success-modal h3 { font-size: 1.5rem; margin-bottom: 0.5rem; color: #fff; }
        .success-modal p { color: #94a3b8; margin-bottom: 2rem; }
        
        .close-modal-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .close-modal-btn:hover { transform: translateY(-2px); background: #059669; }
    </style>
    
    <!-- Success Modal HTML -->
    <div class="modal-overlay" id="successModal">
        <div class="success-modal" id="modalBox">
            <div class="success-icon" id="modalIcon">
                <i data-feather="check"></i>
            </div>
            <h3 id="modalTitle">Awesome!</h3>
            <p id="modalMessage">Action completed successfully.</p>
            <button class="close-modal-btn" onclick="closeModal()">Continue</button>
        </div>
    </div>

    <!-- Create Program Modal -->
    <div class="modal-overlay" id="programModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:1.5rem; color: #fff;">Add New Program</h3>
            <form onsubmit="event.preventDefault(); createProgram(this);">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Program Title</label>
                    <input type="text" name="title" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                     <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Category</label>
                     <select name="category" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                        <option value="Technical">Technical</option>
                        <option value="Livelihood">Livelihood</option>
                        <option value="Agriculture">Agriculture</option>
                        <option value="Business">Business</option>
                        <option value="IT">IT & Digital</option>
                     </select>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Description</label>
                    <textarea name="description" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px; min-height:80px;"></textarea>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="document.getElementById('programModal').classList.remove('show')" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Create Program</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manual Enrollment Modal -->
    <div class="modal-overlay" id="assignModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:0.5rem; color: #fff;">Send Manual Material</h3>
            <p style="color:#aaa; font-size:0.85rem; margin-bottom:1.5rem;" id="assignProgramTitle">Assign program to a citizen.</p>
            <form onsubmit="event.preventDefault(); manualEnroll(this);">
                <input type="hidden" name="program_id" id="assign_prog_id">
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Select Citizen</label>
                    <select name="user_id" id="userSelect" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                        <option value="">Loading users...</option>
                    </select>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="document.getElementById('assignModal').classList.remove('show')" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Send Material</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Doc Modal -->
    <div class="modal-overlay" id="docModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:1.5rem; color: #fff;">Upload Learning Material</h3>
            <form id="uploadDocForm" onsubmit="event.preventDefault(); uploadDoc(this);">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Document Title</label>
                    <input type="text" name="title" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                     <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Category</label>
                     <select name="category" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                        <option value="Agriculture">Agriculture</option>
                        <option value="Business">Business</option>
                        <option value="Technical">Technical</option>
                        <option value="Health">Health</option>
                        <option value="Skills">Skills</option>
                     </select>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">PDF File</label>
                    <input type="file" name="doc_file" accept=".pdf,.doc,.docx" required style="width:100%; color:#fff;">
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="document.getElementById('docModal').classList.remove('show')" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="uploadBtn" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Upload Now</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Program Modal -->
    <div class="modal-overlay" id="editProgramModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:1.5rem; color: #fff;">Edit Program</h3>
            <form onsubmit="event.preventDefault(); updateProgram(this);">
                <input type="hidden" name="id" id="edit_prog_id">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Program Title</label>
                    <input type="text" name="title" id="edit_prog_title" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                     <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Category</label>
                     <select name="category" id="edit_prog_cat" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                        <option value="Technical">Technical</option>
                        <option value="Livelihood">Livelihood</option>
                        <option value="Agriculture">Agriculture</option>
                        <option value="Business">Business</option>
                        <option value="IT">IT & Digital</option>
                     </select>
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Description</label>
                    <textarea name="description" id="edit_prog_desc" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px; min-height:100px;"></textarea>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="document.getElementById('editProgramModal').classList.remove('show')" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Program Modal -->
    <div class="modal-overlay" id="viewProgramModal">
        <div class="success-modal" style="text-align:left; max-width:450px; padding:2rem;">
            <h3 id="view_prog_title" style="margin-bottom:0.5rem; color: #fff;">Program Details</h3>
            <span id="view_prog_cat" class="badge active" style="margin-bottom:1.5rem; display:inline-block;">Category</span>
            <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:12px; border:1px solid #334155;">
                <p id="view_prog_desc" style="color:#d1d5db; line-height:1.6; font-size:0.95rem; white-space: pre-wrap; margin:0;"></p>
            </div>
            <button type="button" onclick="document.getElementById('viewProgramModal').classList.remove('show')" style="margin-top:1.5rem; width:100%; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer; font-weight:600;">Close</button>
        </div>
    </div>

    <!-- Add Calendar Event Modal -->
    <div class="modal-overlay" id="eventModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:1.5rem; color: #fff;">Add Calendar Event</h3>
            <form onsubmit="event.preventDefault(); saveEvent(this);">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Event Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g., Training Workshop" style="width:100%;">
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div>
                        <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Date</label>
                        <input type="date" name="event_date" class="form-control" required style="width:100%;">
                    </div>
                    <div>
                        <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Time</label>
                        <input type="time" name="event_time" class="form-control" value="09:00" style="width:100%;">
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Type</label>
                    <select name="type" class="form-control" style="width:100%;">
                        <option value="task">Personal Task</option>
                        <option value="training">Training Program</option>
                        <option value="work">Regular Work</option>
                        <option value="meeting">Meeting</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Tag Citizens (Optional)</label>
                    <div style="display:flex; gap:0.5rem; margin-bottom:0.8rem;">
                        <select id="user_to_tag" class="form-control" style="flex:1;">
                            <option value="">Select a citizen...</option>
                        </select>
                        <button type="button" onclick="addTag()" style="padding:0 1rem; background:rgba(99, 102, 241, 0.1); color:var(--primary); border:1px solid var(--primary); border-radius:8px; cursor:pointer;"><i data-feather="plus" style="width:16px;"></i></button>
                    </div>
                    <div id="tagged_users_list" style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        <!-- Tag pills will appear here -->
                    </div>
                    <input type="hidden" name="target_user_ids_json" id="target_user_ids_json">
                </div>
                <div style="margin-bottom:1.5rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Description</label>
                    <textarea name="description" class="form-control" style="width:100%; min-height:80px;"></textarea>
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="document.getElementById('eventModal').classList.remove('show')" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Add Event</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <!-- Create/Edit Skill Test Modal -->
    <div class="modal-overlay" id="skillTestModal">
        <div class="success-modal" style="text-align:left; max-width:400px; padding:2rem;">
            <h3 style="margin-bottom:1.5rem; color: #fff;" id="stm-title">Add Skill Test</h3>
            <form onsubmit="event.preventDefault(); saveSkillTest(this);">
                <input type="hidden" name="id" id="stm-id">
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Test Title</label>
                    <input type="text" name="title" id="stm-name" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Description</label>
                    <textarea name="description" id="stm-desc" class="form-control" required style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px; min-height:80px;"></textarea>
                </div>
                <div style="margin-bottom:1.5rem;">
                     <label style="display:block; color:#aaa; font-size:0.9rem; margin-bottom:0.5rem;">Thumbnail URL</label>
                     <input type="url" name="thumbnail" id="stm-thumb" class="form-control" required placeholder="https://..." style="width:100%; padding:0.8rem; background:#1e293b; border:1px solid #475569; color:white; border-radius:8px;">
                </div>
                <div style="display:flex; gap:1rem;">
                    <button type="button" onclick="closeSkillModal()" style="flex:1; padding:0.8rem; background:transparent; border:1px solid #475569; color:white; border-radius:8px; cursor:pointer;">Cancel</button>
                    <button type="submit" style="flex:1; padding:0.8rem; background:var(--primary); border:none; color:white; border-radius:8px; cursor:pointer;">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Stages Modal -->
    <div class="modal-overlay" id="stagesModal">
        <div class="success-modal" style="text-align:left; max-width:600px; width:90%; padding:2rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h3 style="margin:0; color: #fff;">Manage Stages</h3>
                <button onclick="closeStagesModal()" style="background:none; border:none; color:#94a3b8; cursor:pointer;"><i data-feather="x"></i></button>
            </div>
            
            <div id="stm-stages-list" style="max-height:400px; overflow-y:auto; margin-bottom:1.5rem; display:flex; flex-direction:column; gap:1rem;">
                <!-- Stages populated here -->
            </div>
            
            <div style="background:rgba(255,255,255,0.05); padding:1rem; border-radius:8px; border:1px solid #334155;">
                <h4 style="color:#fff; margin:0 0 1rem 0; font-size:0.9rem;">Add New Stage</h4>
                <form onsubmit="event.preventDefault(); addStage(this);">
                    <input type="hidden" name="test_id" id="stm-stage-tid">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                        <input type="text" name="title" placeholder="Stage Title" required class="form-control" style="background:#0f172a;">
                        <input type="url" name="video_url" placeholder="Video/Content URL" required class="form-control" style="background:#0f172a;">
                    </div>
                    <textarea name="content" placeholder="Description/Content" required class="form-control" style="background:#0f172a; min-height:60px; margin-bottom:1rem;"></textarea>
                    <button type="submit" class="primary-action-btn" style="width:100%; font-size:0.85rem;">Add Stage</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        feather.replace();

        // --- Navigation Logic ---
        function showSection(id, element) {
            // Hide all
            document.querySelectorAll('.section-view').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            // Show target
            const targetSection = document.getElementById(id);
            if(targetSection) targetSection.classList.add('active');
            if(element) element.classList.add('active');

            // Mobile specific: Close menu automatically
            if(window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                if(sidebar.classList.contains('mobile-open')) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                }
            }

            // Trigger fetch if needed
            if(id === 'overview') { updateStats(); fetchRecentUsers(); }
            else if(id === 'users') fetchUsers();
            else if(id === 'reports') fetchReports();
            else if(id === 'programs') fetchPrograms();
            else if(id === 'applications') fetchApplications();
            else if(id === 'learning-docs') fetchAdminDocs();
            else if(id === 'requests') fetchRequests();
            else if(id === 'calendar') fetchCalendar();
            else if(id === 'skill-analytics') fetchSkillAnalytics();
            else if(id === 'skill-mgmt') fetchSkillTests();
            else if(id === 'smart-insights') fetchSmartInsights();
            
            feather.replace();
        }

        async function fetchApplications() {
             try {
                 const res = await fetch('api.php?action=admin_applications');
                 const data = await res.json();
                 const tbody = document.getElementById('applications-table');
                 
                 if(data.length === 0) {
                      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:1rem;">No applications found.</td></tr>';
                      return;
                 }
                 
                 tbody.innerHTML = '';
                 data.forEach(app => {
                     let statusColor = app.status === 'approved' ? 'active' : 'pending';
                     let actionBtn = app.status === 'pending' ? 
                        `<button class="primary-action-btn" style="padding: 0.5rem 1rem; font-size:0.8rem;" onclick="sendMaterial(${app.id})">Send Material</button>` :
                        `<span style="color:#10b981; font-size:0.8rem;">Sent <i data-feather="check" style="width:12px;"></i></span>`;
                     
                     tbody.innerHTML += `
                         <tr>
                             <td><span style="font-weight:600; color:#fff;">${app.full_name}</span></td>
                             <td>${app.program_title}</td>
                             <td>${app.created_at.split(' ')[0]}</td>
                             <td><span class="badge ${statusColor}">${app.status}</span></td>
                             <td>
                                 <div style="display:flex; gap:0.5rem; align-items:center;">
                                     ${actionBtn}
                                     <button class="icon-btn warning" style="background:#ef4444; border:none; padding:0.4rem;" onclick="deleteApplication(${app.id})" title="Remove"><i data-feather="trash-2" style="width:14px; color:white;"></i></button>
                                 </div>
                             </td>
                         </tr>
                     `;
                 });
                 feather.replace();
             } catch(e) {}
         }

         async function sendMaterial(id) {
             if(!confirm('This will approve the application and send the learning material link. Proceed?')) return;
             try {
                 const res = await fetch('api.php?action=send_material', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify({id: id})
                 });
                 const data = await res.json();
                 if(data.success) {
                     showSuccessModal('Learning material module has been sent to the user!');
                     fetchApplications();
                 } else {
                     alert('Error: ' + data.error);
                 }
             } catch(e) { alert('Connection Error'); }
         }

        async function fetchPrograms() {
             try {
                 const res = await fetch('api.php?action=admin_programs');
                 const data = await res.json();
                 const tbody = document.getElementById('programs-table');
                 
                 if(data.length === 0) {
                      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:1rem;">No programs found.</td></tr>';
                      return;
                 }
                 
                tbody.innerHTML = '';
                data.forEach(p => {
                    tbody.innerHTML += `
                        <tr>
                            <td><span style="font-weight:600; color:#fff;">${p.title}</span></td>
                            <td><span class="badge active">${p.category}</span></td>
                            <td style="color:#aaa; font-size:0.9rem;">${p.description.substring(0,50)}...</td>
                            <td>
                                <div style="display:flex; gap:0.5rem; align-items:center;">
                                    <button class="icon-btn" style="background:#334155; border:none; padding:0.4rem;" onclick="openViewModal(${JSON.stringify(p).replace(/"/g, '&quot;')})" title="View Details"><i data-feather="eye" style="width:14px; color:white;"></i></button>
                                    <button class="icon-btn" style="background:var(--primary); border:none; padding:0.4rem;" onclick="openEditModal(${JSON.stringify(p).replace(/"/g, '&quot;')})" title="Edit Program"><i data-feather="edit-2" style="width:14px; color:white;"></i></button>
                                    <button class="primary-action-btn" style="padding:0.4rem 0.8rem; font-size:0.75rem;" onclick="openAssignModal(${p.id}, '${p.title}')">Send to User</button>
                                    <button class="icon-btn warning" style="background:#ef4444; border:none; padding:0.4rem;" onclick="deleteProgram(${p.id})" title="Delete Program"><i data-feather="trash-2" style="width:14px; color:white;"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                feather.replace();
            } catch(e) {}
        }

        async function fetchAdminDocs() {
              try {
                  const res = await fetch('api.php?action=get_docs');
                  const data = await res.json();
                  const tbody = document.getElementById('docs-table');
                  tbody.innerHTML = '';
                  if(data.length === 0) {
                      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:1rem;">No documents uploaded.</td></tr>';
                      return;
                  }
                  data.forEach(d => {
                      tbody.innerHTML += `
                          <tr>
                              <td><span style="font-weight:600; color:#fff;">${d.title}</span></td>
                              <td><span class="badge active">${d.category}</span></td>
                              <td><a href="${d.file_path}" target="_blank" style="color:var(--primary); font-size:0.8rem;">View File</a></td>
                              <td>${d.created_at.split(' ')[0]}</td>
                              <td>
                                  <button class="icon-btn warning" style="background:#ef4444; border:none; padding:0.4rem;" onclick="deleteDoc(${d.id})" title="Delete Doc"><i data-feather="trash-2" style="width:14px; color:white;"></i></button>
                              </td>
                          </tr>
                      `;
                  });
                  feather.replace();
              } catch(e) {}
          }

          function openDocModal() { document.getElementById('docModal').classList.add('show'); }

          async function uploadDoc(form) {
              const btn = document.getElementById('uploadBtn');
              btn.innerText = 'Uploading...';
              btn.disabled = true;
              
              const formData = new FormData(form);
              try {
                  const res = await fetch('api.php?action=upload_doc', { method: 'POST', body: formData });
                  const result = await res.json();
                  if(result.success) {
                      document.getElementById('docModal').classList.remove('show');
                      form.reset();
                      showSuccessModal('Document uploaded and shared with citizens!');
                      fetchAdminDocs();
                  } else alert(result.error);
              } catch(e) { alert('Upload failed'); }
              finally { btn.innerText = 'Upload Now'; btn.disabled = false; }
          }

          async function deleteDoc(id) {
              if(!confirm('Delete this document?')) return;
              try {
                  const res = await fetch('api.php?action=delete_doc', {
                      method: 'POST',
                      headers: {'Content-Type': 'application/json'},
                      body: JSON.stringify({id: id})
                  });
                  const result = await res.json();
                  if(result.success) {
                      showSuccessModal('Document deleted.');
                      fetchAdminDocs();
                  }
              } catch(e) {}
          }
 
         function openProgramModal() {
             document.getElementById('programModal').classList.add('show');
         }
 
         function openAssignModal(pid, title) {
             document.getElementById('assign_prog_id').value = pid;
             document.getElementById('assignProgramTitle').innerText = "Assigning: " + title;
             document.getElementById('assignModal').classList.add('show');
             fetchCitizensPool();
         }

         async function fetchCitizensPool() {
             const select = document.getElementById('userSelect');
             try {
                 const res = await fetch('api.php?action=all_citizens');
                 const data = await res.json();
                 select.innerHTML = '';
                 data.forEach(u => {
                     select.innerHTML += `<option value="${u.id}">${u.full_name} (${u.email})</option>`;
                 });
             } catch(e) {
                 select.innerHTML = '<option>Error loading users</option>';
             }
         }

         async function deleteProgram(id) {
             if(!confirm('Are you sure? This will delete the program and all associated applications.')) return;
             try {
                 const res = await fetch('api.php?action=delete_program', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify({id: id})
                 });
                 const data = await res.json();
                 if(data.success) {
                     showSuccessModal('Program deleted successfully.');
                     fetchPrograms();
                 } else alert(data.error);
             } catch(e) { alert('Connection Error'); }
         }

         async function deleteApplication(id) {
             if(!confirm('Are you sure you want to remove this application?')) return;
             try {
                 const res = await fetch('api.php?action=delete_application', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify({id: id})
                 });
                 const data = await res.json();
                 if(data.success) {
                     showSuccessModal('Application removed successfully.');
                     fetchApplications();
                 } else alert(data.error);
             } catch(e) { alert('Connection Error'); }
         }

         async function manualEnroll(form) {
             const formData = new FormData(form);
             const data = Object.fromEntries(formData.entries());
             
             try {
                 const res = await fetch('api.php?action=manual_enroll', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify(data)
                 });
                 const result = await res.json();
                 
                 if(result.success) {
                     document.getElementById('assignModal').classList.remove('show');
                     showSuccessModal('Program material successfully sent to user!');
                 } else {
                     alert('Error: ' + result.error);
                 }
             } catch(e) { alert('Connection Error'); }
         }

         function openEditModal(program) {
             document.getElementById('edit_prog_id').value = program.id;
             document.getElementById('edit_prog_title').value = program.title;
             document.getElementById('edit_prog_cat').value = program.category;
             document.getElementById('edit_prog_desc').value = program.description;
             document.getElementById('editProgramModal').classList.add('show');
         }

         function openViewModal(program) {
             document.getElementById('view_prog_title').innerText = program.title;
             document.getElementById('view_prog_cat').innerText = program.category;
             document.getElementById('view_prog_desc').innerText = program.description;
             document.getElementById('viewProgramModal').classList.add('show');
         }

         async function updateProgram(form) {
             const formData = new FormData(form);
             const data = Object.fromEntries(formData.entries());
             
             try {
                 const res = await fetch('api.php?action=update_program', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify(data)
                 });
                 const result = await res.json();
                 
                 if(result.success) {
                     document.getElementById('editProgramModal').classList.remove('show');
                     showSuccessModal('Program updated successfully!');
                     fetchPrograms();
                 } else {
                     alert('Error: ' + result.error);
                 }
             } catch(e) { alert('Connection Error'); }
         }

         async function createProgram(form) {
             const formData = new FormData(form);
             const data = Object.fromEntries(formData.entries());
             
             try {
                 const res = await fetch('api.php?action=create_program', {
                     method: 'POST',
                     headers: {'Content-Type': 'application/json'},
                     body: JSON.stringify(data)
                 });
                 const result = await res.json();
                 
                 if(result.success) {
                     document.getElementById('programModal').classList.remove('show');
                     form.reset();
                     showSuccessModal('Program created successfully!');
                     fetchPrograms();
                 } else {
                     alert('Error: ' + result.error);
                 }
             } catch(e) {
                 alert('Connection Error');
             }
         }

        // --- Realtime Pollers ---

        async function updateStats() {
            try {
                // Main Stats
                const response = await fetch('api.php?action=stats');
                const data = await response.json();
                if (!data.error) {
                    document.getElementById('stat-total-users').innerText = data.total_users;
                    document.getElementById('stat-pending-reports').innerText = data.pending_reports;
                    document.getElementById('stat-active-sessions').innerText = data.active_sessions;
                }

                // Calendar Stats
                const resStats = await fetch('api.php?action=get_calendar_stats');
                const stats = await resStats.json();
                renderCalendarStats(stats);
            } catch (e) {
                console.error("API Error", e);
            }
        }

        async function fetchRecentUsers() {
            try {
                const response = await fetch('api.php?action=recent_users');
                const users = await response.json();
                
                const tbody = document.getElementById('recent-users-table');
                tbody.innerHTML = '';
                
                users.forEach(user => {
                    const initials = user.full_name.substring(0,2).toUpperCase();
                    const tr = `
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm">${initials}</div>
                                    <span>${user.full_name}</span>
                                </div>
                            </td>
                            <td>${user.email}</td>
                            <td><span class="badge ${user.role === 'admin' ? 'active' : 'pending'}">${user.role}</span></td>
                            <td><span class="badge active">Active</span></td>
                        </tr>
                    `;
                    tbody.innerHTML += tr;
                });
            } catch (e) {
                 // Ignore
            }
        }

        async function fetchUsers() {
             try {
                 const response = await fetch('api.php?action=all_citizens');
                 const users = await response.json();
                 const tbody = document.getElementById('all-users-table');
                 if(!tbody) return;
                 
                 if (!Array.isArray(users)) {
                     tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--danger); padding:2rem;">
                        ${users.error || 'Failed to load citizens'}
                     </td></tr>`;
                     return;
                 }

                 tbody.innerHTML = '';
                 if (users.length === 0) {
                     tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:2rem; color:#64748b;">No citizens found.</td></tr>';
                     return;
                 }

                 users.forEach(user => {
                     const date = user.created_at ? user.created_at.split(' ')[0] : '---';
                     const activeIcon = user.is_active == 1 ? 'user-x' : 'user-check';
                     const activeLabel = user.is_active == 1 ? 'Deactivate' : 'Activate';
                     const btnClass = user.is_active == 1 ? 'danger-btn' : 'success-btn';

                     tbody.innerHTML += `<tr>
                        <td>
                            <div class="user-cell">
                                <div class="avatar-sm">${user.full_name.substring(0,2).toUpperCase()}</div>
                                <span style="${user.is_active == 0 ? 'text-decoration: line-through; color: #64748b;' : ''}">${user.full_name}</span>
                            </div>
                        </td>
                        <td>${user.email}</td>
                        <td><span class="badge ${user.role === 'admin' ? 'active' : 'pending'}">${user.role}</span></td>
                        <td>${date}</td>
                        <td style="display:flex; gap:0.5rem;">
                            <button class="primary-action-btn" style="padding:0.4rem 0.6rem; font-size:0.7rem;" onclick="approveEdit(${user.id})" title="Authorize Name Edit">
                                <i data-feather="key" style="width:12px;"></i>
                            </button>
                            <button class="${btnClass}" style="padding:0.4rem 0.6rem; font-size:0.7rem; display:flex; align-items:center; gap:4px; border-radius:6px; border:none; cursor:pointer;" onclick="toggleUserStatus(${user.id}, ${user.is_active == 1 ? 0 : 1})">
                                <i data-feather="${activeIcon}" style="width:12px;"></i> ${activeLabel}
                            </button>
                        </td>
                     </tr>`;
                 });
                 feather.replace();
             } catch(e) {
                 console.error(e);
                 const tbody = document.getElementById('all-users-table');
                 if(tbody) tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:var(--danger);">Connection Error</td></tr>';
             }
        }

        async function toggleUserStatus(userId, newStatus) {
            const action = newStatus ? 'Activate' : 'Deactivate';
            if(!confirm(`Are you sure you want to ${action} this account?`)) return;
            
            try {
                const res = await fetch('api.php?action=toggle_user_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, is_active: newStatus})
                });
                const data = await res.json();
                if(data.success) {
                    showSuccessModal(`Account ${action}d successfully.`);
                    fetchUsers();
                } else alert(data.error);
            } catch(e) { alert('Operation failed'); }
        }

        async function approveEdit(userId) {
            if(!confirm('Authorize this user to edit their name for 25 minutes?')) return;
            try {
                const res = await fetch('api.php?action=approve_edit', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId})
                });
                const data = await res.json();
                if(data.success) {
                    showSuccessModal('User authorized to edit name for 25 minutes!');
                } else alert(data.error);
            } catch(e) { alert('Approval failed'); }
        }

        async function fetchReports() {
             try {
                 const response = await fetch('api.php?action=reports');
                 const text = await response.text();
                 let reports;
                 try { reports = JSON.parse(text); } catch (e) { return; }
                 
                 const tbody = document.getElementById('reports-table');
                 if (reports.length === 0) {
                     tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">No reports found.</td></tr>';
                     return;
                 }

                 tbody.innerHTML = '';
                 reports.forEach(r => {
                     let badgeClass = 'pending';
                     if(r.status === 'approved') badgeClass = 'active';
                     else if(r.status === 'rejected') badgeClass = 'warning'; 

                     tbody.innerHTML += `<tr>
                        <td>
                            <div style="font-weight:600">${r.title}</div>
                            <small style="color:#aaa">${r.description ? r.description.substring(0,30)+'...' : ''}</small>
                        </td>
                        <td>${r.full_name}</td>
                        <td><span class="badge ${badgeClass}">${r.status}</span></td>
                        <td>${r.created_at}</td>
                        <td>
                            <button class="icon-btn" onclick="updateStatus(${r.id}, 'approved')" title="Approve"><i data-feather="check"></i></button>
                            <button class="icon-btn" onclick="updateStatus(${r.id}, 'rejected')" title="Reject"><i data-feather="x"></i></button>
                        </td>
                     </tr>`;
                 });
                 feather.replace(); 
             } catch(e) {}
        }

        async function fetchRequests() {
            try {
                // Fetch both name change requests and general notifications
                const [reqRes, notifRes] = await Promise.all([
                    fetch('api.php?action=get_edit_requests'),
                    fetch('api.php?action=get_notifications')
                ]);
                
                const requests = await reqRes.json();
                const notifications = await notifRes.json();
                
                const tableBody = document.getElementById('requests-table');
                const notifList = document.getElementById('notif-list');
                const countBadge = document.getElementById('notif-count');
                
                if(!tableBody) return;

                // Update Request Table
                tableBody.innerHTML = '';
                if(requests.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:2rem; color:#64748b;">No pending requests.</td></tr>';
                } else {
                    requests.forEach(req => {
                        tableBody.innerHTML += `
                            <tr>
                                <td>${req.full_name}</td>
                                <td><span class="badge warning">Name Change</span></td>
                                <td>${req.created_at}</td>
                                <td>
                                    <button class="primary-action-btn" style="padding:0.4rem 0.8rem; font-size:0.75rem;" onclick="approveEditRequest(${req.id})">
                                        <i data-feather="check" style="width:12px; margin-right:4px;"></i> Approve (25m)
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }

                // Update Notification Dropdown
                const unreadNotifs = Array.isArray(notifications) ? notifications.filter(n => n.is_read == 0) : [];
                const totalCount = requests.length + unreadNotifs.length;
                
                if(totalCount > 0) {
                    countBadge.innerText = totalCount;
                    countBadge.style.display = 'block';
                } else {
                    countBadge.style.display = 'none';
                }

                notifList.innerHTML = '';
                
                // Add Pending Name Requests to dropdown
                requests.forEach(req => {
                    notifList.innerHTML += `
                        <div style="padding:1rem; border-bottom:1px solid #334155; cursor:pointer; background:rgba(251, 191, 36, 0.05);" onclick="showSection('requests')">
                            <div style="display:flex; justify-content:space-between; align-items:start;">
                                <h5 style="margin:0; font-size:0.85rem; color:#fff;">${req.full_name}</h5>
                                <span style="font-size:0.6rem; color:#fbbf24; text-transform:uppercase; font-weight:700;">Request</span>
                            </div>
                            <p style="margin:5px 0 0 0; font-size:0.75rem; color:#94a3b8;">Requested access to change account name.</p>
                            <small style="font-size:0.65rem; color:#475569; display:block; margin-top:5px;">${req.created_at}</small>
                        </div>
                    `;
                });

                // Add General Notifications to dropdown
                if(Array.isArray(notifications)) {
                    notifications.forEach(notif => {
                        const isUnread = notif.is_read == 0;
                        const bgColor = isUnread ? 'rgba(59, 130, 246, 0.05)' : 'transparent';
                        const dot = isUnread ? '<span style="width:8px; height:8px; background:var(--primary); border-radius:50%; display:inline-block; margin-right:5px;"></span>' : '';
                        
                        notifList.innerHTML += `
                            <div style="padding:1rem; border-bottom:1px solid #334155; cursor:pointer; background:${bgColor};" onclick="markRead(${notif.id})">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <h5 style="margin:0; font-size:0.85rem; color:#fff;">${dot}${notif.title}</h5>
                                    <span style="font-size:0.6rem; color:#94a3b8;">${notif.type.toUpperCase()}</span>
                                </div>
                                <p style="margin:5px 0 0 0; font-size:0.75rem; color:#94a3b8; line-height:1.4;">${notif.message}</p>
                                <small style="font-size:0.65rem; color:#475569; display:block; margin-top:5px;">${notif.created_at}</small>
                            </div>
                        `;
                    });
                }

                if(totalCount === 0 && (!notifications || notifications.length === 0)) {
                    notifList.innerHTML = '<div style="padding:2rem; text-align:center; color:#64748b; font-size:0.9rem;">No new notifications</div>';
                }

                feather.replace();
            } catch(e) {
                console.error("Fetch Notifs Error:", e);
            }
        }

        async function markRead(id) {
            try {
                await fetch('api.php?action=mark_read', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                fetchRequests(); // Refresh
            } catch(e) {}
        }

        async function approveEditRequest(userId) {
            if(!confirm('Authorize this name change?')) return;
            try {
                const res = await fetch('api.php?action=approve_edit', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId})
                });
                const data = await res.json();
                if(data.success) {
                    showSuccessModal('Profile edit authorized for 25 minutes.');
                    fetchRequests();
                }
            } catch(e) {}
        }


        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }


        // Apply saved sidebar state
        window.addEventListener('load', () => {
            // Sidebar is now permanently expanded
            localStorage.removeItem('sidebarMinimized');

            // Theme initialization
            const savedTheme = localStorage.getItem('theme') || 'dark';
            setTheme(savedTheme);
        });

        function setTheme(theme) {
            if (theme === 'light') {
                document.body.classList.add('light-theme');
                document.getElementById('btn-light').classList.add('active');
                document.getElementById('btn-dark').classList.remove('active');
            } else {
                document.body.classList.remove('light-theme');
                document.getElementById('btn-dark').classList.add('active');
                document.getElementById('btn-light').classList.remove('active');
            }
            localStorage.setItem('theme', theme);
        }

        function toggleNotifs(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        document.addEventListener('click', () => {
            const d = document.getElementById('notif-dropdown');
            if(d) d.style.display = 'none';
        });

        async function updateStatus(id, status) {
            try {
                const res = await fetch('api.php?action=update_report_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: id, status: status })
                });
                const data = await res.json();
                
                if(data.success) {
                    const msg = status === 'approved' ? 'Report has been approved.' : 'Report has been rejected.';
                    showSuccessModal(msg, status);
                    fetchReports(); 
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(e) {
                alert('Connection error');
            }
        }

        function showSuccessModal(msg, type = 'approved') {
            document.getElementById('modalMessage').innerText = msg;
            
            const box = document.getElementById('modalBox');
            const iconContainer = document.getElementById('modalIcon');
            const title = document.getElementById('modalTitle');
            
            // Reset
            box.classList.remove('rejected');
            
            if (type === 'rejected') {
                box.classList.add('rejected');
                iconContainer.innerHTML = '<i data-feather="x-circle"></i>';
                title.innerText = "Rejected";
            } else {
                iconContainer.innerHTML = '<i data-feather="check"></i>';
                title.innerText = "Awesome!";
            }
            
            const modal = document.getElementById('successModal');
            modal.classList.add('show');
            feather.replace();
        }

        function closeModal() {
             document.getElementById('successModal').classList.remove('show');
        }

        // --- Calendar Logic ---
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();
        let calendarEvents = [];
        let selectedTags = []; // Array of objects {id, name}

        async function openEventModal() {
            const modal = document.getElementById('eventModal');
            modal.classList.add('show');
            selectedTags = [];
            renderTags();
            
            // Populate Citizens Dropdown
            const select = document.getElementById('user_to_tag');
            select.innerHTML = '<option value="">Select a citizen...</option>';
            try {
                const res = await fetch('api.php?action=all_citizens');
                const users = await res.json();
                users.forEach(u => {
                    select.innerHTML += `<option value="${u.id}" data-name="${u.full_name}">${u.full_name}</option>`;
                });
            } catch(e) {}
        }

        function addTag() {
            const select = document.getElementById('user_to_tag');
            const userId = select.value;
            const userName = select.options[select.selectedIndex].getAttribute('data-name');
            
            if(!userId) return;
            if(selectedTags.find(t => t.id == userId)) return; // No duplicates

            selectedTags.push({id: userId, name: userName});
            renderTags();
            select.value = "";
        }

        function removeTag(id) {
            selectedTags = selectedTags.filter(t => t.id != id);
            renderTags();
        }

        function renderTags() {
            const container = document.getElementById('tagged_users_list');
            container.innerHTML = '';
            selectedTags.forEach(tag => {
                const pill = document.createElement('div');
                pill.style.cssText = "background:rgba(99, 102, 241, 0.15); color:var(--primary); padding:4px 10px; border-radius:100px; font-size:0.8rem; display:flex; align-items:center; gap:6px; border:1px solid rgba(99, 102, 241, 0.3);";
                pill.innerHTML = `
                    <span>${tag.name}</span>
                    <i data-feather="x" onclick="removeTag(${tag.id})" style="width:12px; cursor:pointer;"></i>
                `;
                container.appendChild(pill);
            });
            feather.replace();
        }

        async function saveEvent(form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Use the collected tags
            data.target_user_ids = selectedTags.map(t => t.id);
            
            try {
                const res = await fetch('api.php?action=add_calendar_event', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if(result.success) {
                    document.getElementById('eventModal').classList.remove('show');
                    form.reset();
                    showSuccessModal('Event scheduled successfully!');
                    fetchCalendar();
                } else alert(result.error);
            } catch(e) { alert('Save failed'); }
        }

        async function fetchCalendar() {
            try {
                const res = await fetch('api.php?action=get_calendar');
                calendarEvents = await res.json();
                renderCalendar();
                renderEventList();
                
                // Also fetch participation stats
                const resStats = await fetch('api.php?action=get_calendar_stats');
                const stats = await resStats.json();
                renderCalendarStats(stats);
            } catch(e) { console.error("Cal Load Error", e); }
        }

        function renderCalendar() {
            const grid = document.getElementById('calendar-grid');
            const header = document.getElementById('calendar-month-year');
            if(!grid) return;

            // Month Name
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            header.innerText = `${monthNames[currentMonth]} ${currentYear}`;

            // Clear old days (keep headers)
            const headersCount = 7;
            while(grid.children.length > headersCount) grid.removeChild(grid.lastChild);

            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            // Padding
            for(let i=0; i<firstDay; i++) {
                const dev = document.createElement('div');
                dev.style.cssText = "background:var(--bg-color); height:100px; padding:10px; opacity:0.3;";
                grid.appendChild(dev);
            }

            // Days
            for(let d=1; d<=daysInMonth; d++) {
                const dayBox = document.createElement('div');
                dayBox.style.cssText = "background:var(--card-bg); height:100px; padding:10px; font-size:0.9rem; border:1px solid rgba(255,255,255,0.02); display:flex; flex-direction:column; gap:5px; overflow:hidden;";
                
                const dayNum = document.createElement('span');
                dayNum.innerText = d;
                dayNum.style.fontWeight = "600";
                dayBox.appendChild(dayNum);

                // Date string for matching
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                
                // Add dots/lines for events
                const dayEvents = calendarEvents.filter(e => e.event_date === dateStr);
                dayEvents.forEach(ev => {
                    const dot = document.createElement('div');
                    let color = "var(--primary)";
                    if(ev.type === 'training') color = "var(--success)";
                    if(ev.type === 'work') color = "var(--warning)";
                    if(ev.type === 'meeting') color = "#ec4899";
                    
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
                list.innerHTML = '<div style="text-align:center; padding:2rem; color:#64748b;">No upcoming activities.</div>';
                return;
            }

            calendarEvents.forEach(ev => {
                const timeStr = ev.event_time.substring(0,5);
                const hasTags = ev.tagged_names ? true : false;
                
                list.innerHTML += `
                    <div style="background:rgba(255,255,255,0.03); border-radius:12px; padding:1rem; border:1px solid var(--border-color); position:relative;">
                        <button class="icon-btn" onclick="deleteCalendarEvent(${ev.id})" style="position:absolute; top:10px; right:10px; color:var(--danger); opacity:0.6;"><i data-feather="trash-2" style="width:14px;"></i></button>
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
                            <span class="badge" style="font-size:0.7rem; background:rgba(99, 102, 241, 0.2); color:var(--primary); ">${ev.type.toUpperCase()}</span>
                            <span style="font-size:0.8rem; color:#64748b;"><i data-feather="clock" style="width:12px; vertical-align:middle; margin-right:4px;"></i>${ev.event_date} @ ${timeStr}</span>
                        </div>
                        <h4 style="margin:0 0 5px 0; font-size:1rem; color:#fff;">${ev.title}</h4>
                        <p style="margin:0; font-size:0.85rem; color:#94a3b8;">${ev.description || 'No description'}</p>
                        ${hasTags ? `<div style="margin-top:10px; font-size:0.75rem; color:var(--success); border-top:1px solid #334155; padding-top:8px; line-height: 1.4;">
                            <i data-feather="users" style="width:10px;"></i> Tagged: <span style="color:#d1d5db;">${ev.tagged_names}</span>
                        </div>` : ''}
                    </div>
                `;
            });
            feather.replace();
        }

        function renderCalendarStats(stats) {
            // Update Overview version
            const ovJoinRate = document.getElementById('ov-join-rate');
            if(ovJoinRate) {
                ovJoinRate.innerText = stats.join_percentage + '%';
                document.getElementById('ov-join-count').innerText = stats.joined;
                document.getElementById('ov-join-bar').style.width = stats.join_percentage + '%';

                document.getElementById('ov-decline-rate').innerText = stats.decline_percentage + '%';
                document.getElementById('ov-decline-count').innerText = stats.declined;
                document.getElementById('ov-decline-bar').style.width = stats.decline_percentage + '%';

                document.getElementById('ov-pending-count').innerText = stats.pending;
                document.getElementById('ov-total-tagged').innerText = stats.total;
            }
        }

        async function deleteCalendarEvent(id) {
            if(!confirm('Remove this event from schedule?')) return;
            try {
                const res = await fetch('api.php?action=delete_calendar_event', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: id})
                });
                const data = await res.json();
                if(data.success) fetchCalendar();
                else alert(data.error);
            } catch(e) {}
        }

        // --- Skill Analytics ---
        async function fetchSkillAnalytics() {
            try {
                const res = await fetch('api.php?action=get_skill_analytics');
                const data = await res.json();
                
                document.getElementById('sa-total').innerText = data.total_enrollments;
                document.getElementById('sa-completed').innerText = data.completed_count;
                
                const list = document.getElementById('sa-test-list');
                list.innerHTML = '';
                
                if(data.tests.length === 0) {
                     list.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:2rem; color:#aaa;">No skill tests found.</div>';
                     return;
                }
                
                data.tests.forEach(test => {
                    const completionRate = test.enrolled > 0 ? Math.round((test.completed / test.enrolled) * 100) : 0;
                    
                    let userRows = '';
                    if(test.recent_users.length === 0) {
                        userRows = '<tr><td colspan="4" style="text-align:center; color:#64748b; font-size:0.8rem; padding:0.5rem;">No recent enrollees</td></tr>';
                    } else {
                        test.recent_users.slice(0, 3).forEach(u => { // Limit to 3 for compact view
                            let statusBadge = u.status === 'completed' ? 
                                '<span style="color:#10b981; font-size:0.75rem;">Done</span>' : 
                                '<span style="color:#fbbf24; font-size:0.75rem;">Stg ' + u.current_stage + '</span>';
                            
                            userRows += `
                                <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                    <td style="font-size:0.8rem; color:#e2e8f0; padding:0.5rem 1rem;">${u.full_name}</td>
                                    <td style="font-size:0.75rem; color:#94a3b8; padding:0.5rem 1rem;">${u.started_at ? u.started_at.split(' ')[0] : '-'}</td>
                                    <td style="padding:0.5rem 1rem;">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    }

                    list.innerHTML += `
                        <div style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; overflow:hidden; display:flex; flex-direction:column;">
                            <div style="padding:1rem 1.25rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <h3 style="margin:0 0 2px 0; font-size:1rem; color:#fff;">${test.title}</h3>
                                    <div style="font-size:0.75rem; color:#94a3b8;">
                                        <span style="color:#fff;">${test.enrolled}</span> Enrolled &bull; 
                                        <span style="color:#10b981;">${test.completed}</span> Done
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:1.2rem; font-weight:700; color:${completionRate >= 50 ? '#10b981' : '#f59e0b'}; line-height:1;">${completionRate}%</div>
                                    <div style="font-size:0.65rem; color:#64748b;">Rate</div>
                                </div>
                            </div>
                            <div style="padding:0; flex:1; background:rgba(0,0,0,0.2);">
                                <table style="width:100%; border-collapse:collapse;">
                                    <thead style="background:rgba(255,255,255,0.02);">
                                        <tr>
                                            <th style="text-align:left; padding:0.5rem 1rem; font-size:0.7rem; color:#94a3b8; font-weight:500;">Recent User</th>
                                            <th style="text-align:left; padding:0.5rem 1rem; font-size:0.7rem; color:#94a3b8; font-weight:500;">Date</th>
                                            <th style="text-align:left; padding:0.5rem 1rem; font-size:0.7rem; color:#94a3b8; font-weight:500;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${userRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
                
            } catch(e) { console.error(e); }
        }

        // --- MANAGE SKILLS (CRUD) ---
        async function fetchSkillTests() {
            try {
                const res = await fetch('api.php?action=get_skill_tests');
                const data = await res.json();
                const container = document.getElementById('sm-test-list');
                
                if(data.length === 0) {
                     container.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:3rem; color:#64748b; font-size:1.1rem; border:2px dashed #334155; border-radius:16px;">No skill tests created yet.<br><span style="font-size:0.9rem;">Click "+ New Skill Test" to get started.</span></div>';
                     return;
                }
                
                container.innerHTML = '';
                data.forEach(t => {
                    const safeTitle = t.title.replace(/'/g, "\\'");
                    const safeDesc = t.description.replace(/'/g, "\\'");
                    container.innerHTML += `
                        <div class="skill-mgmt-card" style="background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); transition:transform 0.2s, border-color 0.2s;">
                            <div style="height:110px; background:url('${t.thumbnail}') center/cover; position:relative;">
                                <div style="position:absolute; inset:0; background:linear-gradient(to top, rgba(15,23,42,1), transparent);"></div>
                                <div style="position:absolute; bottom:0.8rem; left:1rem; right:1rem;">
                                    <h3 style="color:#fff; margin:0; font-size:1rem; text-shadow:0 1px 2px rgba(0,0,0,0.8);">${t.title}</h3>
                                </div>
                            </div>
                            <div style="padding:1rem; flex:1; display:flex; flex-direction:column;">
                                <p style="color:#94a3b8; font-size:0.8rem; margin-bottom:1rem; flex:1; line-height:1.4;">${t.description.substring(0,80)}...</p>
                                
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-bottom:0.75rem;">
                                    <button onclick="openSkillModal(${t.id}, '${safeTitle}', '${safeDesc}', '${t.thumbnail}')" 
                                            style="padding:0.5rem; border-radius:6px; border:1px solid #475569; background:transparent; color:#e2e8f0; font-size:0.75rem; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:0.3rem;">
                                        <i data-feather="edit-2" style="width:12px;"></i> Edit
                                    </button>
                                    <button onclick="openStagesModal(${t.id}, '${safeTitle}')" 
                                            style="padding:0.5rem; border-radius:6px; border:1px solid var(--primary); background:rgba(99,102,241,0.1); color:var(--primary); font-size:0.75rem; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:0.3rem;">
                                        <i data-feather="list" style="width:12px;"></i> Stages
                                    </button>
                                </div>
                                <button onclick="deleteSkillTest(${t.id})" 
                                        style="width:100%; border:none; background:transparent; color:#ef4444; font-size:0.75rem; cursor:pointer; opacity:0.8; transition:opacity 0.2s; display:flex; align-items:center; justify-content:center; gap:0.3rem;">
                                    Delete
                                </button>
                            </div>
                        </div>
                    `;
                });
                feather.replace();
            } catch(e) { console.error(e); }
        }

        function openSkillModal(id=null, title='', desc='', thumb='') {
            document.getElementById('stm-title').innerText = id ? 'Edit Skill Test' : 'Add Skill Test';
            document.getElementById('stm-id').value = id || '';
            document.getElementById('stm-name').value = title;
            document.getElementById('stm-desc').value = desc;
            document.getElementById('stm-thumb').value = thumb;
            document.getElementById('skillTestModal').classList.add('show');
        }

        function closeSkillModal() {
            document.getElementById('skillTestModal').classList.remove('show');
        }

        async function saveSkillTest(form) {
            const formData = new FormData(form);
            const id = formData.get('id');
            const action = id ? 'update_skill_test' : 'create_skill_test';
            
            const payload = {
                id: id,
                title: formData.get('title'),
                description: formData.get('description'),
                thumbnail: formData.get('thumbnail')
            };

            try {
                const res = await fetch('api.php?action=' + action, {
                    method:'POST', body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.success) {
                    closeSkillModal();
                    fetchSkillTests();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(e) {}
        }

        async function deleteSkillTest(id) {
            if(!confirm('Delete this skill test? This will remove all enrollments and progress.')) return;
            try {
                const res = await fetch('api.php?action=delete_skill_test', {
                    method:'POST', body:JSON.stringify({id})
                });
                const data = await res.json();
                if(data.success) fetchSkillTests();
            } catch(e) {}
        }

        // --- STAGES MANAGEMENT ---
        let currentManageTestId = null;

        async function openStagesModal(tid, title) {
            currentManageTestId = tid;
            document.getElementById('stm-stage-tid').value = tid;
            document.getElementById('stagesModal').classList.add('show');
            fetchStages(tid);
        }

        function closeStagesModal() {
            document.getElementById('stagesModal').classList.remove('show');
        }

        async function fetchStages(tid) {
            try {
                const res = await fetch('api.php?action=get_test_stages&test_id=' + tid);
                const data = await res.json();
                const list = document.getElementById('stm-stages-list');
                list.innerHTML = '';
                
                if(data.length === 0) {
                    list.innerHTML = '<div style="text-align:center; padding:1rem; color:#64748b;">No stages added yet.</div>';
                    return;
                }

                data.forEach(s => {
                    list.innerHTML += `
                        <div style="background:rgba(255,255,255,0.03); padding:0.8rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="color:#fff; font-weight:500;">${s.stage_number}. ${s.title}</div>
                                <div style="color:#64748b; font-size:0.8rem;">${s.content.substring(0,40)}...</div>
                            </div>
                            <button onclick="deleteStage(${s.id})" style="background:none; border:none; color:#ef4444; cursor:pointer;"><i data-feather="trash-2" style="width:14px;"></i></button>
                        </div>
                    `;
                });
                feather.replace();
            } catch(e) {}
        }

        async function addStage(form) {
             const formData = new FormData(form);
             const payload = {
                 test_id: formData.get('test_id'),
                 title: formData.get('title'),
                 content: formData.get('content'),
                 video_url: formData.get('video_url')
             };
             
             try {
                 const res = await fetch('api.php?action=add_test_stage', {
                     method: 'POST', body: JSON.stringify(payload)
                 });
                 const data = await res.json();
                 if(data.success) {
                     form.reset();
                     document.getElementById('stm-stage-tid').value = currentManageTestId; // reset clears hidden too? no, usually not type=hidden but lets be safe
                     fetchStages(currentManageTestId);
                 } else {
                     alert(data.error);
                 }
             } catch(e) {}
        }

        async function deleteStage(id) {
            if(!confirm('Delete this stage?')) return;
            try {
                 const res = await fetch('api.php?action=delete_test_stage', {
                     method: 'POST', body: JSON.stringify({id})
                 });
                 if((await res.json()).success) fetchStages(currentManageTestId);
            } catch(e) {}
        }

        // --- SMART INSIGHTS ---
        async function fetchSmartInsights() {
            try {
                const res = await fetch('api.php?action=get_intelligence_data');
                const data = await res.json();
                
                // Sentiment Stats
                const s = data.sentiment;
                const total = s.positive + s.neutral + s.negative;
                const posPct = total > 0 ? Math.round((s.positive / total) * 100) : 0;
                const negPct = total > 0 ? Math.round((s.negative / total) * 100) : 0;
                const neuPct = total > 0 ? Math.round((s.neutral / total) * 100) : 0;

                document.getElementById('sent-score').innerText = posPct + '%';
                document.getElementById('sentiment-circle').setAttribute('stroke-dasharray', `${posPct}, 100`);
                
                document.getElementById('sent-pos-val').innerText = s.positive;
                document.getElementById('sent-neu-val').innerText = s.neutral;
                document.getElementById('sent-neg-val').innerText = s.negative;
                
                document.getElementById('sent-pos-bar').style.width = posPct + '%';
                document.getElementById('sent-neu-bar').style.width = neuPct + '%';
                document.getElementById('sent-neg-bar').style.width = negPct + '%';

                // Urgent Issues / Recent Logs (Updated Logic)
                const uList = document.getElementById('urgent-issues-list');
                if(data.urgent_issues.length > 0) {
                    uList.innerHTML = '';
                    data.urgent_issues.forEach(issue => {
                        // Color coding based on sentiment
                        let bg = 'rgba(255,255,255,0.05)';
                        let color = '#94a3b8';
                        let icon = 'activity';
                        
                        if(issue.sentiment === 'positive') { bg = 'rgba(16, 185, 129, 0.1)'; color = '#10b981'; icon = 'thumbs-up'; }
                        if(issue.sentiment === 'negative') { bg = 'rgba(239, 68, 68, 0.1)'; color = '#ef4444'; icon = 'alert-triangle'; }
                        if(issue.sentiment === 'neutral') { bg = 'rgba(251, 191, 36, 0.1)'; color = '#fbbf24'; icon = 'minus'; }
                        
                        uList.innerHTML += `
                            <div style="background:${bg}; border:1px solid ${bg.replace('0.1','0.2')}; padding:0.8rem; border-radius:8px; display:flex; gap:0.8rem; align-items:center;">
                                <i data-feather="${icon}" style="color:${color}; width:16px;"></i>
                                <div style="flex:1;">
                                     <div style="color:#fff; font-size:0.8rem; font-weight:600;">${issue.title || 'Report'}</div>
                                     <div style="font-size:0.75rem; color:${color}; opacity:0.8;">"${issue.text}"</div>
                                </div>
                                <div style="font-size:0.6rem; text-transform:uppercase; font-weight:700; color:${color};">${issue.sentiment}</div>
                            </div>
                        `;
                    });
                } else {
                    uList.innerHTML = '<div style="color:#64748b; font-size:0.8rem; text-align:center; padding:1rem; border-radius:8px;">No reports analyzed yet.</div>';
                }

                // Trends & Prediction
                document.getElementById('pred-val').innerText = data.prediction;
                
                const chart = document.getElementById('trend-chart');
                const dataTable = document.getElementById('ml-data-table');
                
                if(data.trends.length > 0) {
                    chart.innerHTML = '';
                    dataTable.innerHTML = '';
                    
                    const maxVal = Math.max(...data.trends.map(t => t.count), 10); // Find max for scaling
                    
                    data.trends.forEach(t => {
                        const h = Math.round((t.count / maxVal) * 100);
                        const displayVal = t.count > 0 ? t.count : '';
                        
                        // Chart Bar
                        chart.innerHTML += `
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.5rem; height:100%;">
                                <div style="width:100%; flex:1; display:flex; align-items:flex-end; justify-content:center; position:relative;">
                                    <div style="position:absolute; bottom:${h}%; margin-bottom:5px; font-size:0.7rem; color:#fff; font-weight:600;">${displayVal}</div>
                                    <div style="width:100%; height:${h}%; background:var(--primary); border-radius:4px 4px 0 0; opacity:0.8; transition:height 0.5s;"></div>
                                </div>
                                <div style="font-size:0.7rem; color:#64748b;">${t.month.split('-')[1]}</div>
                            </div>
                        `;
                        
                        // Data Table Row
                        dataTable.innerHTML += `
                            <div style="display:flex; justify-content:space-between; padding:0.4rem; background:rgba(255,255,255,0.03); border-radius:4px;">
                                <span>${t.month}</span>
                                <span style="font-weight:600;">${t.count} reports</span>
                            </div>
                        `;
                    });
                    
                    // Add Prediction Bar
                    const predH = Math.round((data.prediction / maxVal) * 100);
                    chart.innerHTML += `
                        <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.5rem; height:100%;">
                             <div style="width:100%; flex:1; display:flex; align-items:flex-end; justify-content:center; position:relative;">
                                 <div style="position:absolute; bottom:${predH}%; margin-bottom:5px; font-size:0.7rem; color:#3b82f6; font-weight:700;">${data.prediction}</div>
                                 <div style="width:100%; height:${predH}%; background:repeating-linear-gradient(45deg, #3b82f6, #3b82f6 5px, rgba(59,130,246,0.5) 5px, rgba(59,130,246,0.5) 10px); border-radius:4px 4px 0 0;"></div>
                             </div>
                             <div style="font-size:0.7rem; color:#3b82f6; font-weight:700;">Fcst</div>
                        </div>
                    `;
                }
                feather.replace();

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


        // --- Init ---
        document.querySelector('.logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        });

        updateStats(); 
        fetchRecentUsers();
        fetchRequests();
        fetchApplications(); // Load initial
        setInterval(updateStats, 3000); 
        setInterval(fetchRecentUsers, 10000); 
        setInterval(fetchRequests, 5000); 
        setInterval(fetchApplications, 30000); 
    </script>
</body>
</html>