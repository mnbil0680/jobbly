<?php
require_once 'header.php';
require_once '../src/SourceTester.php';
require_once '../config/config.php';

$config = [
    'RAPIDAPI_KEY' => defined('RAPIDAPI_KEY') ? RAPIDAPI_KEY : '',
    'ADZUNA_APP_ID' => defined('ADZUNA_APP_ID') ? ADZUNA_APP_ID : '',
    'ADZUNA_APP_KEY' => defined('ADZUNA_APP_KEY') ? ADZUNA_APP_KEY : '',
    'FINDWORK_API_KEY' => defined('FINDWORK_API_KEY') ? FINDWORK_API_KEY : '',
    'JOOBLE_API_KEY' => defined('JOOBLE_API_KEY') ? JOOBLE_API_KEY : '',
    'REED_API_KEY' => defined('REED_API_KEY') ? REED_API_KEY : '',
    'USAJOBS_API_KEY' => defined('USAJOBS_API_KEY') ? USAJOBS_API_KEY : ''
];

$tester = new SourceTester($config);
$results = $tester->test_all();

$passed = 0;
$failed = 0;
$skipped = 0;
$totalJobs = 0;

foreach ($results as $r) {
    if ($r['status'] === 'skipped') $skipped++;
    elseif ($r['status'] === 'ok') {
        $passed++;
        $totalJobs += ($r['count'] ?? 0);
    } else $failed++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Sources | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
</head>
<body class="bg-surface">
    <main class="main-layout">
        <div class="test-endpoints-header">
            <h1>🧪 Test Job Sources (PHP Mode)</h1>
            <p class="subtitle">Results generated on server-side</p>
            <div class="test-header-controls">
                <a href="test_sources.php" class="search-btn" style="text-decoration:none;">🔄 Rerun Tests</a>
            </div>
        </div>

        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $passed; ?></div>
                <div class="stat-label">✓ Working</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $failed; ?></div>
                <div class="stat-label">✗ Failed</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $skipped; ?></div>
                <div class="stat-label">⊘ Skipped</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $totalJobs; ?></div>
                <div class="stat-label">📊 Available Jobs</div>
            </div>
        </div>

        <div class="sources-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 40px;">
            <?php foreach ($results as $id => $res): ?>
                <div class="job-row" style="flex-direction: column; align-items: start; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; width: 100%;">
                        <strong style="font-size: 1.1rem; color: var(--primary);"><?php echo htmlspecialchars($id); ?></strong>
                        <span class="badge" style="<?php echo $res['status'] === 'ok' ? 'background:#dcfce7;color:#166534;' : ($res['status'] === 'skipped' ? 'background:#fef9c3;color:#854d0e;' : 'background:#fee2e2;color:#991b1b;'); ?>">
                            <?php echo strtoupper($res['status']); ?>
                        </span>
                    </div>
                    <?php if ($res['status'] === 'ok'): ?>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                            Found <strong><?php echo $res['count']; ?></strong> jobs in <?php echo $res['time']; ?>s
                        </p>
                    <?php else: ?>
                        <p style="margin: 0; font-size: 0.85rem; color: #f87171;">
                            <?php echo htmlspecialchars($res['message'] ?? 'Unknown error'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
