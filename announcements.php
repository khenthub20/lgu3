<?php
session_start();
include 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Announcements | Baranggay 624 Laforteza holdings</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: #6366f1;
            --bg: #ffffff;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #4b5563;
            --border: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg); color: var(--text-main); line-height: 1.6; }

        .container { max-width: 1200px; margin: 0 auto; padding: 2rem 5%; }

        header {
            padding: 4rem 0 2rem;
            text-align: center;
        }

        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: 0.3s;
        }
        .back-home:hover { color: var(--primary); }

        /* Search and Filter Bar */
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            max-width: 600px;
        }
        .search-input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3.5rem;
            border-radius: 50px;
            border: 1px solid var(--border);
            background: #f9fafb;
            font-size: 1rem;
            outline: none;
            transition: 0.3s;
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
        .search-icon { position: absolute; left: 1.5rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); }

        .filter-group { display: flex; align-items: center; gap: 1rem; }
        .sort-select {
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text-main);
            font-weight: 600;
            cursor: pointer;
            outline: none;
        }

        .count-display { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }

        /* Announcement Grid */
        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .ann-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        .ann-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .ann-img-container {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #f3f4f6;
        }
        .ann-img { width: 100%; height: 100%; object-fit: cover; }
        .ann-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: var(--primary);
            color: #fff;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 2;
        }

        .ann-content { padding: 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .ann-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; }
        .ann-title { font-size: 1.25rem; font-weight: 700; color: var(--text-main); line-height: 1.4; flex: 1; }
        .ann-date { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        .ann-text { color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        .ann-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            margin-top: auto;
            transition: gap 0.3s;
        }
        .ann-link:hover { gap: 12px; }

        /* Empty State */
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 5rem 0;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .controls-bar { flex-direction: column; align-items: stretch; }
            .search-wrapper { max-width: 100%; }
            .announcement-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <a href="landingpage.php" class="back-home">
                <i data-feather="arrow-left"></i> Return to Home
            </a>
            <h1>Baranggay Announcements</h1>
            <p style="color: var(--text-muted); margin-top: 0.5rem;">Stay updated with the latest news, advisories, and events.</p>
        </header>

        <div class="controls-bar">
            <div class="filter-group">
                <span style="font-weight: 600; color: var(--text-muted);">Sort by:</span>
                <select class="sort-select" id="sort-select" onchange="filterAndRender()">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                </select>
            </div>

            <div class="search-wrapper">
                <i data-feather="search" class="search-icon" style="width: 20px;"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Search announcements..." oninput="filterAndRender()">
            </div>

            <div class="count-display" id="count-display">
                Showing 0 of 0 announcements
            </div>
        </div>

        <div id="announcement-grid" class="announcement-grid">
            <!-- Populated by JS -->
            <div class="empty-state">
                <i data-feather="loader" class="spinner" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                <p>Loading announcements...</p>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        let allAnnouncements = [];

        async function fetchAllAnnouncements() {
            try {
                const res = await fetch('api.php?action=get_announcements');
                const data = await res.json();
                allAnnouncements = data;
                filterAndRender();
            } catch (e) {
                console.error("Failed to fetch announcements", e);
                document.getElementById('announcement-grid').innerHTML = '<div class="empty-state"><p>Failed to load announcements. Please refresh.</p></div>';
            }
        }

        function filterAndRender() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const sortBy = document.getElementById('sort-select').value;
            const grid = document.getElementById('announcement-grid');
            const countDisplay = document.getElementById('count-display');

            let filtered = allAnnouncements.filter(ann => 
                ann.title.toLowerCase().includes(searchTerm) || 
                ann.content.toLowerCase().includes(searchTerm) ||
                (ann.category && ann.category.toLowerCase().includes(searchTerm))
            );

            // Sorting
            if (sortBy === 'newest') {
                filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            } else {
                filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            }

            // Update Count
            countDisplay.innerText = `Showing 1-${filtered.length} of ${allAnnouncements.length} announcements`;

            if (filtered.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <i data-feather="info" style="width: 48px; height: 48px; margin-bottom: 1rem;"></i>
                        <p>No announcements found matching "${searchTerm}"</p>
                    </div>
                `;
                feather.replace();
                return;
            }

            grid.innerHTML = '';
            filtered.forEach(ann => {
                const img = ann.image_path || 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?q=80&w=1000';
                const date = new Date(ann.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                grid.innerHTML += `
                    <div class="ann-card">
                        <div class="ann-img-container">
                            <span class="ann-badge">${ann.category || 'ADVISORY'}</span>
                            <img src="${img}" class="ann-img" alt="${ann.title}">
                        </div>
                        <div class="ann-content">
                            <div class="ann-header">
                                <h3 class="ann-title">${ann.title}</h3>
                                <span class="ann-date">${date}</span>
                            </div>
                            <p class="ann-text">${ann.content}</p>
                            <a href="view_announcement.php?id=${ann.id}" class="ann-link">
                                Learn More <i data-feather="arrow-right" style="width:18px;"></i>
                            </a>
                        </div>
                    </div>
                `;
            });
            feather.replace();
        }

        // Initialize
        fetchAllAnnouncements();
    </script>
</body>
</html>
