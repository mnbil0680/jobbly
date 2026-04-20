// **================================================**
// ** File: test_endpoint_details.js                 **
// ** Responsibility: Test Endpoint details page     **
// ** - Fetch and display comprehensive data         **
// ** - Auto-refresh logic                           **
// ** - Real-time data visualization                 **
// ** - Error handling and retry logic               **
// **================================================**

let currentSourceId = null;
let currentTestResult = null;
let autoRefreshEnabled = false;
let autoRefreshInterval = null;
const AUTO_REFRESH_DELAY = 30000; // 30 seconds

/**
 * Load source details
 */
async function loadSourceDetails(sourceId) {
    currentSourceId = sourceId;
    
    // Show loading state
    document.getElementById('detailsLoading').style.display = 'block';
    document.getElementById('detailsContent').style.display = 'none';
    document.getElementById('detailsError').style.display = 'none';
    
    try {
        // Fetch test data
        const response = await fetch(`/jobbly/src/test_source.php?source=${sourceId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.errors && data.errors.has_errors && !data.source) {
            throw new Error(data.errors.messages[0] || 'Unknown error');
        }
        
        currentTestResult = data;
        
        // Fetch source definition for full documentation
        const sourceDef = await loadSourceDefinition(sourceId);
        
        // Hide loading
        document.getElementById('detailsLoading').style.display = 'none';
        
        // Render all sections
        renderDetailsPage(data, sourceDef);
        
        // Show content
        document.getElementById('detailsContent').style.display = 'block';
        
        // Update timestamp
        updateDetailsLastUpdate();
        
    } catch (error) {
        console.error('Error loading source details:', error);
        
        document.getElementById('detailsLoading').style.display = 'none';
        document.getElementById('detailsContent').style.display = 'none';
        document.getElementById('detailsError').style.display = 'block';
        document.getElementById('detailsErrorText').textContent = error.message;
    }
}

/**
 * Load source definition from job_sources.json
 */
async function loadSourceDefinition(sourceId) {
    try {
        const response = await fetch('/jobbly/src/job_sources.json');
        const sources = await response.json();
        
        const source = sources.find(s => s.id === sourceId);
        return source || null;
    } catch (error) {
        console.error('Error loading source definition:', error);
        return null;
    }
}

/**
 * Render entire details page
 */
function renderDetailsPage(data, sourceDef) {
    // Header
    renderHeader(data.source);
    
    // API Documentation
    if (sourceDef) {
        renderAPIDocumentation(sourceDef);
    }
    
    // Status indicator
    renderStatusIndicator(data);
    
    // Request section
    renderRequestSection(data.http_request);
    
    // Response section
    renderResponseSection(data.http_response);
    
    // Parsing section
    renderParsingSection(data.parsing);
    
    // Metrics section
    renderMetricsSection(data.metrics);
    
    // Validation section
    renderValidationSection(data.validation);
    
    // Errors section
    if (data.errors && data.errors.has_errors) {
        renderErrorsSection(data.errors);
    }
}

/**
 * Render header
 */
function renderHeader(source) {
    document.getElementById('detailsSourceName').textContent = source.name;
    document.getElementById('detailsSourceDescription').textContent = 
        source.description || '';
}

/**
 * Render status indicator
 */
function renderStatusIndicator(data) {
    const statusDiv = document.getElementById('statusIndicator');
    const badge = document.getElementById('statusBadge');
    const message = document.getElementById('statusMessage');
    
    statusDiv.className = 'status-indicator';
    
    if (data.errors && data.errors.has_errors) {
        // Failed
        badge.className = 'status-badge error';
        badge.textContent = '✗';
        message.textContent = data.errors.messages[0] || 'Test failed';
        statusDiv.classList.add('error');
    } else if (data.validation && data.validation.all_checks_passed) {
        // Success
        badge.className = 'status-badge success';
        badge.textContent = '✓';
        message.textContent = `✓ All checks passed! Found ${data.parsing.jobs_found} jobs`;
        statusDiv.classList.add('success');
    } else if (data.parsing && data.parsing.jobs_found > 0) {
        // Partial success
        badge.className = 'status-badge success';
        badge.textContent = '✓';
        message.textContent = `✓ Working! Found ${data.parsing.jobs_found} jobs`;
        statusDiv.classList.add('success');
    } else {
        // Warning
        badge.className = 'status-badge warning';
        badge.textContent = '⚠';
        message.textContent = 'Some checks did not pass';
        statusDiv.classList.add('warning');
    }
}

/**
 * Render API documentation section
 */
function renderAPIDocumentation(sourceDef) {
    // Basic API info
    document.getElementById('apiEndpoint').textContent = sourceDef.endpoint || 'N/A';
    document.getElementById('apiType').textContent = sourceDef.type || 'JSON API';
    document.getElementById('apiMethod').textContent = sourceDef.method || 'GET';
    document.getElementById('apiTimeout').textContent = `${sourceDef.timeout || 10}s`;
    
    // Description
    const description = sourceDef.description || 'No description available';
    document.getElementById('apiFullDescription').textContent = description;
    
    // Authentication details
    document.getElementById('authTypeDetailed').textContent = sourceDef.auth || 'none';
    
    const authKeysList = document.getElementById('authRequiredKeysList');
    if (sourceDef.required_keys && sourceDef.required_keys.length > 0) {
        authKeysList.innerHTML = sourceDef.required_keys
            .map(key => `<li><code>${escapeHtml(key)}</code></li>`)
            .join('');
    } else {
        authKeysList.innerHTML = '<li>No API keys required</li>';
    }
    
    // Auth key locations
    const authLocations = document.getElementById('authKeyLocationsList');
    const authLocation = getAuthLocation(sourceDef.auth);
    if (authLocation) {
        authLocations.innerHTML = `<li>${authLocation}</li>`;
    } else {
        authLocations.innerHTML = '<li>N/A</li>';
    }
    
    // Supported parameters
    const paramsBody = document.getElementById('supportedParamsBody');
    if (sourceDef.params && Object.keys(sourceDef.params).length > 0) {
        paramsBody.innerHTML = Object.entries(sourceDef.params)
            .map(([key, value]) => `
                <tr>
                    <td><code>${escapeHtml(key)}</code></td>
                    <td><code>${escapeHtml(String(value))}</code></td>
                    <td>${getParamDescription(key)}</td>
                </tr>
            `).join('');
    } else {
        paramsBody.innerHTML = '<tr><td colspan="3">No parameters</td></tr>';
    }
    
    // Field mapping reference
    const fieldMapRefBody = document.getElementById('fieldMappingRefBody');
    if (sourceDef.sample_fields && Object.keys(sourceDef.sample_fields).length > 0) {
        fieldMapRefBody.innerHTML = Object.entries(sourceDef.sample_fields)
            .map(([normalized, apiField]) => `
                <tr>
                    <td><code>${escapeHtml(normalized)}</code></td>
                    <td><code>${escapeHtml(apiField)}</code></td>
                    <td>${getFieldDescription(normalized)}</td>
                </tr>
            `).join('');
    } else {
        fieldMapRefBody.innerHTML = '<tr><td colspan="3">No field mapping data</td></tr>';
    }
    
    // Configuration
    document.getElementById('apiEnabled').textContent = sourceDef.enabled ? 'Yes' : 'No';
    document.getElementById('apiEnabled').className = sourceDef.enabled ? 'badge badge-success' : 'badge badge-danger';
    
    document.getElementById('apiParser').textContent = sourceDef.parser || 'json';
    document.getElementById('apiJobPath').textContent = sourceDef.job_path || 'jobs';
    document.getElementById('apiJobIdField').textContent = sourceDef.job_id_field || 'id';
}

/**
 * Get authentication method location description
 */
function getAuthLocation(authType) {
    const locations = {
        'none': 'No authentication required',
        'api_key_header': 'API key in HTTP request header',
        'api_key_param': 'API key in query parameters',
        'basic_auth': 'HTTP Basic Authentication (username:password)',
        'post_body': 'API key in POST request body (JSON)'
    };
    return locations[authType] || 'Unknown location';
}

/**
 * Get parameter description
 */
function getParamDescription(paramName) {
    const descriptions = {
        'limit': 'Maximum number of results to return',
        'offset': 'Number of results to skip',
        'page': 'Page number for pagination',
        'pageNum': 'Page number for pagination',
        'pageSize': 'Results per page',
        'keywords': 'Search keywords',
        'searchMode': 'Search mode (entire/partial)',
        'app_id': 'Adzuna app ID',
        'app_key': 'Adzuna app key',
        'baseUri': 'Base URI for the API',
        'searchMode': 'Search mode for results'
    };
    return descriptions[paramName] || 'Parameter';
}

/**
 * Get field description
 */
function getFieldDescription(fieldName) {
    const descriptions = {
        'title': 'Job title/position',
        'company': 'Company name',
        'location': 'Job location/region',
        'job_type': 'Type of job (full-time, part-time, etc)',
        'salary': 'Job salary/compensation',
        'apply_url': 'URL to apply for the job',
        'description': 'Job description',
        'id': 'Unique job identifier'
    };
    return descriptions[fieldName] || 'Job field';
}

/**
 * Render request section
 */
function renderRequestSection(request) {
    document.getElementById('reqMethod').textContent = request.method;
    document.getElementById('reqBaseUrl').textContent = request.base_url;
    document.getElementById('reqFullUrl').textContent = request.full_url;
    
    // Query parameters
    const paramsBody = document.getElementById('reqParamsBody');
    if (Object.keys(request.query_params || {}).length > 0) {
        paramsBody.innerHTML = Object.entries(request.query_params)
            .map(([key, value]) => `
                <tr>
                    <td><code>${escapeHtml(key)}</code></td>
                    <td><code>${escapeHtml(String(value))}</code></td>
                </tr>
            `).join('');
    } else {
        paramsBody.innerHTML = '<tr><td colspan="2">No query parameters</td></tr>';
    }
    
    // Headers
    const headersBody = document.getElementById('reqHeadersBody');
    if (Object.keys(request.headers || {}).length > 0) {
        headersBody.innerHTML = Object.entries(request.headers)
            .map(([key, value]) => `
                <tr>
                    <td><code>${escapeHtml(key)}</code></td>
                    <td><code>${escapeHtml(String(value).substring(0, 50))}${String(value).length > 50 ? '...' : ''}</code></td>
                </tr>
            `).join('');
    } else {
        headersBody.innerHTML = '<tr><td colspan="2">No headers</td></tr>';
    }
    
    // Authentication
    if (request.auth) {
        document.getElementById('authType').textContent = request.auth.type || 'none';
        document.getElementById('authRequired').textContent = 
            request.auth.required_keys.length > 0 ? request.auth.required_keys.join(', ') : 'None';
        document.getElementById('authProvided').textContent = 
            request.auth.keys_provided.length > 0 ? request.auth.keys_provided.join(', ') : 'None';
    }
}

/**
 * Render response section
 */
function renderResponseSection(response) {
    document.getElementById('respStatus').textContent = response.status_code;
    document.getElementById('respStatusText').textContent = response.status_text;
    document.getElementById('respSize').textContent = formatBytes(response.body_size_bytes);
    document.getElementById('respContentType').textContent = response.content_type;
    
    // Response headers
    const respHeadersBody = document.getElementById('respHeadersBody');
    if (Object.keys(response.headers || {}).length > 0) {
        respHeadersBody.innerHTML = Object.entries(response.headers)
            .map(([key, value]) => `
                <tr>
                    <td><code>${escapeHtml(key)}</code></td>
                    <td><code>${escapeHtml(String(value).substring(0, 50))}${String(value).length > 50 ? '...' : ''}</code></td>
                </tr>
            `).join('');
    } else {
        respHeadersBody.innerHTML = '<tr><td colspan="2">No headers</td></tr>';
    }
    
    // Body preview
    let preview = response.body_preview || '{}';
    
    // Try to format JSON
    try {
        if (typeof preview === 'string' && preview.includes('{')) {
            const jsonStart = preview.indexOf('{');
            const jsonEnd = preview.lastIndexOf('}');
            if (jsonStart >= 0 && jsonEnd > jsonStart) {
                const jsonStr = preview.substring(jsonStart, jsonEnd + 1);
                preview = JSON.stringify(JSON.parse(jsonStr), null, 2);
            }
        }
    } catch (e) {
        // Keep original preview if not valid JSON
    }
    
    document.getElementById('respBodyPreview').textContent = preview;
    
    // Show truncated message if needed
    const truncatedMsg = document.getElementById('bodyTruncatedMsg');
    if (response.body_truncated) {
        truncatedMsg.style.display = 'block';
    } else {
        truncatedMsg.style.display = 'none';
    }
}

/**
 * Render parsing section
 */
function renderParsingSection(parsing) {
    document.getElementById('parseFormat').textContent = parsing.detected_format;
    document.getElementById('parseJobPath').textContent = parsing.job_path;
    document.getElementById('parseJobCount').textContent = parsing.jobs_found;
    
    // Sample job
    if (parsing.sample_job) {
        const sample = parsing.sample_job;
        document.getElementById('sampleTitle').textContent = sample.title || 'N/A';
        document.getElementById('sampleCompany').textContent = sample.company || 'N/A';
        document.getElementById('sampleLocation').textContent = sample.location || 'N/A';
        
        if (sample.job_type) {
            document.getElementById('jobTypeDetail').style.display = 'block';
            document.getElementById('sampleJobType').textContent = sample.job_type;
        }
        
        if (sample.apply_url) {
            document.getElementById('applyUrlDetail').style.display = 'block';
            const link = document.getElementById('sampleApplyUrl');
            link.href = sample.apply_url;
            link.textContent = 'View Job';
        }
    }
    
    // Field mapping
    const mappingBody = document.getElementById('fieldMappingBody');
    if (Object.keys(parsing.field_mapping || {}).length > 0) {
        mappingBody.innerHTML = Object.entries(parsing.field_mapping)
            .map(([normalized, api]) => `
                <tr>
                    <td><code>${escapeHtml(normalized)}</code></td>
                    <td><code>${escapeHtml(api)}</code></td>
                </tr>
            `).join('');
    } else {
        mappingBody.innerHTML = '<tr><td colspan="2">No mapping data</td></tr>';
    }
}

/**
 * Render metrics section
 */
function renderMetricsSection(metrics) {
    document.getElementById('metricAuthMs').textContent = `${metrics.auth_validation_ms}ms`;
    document.getElementById('metricHttpMs').textContent = `${metrics.http_request_ms}ms`;
    document.getElementById('metricParseMs').textContent = `${metrics.parsing_time_ms}ms`;
    document.getElementById('metricTotalMs').textContent = `${metrics.total_time_ms}ms`;
    
    // Metrics bar chart
    const total = metrics.total_time_ms || 1;
    const authPct = (metrics.auth_validation_ms / total) * 100;
    const httpPct = (metrics.http_request_ms / total) * 100;
    const parsePct = (metrics.parsing_time_ms / total) * 100;
    
    document.getElementById('barAuth').style.flex = authPct;
    document.getElementById('barHttp').style.flex = httpPct;
    document.getElementById('barParse').style.flex = parsePct;
    
    // Jobs per second
    if (metrics.jobs_per_second) {
        document.getElementById('jobsPerSecond').style.display = 'block';
        document.getElementById('jobsPerSecVal').textContent = metrics.jobs_per_second;
    } else {
        document.getElementById('jobsPerSecond').style.display = 'none';
    }
}

/**
 * Render validation section
 */
function renderValidationSection(validation) {
    const checks = [
        { id: 'checkApiKeys', key: 'api_keys_valid', label: 'API Keys Valid' },
        { id: 'checkHttpStatus', key: 'http_status_ok', label: 'HTTP Status OK' },
        { id: 'checkFormat', key: 'response_format_valid', label: 'Response Format Valid' },
        { id: 'checkJobs', key: 'jobs_array_found', label: 'Jobs Array Found' },
        { id: 'checkSample', key: 'sample_job_valid', label: 'Sample Job Valid' },
        { id: 'checkAll', key: 'all_checks_passed', label: 'All Checks Passed' }
    ];
    
    checks.forEach(check => {
        const element = document.getElementById(check.id);
        const passed = validation[check.key] === true;
        
        element.classList.toggle('failed', !passed);
        
        const icon = passed ? '✓' : '✗';
        element.querySelector('.check-icon').textContent = icon;
    });
}

/**
 * Render errors section
 */
function renderErrorsSection(errors) {
    const section = document.getElementById('errorsSection');
    const list = document.getElementById('errorsWarningsList');
    
    section.style.display = 'block';
    list.innerHTML = '';
    
    if (errors.messages && errors.messages.length > 0) {
        errors.messages.forEach(msg => {
            const div = document.createElement('div');
            div.className = errors.has_errors ? 'error-item' : 'warning-item';
            div.innerHTML = `<p>${escapeHtml(msg)}</p>`;
            list.appendChild(div);
        });
    }
}

/**
 * Toggle auto-refresh
 */
function toggleAutoRefresh() {
    autoRefreshEnabled = document.getElementById('autoRefreshCheck').checked;
    
    if (autoRefreshEnabled) {
        // Start auto-refresh
        autoRefreshInterval = setInterval(() => {
            refreshSourceDetails();
        }, AUTO_REFRESH_DELAY);
        
        console.log('Auto-refresh enabled');
    } else {
        // Stop auto-refresh
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        
        console.log('Auto-refresh disabled');
    }
}

/**
 * Manually refresh source details
 */
async function refreshSourceDetails() {
    if (!currentSourceId) return;
    
    console.log('Refreshing source details...');
    
    try {
        // Fetch updated data
        const response = await fetch(`/jobbly/src/test_source.php?source=${currentSourceId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        currentTestResult = data;
        
        // Fetch source definition for full documentation
        const sourceDef = await loadSourceDefinition(currentSourceId);
        
        // Render updates
        renderDetailsPage(data, sourceDef);
        
        // Update timestamp
        updateDetailsLastUpdate();
        
        // Show success feedback
        showRefreshFeedback();
        
    } catch (error) {
        console.error('Error refreshing details:', error);
        showRefreshError(error);
    }
}

/**
 * Update last update timestamp on details page
 */
function updateDetailsLastUpdate() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    document.getElementById('detailsLastUpdate').textContent = 
        `Last updated: ${timeStr}`;
}

/**
 * Show refresh feedback
 */
function showRefreshFeedback() {
    const btn = document.getElementById('manualRefreshBtn');
    const original = btn.textContent;
    
    btn.textContent = '✓ Updated!';
    btn.style.backgroundColor = '#28a745';
    
    setTimeout(() => {
        btn.textContent = original;
        btn.style.backgroundColor = '';
    }, 2000);
}

/**
 * Show refresh error
 */
function showRefreshError(error) {
    const btn = document.getElementById('manualRefreshBtn');
    const original = btn.textContent;
    
    btn.textContent = '✗ Error!';
    btn.style.backgroundColor = '#dc3545';
    
    setTimeout(() => {
        btn.textContent = original;
        btn.style.backgroundColor = '';
    }, 2000);
}

/**
 * Toggle collapsible section
 */
function toggleCollapsible(btn) {
    btn.classList.toggle('active');
    
    const content = btn.nextElementSibling;
    if (content && content.classList.contains('collapsible-content')) {
        const isHidden = content.style.display === 'none';
        content.style.display = isHidden ? 'block' : 'none';
    }
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format bytes to human readable format
 */
function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Clean up on unload
window.addEventListener('beforeunload', () => {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
});

console.log('Test endpoint details script loaded');
