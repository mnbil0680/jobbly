<?php
// **================================================**
// ** File: test_source.php                          **
// ** Responsibility: Web endpoint for source testing**
// ** - Test single or multiple sources              **
// ** - Return comprehensive JSON results            **
// ** - Support query parameters                     **
// **================================================**

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SourceTester.php';

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Get parameters
$source = isset($_GET['source']) ? $_GET['source'] : null;
$sources = isset($_GET['sources']) ? explode(',', $_GET['sources']) : [];
$all = isset($_GET['all']) && $_GET['all'] === 'true';
$verbose = isset($_GET['verbose']) && $_GET['verbose'] === 'true';

// Validate parameters
if (!$source && empty($sources) && !$all) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid parameters',
        'message' => 'Provide either ?source=ID, ?sources=ID1,ID2, or ?all=true',
        'example' => 'test_source.php?source=remotive or test_source.php?all=true'
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    $tester = new SourceTester($GLOBALS['config'] ?? []);

    // Test single source
    if ($source) {
        $result = $tester->test_source($source);
        
        // Check if source not found
        if (is_null($result['source'])) {
            http_response_code(404);
        }
        
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    // Test multiple sources
    elseif (!empty($sources)) {
        // Trim whitespace from each source ID
        $sources = array_map('trim', $sources);
        $result = $tester->test_all($sources);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    // Test all sources
    elseif ($all) {
        $result = $tester->test_all();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
