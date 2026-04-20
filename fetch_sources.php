<?php
// **================================================**
// ** File: fetch_sources.php                         **
// ** Responsibility: Web endpoint for fetching jobs  **
// ** - Load SourceFetcher and config                 **
// ** - Fetch all or filtered sources                 **
// ** - Return JSON diagnostic response               **
// ** - Handle errors gracefully                      **
// **================================================**

// Load configuration
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    die(json_encode(['error' => 'config.php not found. Please run setup first.']));
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SourceFetcher.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

// Build config array from constants/variables
$config = [
    'RAPIDAPI_KEY' => defined('RAPIDAPI_KEY') ? RAPIDAPI_KEY : ($RAPIDAPI_KEY ?? ''),
    'ADZUNA_APP_ID' => defined('ADZUNA_APP_ID') ? ADZUNA_APP_ID : ($ADZUNA_APP_ID ?? ''),
    'ADZUNA_APP_KEY' => defined('ADZUNA_APP_KEY') ? ADZUNA_APP_KEY : ($ADZUNA_APP_KEY ?? ''),
    'FINDWORK_API_KEY' => defined('FINDWORK_API_KEY') ? FINDWORK_API_KEY : ($FINDWORK_API_KEY ?? ''),
    'JOOBLE_API_KEY' => defined('JOOBLE_API_KEY') ? JOOBLE_API_KEY : ($JOOBLE_API_KEY ?? ''),
    'REED_API_KEY' => defined('REED_API_KEY') ? REED_API_KEY : ($REED_API_KEY ?? ''),
    'USAJOBS_API_KEY' => defined('USAJOBS_API_KEY') ? USAJOBS_API_KEY : ($USAJOBS_API_KEY ?? '')
];

try {
    $fetcher = new SourceFetcher($config);
    
    // Parse query parameters
    $filter = [];
    if (!empty($_GET['source'])) {
        $filter['source_id'] = sanitize_input($_GET['source']);
    }
    
    // Fetch all sources
    $response = $fetcher->fetch_all($filter);
    
    // Add metadata
    $response['php_version'] = phpversion();
    $response['timestamp'] = date('c');
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'type' => 'exception',
        'timestamp' => date('c')
    ]);
}

/**
 * Sanitize input to prevent injection
 */
function sanitize_input($input) {
    return preg_replace('/[^a-z0-9_-]/', '', strtolower($input));
}
?>
