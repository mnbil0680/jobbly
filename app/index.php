<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobbly | Careers</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">

<?php
include_once 'header.php';
require_once 'DB_Ops.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

// Initial view state from URL if present (to handle deep links)
$initialView = $_GET['view'] ?? 'explore';
$initialSearch = $_GET['search'] ?? '';
$initialPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
?>

<main class="main-layout-inedx">
    <!-- HERO SECTION (Stays static) -->
    <section class="hero-section <?php echo $initialView === 'saved' ? 'hidden' : ''; ?>" id="exploreHero">
        <div class="hero-card">
            <div class="hero-content">
                <h1>Search Roles</h1>
                <div class="hero-search">
                    <div class="hero-search-input">
                        <span class="material-symbols-outlined">search</span>
                        <input id="jobSearchInput" type="text" placeholder="Job title, keywords, or company..." value="<?php echo htmlspecialchars($initialSearch); ?>">
                    </div>
                    <button type="button" class="search-btn" id="searchTrigger">Search</button>
                </div>
            </div>
            <div class="hero-glow"></div>
        </div>
    </section>

    <!-- SPA VIEW NAVIGATION -->
    <!-- <div class="view-tabs">
        <button class="tab-btn <?php echo $initialView === 'explore' ? 'active' : ''; ?>" data-view="explore">Explore</button>
        <?php if ($isLoggedIn): ?>
            <button class="tab-btn <?php echo $initialView === 'saved' ? 'active' : ''; ?>" data-view="saved">Saved Jobs</button>
        <?php endif; ?>
    </div> -->

    <!-- DYNAMIC CONTENT AREA -->
    <section class="content-section">
        <div class="section-head">
            <h2>Recent Listings</h2>
        </div>

        <!-- AJAX Container -->
        <div id="jobsContainer" class="jobs-list">
            <div class="loading-state">
                <span class="material-symbols-outlined rotating">refresh</span>
                <p>Fetching jobs...</p>
            </div>
        </div>

        <!-- AJAX Pagination -->
        <div id="paginationContainer" class="pagination"></div>
    </section>
</main>

<?php include_once 'footer.php'; ?>

<!-- Assignment Requirements: API_Ops.js and main.js -->
<script src="assets/js/main.js"></script>
<script src="assets/js/API_Ops.js"></script>

<script>
    // Initialize SPA State
    document.addEventListener('DOMContentLoaded', () => {
        currentView = <?php echo json_encode($initialView); ?>;
        currentSearchTerm = <?php echo json_encode($initialSearch); ?>;
        const initialPage = <?php echo json_encode($initialPage); ?>;

        // Initial Load
        fetchJobs(currentView, initialPage, currentSearchTerm);

        // Tab Switching Logic
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                currentView = view;
                document.getElementById('exploreHero').style.display = view === 'explore' ? 'block' : 'none';
                
                fetchJobs(view, 1, currentSearchTerm);
            });
        });

        // Search Logic
        const input = document.getElementById('jobSearchInput');
        const trigger = document.getElementById('searchTrigger');

        const performSearch = () => {
            currentSearchTerm = input.value.trim();
            fetchJobs(currentView, 1, currentSearchTerm);
        };

        trigger.addEventListener('click', performSearch);
        input.addEventListener('keypress', (e) => { if (e.key === 'Enter') performSearch(); });
    });
</script>

</body>
</html>
