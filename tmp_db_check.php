<?php
$c = new mysqli('localhost', 'root', 'mohamednabil@012', 'jobbly');
if ($c->connect_error) die($c->connect_error);
echo "TABLES:\n";
$r = $c->query('SHOW TABLES');
while($row = $r->fetch_array()) echo $row[0] . "\n";
echo "\nUSERS COLS:\n";
$r = $c->query('DESCRIBE users');
while($row = $r->fetch_assoc()) echo $row['Field'] . " (" . $row['Type'] . ")\n";
