<?php
// **================================================**
// ** File: config.php                               **
// ** Responsibility: App configuration              **
// ** - Database host, username, password, dbname    **
// ** - API Key for Third-Party API                  **
// ** مهم  DO NOT push this file to GitHub            **
// **================================================**

// Suppress errors for production (Requirement: Good Practices)
error_reporting(0);
ini_set('display_errors', 0);

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'mohamednabil@012');
define('DB_NAME', 'jobbly');

// ===== JOB SOURCE API KEYS =====
define('RAPIDAPI_KEY', '8b25d18763msh5cf770a9987a37dp198e55jsnded39430bea8');
define('ADZUNA_APP_ID', 'd2b4bb6f');
define('ADZUNA_APP_KEY', '08997fad37c2f70cd9d1045501d9f8db');
define('FINDWORK_API_KEY', 'bdb5bbd1e656867634ed0daa78ae0b6565741093');
define('JOOBLE_API_KEY', '0901cd88-5d52-4741-87a1-606ddf4134d0');
define('REED_API_KEY', 'ad37d3b6-3ce0-41ec-a9b6-815cfb7d13de');
define('USAJOBS_API_KEY', '915/GdtPIvFqvlv/uk5euzFxrfpXqQoeAxBamemEGxc=');

// Global config map consumed by test_source.php / test_sources_cli.php
$GLOBALS['config'] = [
	'RAPIDAPI_KEY' => RAPIDAPI_KEY,
	'ADZUNA_APP_ID' => ADZUNA_APP_ID,
	'ADZUNA_APP_KEY' => ADZUNA_APP_KEY,
	'FINDWORK_API_KEY' => FINDWORK_API_KEY,
	'JOOBLE_API_KEY' => JOOBLE_API_KEY,
	'REED_API_KEY' => REED_API_KEY,
	'USAJOBS_API_KEY' => '915/GdtPIvFqvlv/uk5euzFxrfpXqQoeAxBamemEGxc=',
];
?>