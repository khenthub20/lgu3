<?php
require_once 'db_connect.php';

echo "<h2>Seeding Maintenance Schedules</h2>";

$samples = [
    [
        'maint_id' => 'CIMM-0001',
        'facility' => 'Main Water Pipe - Zone 1',
        'maint_type' => 'Plumbing/Water',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'duration' => '4 Hours',
        'priority' => 'High',
        'status' => 'Scheduled',
        'user_id' => null
    ],
    [
        'maint_id' => 'CIMM-0002',
        'facility' => 'Street Light post #42',
        'maint_type' => 'Electrical',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+3 days')),
        'duration' => '2 Hours',
        'priority' => 'Medium',
        'status' => 'Scheduled',
        'user_id' => null
    ],
    [
        'maint_id' => 'CIMM-0003',
        'facility' => 'Barangay Hall Roof',
        'maint_type' => 'Infrastructure',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'duration' => '1 Day',
        'priority' => 'Low',
        'status' => 'Completed',
        'user_id' => null
    ],
    [
        'maint_id' => 'CIMM-0004',
        'facility' => 'Central Drainage Canal',
        'maint_type' => 'Sanitation',
        'scheduled_date' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'duration' => '6 Hours',
        'priority' => 'Critical',
        'status' => 'In Progress',
        'user_id' => null
    ]
];

foreach ($samples as $s) {
    $check = $conn->query("SELECT id FROM maintenance_schedules WHERE maint_id = '{$s['maint_id']}'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO maintenance_schedules (maint_id, facility, maint_type, scheduled_date, duration, priority, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $s['maint_id'], $s['facility'], $s['maint_type'], $s['scheduled_date'], $s['duration'], $s['priority'], $s['status'], $s['user_id']);
        if ($stmt->execute()) {
            echo "Seeded: {$s['maint_id']}<br>";
        } else {
            echo "Error seeding {$s['maint_id']}: " . $conn->error . "<br>";
        }
    } else {
        echo "Already exists: {$s['maint_id']}<br>";
    }
}

echo "Done.";
?>
