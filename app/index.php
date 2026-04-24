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

$view = $_GET['view'] ?? 'explore';
$isLoggedIn = !empty($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
?>

<?php
// Jobs Logic
if ($view === 'explore') {
    $search = trim($_GET['search'] ?? '');
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $totalJobs = $db->getTotalJobsCount($search);
    $totalPages = ceil($totalJobs / $limit);

    if ($page > $totalPages && $totalPages > 0) {
        header("Location: index.php?view=explore&page=1&search=" . urlencode($search));
        exit;
    }

    $rows = $db->getAllJobs($search, $limit, $offset);
}
// Saved Jobs Logic
else if ($view === 'saved' && $isLoggedIn) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $savedJobs = $db->getUserSavedJobs($userId, $limit, $offset);
    $totalSaved = $db->getUserSavedJobsCount($userId);
    $totalPages = ceil($totalSaved / $limit);
}
// Redirect to login if trying to access saved without login
else if ($view === 'saved') {
    header("Location: login.php?redirect=index.php?view=saved");
    exit;
}
?>

<main class="main-layout">
    <?php if ($view === 'explore'): ?>
        <!-- Explore Section -->
        <section class="hero-section">
            <div class="hero-card">
                <div class="hero-content">
                    <h1>Search Roles</h1>
                    <form action="index.php?view=explore" method="GET" class="hero-search">
                        <input type="hidden" name="view" value="explore">
                        <div class="hero-search-input">
                            <span class="material-symbols-outlined">search</span>
                            <input name="search" type="text" placeholder="Job title, keywords, or company..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                </div>
                <div class="hero-glow"></div>
            </div>
        </section>

        <section>
            <div class="section-head">
                <h2>Recent Listings (<?php echo $totalJobs; ?>)</h2>
            </div>

            <div id="jobsContainer" class="jobs-list">
                <?php if (empty($rows)): ?>
                    <div class="empty-state">
                        <h3>No jobs found</h3>
                        <p>Try searching with a different keyword or seeding the database.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rows as $job):
                        $companyName = $job['company_name'] ?? 'U';
                        $companyLogo = strtoupper(function_exists('mb_substr') ? mb_substr($companyName, 0, 1) : substr($companyName, 0, 1));
                        $isSaved = $isLoggedIn ? $db->isJobSaved($userId, $job['id']) : false;

                        $salary = 'N/A';
                        if (($job['salary_min'] ?? 0) > 0 || ($job['salary_max'] ?? 0) > 0) {
                            $currency = $job['currency'] ?? 'USD';
                            $salary = $currency . " " . number_format($job['salary_min']) . " - " . number_format($job['salary_max']);
                        }
                        ?>
                        <article class="job-row group" data-job-id="<?php echo $job['id']; ?>">
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
                                <?php if ($isLoggedIn): ?>
                                    <button class="save-btn <?php echo $isSaved ? 'saved' : ''; ?>" onclick="toggleSavePost(<?php echo $job['id']; ?>, this)">
                                        <span class="material-symbols-outlined">favorite</span>
                                    </button>
                                <?php else: ?>
                                    <button class="save-btn" onclick="showLoginModal()" style="opacity: 0.5; cursor: not-allowed;">
                                        <span class="material-symbols-outlined">favorite</span>
                                    </button>
                                <?php endif; ?>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="view-btn">View</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php?view=explore&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-item">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    $show_items = [];
                    for ($i = 1; $i <= $totalPages; $i++) {
                        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                            $show_items[] = $i;
                        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                            $show_items[] = '...';
                        }
                    }
                    $show_items = array_values(array_unique($show_items));

                    foreach ($show_items as $item):
                        if ($item === '...'): ?>
                            <span class="pagination-dot">...</span>
                        <?php else: ?>
                            <a href="index.php?view=explore&page=<?php echo $item; ?>&search=<?php echo urlencode($search); ?>"
                               class="pagination-item <?php echo ($page == $item) ? 'active' : ''; ?>">
                                <?php echo $item; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?view=explore&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="pagination-item">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php elseif ($view === 'saved'): ?>
        <!-- Saved Jobs Section -->
        <section>
            <div class="section-head">
                <h2>Saved Jobs</h2>
            </div>

            <div id="savedJobsContainer" class="jobs-list">
                <?php if (empty($savedJobs)): ?>
                    <div class="empty-state">
                        <span class="material-symbols-outlined" style="font-size: 64px; color: var(--text-muted); margin-bottom: 20px;">favorite</span>
                        <h3>No saved jobs yet</h3>
                        <p>Save jobs by clicking the heart icon on job listings.</p>
                        <a href="index.php?view=explore" class="btn-primary" style="margin-top: 20px; padding: 12px 24px; display: inline-block;">
                            Start Exploring
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($savedJobs as $job):
                        $companyLogo = strtoupper(substr($job['company_name'] ?? 'U', 0, 1));

                        $salary = 'N/A';
                        if (($job['salary_min'] ?? 0) > 0 || ($job['salary_max'] ?? 0) > 0) {
                            $currency = $job['currency'] ?? 'USD';
                            $salary = $currency . " " . number_format($job['salary_min']) . " - " . number_format($job['salary_max']);
                        }
                        ?>
                        <article class="job-row group" data-job-id="<?php echo $job['id']; ?>">
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
                                <button class="save-btn saved" onclick="toggleSavePost(<?php echo $job['id']; ?>, this)">
                                    <span class="material-symbols-outlined">favorite</span>
                                </button>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="view-btn">View</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php?view=saved&page=<?php echo $page - 1; ?>" class="pagination-item">
                            <span class="material-symbols-outlined">chevron_left</span>
                        </a>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    $show_items = [];
                    for ($i = 1; $i <= $totalPages; $i++) {
                        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                            $show_items[] = $i;
                        } elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                            $show_items[] = '...';
                        }
                    }
                    $show_items = array_values(array_unique($show_items));

                    foreach ($show_items as $item):
                        if ($item === '...'): ?>
                            <span class="pagination-dot">...</span>
                        <?php else: ?>
                            <a href="index.php?view=saved&page=<?php echo $item; ?>"
                               class="pagination-item <?php echo ($page == $item) ? 'active' : ''; ?>">
                                <?php echo $item; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?view=saved&page=<?php echo $page + 1; ?>" class="pagination-item">
                            <span class="material-symbols-outlined">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php include_once 'footer.php'; ?>

<script>
    const currentUserId = <?php echo json_encode($userId); ?>;
    const currentView = <?php echo json_encode($view); ?>;
</script>
<script src="assets/js/main.js"></script>

</body>
</html>
