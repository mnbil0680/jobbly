<?php
// **================================================**
// ** File: config.example.php                       **
// ** Template — copy to config.php and fill in.     **
// ** config.php is gitignored; never commit it.     **
// **================================================**

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jobbly');

// ===== JOB SOURCE API KEYS =====
define('RAPIDAPI_KEY', '');
define('ADZUNA_APP_ID', '');
define('ADZUNA_APP_KEY', '');
define('FINDWORK_API_KEY', '');
define('JOOBLE_API_KEY', '');
define('REED_API_KEY', '');
define('USAJOBS_API_KEY', '');

$GLOBALS['config'] = [
	'RAPIDAPI_KEY' => RAPIDAPI_KEY,
	'ADZUNA_APP_ID' => ADZUNA_APP_ID,
	'ADZUNA_APP_KEY' => ADZUNA_APP_KEY,
	'FINDWORK_API_KEY' => FINDWORK_API_KEY,
	'JOOBLE_API_KEY' => JOOBLE_API_KEY,
	'REED_API_KEY' => REED_API_KEY,
	'USAJOBS_API_KEY' => USAJOBS_API_KEY,
];
?>
