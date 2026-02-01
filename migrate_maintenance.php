<?php
include 'db_connect.php';

// Migration script for Maintenance Integration
$sql = "CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maint_id VARCHAR(50) UNIQUE NOT NULL,
    facility VARCHAR(255) NOT NULL,
    maint_type VARCHAR(100) NOT NULL,
    scheduled_date DATETIME NOT NULL,
    duration VARCHAR(50),
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Delayed') DEFAULT 'Scheduled',
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql)) {
    echo "Table 'maintenance_schedules' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Seed with some initial data if empty
$check = $conn->query("SELECT id FROM maintenance_schedules LIMIT 1");
if ($check->num_rows == 0) {
    // Try to find a user to link to
    $userRes = $conn->query("SELECT id FROM users LIMIT 1");
    $uid = ($userRes && $userRes->num_rows > 0) ? $userRes->fetch_assoc()['id'] : 'NULL';

    $seeds = [
        ['CIMM-14', 'City Hall - 2nd Floor', 'Aircon Filter Cleaning', '2026-02-19 08:00:00', '2 hours', 'Medium', 'Scheduled', $uid],
        ['CIMM-15', 'City Hall - Electrical Room', 'Electrical Panel Inspection', '2026-02-19 09:00:00', '3 hours', 'High', 'In Progress', $uid],
        ['CIMM-3', 'Building C', 'Fire Alarm Inspection', '2026-02-20 08:30:00', '4 hours', 'Medium', 'Scheduled', $uid]
    ];

    foreach ($seeds as $s) {
        $insert = "INSERT INTO maintenance_schedules (maint_id, facility, maint_type, scheduled_date, duration, priority, status, user_id) 
                   VALUES ('$s[0]', '$s[1]', '$s[2]', '$s[3]', '$s[4]', '$s[5]', '$s[6]', $s[7])";
        $conn->query($insert);
    }
    echo "Seeded maintenance data.\n";
}

// Ensure calendar_events can link to maintenance? 
// Or just let maintenance be its own thing. 
// The user said "id ng user yung reference sa maintenance calendar dyan papasok yun".
// Let's add reference_id support if it's not already linked via user_id.

?>
