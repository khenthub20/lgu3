<?php
include 'db_connect.php';

// DROP TABLES to ensure clean slate (reverse order of dependencies)
$conn->query("DROP TABLE IF EXISTS user_skill_progress");
$conn->query("DROP TABLE IF EXISTS skill_test_stages");
$conn->query("DROP TABLE IF EXISTS skill_tests");

echo "Dropped old tables.<br>";

// 1. Create skill_tests table
$sql = "CREATE TABLE skill_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    description TEXT,
    thumbnail VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table skill_tests created.<br>";
} else {
    die("Error creating skill_tests: " . $conn->error);
}

// 2. Create skill_test_stages table
$sql = "CREATE TABLE skill_test_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT,
    stage_number INT,
    title VARCHAR(200),
    content TEXT,
    video_url VARCHAR(255),
    FOREIGN KEY (test_id) REFERENCES skill_tests(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table skill_test_stages created.<br>";
} else {
    die("Error creating skill_test_stages: " . $conn->error);
}

// 3. Create user_skill_progress table
$sql = "CREATE TABLE user_skill_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    test_id INT,
    current_stage INT DEFAULT 1,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    score INT DEFAULT 0,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (test_id) REFERENCES skill_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if ($conn->query($sql) === TRUE) {
    echo "Table user_skill_progress created.<br>";
} else {
    die("Error creating user_skill_progress: " . $conn->error);
}

// Seed Data
$stmt = $conn->prepare("INSERT INTO skill_tests (title, description, thumbnail) VALUES (?, ?, ?)");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$title = "Digital Literacy Skill Test";
$desc = "Master the basics of digital literacy in 5 easy stages. Free enrollment.";
$thumb = "https://images.unsplash.com/photo-1572044162444-ad6021194360?auto=format&fit=crop&w=600&q=80";
$stmt->bind_param("sss", $title, $desc, $thumb);
if ($stmt->execute()) {
    $testId = $stmt->insert_id;
    echo "Seeded Skill Test ID: $testId<br>";

    // Insert 5 Stages
    $stages = [
        [1, "Introduction to Computers", "Learn the basic parts of a computer and how to turn it on/off.", "https://www.youtube.com/embed/dQw4w9WgXcQ"], // Dummy video
        [2, "Using the Mouse & Keyboard", "Interactive guide to typing and navigating with a mouse.", "https://www.youtube.com/embed/dQw4w9WgXcQ"],
        [3, "Browsing the Internet", "How to use a web browser, search engines, and safety tips.", "https://www.youtube.com/embed/dQw4w9WgXcQ"],
        [4, "Email Basics", "Creating an email account, sending, and receiving emails.", "https://www.youtube.com/embed/dQw4w9WgXcQ"],
        [5, "Final Assessment", "Complete a quiz to verify your skills and get your certificate.", "https://www.youtube.com/embed/dQw4w9WgXcQ"]
    ];

    $stmt2 = $conn->prepare("INSERT INTO skill_test_stages (test_id, stage_number, title, content, video_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($stages as $stage) {
        $stmt2->bind_param("iisss", $testId, $stage[0], $stage[1], $stage[2], $stage[3]);
        $stmt2->execute();
    }
    echo "Seeded 5 stages.<br>";
} else {
    echo "Error executing seed: " . $stmt->error . "<br>";
}

echo "Setup Complete.";
?>
