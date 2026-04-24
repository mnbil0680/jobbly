<?php
// **================================================**
// ** File: SourceFetcher.php                         **
// ** Responsibility: Unified job source fetcher      **
// ** - Load source definitions from job_sources.json **
// ** - Validate API key requirements                 **
// ** - Fetch via cURL (JSON, RSS, XML, Text)        **
// ** - Parse responses and extract job count         **
// ** - Return normalized result with status          **
// **================================================**

class SourceFetcher {
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
     * Fetch all enabled sources
     * 
     * @param array $filter Filter options: 'source_id', 'has_key', 'no_key'
     * @return array Results array
     */
    public function fetch_all($filter = []) {
        $results = [];
        $run_at = date('c');

        foreach ($this->sources as $source) {
            // Apply filters
            if (!empty($filter['source_id']) && $source['id'] !== $filter['source_id']) {
                continue;
            }
            if (!$source['enabled']) {
                continue;
            }

            // Fetch and normalize
            $result = $this->fetch_source($source);
            $results[] = $result;
        }

        return [
            'run_at' => $run_at,
            'total_sources' => count($this->sources),
            'results' => $results,
            'summary' => $this->build_summary($results)
        ];
    }

    /**
     * Fetch a single source
     * 
     * @param array $source Source definition
     * @return array Normalized result
     */
    public function fetch_source($source) {
        $result = [
            'source_id' => $source['id'],
            'name' => $source['name'],
            'status' => 'ok',
            'reason' => null,
            'http_status' => null,
            'latency_ms' => 0,
            'job_count' => 0,
            'sample_job' => null,
            'error' => null,
            'timestamp' => time()
        ];

        // Pre-check: Validate required API keys
        $missing_keys = $this->check_required_keys($source);
        if (!empty($missing_keys)) {
            $result['status'] = 'skipped';
            $result['reason'] = 'missing_keys: ' . implode(', ', $missing_keys);
            return $result;
        }

        // Build request
        $ch = curl_init();
        $start_time = microtime(true);

        try {
            $this->setup_curl_request($ch, $source);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            $latency = round((microtime(true) - $start_time) * 1000);
            $result['latency_ms'] = $latency;
            $result['http_status'] = $http_code;

            // Handle HTTP errors
            if ($http_code >= 400) {
                $result['status'] = 'failed';
                $result['reason'] = "HTTP {$http_code}";
                $result['error'] = $this->get_http_error_message($http_code);
                return $result;
            }

            // Handle cURL errors
            if ($curl_error) {
                $result['status'] = 'failed';
                $result['reason'] = 'curl_error';
                $result['error'] = $curl_error;
                return $result;
            }

            // Parse response based on type
            $parsed = $this->parse_response($response, $source);
            if ($parsed['error']) {
                $result['status'] = 'failed';
                $result['reason'] = 'parse_error';
                $result['error'] = $parsed['error'];
                return $result;
            }

            $result['job_count'] = $parsed['job_count'];
            $result['all_jobs'] = $parsed['all_jobs'] ?? [];
            $result['sample_job'] = $parsed['sample_job'];
            $result['status'] = 'ok';

        } catch (Exception $e) {
            curl_close($ch);
            $result['status'] = 'failed';
            $result['reason'] = 'exception';
            $result['error'] = $e->getMessage();
            $result['latency_ms'] = round((microtime(true) - $start_time) * 1000);
        }

        return $result;
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
     * Setup cURL request with headers, auth, and parameters
     * 
        * @param CurlHandle $ch cURL handle
     * @param array $source Source definition
     */
    private function setup_curl_request(&$ch, $source) {
        $url = $source['endpoint'];
        $timeout = $source['timeout'] ?? $this->default_timeout;

        // Replace endpoint placeholders that are key-in-path (e.g., Jooble)
        if (strpos($url, 'JOOBLE_API_KEY') !== false) {
            $url = str_replace('JOOBLE_API_KEY', $this->config['JOOBLE_API_KEY'] ?? '', $url);
        }

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
            // Replace placeholder keys with actual config values
            if ($value === 'ADZUNA_APP_ID') {
                $value = $this->config['ADZUNA_APP_ID'] ?? '';
            } elseif ($value === 'ADZUNA_APP_KEY') {
                $value = $this->config['ADZUNA_APP_KEY'] ?? '';
            } else {
                $value = $value;
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
                'location' => 'remote',
                'page' => 1
            ];
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
        $response = $this->strip_http_headers($response);

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
            } elseif ($source['parser'] === 'linkedin_html') {
                return $this->parse_linkedin_html($response);
            } else {
                $result['error'] = "Unknown parser: {$source['parser']}";
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Parse LinkedIn guest search HTML snippets.
     *
     * @param string $response HTML markup
     * @return array ['job_count', 'sample_job', 'error']
     */
    private function parse_linkedin_html($response) {
        $result = [
            'job_count' => 0,
            'sample_job' => null,
            'error' => null
        ];

        if (!is_string($response) || trim($response) === '') {
            $result['error'] = 'Empty HTML response';
            return $result;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $response);
        libxml_clear_errors();

        if (!$loaded) {
            $result['error'] = 'Invalid LinkedIn HTML response';
            return $result;
        }

        $xpath = new DOMXPath($dom);
        $cards = $xpath->query('//*[contains(@class, "base-search-card") or contains(@class, "job-search-card") or contains(@class, "base-card")]');
        $result['job_count'] = $cards ? $cards->length : 0;
        $result['all_jobs'] = [];

        if ($cards && $cards->length > 0) {
            foreach ($cards as $card) {
                $title = trim($xpath->evaluate('string(.//h3[contains(@class, "base-search-card__title")])', $card));
                $company = trim($xpath->evaluate('string(.//h4[contains(@class, "base-search-card__subtitle")])', $card));
                $location = trim($xpath->evaluate('string(.//span[contains(@class, "job-search-card__location")])', $card));
                $applyUrl = trim($xpath->evaluate('string(.//a[contains(@class, "base-card__full-link")]/@href)', $card));
                
                // For LinkedIn guest API, the applyUrl is often a redirect or the jobId is in the URL
                $jobId = 'N/A';
                if (preg_match('/-([0-9]{10,})/', $applyUrl, $matches)) {
                    $jobId = $matches[1];
                }

                if ($title === 'Unknown' || $company === 'Unknown' || $title === '' || $company === '') {
                    continue;
                }

                $jobData = [
                    'title' => $title,
                    'company_name' => $company,
                    'location' => $location !== '' ? $location : 'Remote',
                    'apply_url' => $applyUrl !== '' ? $applyUrl : '#',
                    'poster_id' => 'linkedin_' . ($jobId !== 'N/A' ? $jobId : md5($title . $company)),
                    'category_id' => 1,
                    'description' => '',
                    'job_type' => 'Full-time',
                    'salary_min' => 0,
                    'salary_max' => 0,
                    'currency' => 'USD'
                ];
                
                $result['all_jobs'][] = $jobData;
            }
            
            if (!empty($result['all_jobs'])) {
                $result['sample_job'] = $result['all_jobs'][0];
            }
        }

        return $result;
    }

    /**
     * Strip one or more HTTP header blocks from a cURL response.
     * Handles redirect chains that can prepend multiple headers.
     *
     * @param string $response Raw cURL response
     * @return string Body payload
     */
    private function strip_http_headers($response) {
        if (!is_string($response) || $response === '') {
            return '';
        }

        $payload = ltrim($response);

        while (preg_match('/^HTTP\/\d(?:\.\d)?\s+\d{3}/i', $payload) === 1) {
            $header_end = strpos($payload, "\r\n\r\n");
            if ($header_end === false) {
                break;
            }
            $payload = ltrim(substr($payload, $header_end + 4));
        }

        return $payload;
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

        // Filter out non-job records (e.g., metadata rows from some APIs).
        $filtered_jobs = [];
        foreach ($jobs as $job) {
            if ($this->is_job_like_record($job, $source)) {
                $filtered_jobs[] = $job;
            }
        }

        // Fallback to original list if filtering becomes too strict.
        if (!empty($filtered_jobs)) {
            $jobs = $filtered_jobs;
        }

        $result['job_count'] = count($jobs);

        // Extract all jobs and sample job
        if (!empty($jobs) && is_array($jobs)) {
            $result['all_jobs'] = [];
            foreach ($jobs as $job) {
                if (is_array($job)) {
                    $result['all_jobs'][] = $this->normalize_for_db($job, $source);
                }
            }
            if (!empty($result['all_jobs'])) {
                $result['sample_job'] = $result['all_jobs'][0];
            }
        }

        return $result;
    }

    /**
     * Determine if a decoded array item looks like a real job row.
     *
     * @param mixed $item
     * @param array $source
     * @return bool
     */
    private function is_job_like_record($item, $source) {
        if (!is_array($item)) {
            return false;
        }

        $job_id_field = $source['job_id_field'] ?? '';
        if (!empty($job_id_field) && $this->get_nested_value($item, $job_id_field) !== null) {
            return true;
        }

        foreach (($source['sample_fields'] ?? []) as $mapped_field) {
            if (!empty($mapped_field) && $this->get_nested_value($item, $mapped_field) !== null) {
                return true;
            }
        }

        return false;
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
            $result['all_jobs'] = [];

            if (!empty($items)) {
                foreach ($items as $item) {
                   $jobData = [
                        'title' => (string)($item->title ?? 'Unknown'),
                        'company_name' => (string)($item->author ?? 'Unknown'),
                        'apply_url' => (string)($item->link ?? '#'),
                        'poster_id' => $source['id'] . "_" . (string)($item->guid ?? $item->link ?? md5((string)$item->title)),
                        'description' => (string)($item->description ?? ''),
                        'location' => 'Remote',
                        'job_type' => 'Full-time'
                    ];
                    $result['all_jobs'][] = $jobData;
                }
                $result['sample_job'] = $result['all_jobs'][0] ?? null;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Helper to ensure a value is a string, converting arrays/objects if needed
     */
    private function ensure_string($value) {
        if (is_string($value)) return $value;
        if (is_array($value)) {
            if (empty($value)) return '';
            // If it's an associative array, JSON encode it. If sequential, implode.
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return json_encode($value);
            }
            return implode(', ', $value);
        }
        if (is_object($value)) return json_encode($value);
        if ($value === null) return '';
        return (string)$value;
    }

    /**
     * Helper to get a nested value from an array using dot notation or array syntax
     */
    private function get_nested_value($array, $path) {
        if (empty($path)) return null;
        
        // Handle array syntax like locations[0].name
        $path = preg_replace('/\[(\d+)\]/', '.$1', $path);
        $parts = explode('.', $path);
        
        $current = $array;
        foreach ($parts as $part) {
            if (is_array($current) && isset($current[$part])) {
                $current = $current[$part];
            } else {
                return null;
            }
        }
        return $current;
    }

    /**
     * Normalize a raw job object into database-ready format
     * 
     * @param array $job Raw job data
     * @param array $source Source definition
     * @return array Normalized job
     */
    public function normalize_for_db($job, $source) {
        $normalized = [
            'company_name' => 'Unknown',
            'poster_id' => null,
            'category_id' => 1, // Default
            'title' => 'Untitled Job',
            'description' => '',
            'location' => 'Unknown',
            'job_type' => 'Full-time',
            'salary_min' => 0,
            'salary_max' => 0,
            'currency' => 'USD',
            'apply_url' => ''
        ];

        $field_map = $source['sample_fields'] ?? [];
        
        // Map fields based on source definition
        foreach($field_map as $key => $path) {
            $value = $this->get_nested_value($job, $path);
            if ($value !== null) {
                if ($key === 'salary_min' || $key === 'salary_max') {
                    $normalized[$key] = $value;
                } elseif ($key === 'company') {
                    $normalized['company_name'] = $this->ensure_string($value);
                } else {
                    $normalized[$key] = $this->ensure_string($value);
                }
            }
        }

        // Handle description (might be 'description' or 'body' or 'snippet')
        $desc_fields = ['description', 'description_html', 'body', 'snippet', 'job_description'];
        foreach($desc_fields as $df) {
            if (isset($job[$df]) && !empty($job[$df])) {
                $rawDesc = $this->ensure_string($job[$df]);
                $normalized['description'] = $this->sanitizeDescription($rawDesc);
                break;
            }
        }

        // Generate poster_id
        $external_id = $this->get_nested_value($job, $source['job_id_field'] ?? '') ?? md5($normalized['title'] . $normalized['company_name']);
        $normalized['poster_id'] = $source['id'] . "_" . $external_id;

        // Simple Category Mapping
        $title_lower = strtolower($normalized['title']);
        if (strpos($title_lower, 'market') !== false || strpos($title_lower, 'seo') !== false) {
            $normalized['category_id'] = 2; // Marketing
        } elseif (strpos($title_lower, 'health') !== false || strpos($title_lower, 'nurse') !== false || strpos($title_lower, 'doctor') !== false) {
            $normalized['category_id'] = 3; // Healthcare
        } elseif (strpos($title_lower, 'sale') !== false || strpos($title_lower, 'account manager') !== false) {
            $normalized['category_id'] = 4; // Sales
        } else {
            $normalized['category_id'] = 1; // Default: Software Engineering
        }

        return $normalized;
    }

    /**
     * Extract job summary from raw job object
     * 
     * @param array $job Raw job data
     * @param array $source Source definition
     * @return array Job summary with title, company, location
     */
    private function extract_job_summary($job, $source) {
        $summary = $this->normalize_for_db($job, $source);
        
        // Adapt for UI display
        $summary['company'] = $summary['company_name'];
        $summary['id'] = $summary['poster_id'];

        // Limit string lengths for display (use mb_substr for UTF-8 safety)
        $summary['title'] = function_exists('mb_substr') ? mb_substr($summary['title'], 0, 60) : substr($summary['title'], 0, 60);
        $summary['company'] = function_exists('mb_substr') ? mb_substr($summary['company'], 0, 40) : substr($summary['company'], 0, 40);

        return $summary;
    }

    /**
     * Build summary statistics from results
     * 
     * @param array $results Fetch results
     * @return array Summary with counts
     */
    private function build_summary($results) {
        $summary = [
            'ok' => 0,
            'skipped' => 0,
            'failed' => 0,
            'skipped_reasons' => [],
            'failed_reasons' => []
        ];

        foreach ($results as $result) {
            if ($result['status'] === 'ok') {
                $summary['ok']++;
            } elseif ($result['status'] === 'skipped') {
                $summary['skipped']++;
                if ($result['reason'] && !in_array($result['reason'], $summary['skipped_reasons'])) {
                    $summary['skipped_reasons'][] = $result['reason'];
                }
            } elseif ($result['status'] === 'failed') {
                $summary['failed']++;
                if ($result['reason'] && !in_array($result['reason'], $summary['failed_reasons'])) {
                    $summary['failed_reasons'][] = $result['reason'];
                }
            }
        }

        return $summary;
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
     * Sanitize job description HTML
     */
    private function sanitizeDescription($html) {
        if (empty($html)) return '';

        // Strip scripts and styles
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        
        // Strip tracking pixels and images that look like trackers
        $html = preg_replace('/<img[^>]+src=[^>]*blank\.gif[^>]*>/i', '', $html);
        $html = preg_replace('/<img[^>]+src=[^>]*track[^>]*>/i', '', $html);

        // Remove inline styles and classes to keep it clean for our UI
        $html = preg_replace('/style="[^"]*"/', '', $html);
        $html = preg_replace('/class="[^"]*"/', '', $html);

        // Remove empty tags
        $html = preg_replace('/<(p|div|span|div)>\s*(&nbsp;)?\s*<\/\1>/i', '', $html);

        return trim($html);
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
