<?php require __DIR__ . '/../app/DB_Ops.php';
try {
    $db = new JobsDatabase();
    $jobs = $db->getAllJobs('');
    echo "OK jobs=" . count($jobs) . PHP_EOL;
    print_r(array_slice($jobs, 0, 2));
} catch (Throwable $e) {
    echo "ERR " . $e->getMessage() . PHP_EOL;
}
