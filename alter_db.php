<?php
$c = new mysqli('localhost', 'root', 'mohamednabil@012', 'jobbly');
if ($c->connect_error) die($c->connect_error);

$queries = [
    "ALTER TABLE users ADD COLUMN email VARCHAR(191) UNIQUE AFTER name",
    "ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER email",
    "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) AFTER details",
    "ALTER TABLE users ADD COLUMN cv_path VARCHAR(255) AFTER profile_photo"
];

foreach ($queries as $q) {
    if ($c->query($q)) {
        echo "SUCCESS: $q\n";
    } else {
        echo "ERROR: $q -> " . $c->error . "\n";
    }
}
