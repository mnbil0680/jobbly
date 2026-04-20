<?php
// **================================================**
// ** File: test_sources_cli.php                     **
// ** Responsibility: CLI tool for source testing    **
// ** - Interactive and batch testing modes          **
// ** - Comprehensive output with metrics            **
// ** - JSON export support                          **
// **================================================**

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/SourceTester.php';

class TestSourcesCLI {
    private $tester;
    private $config;
    private $output_json = false;
    private $verbose = false;

    public function __construct($config = []) {
        $this->config = $config;
        $this->tester = new SourceTester($config);
    }

    /**
     * Parse command line arguments
     */
    public function parse_args($argv) {
        $options = [
            'source' => null,
            'sources' => [],
            'all' => false,
            'interactive' => false,
            'json' => false,
            'verbose' => false
        ];

        for ($i = 1; $i < count($argv); $i++) {
            $arg = $argv[$i];

            if ($arg === '--source' && isset($argv[$i + 1])) {
                $options['source'] = $argv[++$i];
            } elseif ($arg === '--sources' && isset($argv[$i + 1])) {
                $options['sources'] = array_map('trim', explode(',', $argv[++$i]));
            } elseif ($arg === '--all') {
                $options['all'] = true;
            } elseif ($arg === '--interactive') {
                $options['interactive'] = true;
            } elseif ($arg === '--json') {
                $options['json'] = true;
                $this->output_json = true;
            } elseif ($arg === '--verbose') {
                $options['verbose'] = true;
                $this->verbose = true;
            } elseif ($arg === '--help' || $arg === '-h') {
                $this->show_help();
                exit(0);
            }
        }

        return $options;
    }

    /**
     * Show help message
     */
    private function show_help() {
        $help = <<<'HELP'
Job Sources Test CLI Tool

USAGE:
    php test_sources_cli.php [OPTIONS]

OPTIONS:
    --source SOURCE_ID      Test a single source (e.g., remotive)
    --sources ID1,ID2,ID3   Test multiple sources (comma-separated)
    --all                   Test all enabled sources
    --interactive           Interactive mode (select from menu)
    --json                  Output as JSON (for piping to other tools)
    --verbose               Show detailed information
    --help, -h             Show this help message

EXAMPLES:
    # Test single source
    php test_sources_cli.php --source remotive

    # Test multiple sources
    php test_sources_cli.php --sources remotive,jobicy,themuse

    # Test all sources
    php test_sources_cli.php --all

    # Interactive selection
    php test_sources_cli.php --interactive

    # Get JSON output
    php test_sources_cli.php --all --json

    # Pipe to jq for filtering
    php test_sources_cli.php --all --json | jq '.results[] | select(.errors.has_errors == false)'

    # Save results to file
    php test_sources_cli.php --all --json > test_results.json

    # Verbose mode with pretty colors
    php test_sources_cli.php --source remotive --verbose

HELP;
        echo $help;
    }

    /**
     * Run the CLI tool
     */
    public function run($argv) {
        $options = $this->parse_args($argv);

        // No options provided - show interactive menu
        if (!$options['source'] && empty($options['sources']) && !$options['all'] && !$options['interactive']) {
            $this->show_interactive_menu();
            return;
        }

        // Interactive mode
        if ($options['interactive']) {
            $this->show_interactive_menu();
            return;
        }

        // Test single source
        if ($options['source']) {
            $result = $this->tester->test_source($options['source']);
            $this->display_result($result);
            return;
        }

        // Test multiple sources
        if (!empty($options['sources'])) {
            $result = $this->tester->test_all($options['sources']);
            $this->display_results($result);
            return;
        }

        // Test all sources
        if ($options['all']) {
            $result = $this->tester->test_all();
            $this->display_results($result);
            return;
        }
    }

    /**
     * Show interactive menu
     */
    private function show_interactive_menu() {
        $this->clear_screen();
        $sources = $this->tester->get_sources();

        echo "\n";
        echo $this->color("╔════════════════════════════════════╗\n", 'blue');
        echo $this->color("║   Job Sources Interactive Tester    ║\n", 'blue');
        echo $this->color("╚════════════════════════════════════╝\n\n", 'blue');

        echo "Available sources:\n\n";

        $i = 1;
        $source_map = [];
        foreach ($sources as $source) {
            if ($source['enabled']) {
                $status = empty($source['required_keys']) ? 'Free' : 'Keyed';
                printf("  %2d. %-25s [%s] %s\n", 
                    $i, 
                    $source['name'], 
                    $status,
                    $source['description'] ?? ''
                );
                $source_map[$i] = $source['id'];
                $i++;
            }
        }

        echo "\n";
        echo "  a. Test all sources\n";
        echo "  q. Quit\n";
        echo "\n";

        echo "Select source number(s) (comma-separated) or action: ";
        $input = trim(fgets(STDIN));

        if (strtolower($input) === 'q') {
            echo "\nGoodbye!\n\n";
            exit(0);
        }

        if (strtolower($input) === 'a') {
            $this->clear_screen();
            echo $this->color("\n▶ Testing all sources...\n\n", 'cyan');
            $result = $this->tester->test_all();
            $this->display_results($result);
            return;
        }

        // Parse selected sources
        $selected_ids = [];
        $selections = explode(',', $input);
        
        foreach ($selections as $sel) {
            $sel = trim($sel);
            if (isset($source_map[$sel])) {
                $selected_ids[] = $source_map[$sel];
            }
        }

        if (empty($selected_ids)) {
            echo $this->color("Invalid selection. Please try again.\n", 'red');
            sleep(2);
            $this->show_interactive_menu();
            return;
        }

        $this->clear_screen();
        echo $this->color("\n▶ Testing " . count($selected_ids) . " source(s)...\n\n", 'cyan');
        $result = $this->tester->test_all($selected_ids);
        $this->display_results($result);
    }

    /**
     * Display single test result
     */
    private function display_result($result) {
        if ($this->output_json) {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            return;
        }

        echo "\n";
        echo $this->color("═══════════════════════════════════════════════════════════\n", 'blue');
        echo $this->color(sprintf("  Test: %s\n", $result['source']['name']), 'blue');
        echo $this->color("═══════════════════════════════════════════════════════════\n\n", 'blue');

        // Show errors if any
        if ($result['errors']['has_errors']) {
            echo $this->color("✘ ERRORS:\n", 'red');
            foreach ($result['errors']['messages'] as $msg) {
                echo "  • " . $msg . "\n";
            }
            echo "\n";
            return;
        }

        // Show HTTP Request
        echo $this->color("▶ HTTP REQUEST\n", 'cyan');
        echo "  Method: " . $result['http_request']['method'] . "\n";
        echo "  URL: " . $result['http_request']['full_url'] . "\n";
        if (!empty($result['http_request']['query_params'])) {
            echo "  Query Params:\n";
            foreach ($result['http_request']['query_params'] as $k => $v) {
                $display_v = (strlen($v) > 50) ? substr($v, 0, 47) . '...' : $v;
                echo "    • $k = $display_v\n";
            }
        }
        if (!empty($result['http_request']['headers'])) {
            echo "  Headers:\n";
            foreach ($result['http_request']['headers'] as $k => $v) {
                $display_v = (strlen($v) > 50) ? substr($v, 0, 47) . '...' : $v;
                echo "    • $k = $display_v\n";
            }
        }
        echo "\n";

        // Show HTTP Response
        echo $this->color("▶ HTTP RESPONSE\n", 'cyan');
        echo "  Status: " . $this->color($result['http_response']['status_code'], $result['http_response']['status_code'] === 200 ? 'green' : 'red') . " " . $result['http_response']['status_text'] . "\n";
        echo "  Size: " . $this->format_bytes($result['http_response']['body_size_bytes']) . "\n";
        echo "  Content-Type: " . $result['http_response']['content_type'] . "\n";
        
        if ($this->verbose && !empty($result['http_response']['body_preview'])) {
            echo "  Body Preview:\n";
            $preview = substr($result['http_response']['body_preview'], 0, 500);
            echo "    " . str_replace("\n", "\n    ", $preview) . "\n";
            if ($result['http_response']['body_truncated']) {
                echo "    [... truncated ...]\n";
            }
        }
        echo "\n";

        // Show Parsing
        echo $this->color("▶ PARSING RESULTS\n", 'cyan');
        echo "  Format: " . $result['parsing']['detected_format'] . "\n";
        echo "  Job Path: " . $result['parsing']['job_path'] . "\n";
        echo "  Jobs Found: " . $this->color($result['parsing']['jobs_found'], 'green') . "\n";
        
        if (!empty($result['parsing']['sample_job'])) {
            echo "  Sample Job:\n";
            echo "    • Title: " . $result['parsing']['sample_job']['title'] . "\n";
            echo "    • Company: " . $result['parsing']['sample_job']['company'] . "\n";
            echo "    • Location: " . $result['parsing']['sample_job']['location'] . "\n";
        }
        echo "\n";

        // Show Metrics
        echo $this->color("▶ PERFORMANCE METRICS\n", 'cyan');
        echo "  Auth Validation: " . $result['metrics']['auth_validation_ms'] . "ms\n";
        echo "  HTTP Request: " . $result['metrics']['http_request_ms'] . "ms\n";
        echo "  Parsing: " . $result['metrics']['parsing_time_ms'] . "ms\n";
        echo "  Total Time: " . $this->color($result['metrics']['total_time_ms'] . "ms", 'yellow') . "\n";
        if (isset($result['metrics']['jobs_per_second'])) {
            echo "  Jobs/Second: " . $result['metrics']['jobs_per_second'] . "\n";
        }
        echo "\n";

        // Show Validation
        echo $this->color("▶ VALIDATION\n", 'cyan');
        echo "  HTTP Status Valid: " . ($result['validation']['http_status_ok'] ? $this->color("✓", 'green') : $this->color("✗", 'red')) . "\n";
        echo "  Format Valid: " . ($result['validation']['response_format_valid'] ? $this->color("✓", 'green') : $this->color("✗", 'red')) . "\n";
        echo "  Jobs Found: " . ($result['validation']['jobs_array_found'] ? $this->color("✓", 'green') : $this->color("✗", 'red')) . "\n";
        echo "  Sample Valid: " . ($result['validation']['sample_job_valid'] ? $this->color("✓", 'green') : $this->color("✗", 'red')) . "\n";
        echo "\n";
    }

    /**
     * Display multiple test results
     */
    private function display_results($result) {
        if ($this->output_json) {
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            return;
        }

        echo "\n";
        echo $this->color("═════════════════════════════════════════════════════════════════════════════════\n", 'blue');
        echo $this->color(sprintf("  Test Results - %d source(s) tested\n", $result['total_sources_tested']), 'blue');
        echo $this->color("═════════════════════════════════════════════════════════════════════════════════\n\n", 'blue');

        // Show summary
        echo $this->color("SUMMARY\n", 'yellow');
        echo "  Passed: " . $this->color($result['summary']['passed'], 'green') . "\n";
        echo "  Failed: " . $this->color($result['summary']['failed'], $result['summary']['failed'] > 0 ? 'red' : 'green') . "\n";
        if ($result['summary']['fastest_source']) {
            echo "  Fastest: " . $result['summary']['fastest_source'] . "\n";
        }
        if ($result['summary']['slowest_source']) {
            echo "  Slowest: " . $result['summary']['slowest_source'] . "\n";
        }
        if ($result['summary']['most_jobs']) {
            echo "  Most Jobs: " . $result['summary']['most_jobs'] . "\n";
        }
        echo "\n";

        // Show detailed results table
        echo $this->color("DETAILED RESULTS\n", 'yellow');
        echo $this->color("─────────────────────────────────────────────────────────────────────────────────\n", 'gray');

        foreach ($result['results'] as $test) {
            $status_icon = $test['errors']['has_errors'] ? "✘" : "✓";
            $status_color = $test['errors']['has_errors'] ? 'red' : 'green';
            $source_name = $test['source']['name'];
            
            if ($test['errors']['has_errors']) {
                $detail = $test['errors']['messages'][0] ?? 'Unknown error';
            } else {
                $jobs = $test['parsing']['jobs_found'];
                $time = $test['metrics']['total_time_ms'];
                $detail = sprintf("%d jobs in %dms", $jobs, $time);
            }

            printf("  %s %-25s %s\n", 
                $this->color($status_icon, $status_color),
                $source_name,
                $detail
            );
        }

        echo $this->color("─────────────────────────────────────────────────────────────────────────────────\n", 'gray');
        echo "  Total Duration: " . $result['test_duration_ms'] . "ms\n";
        echo "\n";

        // Show error messages if any
        if (!empty($result['summary']['error_messages'])) {
            echo $this->color("ERROR MESSAGES\n", 'yellow');
            foreach ($result['summary']['error_messages'] as $msg) {
                echo "  • " . $msg . "\n";
            }
            echo "\n";
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Apply ANSI color codes
     */
    private function color($text, $color = 'white') {
        $colors = [
            'black' => "\033[30m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'magenta' => "\033[35m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'gray' => "\033[90m",
        ];

        $reset = "\033[0m";
        return ($colors[$color] ?? '') . $text . $reset;
    }

    /**
     * Clear terminal screen
     */
    private function clear_screen() {
        if (PHP_OS_FAMILY === 'Windows') {
            system('cls');
        } else {
            system('clear');
        }
    }
}

// Run CLI tool
$cli = new TestSourcesCLI($GLOBALS['config'] ?? []);
$cli->run($argv);
?>
