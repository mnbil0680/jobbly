<?php
require_once __DIR__ . '/config/config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE title = 'Unknown'");
$row = $res->fetch_assoc();
echo "Unknown Titles: " . $row['c'] . "\n";

$res = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE company_name = 'Unknown'");
$row = $res->fetch_assoc();
echo "Unknown Companies: " . $row['c'] . "\n";

$res = $conn->query("SELECT COUNT(*) as c FROM jobs WHERE description LIKE '%figma-padding%'");
$row = $res->fetch_assoc();
echo "Figma Padding Descriptions: " . $row['c'] . "\n";
