<?php
$c = new mysqli('localhost', 'root', 'mohamednabil@012', 'jobbly');
if ($c->connect_error) die($c->connect_error);

$queries = [
    "ALTER TABLE jobs MODIFY COLUMN job_type VARCHAR(100)",
    "ALTER TABLE jobs MODIFY COLUMN status VARCHAR(50) DEFAULT 'open'",
    "ALTER TABLE jobs MODIFY COLUMN poster_id VARCHAR(255)",
    "ALTER TABLE jobs MODIFY COLUMN company_name VARCHAR(255) DEFAULT 'Unknown'"
];

foreach ($queries as $q) {
    if ($c->query($q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: $q -> " . $c->error . "\n";
    }
}
