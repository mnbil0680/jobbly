<?php
// **================================================**
// ** File: SourceTester.php                         **
// ** Responsibility: Enhanced job source testing    **
// ** - Capture raw HTTP requests and responses      **
// ** - Record detailed metrics and performance      **
// ** - Comprehensive JSON test results              **
// ** - Full visibility into source data flow        **
// **================================================**

class SourceTester {
    private $config = [];
    private $sources = [];
    private $default_timeout = 10;

    public function __construct($config = []) {
        $this->config = $config;
        $this->load_sources();
    }

    /**
     * Load source definitions from job_sources.json
     */
    private function load_sources() {
        $sources_file = __DIR__ . '/job_sources.json';
        if (!file_exists($sources_file)) {
            throw new Exception("job_sources.json not found at {$sources_file}");
        }
        $json = file_get_contents($sources_file);
        $this->sources = json_decode($json, true);
        if ($this->sources === null) {
            throw new Exception("Failed to parse job_sources.json");
        }
    }

    /**
     * Test a single source with full data capture
     * 
     * @param string $source_id Source ID to test
     * @return array Comprehensive test result
     */
    public function test_source($source_id) {
        $test_id = $this->generate_test_id();
        $source = $this->get_source($source_id);

        if (!$source) {
            return [
                'test_id' => $test_id,
                'run_at' => date('c'),
                'source' => null,
                'errors' => [
                    'has_errors' => true,
                    'has_warnings' => false,
                    'messages' => ["Source '{$source_id}' not found"]
                ]
            ];
        }

        // Initialize test result structure
        $test_result = [
            'test_id' => $test_id,
            'run_at' => date('c'),
            'source' => [
                'id' => $source['id'],
                'name' => $source['name'],
                'type' => $source['type'],
                'description' => $source['description'] ?? 'N/A',
                'enabled' => $source['enabled'] ?? true
            ],
            'http_request' => [],
            'http_response' => [],
            'parsing' => [],
            'metrics' => [],
            'validation' => [],
            'errors' => [
                'has_errors' => false,
                'has_warnings' => false,
                'messages' => []
            ]
        ];

        // Step 1: Validate API keys
        $key_validation_start = microtime(true);
        $missing_keys = $this->check_required_keys($source);
        $key_validation_time = round((microtime(true) - $key_validation_start) * 1000);

        if (!empty($missing_keys)) {
            $test_result['errors']['has_errors'] = true;
            $test_result['errors']['messages'][] = "Missing API keys: " . implode(', ', $missing_keys);
            $test_result['metrics']['auth_validation_ms'] = $key_validation_time;
            $test_result['validation']['api_keys_valid'] = false;
            $test_result['validation']['missing_keys'] = $missing_keys;
            return $test_result;
        }

        $test_result['validation']['api_keys_valid'] = true;
        $test_result['metrics']['auth_validation_ms'] = $key_validation_time;

        // Step 2: Build HTTP request
        $request_info = $this->build_request_info($source);
        $test_result['http_request'] = $request_info;

        // Step 3: Execute HTTP request
        $http_start = microtime(true);
        $ch = curl_init();

        try {
            $this->setup_curl_request($ch, $source);
            
            // Capture request info
            $curl_info_before = curl_getinfo($ch);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $response_headers = [];
            
            // Try to capture response headers
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            if ($header_size > 0) {
                $headers_str = substr($response, 0, $header_size);
                $body = substr($response, $header_size);
                $response_headers = $this->parse_response_headers($headers_str);
            }
            
            curl_close($ch);

            $http_time = round((microtime(true) - $http_start) * 1000);
            $test_result['metrics']['http_request_ms'] = $http_time;

            // Step 4: Capture response
            $response_info = $this->build_response_info($response, $http_code, $response_headers);
            $test_result['http_response'] = $response_info;

            $test_result['validation']['http_status_ok'] = ($http_code >= 200 && $http_code < 400);

            // Check for HTTP errors
            if ($http_code >= 400) {
                $test_result['errors']['has_errors'] = true;
                $test_result['errors']['messages'][] = "HTTP {$http_code}: " . $this->get_http_error_message($http_code);
                return $test_result;
            }

            // Check for cURL errors
            if ($curl_error) {
                $test_result['errors']['has_errors'] = true;
                $test_result['errors']['messages'][] = "cURL Error: {$curl_error}";
                return $test_result;
            }

            // Step 5: Parse response
            $parse_start = microtime(true);
            $parsed = $this->parse_response($response, $source);
            $parse_time = round((microtime(true) - $parse_start) * 1000);

            $test_result['metrics']['parsing_time_ms'] = $parse_time;

            if ($parsed['error']) {
                $test_result['errors']['has_errors'] = true;
                $test_result['errors']['messages'][] = "Parse Error: {$parsed['error']}";
                $test_result['validation']['response_format_valid'] = false;
                return $test_result;
            }

            // Step 6: Build parsing results
            $parsing_info = $this->build_parsing_info($parsed, $source);
            $test_result['parsing'] = $parsing_info;

            // Step 7: Validate parsing results
            $test_result['validation']['response_format_valid'] = true;
            $test_result['validation']['jobs_array_found'] = $parsed['job_count'] > 0;
            $test_result['validation']['sample_job_valid'] = !empty($parsed['sample_job']);
            $test_result['validation']['all_checks_passed'] = true;

            // Step 8: Calculate metrics
            $total_time = $test_result['metrics']['auth_validation_ms'] + 
                         $test_result['metrics']['http_request_ms'] + 
                         $test_result['metrics']['parsing_time_ms'];
            
            $test_result['metrics']['total_time_ms'] = $total_time;
            
            if ($parsed['job_count'] > 0) {
                $test_result['metrics']['jobs_per_second'] = round(($parsed['job_count'] / $total_time) * 1000);
            }

        } catch (Exception $e) {
            curl_close($ch);
            $test_result['errors']['has_errors'] = true;
            $test_result['errors']['messages'][] = "Exception: " . $e->getMessage();
            $test_result['metrics']['http_request_ms'] = round((microtime(true) - $http_start) * 1000);
        }

        return $test_result;
    }

    /**
     * Test multiple sources
     * 
     * @param array $source_ids Array of source IDs to test, or empty to test all
     * @return array Results for all tested sources
     */
    public function test_all($source_ids = []) {
        $results = [];
        $all_start = microtime(true);

        $sources_to_test = empty($source_ids) ? $this->sources : [];
        
        if (!empty($source_ids)) {
            foreach ($source_ids as $id) {
                $source = $this->get_source($id);
                if ($source) {
                    $sources_to_test[] = $source;
                }
            }
        }

        foreach ($sources_to_test as $source) {
            if (!$source['enabled']) {
                continue;
            }
            $results[] = $this->test_source($source['id']);
        }

        $total_time = round((microtime(true) - $all_start) * 1000);

        return [
            'run_at' => date('c'),
            'total_sources_tested' => count($results),
            'test_duration_ms' => $total_time,
            'results' => $results,
            'summary' => $this->build_test_summary($results)
        ];
    }

    /**
     * Build HTTP request information for JSON output
     * 
     * @param array $source Source definition
     * @return array Request info
     */
    private function build_request_info($source) {
        $url = $source['endpoint'];
        $query_params = [];
        $headers = [];
        $auth_info = [
            'type' => $source['auth'],
            'required_keys' => $source['required_keys'],
            'keys_provided' => []
        ];

        // Build query parameters
        if ($source['method'] === 'GET' && !empty($source['params'])) {
            $query_params = $this->build_params($source['params'], $source);
            $url_with_params = $url . '?' . http_build_query($query_params);
        } else {
            $url_with_params = $url;
        }

        // Build headers
        $headers_array = $this->build_headers($source);
        foreach ($headers_array as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $headers[$parts[0]] = trim($parts[1]);
            }
        }

        // Track which keys were provided
        foreach ($source['required_keys'] as $key) {
            if (!empty($this->config[$key])) {
                $auth_info['keys_provided'][] = $key;
            }
        }

        return [
            'method' => $source['method'],
            'base_url' => $url,
            'full_url' => $url_with_params,
            'query_params' => $query_params,
            'headers' => $headers,
            'body' => $source['method'] === 'POST' ? 'See POST data in source definition' : null,
            'auth' => $auth_info
        ];
    }

    /**
     * Build HTTP response information for JSON output
     * 
     * @param string $response Raw response
     * @param int $http_code HTTP status code
     * @param array $headers Response headers
     * @return array Response info
     */
    private function build_response_info($response, $http_code, $headers = []) {
        $body_raw = substr($response, 0, 5000); // First 5KB for display
        $is_truncated = strlen($response) > 5000;

        return [
            'status_code' => $http_code,
            'status_text' => $this->get_http_status_text($http_code),
            'headers' => $headers,
            'body_size_bytes' => strlen($response),
            'body_preview' => $body_raw,
            'body_truncated' => $is_truncated,
            'content_type' => $headers['Content-Type'] ?? 'Unknown'
        ];
    }

    /**
     * Build parsing information for JSON output
     * 
     * @param array $parsed Parsed result
     * @param array $source Source definition
     * @return array Parsing info
     */
    private function build_parsing_info($parsed, $source) {
        return [
            'detected_format' => $source['parser'],
            'job_path' => $source['job_path'],
            'jobs_found' => $parsed['job_count'],
            'sample_job' => $parsed['sample_job'],
            'field_mapping' => $source['sample_fields'] ?? []
        ];
    }

    /**
     * Build test summary from all results
     * 
     * @param array $results All test results
     * @return array Summary
     */
    private function build_test_summary($results) {
        $summary = [
            'passed' => 0,
            'failed' => 0,
            'error_messages' => [],
            'fastest_source' => null,
            'slowest_source' => null,
            'most_jobs' => null
        ];

        $fastest_time = PHP_INT_MAX;
        $slowest_time = 0;
        $max_jobs = 0;

        foreach ($results as $result) {
            if ($result['errors']['has_errors']) {
                $summary['failed']++;
                foreach ($result['errors']['messages'] as $msg) {
                    if (!in_array($msg, $summary['error_messages'])) {
                        $summary['error_messages'][] = $msg;
                    }
                }
            } else {
                $summary['passed']++;
            }

            // Track timing
            if (!empty($result['metrics']['total_time_ms'])) {
                $time = $result['metrics']['total_time_ms'];
                if ($time < $fastest_time) {
                    $fastest_time = $time;
                    $summary['fastest_source'] = $result['source']['name'] . " ({$time}ms)";
                }
                if ($time > $slowest_time) {
                    $slowest_time = $time;
                    $summary['slowest_source'] = $result['source']['name'] . " ({$time}ms)";
                }
            }

            // Track job count
            if (!empty($result['parsing']['jobs_found'])) {
                $jobs = $result['parsing']['jobs_found'];
                if ($jobs > $max_jobs) {
                    $max_jobs = $jobs;
                    $summary['most_jobs'] = $result['source']['name'] . " ({$jobs} jobs)";
                }
            }
        }

        return $summary;
    }

    /**
     * Check if required API keys are present in config
     * 
     * @param array $source Source definition
     * @return array Missing key names
     */
    private function check_required_keys($source) {
        $missing = [];
        foreach ($source['required_keys'] as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * Setup cURL request (copied from SourceFetcher for consistency)
     * 
     * @param resource $ch cURL handle
     * @param array $source Source definition
     */
    private function setup_curl_request(&$ch, $source) {
        $url = $source['endpoint'];
        $timeout = $source['timeout'] ?? $this->default_timeout;

        // Build query parameters for GET requests
        if ($source['method'] === 'GET' && !empty($source['params'])) {
            $params = $this->build_params($source['params'], $source);
            $url .= '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in response

        // Setup headers
        $headers = $this->build_headers($source);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Setup POST request
        if ($source['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $post_data = $this->build_post_data($source);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        // Setup Basic Auth if required
        if ($source['auth'] === 'basic_auth' && !empty($this->config[$source['required_keys'][0] ?? ''])) {
            $api_key = $this->config[$source['required_keys'][0]];
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':');
        }
    }

    /**
     * Build request headers with API keys substituted
     * 
     * @param array $source Source definition
     * @return array Headers for cURL
     */
    private function build_headers($source) {
        $headers = [];
        foreach ($source['headers'] as $key => $value) {
            // Replace placeholder keys with actual config values
            if (strpos($value, 'RAPIDAPI_KEY') !== false) {
                $value = str_replace('RAPIDAPI_KEY', $this->config['RAPIDAPI_KEY'] ?? '', $value);
            }
            if (strpos($value, 'FINDWORK_API_KEY') !== false) {
                $value = str_replace('FINDWORK_API_KEY', $this->config['FINDWORK_API_KEY'] ?? '', $value);
            }
            if (strpos($value, 'USAJOBS_API_KEY') !== false) {
                $value = str_replace('USAJOBS_API_KEY', $this->config['USAJOBS_API_KEY'] ?? '', $value);
            }
            $headers[] = "{$key}: {$value}";
        }
        return $headers;
    }

    /**
     * Build query parameters with API keys substituted
     * 
     * @param array $params Source params
     * @param array $source Source definition
     * @return array Query parameters
     */
    private function build_params($params, $source) {
        $built = [];
        foreach ($params as $key => $value) {
            if ($value === 'ADZUNA_APP_ID') {
                $value = $this->config['ADZUNA_APP_ID'] ?? '';
            } elseif ($value === 'ADZUNA_APP_KEY') {
                $value = $this->config['ADZUNA_APP_KEY'] ?? '';
            }
            $built[$key] = $value;
        }
        return $built;
    }

    /**
     * Build POST body for POST requests
     * 
     * @param array $source Source definition
     * @return string JSON or form-encoded body
     */
    private function build_post_data($source) {
        if ($source['id'] === 'jooble') {
            $data = [
                'keywords' => 'remote',
                'searchMode' => 'entire',
                'baseUri' => 'https://jooble.org',
                'pageNum' => 1,
                'pageSize' => 50
            ];
            $data['apiKey'] = $this->config['JOOBLE_API_KEY'] ?? '';
            return json_encode($data);
        }
        return json_encode($source['params'] ?? []);
    }

    /**
     * Parse response based on source type and format
     * 
     * @param string $response Raw response body
     * @param array $source Source definition
     * @return array ['job_count' => N, 'sample_job' => {...}, 'error' => null]
     */
    private function parse_response($response, $source) {
        // Remove response headers from body if present
        $header_size = strpos($response, "\r\n\r\n");
        if ($header_size !== false) {
            $response = substr($response, $header_size + 4);
        }

        $result = [
            'job_count' => 0,
            'sample_job' => null,
            'error' => null
        ];

        try {
            if ($source['parser'] === 'json_array' || $source['parser'] === 'json') {
                return $this->parse_json($response, $source);
            } elseif ($source['parser'] === 'rss') {
                return $this->parse_rss($response, $source);
            } else {
                $result['error'] = "Unknown parser: {$source['parser']}";
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Parse JSON response
     * 
     * @param string $response JSON string
     * @param array $source Source definition
     * @return array ['job_count', 'sample_job', 'error']
     */
    private function parse_json($response, $source) {
        $result = [
            'job_count' => 0,
            'sample_job' => null,
            'error' => null
        ];

        $data = json_decode($response, true);
        if ($data === null) {
            $result['error'] = "Invalid JSON response";
            return $result;
        }

        // Extract jobs array using job_path
        $jobs = $data;
        if (!empty($source['job_path'])) {
            $path_parts = explode('.', $source['job_path']);
            foreach ($path_parts as $part) {
                if (isset($jobs[$part])) {
                    $jobs = $jobs[$part];
                } else {
                    $jobs = [];
                    break;
                }
            }
        }

        // Handle case where jobs is not an array
        if (!is_array($jobs)) {
            $jobs = [];
        }

        $result['job_count'] = count($jobs);

        // Extract sample job (first result)
        if (!empty($jobs) && is_array($jobs)) {
            $first_job = reset($jobs);
            if (is_array($first_job)) {
                $result['sample_job'] = $this->extract_job_summary($first_job, $source);
            }
        }

        return $result;
    }

    /**
     * Parse RSS/XML response
     * 
     * @param string $response XML string
     * @param array $source Source definition
     * @return array ['job_count', 'sample_job', 'error']
     */
    private function parse_rss($response, $source) {
        $result = [
            'job_count' => 0,
            'sample_job' => null,
            'error' => null
        ];

        try {
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xml === false) {
                $result['error'] = "Invalid XML/RSS response";
                return $result;
            }

            $items = $xml->channel->item ?? $xml->item ?? [];
            $result['job_count'] = count($items);

            if (!empty($items)) {
                $first_item = $items[0];
                $result['sample_job'] = [
                    'title' => (string)($first_item->title ?? 'Unknown'),
                    'company' => (string)($first_item->author ?? 'Unknown'),
                    'link' => (string)($first_item->link ?? '#')
                ];
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Extract job summary from raw job object
     * 
     * @param array $job Raw job data
     * @param array $source Source definition
     * @return array Job summary
     */
    private function extract_job_summary($job, $source) {
        $summary = [
            'title' => 'Unknown',
            'company' => 'Unknown',
            'location' => 'Unknown',
            'id' => $job[$source['job_id_field']] ?? 'N/A'
        ];

        $field_map = $source['sample_fields'] ?? [];
        foreach ($field_map as $summary_key => $job_key) {
            if (isset($job[$job_key])) {
                $summary[$summary_key] = $job[$job_key];
            }
        }

        $summary['title'] = substr($summary['title'], 0, 100);
        $summary['company'] = substr($summary['company'], 0, 60);

        return $summary;
    }

    /**
     * Parse response headers from header string
     * 
     * @param string $headers_str Header string
     * @return array Parsed headers
     */
    private function parse_response_headers($headers_str) {
        $headers = [];
        $lines = explode("\r\n", $headers_str);
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
        
        return $headers;
    }

    /**
     * Generate unique test ID
     * 
     * @return string
     */
    private function generate_test_id() {
        return 'test_' . date('YmdHis') . '_' . substr(md5(mt_rand()), 0, 8);
    }

    /**
     * Get HTTP status text
     * 
     * @param int $code HTTP status code
     * @return string Status text
     */
    private function get_http_status_text($code) {
        $statuses = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout'
        ];
        return $statuses[$code] ?? "HTTP {$code}";
    }

    /**
     * Get human-readable HTTP error message
     * 
     * @param int $code HTTP status code
     * @return string Error message
     */
    private function get_http_error_message($code) {
        $errors = [
            400 => 'Bad Request',
            401 => 'Unauthorized (invalid or missing API key)',
            403 => 'Forbidden (access denied)',
            404 => 'Not Found',
            429 => 'Rate Limited (too many requests)',
            500 => 'Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout'
        ];
        return $errors[$code] ?? "HTTP Error {$code}";
    }

    /**
     * Get a single source by ID
     * 
     * @param string $id Source ID
     * @return array|null Source definition
     */
    public function get_source($id) {
        foreach ($this->sources as $source) {
            if ($source['id'] === $id) {
                return $source;
            }
        }
        return null;
    }

    /**
     * Get all sources
     * 
     * @return array All sources
     */
    public function get_sources() {
        return $this->sources;
    }
}
?>
