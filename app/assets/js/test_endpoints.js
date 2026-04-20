// **================================================**
// ** File: test_endpoints.js                        **
// ** Responsibility: Test Endpoints main page logic **
// ** - Load and display all 15 sources              **
// ** - Handle card clicks and navigation            **
// ** - Real-time AJAX data fetching                 **
// ** - Loading states and error handling            **
// **================================================**

let allSourcesData = [];
let testRefreshInterval = null;

/**
 * Load all test endpoints page
 */
function showTestEndpoints() {
    console.log('=== showTestEndpoints() called ===');
    
    // Hide all other containers
    const jobsContainer = document.getElementById('jobsContainer');
    const testDetailsContainer = document.getElementById('testDetailsContainer');
    const testEndpointsContainer = document.getElementById('testEndpointsContainer');
    
    console.log('jobsContainer:', jobsContainer);
    console.log('testDetailsContainer:', testDetailsContainer);
    console.log('testEndpointsContainer:', testEndpointsContainer);
    
    if (jobsContainer) {
        jobsContainer.style.display = 'none';
        console.log('Hidden jobsContainer');
    }
    
    if (testDetailsContainer) {
        testDetailsContainer.style.display = 'none';
        console.log('Hidden testDetailsContainer');
    }
    
    // Show test endpoints container
    if (testEndpointsContainer) {
        testEndpointsContainer.style.display = 'block';
        console.log('Shown testEndpointsContainer');
    } else {
        console.error('testEndpointsContainer not found!');
        return;
    }
    
    // Load sources
    console.log('Calling loadAllSources()...');
    loadAllSources();
}

/**
 * Load all sources from the test endpoint
 */
async function loadAllSources() {
    const sourcesGrid = document.getElementById('sourcesGrid');
    const loadingMsg = document.getElementById('loadingMessage');
    const errorMsg = document.getElementById('errorMessage');
    
    // Show loading
    loadingMsg.style.display = 'block';
    sourcesGrid.innerHTML = '';
    errorMsg.style.display = 'none';
    
    try {
        // Fetch all sources data
        const response = await fetch('/jobbly/src/test_source.php?all=true');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        allSourcesData = data.results || [];
        
        // Hide loading
        loadingMsg.style.display = 'none';
        
        // Update summary stats
        updateSummaryStats(data);
        
        // Display cards
        displaySourceCards(allSourcesData);
        
        // Update last updated time
        updateLastUpdateTime();
        
    } catch (error) {
        console.error('Error loading sources:', error);
        loadingMsg.style.display = 'none';
        
        // Show error message
        errorMsg.style.display = 'block';
        document.getElementById('errorText').textContent = 
            'Failed to load sources: ' + error.message;
        
        // Create cards from job_sources.json anyway
        loadSourceDefinitions();
    }
}

/**
 * Load source definitions from job_sources.json
 */
async function loadSourceDefinitions() {
    try {
        const response = await fetch('/jobbly/src/job_sources.json');
        const sources = await response.json();
        
        const sourcesGrid = document.getElementById('sourcesGrid');
        sourcesGrid.innerHTML = '';
        
        sources.forEach(source => {
            if (source.enabled) {
                const card = createSourceCard({
                    source: {
                        id: source.id,
                        name: source.name,
                        description: source.description
                    },
                    status: 'pending',
                    errors: { has_errors: false }
                });
                sourcesGrid.appendChild(card);
            }
        });
    } catch (error) {
        console.error('Error loading source definitions:', error);
    }
}

/**
 * Click handler for source cards
 */
function handleCardClick(sourceId) {
    console.log('=== CARD CLICKED (via onclick) ===');
    console.log('Source ID:', sourceId);
    showSourceDetails(sourceId);
}

// Track if listener already added
let gridClickListenerAdded = false;

/**
 * Display source cards
 */
function displaySourceCards(sources) {
    const sourcesGrid = document.getElementById('sourcesGrid');
    if (!sourcesGrid) {
        console.error('sourcesGrid element not found!');
        return;
    }
    
    // Clear existing content
    sourcesGrid.innerHTML = '';
    
    console.log('=== Displaying', sources.length, 'source cards ===');
    
    sources.forEach((testResult, index) => {
        const card = createSourceCard(testResult);
        // Add direct click handler to each card as well
        card.onclick = function(e) {
            console.log('=== DIRECT CARD ONCLICK FIRED ===');
            e.stopPropagation();
            showSourceDetails(testResult.source.id);
        };
        sourcesGrid.appendChild(card);
        console.log(`Card ${index + 1}: ${testResult.source.name} (ID: ${testResult.source.id})`);
    });
    
    console.log('All cards created and added to grid');
    console.log('Each card has onclick handler attached');
}

/**
 * Create a source card DOM element
 */
function createSourceCard(testResult) {
    const card = document.createElement('div');
    card.className = 'source-card';
    card.setAttribute('role', 'button');
    card.setAttribute('tabindex', '0');
    
    const source = testResult.source;
    const status = testResult.status || 'pending';
    const hasErrors = testResult.errors && testResult.errors.has_errors;
    
    // Determine card styling
    if (status === 'pending') {
        card.classList.add('loading');
    } else if (hasErrors) {
        card.classList.add('failed');
    } else {
        card.classList.add('passed');
    }
    
    // Build status icon and text
    let statusIcon = '⟳';
    let statusText = 'Testing...';
    let statusClass = '';
    
    if (testResult.parsing && testResult.parsing.jobs_found !== undefined) {
        // Passed
        statusIcon = '✓';
        statusText = 'Working';
        statusClass = 'success';
    } else if (hasErrors) {
        // Failed
        statusIcon = '✗';
        statusText = 'Failed';
        statusClass = 'error';
    } else if (testResult.validation && testResult.validation.api_keys_valid === false) {
        // Skipped (missing keys)
        statusIcon = '⊘';
        statusText = 'Skipped';
        statusClass = 'warning';
    }
    
    // Build badges
    let badgesHTML = '';
    if (testResult.parsing && testResult.parsing.jobs_found !== undefined) {
        badgesHTML += `
            <div class="badge badge-jobs">
                📊 ${testResult.parsing.jobs_found} jobs
            </div>
        `;
    }
    
    if (testResult.metrics && testResult.metrics.total_time_ms) {
        badgesHTML += `
            <div class="badge badge-latency">
                ⚡ ${testResult.metrics.total_time_ms}ms
            </div>
        `;
    }
    
    if (hasErrors && testResult.errors && testResult.errors.messages && testResult.errors.messages[0]) {
        badgesHTML += `
            <div class="badge badge-error">
                ${testResult.errors.messages[0].substring(0, 30)}...
            </div>
        `;
    }
    
    card.innerHTML = `
        <div class="source-card-name">${escapeHtml(source.name)}</div>
        <div class="source-card-status">
            <span class="status-icon ${statusClass}">${statusIcon}</span>
            <span class="status-text">${statusText}</span>
        </div>
        <div class="source-card-badges">
            ${badgesHTML}
        </div>
    `;
    
    // Add data attribute for event delegation
    card.setAttribute('data-source-id', source.id);
    card.setAttribute('data-source-name', source.name);
    card.title = `Click to view details for ${source.name}`;
    card.style.cursor = 'pointer';
    
    return card;
}

/**
 * Update summary statistics
 */
function updateSummaryStats(data) {
    const summary = data.summary || {};
    
    document.getElementById('passedCount').textContent = summary.ok || 0;
    document.getElementById('failedCount').textContent = summary.failed || 0;
    document.getElementById('skippedCount').textContent = summary.skipped || 0;
    
    // Calculate total jobs
    let totalJobs = 0;
    (data.results || []).forEach(result => {
        if (result.parsing && result.parsing.jobs_found) {
            totalJobs += result.parsing.jobs_found;
        }
    });
    
    document.getElementById('totalJobs').textContent = totalJobs;
}

/**
 * Update last update timestamp
 */
function updateLastUpdateTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    document.getElementById('lastUpdateTime').textContent = 
        `Last tested: ${timeStr}`;
}

/**
 * Refresh all sources
 */
async function refreshAllSources() {
    console.log('Refreshing all sources...');
    await loadAllSources();
}

/**
 * Show source details page
 */
function showSourceDetails(sourceId) {
    console.log('=== showSourceDetails called ===');
    console.log('Source ID:', sourceId);
    
    if (!sourceId) {
        console.error('No source ID provided!');
        return;
    }
    
    // Hide test endpoints page
    const testContainer = document.getElementById('testEndpointsContainer');
    if (testContainer) {
        testContainer.style.display = 'none';
        console.log('Hidden test endpoints container');
    }
    
    // Show details container
    const detailsContainer = document.getElementById('testDetailsContainer');
    if (detailsContainer) {
        detailsContainer.style.display = 'block';
        console.log('Shown details container');
    } else {
        console.error('Details container not found!');
        return;
    }
    
    // Load details
    console.log('Loading source details for:', sourceId);
    loadSourceDetails(sourceId);
}

// Expose function globally
window.showSourceDetails = showSourceDetails;

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

// Expose functions globally for onclick in HTML
window.showTestEndpoints = showTestEndpoints;
window.showSourceDetails = showSourceDetails;
window.refreshAllSources = refreshAllSources;
window.handleCardClick = handleCardClick;

// Initialize when page loads
console.log('Test endpoints script loading...');

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Test endpoints DOM loaded');
    });
} else {
    console.log('Test endpoints script ready');
}
