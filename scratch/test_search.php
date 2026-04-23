<?php
require_once __DIR__ . '/../app/DB_Ops.php';

try {
    $db = new JobsDatabase();
    $search = "Developer";
    echo "Searching for: $search\n";
    $count = $db->getTotalJobsCount($search);
    echo "Total found: $count\n";
    
    $jobs = $db->getAllJobs($search, 5, 0);
    echo "First 5 jobs:\n";
    foreach ($jobs as $job) {
        echo " - " . $job['title'] . " (" . $job['company_name'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
