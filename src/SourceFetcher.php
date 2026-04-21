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
                'location' => '',
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
        $cards = $xpath->query('//div[contains(@class, "base-search-card") or contains(@class, "job-search-card") or contains(@class, "base-card")]');
        $result['job_count'] = $cards ? $cards->length : 0;

        if ($cards && $cards->length > 0) {
            $first = $cards->item(0);

            $title = trim($xpath->evaluate('string(.//h3[contains(@class, "base-search-card__title")])', $first));
            $company = trim($xpath->evaluate('string(.//h4[contains(@class, "base-search-card__subtitle")])', $first));
            $location = trim($xpath->evaluate('string(.//span[contains(@class, "job-search-card__location")])', $first));
            $applyUrl = trim($xpath->evaluate('string(.//a[contains(@class, "base-card__full-link")]/@href)', $first));

            $result['sample_job'] = [
                'title' => $title !== '' ? $title : 'Unknown',
                'company' => $company !== '' ? $company : 'Unknown',
                'location' => $location !== '' ? $location : 'Unknown',
                'apply_url' => $applyUrl !== '' ? $applyUrl : 'N/A',
                'id' => $applyUrl !== '' ? $applyUrl : 'N/A'
            ];
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

        // Extract sample job (first valid result)
        if (!empty($jobs) && is_array($jobs)) {
            $first_job = reset($jobs);
            if (is_array($first_job)) {
                $result['sample_job'] = $this->extract_job_summary($first_job, $source);
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
        if (!empty($job_id_field) && array_key_exists($job_id_field, $item)) {
            return true;
        }

        foreach (($source['sample_fields'] ?? []) as $mapped_field) {
            if (!empty($mapped_field) && array_key_exists($mapped_field, $item)) {
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
     * @return array Job summary with title, company, location
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
                $value = $job[$job_key];

                // Convert arrays/objects to readable strings for UI display.
                if (is_array($value)) {
                    if (empty($value)) {
                        $value = 'N/A';
                    } else {
                        $scalar_values = array_values(array_filter($value, function ($item) {
                            return is_scalar($item);
                        }));
                        $value = !empty($scalar_values) ? implode(', ', $scalar_values) : json_encode($value);
                    }
                } elseif (is_object($value)) {
                    $value = json_encode($value);
                }

                $summary[$summary_key] = (string)$value;
            }
        }

        // Limit string lengths for display
        $summary['title'] = substr($summary['title'], 0, 60);
        $summary['company'] = substr($summary['company'], 0, 40);

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
     * Get all sources
     * 
     * @return array All sources
     */
    public function get_sources() {
        return $this->sources;
    }
}
?>
