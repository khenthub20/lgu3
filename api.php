<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db_connect.php';

$action = $_GET['action'] ?? '';
$role = $_SESSION['role'] ?? '';
$uid = $_SESSION['user_id'] ?? 0;

if (!$uid) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Admin-only actions
$admin_actions = ['stats', 'recent_users', 'reports', 'admin_programs', 'create_program', 'admin_applications', 'send_material', 'all_citizens', 'manual_enroll', 'delete_program', 'delete_application', 'update_program', 'upload_doc', 'delete_doc', 'approve_edit', 'get_edit_requests', 'toggle_user_status', 'add_calendar_event', 'delete_calendar_event', 'get_calendar_stats'];
if (in_array($action, $admin_actions) && $role !== 'admin') {
     echo json_encode(['error' => 'Unauthorized Access']);
     exit;
}

// Ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(200),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    type VARCHAR(50) DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- USER & SHARED ACTIONS ---

if ($action === 'user_stats') {
    // Stats for specific user
    $stats = [];
    
    // Total Reports
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM reports WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stats['total_reports'] = $stmt->get_result()->fetch_assoc()['c'];

    // Pending
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM reports WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stats['pending_reports'] = $stmt->get_result()->fetch_assoc()['c'];

    echo json_encode($stats);
    exit;
}

if ($action === 'my_reports') {
    $reports = [];
    
    // Schema Patch: Check correct timestamp column
    $tsCol = 'created_at';
    $check = $conn->query("SHOW COLUMNS FROM reports LIKE 'submitted_at'");
    if($check && $check->num_rows > 0) $tsCol = 'submitted_at';
    
    $stmt = $conn->prepare("SELECT id, title, description, status, $tsCol as created_at FROM reports WHERE user_id = ? ORDER BY $tsCol DESC");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    echo json_encode($reports);
    exit;
}


if ($action === 'create_report') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'] ?? '';
    $desc = $input['description'] ?? '';
    
    if(!$title || !$desc) {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    $sql = "INSERT INTO reports (user_id, title, description, status) VALUES (?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);

    // Auto-healing: If prepare fails, table might not exist
    if (!$stmt) {
        $createSql = "CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            title VARCHAR(200),
            description TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $conn->query($createSql);
        
        // Retry
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
             // Check if it's a column mismatch (specifically 'description')
             if (strpos($conn->error, "Unknown column 'description'") !== false) {
                 $conn->query("ALTER TABLE reports ADD COLUMN description TEXT AFTER title");
                 $stmt = $conn->prepare($sql); // Retry 2
             }
        }

        if (!$stmt) {
            // Still failing? Return DB error
            echo json_encode(['error' => 'Database Error: ' . $conn->error]);
            exit;
        }
    }

    $stmt->bind_param("iss", $uid, $title, $desc);
    
    if(!$stmt->execute()) {
        // Execute failed, might be column missing if prepare passed (some drivers lazy check)
        if (strpos($stmt->error, "Unknown column 'description'") !== false) {
             $conn->query("ALTER TABLE reports ADD COLUMN description TEXT AFTER title");
             // Re-bind and execute
             if($stmt->execute()) {
                 $reportId = $stmt->insert_id;
                 $success = true;
             }
        }
        if (!$success) {
            echo json_encode(['error' => $stmt->error]);
            exit;
        }
    } else {
        $reportId = $stmt->insert_id;
    }

    // --- NOTIFICATIONS ---
    // 1. Notify User
    $uMsg = "Your report '$title' has been received. Our team will review it shortly.";
    $stmtU = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Report Received', ?, 'info')");
    $stmtU->bind_param("is", $uid, $uMsg);
    $stmtU->execute();

    // 2. Notify Admin
    $uname = 'A citizen';
    $uRes = $conn->query("SELECT full_name FROM users WHERE id = $uid");
    if($uRes && $uRes->num_rows > 0) $uname = $uRes->fetch_assoc()['full_name'];

    $adminId = 1;
    $adminRes = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if($adminRes && $adminRes->num_rows > 0) $adminId = $adminRes->fetch_assoc()['id'];

    $aMsg = "$uname has submitted a new report: '$title'.";
    $stmtA = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Report Submitted', ?, 'warning')");
    $stmtA->bind_param("is", $adminId, $aMsg);
    $stmtA->execute();

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update_profile') {
    // Handle File Upload
    $response = ['success' => true];
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $fileName;
        
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $target_file, $uid);
                $stmt->execute();
                $response['image_url'] = $target_file;
            } else {
                $response['error'] = 'DB Prepare Error: ' . $conn->error;
            }
        } else {
            $response['error'] = 'Failed to upload image';
            echo json_encode($response); exit;
        }
    }
    
    // Update Name/Email if provided (optional, simple implementation)
    // For now we just handled the image as per request "can upload image"
    
    echo json_encode($response);
    exit;
}


if ($action === 'submit_assessment') {
    $input = json_decode(file_get_contents('php://input'), true);
    $skills = $input['skills'] ?? '';
    $interests = $input['interests'] ?? '';
    $status = $input['employment_status'] ?? '';
    
    // 1. Save Profile
    $stmt = $conn->prepare("UPDATE users SET skills = ?, interests = ?, employment_status = ? WHERE id = ?");
    $stmt->bind_param("sssi", $skills, $interests, $status, $uid);
    $stmt->execute();
    
    // 2. AI Engine Simulation (Keyword Matching)
    $recommendations = [];
    $userText = strtolower($skills . ' ' . $interests);
    
    $resProgs = $conn->query("SELECT * FROM programs");
    while($prog = $resProgs->fetch_assoc()) {
        $score = 0;
        $progText = strtolower($prog['title'] . ' ' . $prog['category'] . ' ' . $prog['description']);
        
        // Split user input into words
        $keywords = explode(' ', $userText);
        foreach($keywords as $word) {
            if(strlen($word) > 3 && strpos($progText, $word) !== false) {
                $score++;
            }
        }
        
        // Base Match: Category checks
        if(strpos($userText, strtolower($prog['category'])) !== false) $score += 2;
        
        // Threshold
        if($score > 0) {
            $recommendations[] = [
                'id' => $prog['id'],
                'title' => $prog['title'],
                'category' => $prog['category'],
                'score' => $score
            ];
        }
    }
    
    // Sort by score
    usort($recommendations, function($a, $b) { return $b['score'] - $a['score']; });
    
    // Save Result
    $jsonResult = json_encode(array_slice($recommendations, 0, 5)); // Top 5
    $stmt = $conn->prepare("UPDATE users SET ai_analysis_result = ? WHERE id = ?");
    $stmt->bind_param("si", $jsonResult, $uid);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'admin_programs') {
    $programs = [];
    $sql = "SELECT * FROM programs ORDER BY created_at DESC";
    $res = $conn->query($sql);
    
    if(!$res) {
        // Auto-heal: Create unique table
        $conn->query("CREATE TABLE IF NOT EXISTS programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200),
            category VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $res = $conn->query($sql);
    }
    
    if($res) {
        while($row = $res->fetch_assoc()) {
            $programs[] = $row;
        }
    }
    echo json_encode($programs);
    exit;
}

if ($action === 'create_program') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'] ?? '';
    $cat = $input['category'] ?? '';
    $desc = $input['description'] ?? '';
    
    if(!$title || !$cat) {
        echo json_encode(['error' => 'Title and Category required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO programs (title, category, description) VALUES (?, ?, ?)");
    
    // Auto-heal
    if(!$stmt) {
        $conn->query("CREATE TABLE IF NOT EXISTS programs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200),
            category VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $stmt = $conn->prepare("INSERT INTO programs (title, category, description) VALUES (?, ?, ?)");
    }
    
    if ($stmt) {
        $stmt->bind_param("sss", $title, $cat, $desc);
    } else {
        echo json_encode(['error' => 'Database Error: ' . $conn->error]);
        exit;
    }
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

// --- APPLICATION ACTIONS ---

if ($action === 'apply_program') {
    $input = json_decode(file_get_contents('php://input'), true);
    $progId = $input['program_id'];
    
    // Auto-heal table
    $conn->query("CREATE TABLE IF NOT EXISTS program_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        program_id INT,
        status VARCHAR(50) DEFAULT 'pending',
        material_link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Ensure notifications table exists
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(200),
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        type VARCHAR(50) DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Check duplicate
    $check = $conn->query("SELECT id FROM program_applications WHERE user_id = $uid AND program_id = $progId");
    if($check && $check->num_rows > 0) {
        echo json_encode(['error' => 'Already applied']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO program_applications (user_id, program_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $uid, $progId);
    if($stmt->execute()) {
        // Get program title
        $pRes = $conn->query("SELECT title FROM programs WHERE id = $progId");
        $pTitle = ($pRes && $pRes->num_rows > 0) ? $pRes->fetch_assoc()['title'] : 'Program';

        // 1. Notify User
        $uMsg = "You have successfully applied for '$pTitle'. Please wait for admin approval and materials.";
        $stmtU = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Application Sent', ?, 'info')");
        $stmtU->bind_param("is", $uid, $uMsg);
        $stmtU->execute();

        // 2. Notify Admin
        $uName = 'A user';
        $uRes = $conn->query("SELECT full_name FROM users WHERE id = $uid");
        if($uRes && $uRes->num_rows > 0) $uName = $uRes->fetch_assoc()['full_name'];

        $adminId = 1;
        $adminRes = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if($adminRes && $adminRes->num_rows > 0) $adminId = $adminRes->fetch_assoc()['id'];

        $aMsg = "$uName has applied for '$pTitle'. Review available in Applications panel.";
        $stmtA = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Program Application', ?, 'info')");
        $stmtA->bind_param("is", $adminId, $aMsg);
        $stmtA->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    exit;
}

if ($action === 'get_notifications') {
    $notifs = [];
    $conn->query("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(200),
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        type VARCHAR(50) DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $res = $conn->query("SELECT * FROM notifications WHERE user_id = $uid ORDER BY created_at DESC LIMIT 10");
    while($row = $res->fetch_assoc()) $notifs[] = $row;
    echo json_encode($notifs);
    exit;
}

if ($action === 'mark_read') {
    $input = json_decode(file_get_contents('php://input'), true);
    $notifId = $input['id'];
    $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $notifId AND user_id = $uid");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_my_applications') {
    $apps = [];
    
    // Auto-heal
    $conn->query("CREATE TABLE IF NOT EXISTS program_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        program_id INT,
        status VARCHAR(50) DEFAULT 'pending',
        material_link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Join with programs to get title
    $sql = "SELECT pa.*, p.title, p.category FROM program_applications pa 
            JOIN programs p ON pa.program_id = p.id 
            WHERE pa.user_id = $uid ORDER BY pa.created_at DESC";
    
    $res = $conn->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) $apps[] = $row;
    }
    echo json_encode($apps);
    exit;
}

if ($action === 'admin_applications') {
    $apps = [];
    
    // Auto-heal
    $conn->query("CREATE TABLE IF NOT EXISTS program_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        program_id INT,
        status VARCHAR(50) DEFAULT 'pending',
        material_link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $sql = "SELECT pa.*, u.full_name, p.title as program_title FROM program_applications pa
            JOIN users u ON pa.user_id = u.id
            JOIN programs p ON pa.program_id = p.id
            ORDER BY pa.created_at DESC";
    $res = $conn->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) $apps[] = $row;
    }
    echo json_encode($apps);
    exit;
}

if ($action === 'send_material') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appId = $input['id'];
    $link = "view_material.php?app_id=" . $appId; 
    
    $stmt = $conn->prepare("UPDATE program_applications SET status = 'approved', material_link = ? WHERE id = ?");
    $stmt->bind_param("si", $link, $appId);
    
    if($stmt->execute()) {
        // Send Notification
        $res = $conn->query("SELECT pa.user_id, p.title FROM program_applications pa JOIN programs p ON pa.program_id = p.id WHERE pa.id = $appId");
        if($res && $res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $targetUid = $data['user_id'];
            $pTitle = $data['title'];
            $notifTitle = "Application Approved: $pTitle";
            $notifMsg = "Your application for $pTitle has been approved and the material link is now available on your dashboard.";
            $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
            $stmtNotif->bind_param("iss", $targetUid, $notifTitle, $notifMsg);
            $stmtNotif->execute();
        }
        echo json_encode(['success' => true]);
    }
    else echo json_encode(['error' => $conn->error]);
    exit;
}



if ($action === 'manual_enroll') {
    $input = json_decode(file_get_contents('php://input'), true);
    $uid_target = $input['user_id'];
    $progId = $input['program_id'];
    
    // Check duplicate
    $check = $conn->query("SELECT id FROM program_applications WHERE user_id = $uid_target AND program_id = $progId");
    if($check && $check->num_rows > 0) {
        $appId = $check->fetch_assoc()['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO program_applications (user_id, program_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $uid_target, $progId);
        $stmt->execute();
        $appId = $stmt->insert_id;
    }
    
    $link = "view_material.php?app_id=" . $appId;
    $stmt = $conn->prepare("UPDATE program_applications SET status = 'approved', material_link = ? WHERE id = ?");
    $stmt->bind_param("si", $link, $appId);
    
    if($stmt->execute()) {
        // Send Notification to User
        $res = $conn->query("SELECT user_id, program_id FROM program_applications WHERE id = $appId");
        $appData = $res->fetch_assoc();
        $targetUid = $appData['user_id'];
        $pId = $appData['program_id'];
        $pTitle = $conn->query("SELECT title FROM programs WHERE id = $pId")->fetch_assoc()['title'];
        
        $notifTitle = "Module Sent: $pTitle";
        $notifMsg = "Admin has sent you the learning materials for $pTitle. Check your dashboard!";
        $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
        $stmtNotif->bind_param("iss", $targetUid, $notifTitle, $notifMsg);
        $stmtNotif->execute();

        echo json_encode(['success' => true]);
    } else echo json_encode(['error' => $conn->error]);
    exit;
}

if ($action === 'delete_program') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pid = $input['id'];
    // Optional: Delete applications associated with this program
    $conn->query("DELETE FROM program_applications WHERE program_id = $pid");
    $stmt = $conn->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->bind_param("i", $pid);
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['error' => $conn->error]);
    exit;
}

if ($action === 'update_program') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pid = $input['id'];
    $title = $input['title'];
    $cat = $input['category'];
    $desc = $input['description'];
    
    $stmt = $conn->prepare("UPDATE programs SET title = ?, category = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $cat, $desc, $pid);
    
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['error' => $conn->error]);
    exit;
}

if ($action === 'upload_doc') {
    if (!isset($_FILES['doc_file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }
    
    $title = $_POST['title'] ?? 'Untitled';
    $cat = $_POST['category'] ?? 'General';
    $file = $_FILES['doc_file'];
    
    $uploadDir = 'uploads/docs/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $conn->prepare("INSERT INTO learning_docs (title, category, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $cat, $targetPath);
        if ($stmt->execute()) echo json_encode(['success' => true]);
        else echo json_encode(['error' => $conn->error]);
    } else {
        echo json_encode(['error' => 'Failed to move uploaded file']);
    }
    exit;
}

if ($action === 'get_docs') {
    // Auto-heal
    $conn->query("CREATE TABLE IF NOT EXISTS learning_docs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200),
        category VARCHAR(100),
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $docs = [];
    $res = $conn->query("SELECT * FROM learning_docs ORDER BY created_at DESC");
    while($row = $res->fetch_assoc()) $docs[] = $row;
    echo json_encode($docs);
    exit;
}

if ($action === 'delete_doc') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'];
    
    // Get path to delete file
    $res = $conn->query("SELECT file_path FROM learning_docs WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        if (file_exists($row['file_path'])) unlink($row['file_path']);
    }
    
    $conn->query("DELETE FROM learning_docs WHERE id = $id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete_application') {
    $input = json_decode(file_get_contents('php://input'), true);
    $aid = $input['id'];
    $stmt = $conn->prepare("DELETE FROM program_applications WHERE id = ?");
    $stmt->bind_param("i", $aid);
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['error' => $conn->error]);
    exit;
}

if ($action === 'submitted_programs') { // Renaming reports to programs context in user dash? No, let's keep separate.
    // Create new action for fetching recommendations
}

if ($action === 'get_recommendations') {
    $res = $conn->query("SELECT ai_analysis_result FROM users WHERE id = $uid");
    $row = $res->fetch_assoc();
    $recs = $row['ai_analysis_result'] ? json_decode($row['ai_analysis_result'], true) : [];
    
    // If empty (no matches), return random/all or specific message
    if(empty($recs)) {
        // Fallback: Get recent programs
         $resProgs = $conn->query("SELECT id, title, category, 1 as score FROM programs LIMIT 3");
         while($p = $resProgs->fetch_assoc()) $recs[] = $p;
    }
    
    echo json_encode($recs);
    exit;
}

// End of Admin Actions
if ($action === 'stats') {
    // 1. Total Users (exclude admins maybe? usually yes)
    $resUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'");
    $totalUsers = $resUsers->fetch_assoc()['c'];

    // 2. Pending Reports
    // Check if table exists first to avoid error if migration not run
    $pendingReports = 0;
    $resCheck = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($resCheck && $resCheck->num_rows > 0) {
        $resRep = $conn->query("SELECT COUNT(*) as c FROM reports WHERE status = 'pending'");
        if($resRep) $pendingReports = $resRep->fetch_assoc()['c'];
    }

    // 3. Active Sessions (Users active in last 30 mins)
    // Needs last_activity column
    $activeSessions = 0;
    $resCheckCol = $conn->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
    if ($resCheckCol && $resCheckCol->num_rows > 0) {
         $resActive = $conn->query("SELECT COUNT(*) as c FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
         if($resActive) $activeSessions = $resActive->fetch_assoc()['c'];
    }

    echo json_encode([
        'total_users' => $totalUsers,
        'pending_reports' => $pendingReports,
        'active_sessions' => $activeSessions
    ]);
    exit;
}

if ($action === 'chatbot') {
    $input = json_decode(file_get_contents('php://input'), true);
    $question = strtolower($input['message'] ?? '');
    
    // Knowledge Base for Barangay and LGU
    $knowledge = [
        'barangay 175' => "Barangay 175 (Camarin) process usually involves visiting the Barangay Hall located near the Camarin D Market. For clearances, bring a valid ID and proof of residency. They are open Monday to Friday, 8:00 AM to 5:00 PM.",
        'clearance' => "To get a Barangay Clearance, you need to provide a valid ID, a cedula (Community Tax Certificate), and a small fee (usually around 50-100 pesos). The process takes about 15-30 minutes.",
        'business permit' => "For Business Permits in our LGU, you must first obtain a Barangay Business Clearance, followed by a Fire Safety Certificate and Health Permit before applying at the City Hall Business One-Stop Shop.",
        'livelihood' => "Our Livelihood programs include Agriculture training, Technical skills, and small business grants. You can apply for these directly through the 'Programs' section in your dashboard.",
        'report' => "If you have a concern like broken street lights or trash collection, use the 'Create Report' button in your dashboard. Our team will review and address it within 3-5 working days.",
        'hours' => "The Barangay Hall and LGU offices are open from Monday to Friday, 8:00 AM up to 5:00 PM (excluding holidays).",
        'location' => "The main LGU City Hall is located in the city center near the transport terminal. Barangay halls are distributed per district.",
        'contact' => "You can contact the LGU hotline at 8-123-4567 for emergency assistance or email us at support@lgu3.gov.ph."
    ];

    $response = "I'm sorry, I don't have specific details on that yet. However, for most barangay processes, you can visit your local Barangay Hall with a valid ID. Would you like to know about our Livelihood programs instead?";

    // Simple keyword matching
    foreach ($knowledge as $key => $answer) {
        if (strpos($question, $key) !== false) {
            $response = $answer;
            break;
        }
    }

    echo json_encode([
        'response' => $response,
        'timestamp' => date('H:i')
    ]);
    exit;
}

if ($action === 'all_citizens') {
    $users = [];
    $query = "SELECT id, full_name, email, created_at, role, is_active FROM users WHERE role = 'user' ORDER BY created_at DESC";
    $result = $conn->query($query);
    while($row = $result->fetch_assoc()) $users[] = $row;
    echo json_encode($users);
    exit;
}

if ($action === 'recent_users') {
    $users = [];
    $query = "SELECT id, full_name, email, created_at, role FROM users ORDER BY created_at DESC LIMIT 5";
    $result = $conn->query($query);
    while($row = $result->fetch_assoc()) $users[] = $row;
    echo json_encode($users);
    exit;
}

if ($action === 'update_report_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $status = $input['status'] ?? '';
    
    if(!$id || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if($stmt->execute()) {
        // Send Notification
        $res = $conn->query("SELECT r.user_id, r.title FROM reports r WHERE r.id = $id");
        if($res && $res->num_rows > 0) {
            $data = $res->fetch_assoc();
            $targetUid = $data['user_id'];
            $rTitle = $data['title'];
            $notifType = ($status === 'approved') ? 'success' : 'warning';
            $notifTitle = "Report Update: $rTitle";
            $notifMsg = "Your report '$rTitle' has been marked as " . strtoupper($status) . " by the administration.";
            $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmtNotif->bind_param("isss", $targetUid, $notifTitle, $notifMsg, $notifType);
            $stmtNotif->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }
    exit;
}

if ($action === 'reports') {
    $reports = [];
    $resCheck = $conn->query("SHOW TABLES LIKE 'reports'");
    if ($resCheck->num_rows > 0) {
        
        // Dynamic Timestamp Column Check
        $tsCol = 'created_at';
        $check = $conn->query("SHOW COLUMNS FROM reports LIKE 'submitted_at'");
        if($check && $check->num_rows > 0) $tsCol = 'submitted_at';

        $query = "SELECT r.id, r.title, r.description, r.status, r.$tsCol as created_at, u.full_name 
                  FROM reports r 
                  JOIN users u ON r.user_id = u.id 
                  ORDER BY r.$tsCol DESC LIMIT 10";
        $result = $conn->query($query);
        if (!$result) {
            echo json_encode(['error' => $conn->error]);
            exit;
        }
        while($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    }
    echo json_encode($reports);
    exit;
}

if ($action === 'request_edit') {
    // Set flag
    $conn->query("UPDATE users SET requesting_edit = 1 WHERE id = $uid");

    // Get user name
    $uname = 'A user';
    $uRes = $conn->query("SELECT full_name FROM users WHERE id = $uid");
    if ($uRes && $uRes->num_rows > 0) {
        $uname = $uRes->fetch_assoc()['full_name'];
    }
    
    // Find an Admin ID
    $adminId = 1;
    $adminRes = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($adminRes && $adminRes->num_rows > 0) {
        $adminId = $adminRes->fetch_assoc()['id'];
    }
    
    $msg = "$uname has requested permission to edit their profile name.";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Direct Name Edit Request', ?, 'warning')");
    
    if ($stmt) {
        $stmt->bind_param("is", $adminId, $msg);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Request sent to Administrator. Please wait for approval.']);
    } else {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }
    exit;
}

if ($action === 'check_edit_auth') {
    $res = $conn->query("SELECT edit_authorized_until FROM users WHERE id = $uid");
    $row = ($res) ? $res->fetch_assoc() : null;
    
    $authorized = false;
    $remaining = 0;
    
    if ($row && $row['edit_authorized_until']) {
        $expiry = strtotime($row['edit_authorized_until']);
        $now = time();
        if ($expiry > $now) {
            $authorized = true;
            $remaining = $expiry - $now;
        }
    }
    
    echo json_encode(['authorized' => $authorized, 'remaining' => $remaining]);
    exit;
}

if ($action === 'update_name') {
    $input = json_decode(file_get_contents('php://input'), true);
    $newName = $input['full_name'] ?? '';
    
    // Check auth again
    $res = $conn->query("SELECT edit_authorized_until FROM users WHERE id = $uid");
    $row = $res->fetch_assoc();
    if (!$row['edit_authorized_until'] || strtotime($row['edit_authorized_until']) < time()) {
        echo json_encode(['error' => 'Edit period expired or not authorized.']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, edit_authorized_until = NULL WHERE id = ?");
    $stmt->bind_param("si", $newName, $uid);
    if($stmt->execute()) echo json_encode(['success' => true]);
    else echo json_encode(['error' => $conn->error]);
    exit;
}

if ($action === 'approve_edit') {
    if($role !== 'admin') exit;
    $input = json_decode(file_get_contents('php://input'), true);
    $targetUid = $input['user_id'];
    
    // Clear request flag
    $conn->query("UPDATE users SET requesting_edit = 0 WHERE id = $targetUid");
    
    // Set 25 minutes from now
    $conn->query("UPDATE users SET edit_authorized_until = DATE_ADD(NOW(), INTERVAL 25 MINUTE) WHERE id = $targetUid");
    
    // Notify user
    $msg = "Administrator has approved your name change. You have 25 minutes to update your profile.";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Edit Approved', ?, 'success')");
    $stmt->bind_param("is", $targetUid, $msg);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_edit_requests') {
    if($role !== 'admin') exit;
    $users = [];
    $res = $conn->query("SELECT id, full_name, email, created_at FROM users WHERE requesting_edit = 1");
    if($res) {
        while($row = $res->fetch_assoc()) $users[] = $row;
    }
    echo json_encode($users);
    exit;
}

if ($action === 'toggle_user_status') {
    if($role !== 'admin') exit;
    $input = json_decode(file_get_contents('php://input'), true);
    $targetUid = $input['user_id'];
    $newStatus = $input['is_active']; // 1 or 0
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStatus, $targetUid);
    
    if($stmt->execute()) {
        $statusText = $newStatus ? "Activated" : "Deactivated";
        
        // Notify user if possible (they can see it next time or if session allowed)
        $msg = "Your account has been $statusText by an Administrator.";
        $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Account Update', ?, 'info')");
        $stmtNotif->bind_param("is", $targetUid, $msg);
        $stmtNotif->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    exit;
}

if ($action === 'get_calendar') {
    $events = [];
    if ($role === 'admin') {
        // Admin sees everything
        $sql = "SELECT e.*, GROUP_CONCAT(u.full_name SEPARATOR ', ') as tagged_names 
                FROM calendar_events e 
                LEFT JOIN event_tags et ON e.id = et.event_id
                LEFT JOIN users u ON et.user_id = u.id
                GROUP BY e.id
                ORDER BY e.event_date ASC, e.event_time ASC";
    } else {
        // User sees events they created OR where they are tagged
        $sql = "SELECT e.*, et.status as user_status 
                FROM calendar_events e 
                LEFT JOIN event_tags et ON e.id = et.event_id
                WHERE e.creator_id = $uid OR et.user_id = $uid
                ORDER BY e.event_date ASC, e.event_time ASC";
    }
    
    $res = $conn->query($sql);
    if($res) {
        while($row = $res->fetch_assoc()) $events[] = $row;
    }
    echo json_encode($events);
    exit;
}

if ($action === 'add_calendar_event') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = $input['title'];
    $desc = $input['description'] ?? '';
    $date = $input['event_date'];
    $time = $input['event_time'] ?? '09:00:00';
    $type = $input['type'] ?? 'task';
    $target_uids = $input['target_user_ids'] ?? []; // Array of IDs

    $stmt = $conn->prepare("INSERT INTO calendar_events (creator_id, title, description, event_date, event_time, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $uid, $title, $desc, $date, $time, $type);
    
    if($stmt->execute()) {
        $eventId = $conn->insert_id;
        
        // Tag multiple users
        if(!empty($target_uids)) {
            $notifTitle = "New Calendar Event: $title";
            $notifMsg = "You have been tagged in a new calendar event: $title scheduled for $date at $time.";
            
            foreach($target_uids as $target_uid) {
                if(empty($target_uid)) continue;
                $stmtTag = $conn->prepare("INSERT INTO event_tags (event_id, user_id) VALUES (?, ?)");
                $stmtTag->bind_param("ii", $eventId, $target_uid);
                $stmtTag->execute();

                $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
                $stmtNotif->bind_param("iss", $target_uid, $notifTitle, $notifMsg);
                $stmtNotif->execute();
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    exit;
}

if ($action === 'delete_calendar_event') {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['id'];
    
    // Only creator can delete
    $stmt = $conn->prepare("DELETE FROM calendar_events WHERE id = ? AND creator_id = ?");
    $stmt->bind_param("ii", $eventId, $uid);
    
    if($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Delete failed or unauthorized']);
    }
    exit;
}

if ($action === 'respond_to_event') {
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = $input['id'];
    $newStatus = $input['status']; // 'joined' or 'declined'
    
    // Update status in event_tags
    $stmt = $conn->prepare("UPDATE event_tags SET status = ? WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("sii", $newStatus, $eventId, $uid);
    
    if($stmt->execute() && $stmt->affected_rows > 0) {
        $q = "SELECT title, creator_id FROM calendar_events WHERE id = $eventId";
        $res = $conn->query($q);
        if($res && $row = $res->fetch_assoc()) {
            $creatorId = $row['creator_id'];
            $title = $row['title'];
            $userName = $_SESSION['full_name'];
            $statusText = ucfirst($newStatus);
            
            $notifTitle = "Event Response: $statusText";
            $notifMsg = "$userName has $newStatus the event: $title";
            
            $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
            $stmtNotif->bind_param("iss", $creatorId, $notifTitle, $notifMsg);
            $stmtNotif->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Response failed or unauthorized']);
    }
    exit;
}

if ($action === 'get_calendar_stats') {
    $stats = [];
    $sql = "SELECT status, COUNT(*) as count FROM event_tags GROUP BY status";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['count'];
    }
    
    $stats['pending'] = $stats['pending'] ?? 0;
    $stats['joined'] = $stats['joined'] ?? 0;
    $stats['declined'] = $stats['declined'] ?? 0;
    
    $total = $stats['pending'] + $stats['joined'] + $stats['declined'];
    $stats['total'] = $total;
    
    if($total > 0) {
        $stats['join_percentage'] = round(($stats['joined'] / $total) * 100, 1);
        $stats['decline_percentage'] = round(($stats['declined'] / $total) * 100, 1);
    } else {
        $stats['join_percentage'] = 0;
        $stats['decline_percentage'] = 0;
    }
    
    echo json_encode($stats);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
exit;
