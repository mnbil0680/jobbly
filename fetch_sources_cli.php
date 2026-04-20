#!/usr/bin/env php
<?php
// **================================================**
// ** File: fetch_sources_cli.php                     **
// ** Responsibility: CLI tool for testing sources    **
// ** - Load SourceFetcher and config                 **
// ** - Fetch and display results in terminal         **
// ** - Color-coded status output                     **
// ** - Usage: php fetch_sources_cli.php [options]    **
// **================================================**

// Load configuration
if (!file_exists(__DIR__ . '/config.php')) {
    error_log("ERROR: config.php not found. Please create it from config.example.php");
    exit(1);
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SourceFetcher.php';

// Color codes for terminal
class Color {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const GRAY = "\033[90m";
    const BOLD = "\033[1m";
}

// Parse CLI arguments
$options = parse_cli_args($argv);

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
    
    // Show help if requested
    if (isset($options['help'])) {
        show_help();
        exit(0);
    }
    
    // Fetch sources
    $filter = [];
    if (isset($options['source'])) {
        $filter['source_id'] = $options['source'];
    }
    
    $response = $fetcher->fetch_all($filter);
    
    // Display results
    display_results($response, $options);
    
} catch (Exception $e) {
    echo Color::RED . "ERROR: " . $e->getMessage() . Color::RESET . "\n";
    exit(1);
}

/**
 * Parse CLI arguments
 */
function parse_cli_args($argv) {
    $options = [];
    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif ($arg === '--source' && isset($argv[$i + 1])) {
            $options['source'] = $argv[++$i];
        } elseif ($arg === '--json') {
            $options['json'] = true;
        } elseif ($arg === '--free') {
            $options['free'] = true;
        } elseif ($arg === '--keyed') {
            $options['keyed'] = true;
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        }
    }
    return $options;
}

/**
 * Display formatted results in terminal
 */
function display_results($response, $options) {
    // JSON output if requested
    if (isset($options['json'])) {
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }
    
    echo "\n";
    echo Color::BOLD . "=== Job Sources Diagnostics ===" . Color::RESET . "\n";
    echo "Run at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Display results
    foreach ($response['results'] as $result) {
        $status_icon = get_status_icon($result['status']);
        $status_color = get_status_color($result['status']);
        
        // Build status string
        $status_str = str_pad($result['status'], 6);
        $http_str = $result['http_status'] ? str_pad($result['http_status'], 3, ' ', STR_PAD_LEFT) : '---';
        $latency_str = $result['latency_ms'] ? str_pad($result['latency_ms'] . 'ms', 7, ' ', STR_PAD_LEFT) : '---';
        $jobs_str = $result['job_count'] > 0 ? $result['job_count'] . ' jobs' : '0 jobs';
        
        // Build sample job string
        $sample_str = '';
        if ($result['sample_job']) {
            $title = $result['sample_job']['title'] ?? 'Unknown';
            $company = $result['sample_job']['company'] ?? 'Unknown';
            $sample_str = "  Sample: $title @ $company";
        }
        
        // Build reason string for non-ok status
        $reason_str = '';
        if ($result['status'] !== 'ok') {
            $reason_str = "  ({$result['reason']})";
        }
        
        // Format the line
        $name = str_pad($result['name'], 25);
        $line = sprintf(
            "%s %s %s [%-6s] %s %s %-8s %s%s",
            $status_icon,
            $name,
            $status_color . $status_str . Color::RESET,
            $http_str,
            $latency_str,
            $jobs_str,
            '',
            $sample_str,
            $reason_str
        );
        
        echo $line . "\n";
        
        // Show error if verbose and failed
        if (isset($options['verbose']) && $result['status'] === 'failed' && $result['error']) {
            echo Color::GRAY . "    Error: " . $result['error'] . Color::RESET . "\n";
        }
    }
    
    // Summary
    echo "\n" . Color::BOLD . "Summary:" . Color::RESET . "\n";
    echo Color::GREEN . "✓ OK: {$response['summary']['ok']}" . Color::RESET;
    echo " | ";
    echo Color::YELLOW . "⊘ Skipped: {$response['summary']['skipped']}" . Color::RESET;
    echo " | ";
    echo Color::RED . "✗ Failed: {$response['summary']['failed']}" . Color::RESET;
    echo "\n\n";
    
    // Show skipped reasons if any
    if (!empty($response['summary']['skipped_reasons'])) {
        echo Color::YELLOW . "Skipped Reasons:" . Color::RESET . "\n";
        foreach ($response['summary']['skipped_reasons'] as $reason) {
            echo "  - $reason\n";
        }
        echo "\n";
    }
    
    // Show failed reasons if any
    if (!empty($response['summary']['failed_reasons'])) {
        echo Color::RED . "Failed Reasons:" . Color::RESET . "\n";
        foreach ($response['summary']['failed_reasons'] as $reason) {
            echo "  - $reason\n";
        }
        echo "\n";
    }
}

/**
 * Get status icon (✓, ✗, ⊘)
 */
function get_status_icon($status) {
    switch ($status) {
        case 'ok':
            return Color::GREEN . "✓" . Color::RESET;
        case 'failed':
            return Color::RED . "✗" . Color::RESET;
        case 'skipped':
            return Color::YELLOW . "⊘" . Color::RESET;
        default:
            return "?";
    }
}

/**
 * Get status color code
 */
function get_status_color($status) {
    switch ($status) {
        case 'ok':
            return Color::GREEN;
        case 'failed':
            return Color::RED;
        case 'skipped':
            return Color::YELLOW;
        default:
            return Color::RESET;
    }
}

/**
 * Show help message
 */
function show_help() {
    echo "\n";
    echo Color::BOLD . "Job Sources CLI Checker" . Color::RESET . "\n";
    echo "Usage: php fetch_sources_cli.php [options]\n\n";
    echo "Options:\n";
    echo "  --help, -h           Show this help message\n";
    echo "  --source <id>        Fetch a single source by ID\n";
    echo "  --json               Output as JSON instead of formatted table\n";
    echo "  --verbose, -v        Show error details for failed sources\n";
    echo "  --free               Only show sources without API keys\n";
    echo "  --keyed              Only show sources with API keys\n";
    echo "\n";
    echo "Examples:\n";
    echo "  php fetch_sources_cli.php                  # Fetch all sources\n";
    echo "  php fetch_sources_cli.php --source remotive\n";
    echo "  php fetch_sources_cli.php --verbose\n";
    echo "  php fetch_sources_cli.php --json\n";
    echo "\n";
}
?>
