<?php
// **================================================**
// ** File: config.example.php                       **
// ** Responsibility: Template for config.php        **
// ** - Copy this file and rename it to config.php   **
// ** - Fill in your own credentials                 **
// ** - DO NOT push config.php to GitHub             **
// **================================================**

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jobbly');

// ===== JOB SOURCE API KEYS =====
// Get your API keys from the links in 15_JOB_SOURCES.md

// RapidAPI JSearch (LinkedIn + Indeed + Glassdoor)
// Get key at: https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
define('RAPIDAPI_KEY', '');

// Adzuna Job Board
// Get ID and KEY at: https://developer.adzuna.com/
define('ADZUNA_APP_ID', '');
define('ADZUNA_APP_KEY', '');

// Findwork.dev (Software Dev Remote Jobs)
// Get key at: https://findwork.dev/developers/
define('FINDWORK_API_KEY', '');

// Jooble (Global Jobs)
// Get key at: https://jooble.org/api/about
define('JOOBLE_API_KEY', '');

// Reed.co.uk (UK Jobs)
// Get key at: https://www.reed.co.uk/developers
define('REED_API_KEY', '');

// USAJobs (US Government Remote IT)
// Note: USAJobs API is open, but can provide your email for tracking
define('USAJOBS_API_KEY', '');

// ===== NOTES =====
// - All API keys are OPTIONAL
// - Sources without keys are skipped during fetch
// - Free sources (Remotive, Himalayas, Jobicy, etc.) work without any keys
// - To test: php fetch_sources_cli.php
// - To use web endpoint: browse to /fetch_sources.php
?>
