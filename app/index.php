<?php

// **================================================**
// ** File: index.php                                **
// ** Responsibility: Main SPA page                  **
// ** - Include header.php                           **
// ** - Search / Filter Section                      **
// ** - Form Section (Create / Update)               **
// ** - Display Section (Read)                       **
// ** - Include footer.php                           **
// ** - Link style.css                               **
// ** - Link API_Ops.js                              **
// ** - Link main.js                                 **
// **================================================**

require_once 'DB_Ops.php';
$db = new JobsDatabase();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jobbly | Careers</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
</head>

<body>
<?php include 'header.php'; ?>

<main class="container">

    <!-- HOME PAGE  -->
    <div id="homePage">

        <!-- SEARCH -->
        <section class="search-section">
            <div class="search-card">

                <h1 class="search-title">Find Your Dream Opportunity!</h1>

                <div class="search-container">
                    <div class="input-group">
                        <span class="material-symbols-outlined">search</span>

                        <input type="text"
                               id="searchInput"
                               class="search-input"
                               onkeyup="searchJobs()"
                               placeholder="Job title or company...">
                    </div>

                    <button class="btn btn-primary search-btn"
                            onclick="searchJobs()">
                        Search
                    </button>
                </div>

            </div>
        </section>

        <!-- JOBS -->
        <section class="display-section">
            <h2>Recent Listings</h2>
            <div id="jobsContainer" class="jobs-container"></div>
        </section>

    </div>

    <!-- ABOUT SECTION -->
    <section id="aboutSection" class="about-section" style="display:none;">
        <div class="about-grid">
            <div class="about-card">
                <h3>About Jobbly</h3>
                <p>
                    Jobbly is a modern job search platform designed to simplify how people find career opportunities
                    in one place.
                </p>
            </div>
            <div class="about-card">
                <h3>What it helps you do</h3>
                <p>
                    You can search jobs instantly, browse structured listings, and save time by using a fast
                    single-page experience without reloads.
                </p>
            </div>
            <div class="about-card">
                <h3>Why Jobbly</h3>
                <p>
                    Instead of visiting multiple job sites, Jobbly brings everything together in one clean,
                    simple, and efficient interface.
                </p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php'; ?>

<script src="assets/js/main.js"></script>

</body>
</html>