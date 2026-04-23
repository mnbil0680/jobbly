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

<?php 
include_once 'header.php'; 
require_once 'DB_Ops.php';

$db = new JobsDatabase();
$search = trim($_GET['search'] ?? '');
$rows = $db->getAllJobs($search);
?>

<main class="main-layout">
    <section class="hero-section">
        <div class="hero-card">
            <div class="hero-content">
                <h1>Search Roles</h1>
                <form action="index.php" method="GET" class="hero-search">
                    <div class="hero-search-input">
                        <span class="material-symbols-outlined">search</span>
                        <input name="search" type="text" placeholder="Job title, keywords, or company..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
            <div class="hero-glow"></div>
        </div>
    </section>

    <section>
        <div class="section-head">
            <h2>Recent Listings</h2>
        </div>
        
        <div id="jobsContainer" class="jobs-list">
            <?php if (empty($rows)): ?>
                <div class="empty-state">
                    <h3>No jobs found</h3>
                    <p>Try searching with a different keyword or seeding the database.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rows as $job): 
                    $companyLogo = strtoupper(substr($job['company_name'] ?? 'U', 0, 1));
                    $isSaved = false;
                    if (!empty($_SESSION['user_id'])) {
                        $isSaved = $db->isJobSaved($_SESSION['user_id'], $job['id']);
                    }
                    
                    $salary = 'N/A';
                    if (($job['salary_min'] ?? 0) > 0 || ($job['salary_max'] ?? 0) > 0) {
                        $currency = $job['currency'] ?? 'USD';
                        $salary = $currency . " " . number_format($job['salary_min']) . " - " . number_format($job['salary_max']);
                    }
                ?>
                    <article class="job-row group">
                        <div class="job-main">
                            <div class="job-logo"><?php echo $companyLogo; ?></div>
                            <div>
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="job-meta">
                                    <span><span class="material-symbols-outlined">business</span><?php echo htmlspecialchars($job['company_name']); ?></span>
                                    <span><span class="material-symbols-outlined">location_on</span><?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></span>
                                    <span><span class="material-symbols-outlined">work</span><?php echo htmlspecialchars($job['job_type'] ?: 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="job-side">
                            <span class="badge"><?php echo $salary; ?></span>
                            <button class="save-btn <?php echo $isSaved ? 'saved' : ''; ?>" onclick="toggleSavePost(<?php echo $job['id']; ?>, this)">
                                <span class="material-symbols-outlined">favorite</span>
                            </button>
                            <button class="view-btn" type="button">View</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once 'footer.php'; ?>

<script src="assets/js/main.js"></script>

</body>
</html>