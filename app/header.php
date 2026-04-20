<?php
// **================================================**
// ** File: header.php                               **
// ** Responsibility: Site header                    **
// ** - Add logo / app name                          **
// ** - Add navigation links                         **
// **================================================**
?>
<header class="header">
    <div class="header-container">
        <div class="logo-section">
            <h1 class="logo">💼 Jobbly</h1>
            <p class="tagline">Smart Job Management System</p>
        </div>
        <nav class="nav">
            <ul>
                <li><a href="#" onclick="showJobsPage()">Jobs</a></li>
                <li><a href="#" onclick="showTestEndpoints()">Test Endpoints</a></li>
                <li><a href="#" onclick="loadPage('about')">About</a></li>
                <li><a href="../src/fetch_sources.php" target="_blank">API Endpoint</a></li>
            </ul>
        </nav>
    </div>
</header>
