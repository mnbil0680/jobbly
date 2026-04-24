/**
 * API_Ops.js
 * 
 * This file handles all Single-Page Application (SPA) interactions using AJAX.
 * It fulfills Requirement #3 (AJAX & Server-Side Logic) and Requirement #4 (Third-Party API Integration).
 * 
 * Responsibilities:
 * 1. Send asynchronous Fetch requests to the PHP server (API_Ops.php).
 * 2. Update the UI dynamically without full page reloads.
 * 3. Handle data from the MySQL database and Third-Party APIs.
 */

// Global state tracking
let currentView = 'explore';
let currentSearchTerm = '';
let currentPage = 1;

/**
 * READ Operation (AJAX)
 * Fetches job data from the server based on the current view, page, and search query.
 */
async function fetchJobs(view = 'explore', page = 1, search = '') {
    currentView = view;
    currentPage = page;
    currentSearchTerm = search;

    const container = document.getElementById('jobsContainer');

    if (container) {
        container.innerHTML = `
            <div class="loader-container">
                <div class="spinner"></div>
                <p>Loading jobs from database...</p>
            </div>`;
    }

    try {
        // Cancel previous request
        if (window.currentAbortController) {
            window.currentAbortController.abort();
        }

        window.currentAbortController = new AbortController();

        const params = new URLSearchParams({
            action: 'read',
            view,
            page
        });

        if (search?.trim()) {
            params.append('search', search.trim());
        }

        const controller = window.currentAbortController;
        const timeout = setTimeout(() => controller.abort(), 10000);

        const response = await fetch(`API_Ops.php?${params.toString()}`, {
            signal: controller.signal
        });

        clearTimeout(timeout);

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to fetch jobs');
        }

        renderJobs(data);
        updatePagination(data);
        updateURL(view, page, search);

    } catch (error) {
        console.error('Fetch Error:', error);

        if (error.name === 'AbortError') {
            showToast('Request timed out. Try again.', 'error');
        } else {
            showToast(error.message || 'Connection failed', 'error');
        }
    }
}
/**
 * RENDERING Logic
 * Dynamically updates the HTML content (Requirement #1: SPA behavior)
 */
function renderJobs(data) {
    const container = document.getElementById('jobsContainer');
    const headTitle = document.querySelector('.section-head h2');

    if (headTitle) {
        headTitle.textContent = data.view === 'saved' ? `Saved Jobs (${data.total})` : `Recent Listings (${data.total})`;
    }

    if (data.jobs.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-outlined" style="font-size: 48px; color: var(--text-muted);">search_off</span>
                <h3>No data found</h3>
                <p>There are no jobs matching your criteria in our database.</p>
            </div>`;
        return;
    }

    // Build the job rows (Requirement: HTML output escaped to prevent XSS)
    container.innerHTML = data.jobs.map(job => `
        <article class="job-row group" onclick="window.location.href='job_details.php?id=${job.id}'">
            <div class="job-main">
                <div class="job-logo">${escapeHtml(job.company.charAt(0).toUpperCase())}</div>
                <div>
                    <h3>${escapeHtml(job.title)}</h3>
                    <div class="job-company">${escapeHtml(job.company)}</div>
                    <div class="job-meta">
                        <span><span class="material-symbols-outlined">business</span>${escapeHtml(job.company)}</span>
                        <span><span class="material-symbols-outlined">location_on</span>${escapeHtml(job.location || 'Remote')}</span>
                    </div>
                </div>
            </div>
            <div class="job-side">
                <span class="badge">${escapeHtml(job.salary)}</span>
                <button class="save-btn ${job.isSaved ? 'saved' : ''}" 
                        onclick="event.stopPropagation(); toggleSavePost(${job.id}, this)">
                    <span class="material-symbols-outlined">favorite</span>
                </button>
            </div>
        </article>
    `).join('');
}

/**
 * PAGINATION Logic (AJAX)
 */
function updatePagination(data) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    if (data.totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let buttons = '';

    // Simple sliding window for pagination
    for (let i = 1; i <= data.totalPages; i++) {
        if (i === 1 || i === data.totalPages || (i >= data.page - 2 && i <= data.page + 2)) {
            buttons += `<button onclick="fetchJobs('${data.view}', ${i}, '${escapeHtml(currentSearchTerm)}')" 
                         class="pagination-item ${data.page === i ? 'active' : ''}">${i}</button>`;
        } else if (i === data.page - 3 || i === data.page + 3) {
            buttons += `<span class="pagination-dot">...</span>`;
        }
    }

    container.innerHTML = buttons;
}

/**
 * THIRD-PARTY API INTEGRATION (Requirement #4)
 * Triggers a server-side cURL request to fetch fresh data from external APIs.
 */
async function syncExternalData(sourceId = null) {
    showToast('Syncing with external APIs...', 'info');
    try {
        const url = sourceId ? `API_Ops.php?action=sync&source_id=${sourceId}` : `API_Ops.php?action=sync`;
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            showToast(`Success! Found ${data.saved_count} new records.`, 'success');
            fetchJobs(currentView, 1, currentSearchTerm); // Refresh view
        } else {
            showToast(data.message || 'Sync failed', 'error');
        }
    } catch (e) {
        showToast('Failed to connect to API bridge', 'error');
    }
}

/**
 * Utility: Update URL without reloading (SPA Requirement)
 */
function updateURL(view, page, search) {
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    if (search) url.searchParams.set('search', search); else url.searchParams.delete('search');
    if (page > 1) url.searchParams.set('page', page); else url.searchParams.delete('page');
    window.history.pushState({}, '', url);
}

// Handle browser back/forward buttons
window.onpopstate = function () {
    const params = new URLSearchParams(window.location.search);
    fetchJobs(params.get('view') || 'explore', parseInt(params.get('page')) || 1, params.get('search') || '');
};
