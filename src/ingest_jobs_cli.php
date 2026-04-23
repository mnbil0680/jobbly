#!/usr/bin/env php
<?php
// **================================================**
// ** File: ingest_jobs_cli.php                       **
// ** Responsibility: Fetch and Bulk Insert Jobs      **
// ** - Fetch all 15 APIs via SourceFetcher           **
// ** - Collect all jobs into a single array          **
// ** - Bulk insert into database using JobsDatabase  **
// **================================================**

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SourceFetcher.php';
require_once __DIR__ . '/../app/DB_Ops.php';

// Color codes for terminal
class Color {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const GRAY = "\033[90m";
    const BOLD = "\033[1m";
}

echo "\n" . Color::BOLD . "=== Jobbly Bulk Ingestion Tool ===" . Color::RESET . "\n";
echo "Starting ingestion at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Setup Config
$config = [
    'RAPIDAPI_KEY' => defined('RAPIDAPI_KEY') ? RAPIDAPI_KEY : '',
    'ADZUNA_APP_ID' => defined('ADZUNA_APP_ID') ? ADZUNA_APP_ID : '',
    'ADZUNA_APP_KEY' => defined('ADZUNA_APP_KEY') ? ADZUNA_APP_KEY : '',
    'FINDWORK_API_KEY' => defined('FINDWORK_API_KEY') ? FINDWORK_API_KEY : '',
    'JOOBLE_API_KEY' => defined('JOOBLE_API_KEY') ? JOOBLE_API_KEY : '',
    'REED_API_KEY' => defined('REED_API_KEY') ? REED_API_KEY : '',
    'USAJOBS_API_KEY' => defined('USAJOBS_API_KEY') ? USAJOBS_API_KEY : ''
];

try {
    $fetcher = new SourceFetcher($config);
    $db = new JobsDatabase();
    
    $all_jobs_to_insert = [];
    $sources_summary = [];

    // 2. Fetch all jobs
    echo "Fetching jobs from " . count($fetcher->get_sources()) . " sources...\n";
    $response = $fetcher->fetch_all();

    foreach ($response['results'] as $result) {
        if ($result['status'] === 'ok') {
            $count = count($result['all_jobs'] ?? []);
            if ($count > 0) {
                echo "  - " . str_pad($result['name'], 20) . ": " . Color::GREEN . "$count jobs fetched" . Color::RESET . "\n";
                $all_jobs_to_insert = array_merge($all_jobs_to_insert, $result['all_jobs']);
            } else {
                echo "  - " . str_pad($result['name'], 20) . ": " . Color::GRAY . "0 jobs found" . Color::RESET . "\n";
            }
        } elseif ($result['status'] === 'skipped') {
            $reason = $result['reason'] ?? 'Skipped';
            echo "  - " . str_pad($result['name'], 20) . ": " . Color::YELLOW . "Skipped ($reason)" . Color::RESET . "\n";
        } else {
            $reason = $result['reason'] ?? 'Unknown error';
            echo "  - " . str_pad($result['name'], 20) . ": " . Color::RED . "Failed ($reason)" . Color::RESET . "\n";
        }
    }

    $total_collected = count($all_jobs_to_insert);
    echo "\nTotal jobs collected: " . Color::BOLD . $total_collected . Color::RESET . "\n";

    if ($total_collected === 0) {
        echo Color::YELLOW . "No jobs to insert. Exiting." . Color::RESET . "\n";
        exit(0);
    }

    // 3. Bulk Insert
    echo "Inserting jobs into database (batch mode)...\n";
    $affected_rows = $db->bulkInsertJobs($all_jobs_to_insert);

    // Note: mysqli::affected_rows for INSERT IGNORE returns the number of actually inserted rows.
    echo Color::GREEN . Color::BOLD . "SUCCESS: Ingestion complete!" . Color::RESET . "\n";
    echo "Jobs processed: " . $total_collected . "\n";
    echo "New jobs stored: " . $affected_rows . "\n"; // This might be approximate depending on driver behavior

    echo "\nDone at: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo Color::RED . "FATAL ERROR: " . $e->getMessage() . Color::RESET . "\n";
    exit(1);
}
?>
