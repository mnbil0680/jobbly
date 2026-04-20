#!/usr/bin/env php
<?php
// **================================================**
// ** File: verify_setup.php                         **
// ** Responsibility: Check project setup and deps   **
// ** Usage: php verify_setup.php                    **
// **================================================**

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║   Jobbly Project - Setup Verification          ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

$checks = [];
$all_pass = true;

// Check 1: PHP Version
echo "Checking PHP version...";
$php_version = phpversion();
if (version_compare($php_version, '7.0.0') >= 0) {
    echo " ✓ OK\n";
    echo "  PHP {$php_version}\n";
    $checks[] = ['PHP Version', 'OK', $php_version];
} else {
    echo " ✗ FAIL\n";
    echo "  Minimum PHP 7.0 required, you have {$php_version}\n";
    $checks[] = ['PHP Version', 'FAIL', $php_version];
    $all_pass = false;
}

// Check 2: Required Files
echo "\nChecking required files...";
$required_files = [
    'config/config.php',
    'config/config.example.php',
    'src/SourceFetcher.php',
    'src/fetch_sources.php',
    'src/fetch_sources_cli.php',
    'src/job_sources.json',
    'app/index.php',
    'app/DB_Ops.php',
    'app/API_Ops.php'
];

$missing = [];
$base_dir = dirname(__DIR__); // Get parent directory
foreach ($required_files as $file) {
    if (!file_exists($base_dir . '/' . $file)) {
        $missing[] = $file;
    }
}

if (empty($missing)) {
    echo " ✓ OK\n";
    echo "  All " . count($required_files) . " required files found\n";
    $checks[] = ['Required Files', 'OK', count($required_files) . ' files'];
} else {
    echo " ✗ FAIL\n";
    echo "  Missing: " . implode(', ', $missing) . "\n";
    $checks[] = ['Required Files', 'FAIL', count($missing) . ' missing'];
    $all_pass = false;
}

// Check 3: config.php Exists
echo "\nChecking config.php...";
$base_dir = dirname(__DIR__);
if (file_exists($base_dir . '/config/config.php')) {
    echo " ✓ OK\n";
    echo "  config.php exists\n";
    $checks[] = ['config.php', 'OK', 'Found'];
} else {
    echo " ⚠ WARNING\n";
    echo "  config.php not found. Creating from template...\n";
    if (file_exists($base_dir . '/config/config.example.php')) {
        copy($base_dir . '/config/config.example.php', $base_dir . '/config/config.php');
        echo "  Created config.php from config.example.php\n";
        $checks[] = ['config.php', 'CREATED', 'New file'];
    } else {
        echo "  Could not create config.php\n";
        $checks[] = ['config.php', 'FAIL', 'Missing template'];
        $all_pass = false;
    }
}

// Check 4: job_sources.json
echo "\nChecking job_sources.json...";
if (file_exists($base_dir . '/src/job_sources.json')) {
    $json = json_decode(file_get_contents($base_dir . '/src/job_sources.json'), true);
    if ($json && is_array($json)) {
        echo " ✓ OK\n";
        echo "  Valid JSON with " . count($json) . " sources\n";
        $checks[] = ['job_sources.json', 'OK', count($json) . ' sources'];
    } else {
        echo " ✗ FAIL\n";
        echo "  Invalid JSON format\n";
        $checks[] = ['job_sources.json', 'FAIL', 'Invalid JSON'];
        $all_pass = false;
    }
} else {
    echo " ✗ FAIL\n";
    echo "  job_sources.json not found\n";
    $checks[] = ['job_sources.json', 'FAIL', 'Missing'];
    $all_pass = false;
}

// Check 5: SourceFetcher Class
echo "\nChecking SourceFetcher class...";
if (file_exists($base_dir . '/src/SourceFetcher.php')) {
    require_once $base_dir . '/src/SourceFetcher.php';
    if (class_exists('SourceFetcher')) {
        echo " ✓ OK\n";
        echo "  SourceFetcher class loaded successfully\n";
        $checks[] = ['SourceFetcher Class', 'OK', 'Loaded'];
    } else {
        echo " ✗ FAIL\n";
        echo "  SourceFetcher class not found\n";
        $checks[] = ['SourceFetcher Class', 'FAIL', 'Not found'];
        $all_pass = false;
    }
} else {
    echo " ✗ FAIL\n";
    echo "  SourceFetcher.php not found\n";
    $checks[] = ['SourceFetcher Class', 'FAIL', 'File missing'];
    $all_pass = false;
}

// Check 6: cURL Extension
echo "\nChecking PHP cURL extension...";
if (extension_loaded('curl')) {
    echo " ✓ OK\n";
    echo "  cURL is enabled\n";
    $checks[] = ['cURL Extension', 'OK', 'Enabled'];
} else {
    echo " ✗ FAIL\n";
    echo "  cURL extension is not enabled\n";
    echo "  Enable it in php.ini: extension=curl\n";
    $checks[] = ['cURL Extension', 'FAIL', 'Disabled'];
    $all_pass = false;
}

// Check 7: JSON Extension
echo "\nChecking JSON extension...";
if (extension_loaded('json')) {
    echo " ✓ OK\n";
    echo "  JSON is enabled\n";
    $checks[] = ['JSON Extension', 'OK', 'Enabled'];
} else {
    echo " ✗ FAIL\n";
    echo "  JSON extension is not enabled\n";
    $checks[] = ['JSON Extension', 'FAIL', 'Disabled'];
    $all_pass = false;
}

// Check 8: SimpleXML for RSS parsing
echo "\nChecking SimpleXML extension (for RSS feeds)...";
if (extension_loaded('simplexml')) {
    echo " ✓ OK\n";
    echo "  SimpleXML is enabled\n";
    $checks[] = ['SimpleXML Extension', 'OK', 'Enabled'];
} else {
    echo " ⚠ WARNING\n";
    echo "  SimpleXML not enabled. RSS feeds won't work.\n";
    $checks[] = ['SimpleXML Extension', 'WARN', 'Disabled'];
}

// Summary Table
echo "\n╔════════════════════════════════════════════════╗\n";
echo "║                 Summary                        ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

echo str_pad("Component", 35) . str_pad("Status", 12) . "Details\n";
echo str_repeat("─", 70) . "\n";

foreach ($checks as $check) {
    $status_color = '';
    if ($check[1] === 'OK' || $check[1] === 'CREATED') {
        $status_color = "\033[32m✓\033[0m";
    } elseif ($check[1] === 'FAIL') {
        $status_color = "\033[31m✗\033[0m";
    } else {
        $status_color = "\033[33m⚠\033[0m";
    }
    
    echo str_pad($check[0], 35);
    echo $status_color . " " . str_pad($check[1], 10);
    echo $check[2] . "\n";
}

echo "\n";

if ($all_pass) {
    echo "\033[32m✓ All checks passed! Your project is ready.\033[0m\n\n";
    echo "Next steps:\n";
    echo "  1. Start XAMPP (Apache + MySQL)\n";
    echo "  2. Run: php src/fetch_sources_cli.php\n";
    echo "  3. Open: http://localhost/jobbly/app\n";
    exit(0);
} else {
    echo "\033[31m✗ Some checks failed. Please fix the issues above.\033[0m\n\n";
    echo "For help, see:\n";
    echo "  - docs/RUN_AND_TEST.md (setup and testing guide)\n";
    echo "  - docs/QUICK_START.md (quick setup)\n";
    exit(1);
}
?>
