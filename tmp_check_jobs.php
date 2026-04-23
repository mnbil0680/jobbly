<?php
$c = new mysqli('localhost', 'root', 'mohamednabil@012', 'jobbly');
$r = $c->query('DESCRIBE jobs');
while($row = $r->fetch_assoc()) echo $row['Field'] . " (" . $row['Type'] . ")\n";
