<?php
session_start();
include 'db_connect.php';

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$ann = $result->fetch_assoc();

if (!$ann) {
    header("Location: landingpage.php");
    exit;
}

$title = $ann['title'];
$category = $ann['category'] ?? 'ADVISORY';
$content = $ann['content'];
$image = $ann['image_path'] ? $ann['image_path'] : 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?q=80&w=1000';
$date = date('F d, Y', strtotime($ann['created_at']));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> | Baranggay 624 Announcements</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --bg: #ffffff;
            --card: #f9fafb;
            --text-main: #111827;
            --text-muted: #4b5563;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        
        body {
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 900px;
            padding: 4rem 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 2rem;
            transition: color 0.3s;
        }
        .back-btn:hover { color: var(--primary); }

        .announcement-header {
            margin-bottom: 2.5rem;
        }

        .badge {
            display: inline-block;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 3rem;
            line-height: 1.2;
            margin-bottom: 1rem;
            color: #111827;
        }

        .meta {
            display: flex;
            align-items: center; gap: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .feature-image-container {
            width: 100%;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }

        .feature-image {
            width: 100%;
            height: auto;
            max-height: 600px;
            object-fit: contain;
            background: #f3f4f6;
            display: block;
        }

        .content {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #374151;
            white-space: pre-wrap;
            margin-bottom: 4rem;
        }

        .action-footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding-top: 3rem;
            text-align: center;
        }

        .btn-register {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--primary);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.3);
            transition: all 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
        }

        @media (max-width: 768px) {
            h1 { font-size: 2.2rem; }
            .container { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="landingpage.php#announcements" class="back-btn">
            <i data-feather="arrow-left"></i> Back to Updates
        </a>

        <div class="announcement-header">
            <span class="badge"><?php echo $category; ?></span>
            <h1><?php echo $title; ?></h1>
            <div class="meta">
                <span><i data-feather="calendar" style="width:14px; vertical-align:middle; margin-right:5px;"></i> <?php echo $date; ?></span>
                <span><i data-feather="map-pin" style="width:14px; vertical-align:middle; margin-right:5px;"></i> Baranggay 624</span>
            </div>
        </div>

        <div class="feature-image-container">
            <img src="<?php echo $image; ?>" class="feature-image" alt="<?php echo $title; ?>">
        </div>

        <div class="content">
            <?php echo $content; ?>
        </div>

    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
