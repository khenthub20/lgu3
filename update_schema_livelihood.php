<?php
include 'db_connect.php';

// 1. Programs Table
$sql = "CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    category VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// 2. Program Applications Table
$sql2 = "CREATE TABLE IF NOT EXISTS program_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    program_id INT,
    status VARCHAR(50) DEFAULT 'pending',
    material_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql2);

// Seed Programs if empty
$res = $conn->query("SELECT count(*) as c FROM programs");
if($res->fetch_assoc()['c'] == 0) {
    $stmt = $conn->prepare("INSERT INTO programs (title, category, description) VALUES (?, ?, ?)");
    $seed = [
        ['Basic Welding Training', 'Technical', 'Learn shield metal arc welding basics.'],
        ['Baking & Pastry Arts', 'Livelihood', 'Start your own bakery business.'],
        ['Call Center Agent Prep', 'BPO', 'English proficiency and customer service skills.'],
        ['Urban Gardening 101', 'Agriculture', 'Sustainable food production at home.'],
        ['Computer Systems Servicing', 'IT', 'Hardware repair and networking basics.'],
        ['Hairdressing NCII', 'Service', 'Professional hair cutting and styling.'],
        ['Digital Marketing for SMEs', 'Business', 'Promote products using social media.']
    ];
    foreach($seed as $s) {
        $stmt->bind_param("sss", $s[0], $s[1], $s[2]);
        $stmt->execute();
    }
    echo "Seeded Programs.<br>";
}

// 2. User Profiles / Skills (Extended)
// Adding columns to users table is easier than a new table for 1-to-1 extension
$cols = [
    "skills TEXT",
    "interests TEXT", 
    "employment_status VARCHAR(50)",
    "ai_analysis_result TEXT" // Store the JSON recommendations here
];

foreach ($cols as $col) {
    $cName = explode(' ', $col)[0];
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$cName'");
    if($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $col");
        echo "Added column $cName.<br>";
    }
}

echo "Livelihood System Schema Updated!";
?>
