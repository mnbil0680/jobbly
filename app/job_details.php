<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details | Jobbly</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
<?php
require_once 'DB_Ops.php';
require_once 'header.php';

$jobId = $_GET['id'] ?? null;
if (!$jobId) {
    header('Location: index.php');
    exit;
}

$db = new JobsDatabase();
$job = $db->getJobById($jobId);

if (!$job) {
    echo "<main class='main-layout'><div class='empty-state'><h3>Job not found</h3><p>The job you are looking for may have been removed.</p><a href='index.php' class='search-btn'>Back to Search</a></div></main>";
    include_once 'footer.php';
    exit;
}

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

// Ensure apply_url exists (fallback to external_id or source link if needed)
$applyUrl = $job['apply_url'] ?? '#';
?>

<main class="main-layout">
    <section class="job-details-hero">
        <div class="job-row" style="background: none; border: none; padding: 0; box-shadow: none;">
            <div class="job-main">
                <div class="job-logo" style="width: 80px; height: 80px; font-size: 2rem;"><?php echo $companyLogo; ?></div>
                <div>
                    <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <div class="job-meta">
                        <span><span class="material-symbols-outlined">business</span><?php echo htmlspecialchars($job['company_name']); ?></span>
                        <span><span class="material-symbols-outlined">location_on</span><?php echo htmlspecialchars($job['location'] ?: 'Remote'); ?></span>
                        <span><span class="material-symbols-outlined">work</span><?php echo htmlspecialchars($job['job_type'] ?: 'N/A'); ?></span>
                        <span><span class="material-symbols-outlined">payments</span><?php echo $salary; ?></span>
                    </div>
                </div>
            </div>
            <div class="job-side">
                <button class="save-btn <?php echo $isSaved ? 'saved' : ''; ?>" onclick="toggleSavePost(<?php echo $job['id']; ?>, this)">
                    <span class="material-symbols-outlined">favorite</span>
                </button>
                <a href="<?php echo htmlspecialchars($applyUrl); ?>" target="_blank" class="search-btn">Apply Now</a>
            </div>
        </div>
    </section>

    <section class="job-description-section" style="margin-top: 40px;">
        <div class="job-row" style="flex-direction: column; align-items: start; gap: 20px;">
            <h2 style="color: var(--primary);">Job Description</h2>
            <div class="job-description-content" style="line-height: 1.8; color: var(--text-muted); width: 100%;">
                <?php 
                // Detect if description is HTML or plain text
                if (strip_tags($job['description']) !== $job['description']) {
                    echo $job['description']; // It has HTML tags, render as is
                } else {
                    echo nl2br(htmlspecialchars($job['description']));
                }
                ?>
            </div>
        </div>
    </section>
</main>

<?php include_once 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
