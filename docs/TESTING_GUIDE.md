# Job Sources Testing Guide

Complete testing system for Jobbly job sources with comprehensive JSON output showing raw requests, responses, parsed data, and performance metrics.

## Overview

The testing system provides three ways to test job sources:

1. **CLI Tool** - Interactive terminal tool for manual testing
2. **Web Endpoint** - HTTP API for programmatic testing
3. **SourceTester Class** - Direct PHP class for custom testing

All methods return comprehensive JSON data showing:
- Raw HTTP request details (method, URL, headers, params)
- Raw HTTP response details (status, headers, body preview)
- Parsed job data and field mappings
- Performance metrics (latency breakdown, jobs/second)
- Validation results

---

## Quick Start

### Test Single Source via CLI
```bash
php src/test_sources_cli.php --source remotive
```

### Test All Sources via CLI
```bash
php src/test_sources_cli.php --all
```

### Interactive Mode
```bash
php src/test_sources_cli.php --interactive
```

### Test via Web Browser
```
http://localhost/jobbly/src/test_source.php?source=remotive
```

---

## CLI Tool (`test_sources_cli.php`)

### Features
- Interactive source selection menu
- Color-coded output with status indicators
- Batch and single source testing
- JSON export for scripting
- Detailed verbose mode
- Performance metrics display

### Usage Examples

#### Test Single Source
```bash
php src/test_sources_cli.php --source remotive
```
Output shows complete test data for Remotive including HTTP request/response, parsing results, and metrics.

#### Test Multiple Sources
```bash
php src/test_sources_cli.php --sources remotive,jobicy,themuse
```

#### Test All Sources
```bash
php src/test_sources_cli.php --all
```

#### Interactive Menu (No Arguments)
```bash
php src/test_sources_cli.php
```
Displays numbered list of sources to select from.

#### Verbose Mode (Show Request/Response Body)
```bash
php src/test_sources_cli.php --source remotive --verbose
```

#### JSON Output (For Piping to Other Tools)
```bash
php src/test_sources_cli.php --all --json
```

### Output Format

**Terminal Output Example:**
```
═══════════════════════════════════════════════════════════
  Test: Remotive
═══════════════════════════════════════════════════════════

▶ HTTP REQUEST
  Method: GET
  URL: https://remotive.com/api/remote-jobs?limit=100
  Headers:
    • Accept = application/json

▶ HTTP RESPONSE
  Status: 200 OK
  Size: 15.23 KB
  Content-Type: application/json

▶ PARSING RESULTS
  Format: json_array
  Job Path: jobs
  Jobs Found: 21
  Sample Job:
    • Title: Senior PHP Developer
    • Company: Tech Co
    • Location: Remote

▶ PERFORMANCE METRICS
  Auth Validation: 1ms
  HTTP Request: 234ms
  Parsing: 5ms
  Total Time: 240ms
  Jobs/Second: 87

▶ VALIDATION
  HTTP Status Valid: ✓
  Format Valid: ✓
  Jobs Found: ✓
  Sample Valid: ✓
```

---

## Web Endpoint (`test_source.php`)

### Features
- HTTP REST API for remote testing
- Query parameter control
- JSON response with pretty-printing
- HTTP status codes for errors

### Query Parameters

| Parameter | Value | Description |
|-----------|-------|-------------|
| `source` | SOURCE_ID | Test single source |
| `sources` | ID1,ID2,ID3 | Test multiple sources (comma-separated) |
| `all` | true | Test all enabled sources |
| `verbose` | true | Include full request/response bodies |

### Usage Examples

#### Test Single Source
```bash
curl "http://localhost/jobbly/src/test_source.php?source=remotive"
```

#### Test Multiple Sources
```bash
curl "http://localhost/jobbly/src/test_source.php?sources=remotive,jobicy"
```

#### Test All Sources
```bash
curl "http://localhost/jobbly/src/test_source.php?all=true"
```

#### Pretty-Print JSON (Using jq)
```bash
curl -s "http://localhost/jobbly/src/test_source.php?all=true" | jq .
```

#### Filter Only Passing Tests
```bash
curl -s "http://localhost/jobbly/src/test_source.php?all=true" \
  | jq '.results[] | select(.errors.has_errors == false)'
```

#### Filter by Job Count
```bash
curl -s "http://localhost/jobbly/src/test_source.php?all=true" \
  | jq '.results[] | select(.parsing.jobs_found > 10)'
```

#### Get Performance Metrics
```bash
curl -s "http://localhost/jobbly/src/test_source.php?all=true" \
  | jq '.results[] | {name: .source.name, latency_ms: .metrics.total_time_ms, jobs: .parsing.jobs_found}'
```

#### Export to File
```bash
curl -s "http://localhost/jobbly/src/test_source.php?all=true" > test_results.json
```

### JSON Response Structure

```json
{
  "test_id": "test_20260420143215_a1b2c3d4",
  "run_at": "2026-04-20T14:32:15Z",
  "source": {
    "id": "remotive",
    "name": "Remotive",
    "type": "json_api",
    "description": "Remote worldwide",
    "enabled": true
  },
  "http_request": {
    "method": "GET",
    "base_url": "https://remotive.com/api/remote-jobs",
    "full_url": "https://remotive.com/api/remote-jobs?limit=100",
    "query_params": {
      "limit": 100
    },
    "headers": {
      "Accept": "application/json"
    },
    "body": null,
    "auth": {
      "type": "none",
      "required_keys": [],
      "keys_provided": []
    }
  },
  "http_response": {
    "status_code": 200,
    "status_text": "OK",
    "headers": {
      "content-type": "application/json",
      "content-length": "15234",
      "server": "nginx"
    },
    "body_size_bytes": 15234,
    "body_preview": "[{\"id\":1,\"title\":\"...\"}]",
    "body_truncated": false,
    "content_type": "application/json"
  },
  "parsing": {
    "detected_format": "json_array",
    "job_path": "jobs",
    "jobs_found": 21,
    "sample_job": {
      "title": "Senior PHP Developer",
      "company": "Tech Co",
      "location": "Remote",
      "job_type": "Full-time",
      "id": "12345"
    },
    "field_mapping": {
      "title": "title",
      "company": "company_name",
      "location": "candidate_required_location"
    }
  },
  "metrics": {
    "auth_validation_ms": 1,
    "http_request_ms": 234,
    "parsing_time_ms": 5,
    "total_time_ms": 240,
    "jobs_per_second": 87
  },
  "validation": {
    "api_keys_valid": true,
    "http_status_ok": true,
    "response_format_valid": true,
    "jobs_array_found": true,
    "sample_job_valid": true,
    "all_checks_passed": true
  },
  "errors": {
    "has_errors": false,
    "has_warnings": false,
    "messages": []
  }
}
```

---

## SourceTester Class

Use the class directly in custom PHP code:

### Basic Usage
```php
require_once 'config/config.php';
require_once 'src/SourceTester.php';

$tester = new SourceTester($GLOBALS['config']);

// Test single source
$result = $tester->test_source('remotive');
echo json_encode($result, JSON_PRETTY_PRINT);

// Test all sources
$results = $tester->test_all();
echo json_encode($results, JSON_PRETTY_PRINT);

// Test multiple sources
$results = $tester->test_all(['remotive', 'jobicy']);
echo json_encode($results, JSON_PRETTY_PRINT);
```

### Available Methods

```php
// Test single source - returns comprehensive test result
$result = $tester->test_source($source_id);

// Test multiple sources - returns array of results with summary
$results = $tester->test_all($source_ids = []);

// Get single source definition
$source = $tester->get_source($id);

// Get all source definitions
$sources = $tester->get_sources();
```

---

## Understanding the Test Output

### HTTP Request Section
Shows exactly what was sent to the API:
- **method**: HTTP method (GET, POST)
- **base_url**: Endpoint URL
- **full_url**: Complete URL with query parameters
- **query_params**: GET parameters
- **headers**: Request headers
- **auth**: Authentication method and which keys were provided

### HTTP Response Section
Shows exactly what came back from the API:
- **status_code**: HTTP status (200, 404, etc.)
- **status_text**: Human-readable status (OK, Not Found)
- **headers**: Response headers
- **body_size_bytes**: Total response size
- **body_preview**: First 5KB of response for inspection
- **body_truncated**: Whether preview was truncated

### Parsing Results
Shows how job data was extracted:
- **detected_format**: JSON, RSS, or XML
- **job_path**: Dot-notation path to jobs array
- **jobs_found**: Total number of jobs extracted
- **sample_job**: First job with normalized fields
- **field_mapping**: How source fields map to normalized fields

### Performance Metrics
Breakdown of where time is spent:
- **auth_validation_ms**: Time to check API keys
- **http_request_ms**: Network round-trip time
- **parsing_time_ms**: Time to parse response
- **total_time_ms**: Sum of all times
- **jobs_per_second**: Throughput metric

### Validation
Checks that passed/failed:
- **api_keys_valid**: All required keys present
- **http_status_ok**: HTTP status 2xx or 3xx
- **response_format_valid**: Response parsed successfully
- **jobs_array_found**: Jobs array extracted
- **sample_job_valid**: Sample job populated
- **all_checks_passed**: All validations passed

### Errors Section
If anything failed:
- **has_errors**: Whether errors occurred
- **has_warnings**: Whether warnings occurred
- **messages**: Array of error/warning messages

---

## Common Workflows

### Debugging a Failed Source
```bash
# Get full details
php src/test_sources_cli.php --source linkedin --verbose

# Or via web with JSON
curl "http://localhost/jobbly/src/test_source.php?source=linkedin"

# Look at http_response.status_code and errors.messages
```

### Comparing Performance
```bash
# Get all results as JSON
php src/test_sources_cli.php --all --json | \
  jq '.results[] | {name: .source.name, latency: .metrics.total_time_ms, jobs: .parsing.jobs_found}' | \
  sort -k3 -n
```

### Finding Slowest Sources
```bash
php src/test_sources_cli.php --all --json | \
  jq -r '.results[] | "\(.source.name): \(.metrics.total_time_ms)ms"' | \
  sort -t: -k2 -rn | head -5
```

### Finding Most Data
```bash
php src/test_sources_cli.php --all --json | \
  jq -r '.results[] | "\(.source.name): \(.parsing.jobs_found) jobs"' | \
  sort -t: -k2 -rn | head -5
```

### Testing with Missing API Keys
Sources requiring API keys will show:
```json
{
  "errors": {
    "has_errors": true,
    "messages": ["Missing API keys: RAPIDAPI_KEY"]
  }
}
```

Add keys to `config/config.php` to test those sources.

---

## Advanced Filtering with jq

### All passing tests
```bash
jq '.results[] | select(.errors.has_errors == false)' results.json
```

### All failed tests
```bash
jq '.results[] | select(.errors.has_errors == true)' results.json
```

### By HTTP status
```bash
jq '.results[] | select(.http_response.status_code == 200)' results.json
```

### By job count
```bash
jq '.results[] | select(.parsing.jobs_found > 50)' results.json
```

### By latency (faster than 300ms)
```bash
jq '.results[] | select(.metrics.total_time_ms < 300)' results.json
```

### Get just the summary stats
```bash
jq '.summary' results.json
```

---

## Troubleshooting

### Getting "Source not found" error
- Make sure the source ID exists (check `src/job_sources.json`)
- Use `--all` to see all available sources

### Getting "Missing API keys" error
- Add the required API keys to `config/config.php`
- Check `src/job_sources.json` for required_keys array

### Getting HTTP 401 or 403 errors
- API key is invalid or expired
- Check that key is correctly formatted in `config.php`
- Verify key in source definition matches config name

### Getting HTTP 404 errors
- Endpoint URL may have changed
- Check if API is still active
- Verify URL in `src/job_sources.json`

### Response parsing errors
- API response format may have changed
- Check `body_preview` to see what's being returned
- Update `job_path` or `sample_fields` in job_sources.json

---

## Performance Expectations

| Source | Typical Latency | Jobs | Status |
|--------|-----------------|------|--------|
| Remotive | 200-300ms | 20-50 | ✓ Working |
| Jobicy | 250-400ms | 50-100 | ✓ Working |
| RemoteOK | 300-500ms | 100+ | ✓ Working |
| The Muse | 150-250ms | 10-30 | ✓ Working |
| LinkedIn | 400+ms | 0-50 | ⚠ Variable |
| JSearch | 500+ms | 100+ | ⏳ Needs key |

---

## Files Reference

| File | Purpose |
|------|---------|
| `src/SourceTester.php` | Core testing class with full data capture |
| `src/test_source.php` | HTTP web endpoint for testing |
| `src/test_sources_cli.php` | Interactive CLI tool |
| `src/job_sources.json` | Source definitions and configurations |
| `config/config.php` | API keys and credentials |

---

## Integration

Use test results in your application:

```php
// Get test results
$results = json_decode(file_get_contents('test_results.json'), true);

// Process results
foreach ($results['results'] as $test) {
    $source_name = $test['source']['name'];
    $job_count = $test['parsing']['jobs_found'];
    $latency = $test['metrics']['total_time_ms'];
    
    // Update status dashboard
    update_source_status($source_name, [
        'jobs' => $job_count,
        'latency' => $latency,
        'healthy' => !$test['errors']['has_errors']
    ]);
}
```

---

## Support

For issues or questions:
- Check error messages in the JSON output
- Review HTTP response details in body_preview
- Enable verbose mode for more information
- Check API documentation in `docs/` folder
