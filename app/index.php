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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobbly - Job Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/test_endpoints.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <!-- Jobs Page -->
        <div id="jobsPage" class="jobs-page">
            <!-- Search & Filter Section -->
            <section class="search-section">
                <h2>Search Jobs</h2>
                <div class="search-controls">
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="search-input" 
                        placeholder="Search by title, company, or description..."
                    >
                    <button class="btn btn-primary" onclick="searchJobs()">Search</button>
                    <button class="btn btn-secondary" onclick="resetSearch()">Reset</button>
                </div>
            </section>

            <!-- Form Section (Create / Update) -->
            <section class="form-section">
                <h2 id="formTitle">Add New Job</h2>
                <form id="jobForm" onsubmit="submitJobForm(event)">
                    <input type="hidden" id="jobId" value="">
                    
                    <div class="form-group">
                        <label for="jobTitle">Job Title:</label>
                        <input type="text" id="jobTitle" name="jobTitle" required>
                    </div>

                    <div class="form-group">
                        <label for="company">Company:</label>
                        <input type="text" id="company" name="company" required>
                    </div>

                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" required>
                    </div>

                    <div class="form-group">
                        <label for="jobType">Job Type:</label>
                        <select id="jobType" name="jobType" required>
                            <option value="">Select a type</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="salary">Salary (optional):</label>
                        <input type="text" id="salary" name="salary" placeholder="e.g., $50,000 - $70,000">
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn btn-success" id="submitBtn">Add Job</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Clear</button>
                    </div>
                </form>
            </section>

            <!-- Display Section (Read) -->
            <section class="display-section">
                <h2>Jobs List</h2>
                <div id="jobsContainer" class="jobs-container">
                    <p class="loading">Loading jobs...</p>
                </div>
            </section>
        </div>

        <!-- Test Endpoints Page (Included from HTML file) -->
        <?php include 'pages/test_endpoints.html'; ?>

        <!-- Test Details Page (Included from HTML file) -->
        <?php include 'pages/test_endpoint_details.html'; ?>
    </main>

    <?php include 'footer.php'; ?>

    <script src="assets/js/API_Ops.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/test_endpoints.js"></script>
    <script src="assets/js/test_endpoint_details.js"></script>
</body>
</html>
