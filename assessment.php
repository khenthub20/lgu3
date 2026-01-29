<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Check if already assessed
$check = $conn->query("SELECT skills FROM users WHERE id = $uid");
$userData = $check->fetch_assoc();
if ($userData && !empty($userData['skills'])) {
    header("Location: user_dashboard.php"); // Already done
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skills Assessment | LGU3 AI</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .assessment-container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
        }
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            color: var(--text-muted);
        }
        .step-indicator.active { color: var(--primary); font-weight: 600; }
        
        .ai-processing-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 17, 21, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0; pointer-events: none;
            transition: opacity 0.5s ease;
        }
        .ai-processing-overlay.active { opacity: 1; pointer-events: all; }
        
        .loader-ring {
            width: 80px; height: 80px;
            border: 4px solid rgba(99, 102, 241, 0.1);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .typing-text::after { content: '...'; animation: ellipsis 1.5s infinite; }
        @keyframes ellipsis { 0% { content: '.'; } 33% { content: '..'; } 66% { content: '...'; } }
    </style>
</head>
<body class="dashboard-body" style="display:block; overflow-y:auto;">
    
    <div class="assessment-container">
        <div style="text-align:center; margin-bottom: 2rem;">
            <div style="width:60px; height:60px; background:rgba(99,102,241,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; color:var(--primary);">
                <i data-feather="cpu" style="width:30px; height:30px;"></i>
            </div>
            <h1>Community Analysis</h1>
            <p style="color:var(--text-muted); max-width:500px; margin:0 auto;">
                Our AI-Powered system will analyze your profile to recommend the best livelihood programs for you.
            </p>
        </div>

        <form id="assessmentForm">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; color:#fff;">Current Employment Status</label>
                <select name="employment_status" class="form-control" style="width:100%; padding: 1rem; background:var(--input-bg); border:1px solid var(--border-color); color:#fff; border-radius:12px;" required>
                    <option value="" disabled selected>Select Status</option>
                    <option value="Unemployed">Unemployed</option>
                    <option value="Employed">Employed (Full-time)</option>
                    <option value="Student">Student</option>
                    <option value="Self-Employed">Self-Employed</option>
                    <option value="Looking for Upskilling">Looking for Upskilling</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom:0.5rem; color:#fff;">Skills & Expertise</label>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">List your hard and soft skills (e.g., Cooking, Computer Repair, Communication...)</p>
                <textarea name="skills" class="form-control" style="width:100%; min-height:100px; padding: 1rem; background:var(--input-bg); border:1px solid var(--border-color); color:#fff; border-radius:12px;" required placeholder="I am good at..."></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display:block; margin-bottom:0.5rem; color:#fff;">Interests & Goals</label>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">What kind of livelihood do you want to pursue?</p>
                <textarea name="interests" class="form-control" style="width:100%; min-height:100px; padding: 1rem; background:var(--input-bg); border:1px solid var(--border-color); color:#fff; border-radius:12px;" required placeholder="I want to learn about..."></textarea>
            </div>

            <button type="submit" class="primary-action-btn" style="width:100%; padding: 1rem; font-size: 1.1rem;">
                Analyze & Find Programs
            </button>
        </form>
    </div>

    <!-- AI Overlay -->
    <div class="ai-processing-overlay" id="aiOverlay">
        <div class="loader-ring"></div>
        <h2 style="color:#fff; margin-bottom:0.5rem;">AI Analyzing Profile</h2>
        <p style="color:var(--text-muted);" id="aiStep" class="typing-text">Processing Natural Language</p>
    </div>

    <script>
        feather.replace();

        document.getElementById('assessmentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Show AI Animation
            const overlay = document.getElementById('aiOverlay');
            overlay.classList.add('active');
            
            const steps = [
                "Extracting Keywords...",
                "Matching with Livelihood Database...",
                "Running Compatibility Algorithm...",
                "Finalizing Recommendations..."
            ];
            
            const stepText = document.getElementById('aiStep');
            
            // Simulate AI Processing Time
            for (let i = 0; i < steps.length; i++) {
                stepText.innerText = steps[i];
                await new Promise(r => setTimeout(r, 800)); // 800ms per step
            }

            // Submit Data
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('api.php?action=submit_assessment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.success) {
                    // Success Popup & Redirect
                    stepText.innerText = "Done!";
                    setTimeout(() => {
                        alert('Analysis Complete! Redirecting to your personalized dashboard.');
                        window.location.href = 'user_dashboard.php';
                    }, 500);
                } else {
                    alert('Error: ' + result.error);
                    overlay.classList.remove('active');
                }
            } catch (err) {
                 alert('Network Error');
                 overlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>
