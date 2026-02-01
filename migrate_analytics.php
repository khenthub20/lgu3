<?php
include 'db_connect.php';

// Migration for Analytics Expansion
$sqls = [
    "CREATE TABLE IF NOT EXISTS facilities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS facility_reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT,
        user_id INT,
        reservation_date DATE NOT NULL,
        status ENUM('Approved', 'Pending', 'Denied', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "Table processed.\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

// Seed Facilities if empty
$check = $conn->query("SELECT id FROM facilities LIMIT 1");
if ($check->num_rows == 0) {
    $facilities = [
        ['Sanville Covered Court w/ Multipurpose BLDG', 'Brgy. Culiat'],
        ['Pael Multipurpose BLDG/ Burial Site', 'Brgy. Culiat'],
        ['Culiat Highschool', 'Brgy. Culiat'],
        ['Cassanova Multipurpose Building', 'Brgy. Culiat'],
        ['Bernardo Court', 'Brgy. Culiat']
    ];
    foreach ($facilities as $f) {
        $conn->query("INSERT INTO facilities (name, location) VALUES ('$f[0]', '$f[1]')");
    }
    echo "Seeded facilities.\n";
}

// Seed Random Reservations if empty
$check = $conn->query("SELECT id FROM facility_reservations LIMIT 1");
if ($check->num_rows == 0) {
    $facRes = $conn->query("SELECT id FROM facilities");
    $facIds = [];
    while($row = $facRes->fetch_assoc()) $facIds[] = $row['id'];
    
    $userRes = $conn->query("SELECT id FROM users LIMIT 10");
    $userIds = [];
    while($row = $userRes->fetch_assoc()) $userIds[] = $row['id'];
    
    if (!empty($facIds) && !empty($userIds)) {
        $statuses = ['Approved', 'Pending', 'Denied', 'Cancelled'];
        for ($i = 0; $i < 50; $i++) {
            $fId = $facIds[array_rand($facIds)];
            $uId = $userIds[array_rand($userIds)];
            $status = $statuses[array_rand($statuses)];
            $date = date('Y-m-d', strtotime('-' . rand(0, 180) . ' days'));
            $conn->query("INSERT INTO facility_reservations (facility_id, user_id, reservation_date, status) VALUES ($fId, $uId, '$date', '$status')");
        }
        echo "Seeded reservations.\n";
    }
}
?>
