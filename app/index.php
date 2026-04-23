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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<?php include_once 'header.php'; ?>

<main class="main-layout">
    <section class="hero-section">
        <div class="hero-card">
            <div class="hero-content">
                <h1>Search Roles</h1>
                <div class="hero-search">
                    <div class="hero-search-input">
                        <span class="material-symbols-outlined">search</span>
                        <input id="searchInput" type="text" placeholder="Job title, keywords, or company...">
                    </div>
                    <button id="searchBtn" class="search-btn">Search</button>
                </div>
            </div>
            <div class="hero-glow"></div>
        </div>
    </section>

    <section>
        <div class="section-head">
            <h2>Recent Listings</h2>
        </div>
        <div id="loader" class="loader-container" style="display: none;">
            <div class="spinner"></div>
            <p>Fetching the best jobs for you...</p>
        </div>
        <div id="jobsContainer" class="jobs-list"></div>
    </section>
</main>

<?php include_once 'footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>
</html>