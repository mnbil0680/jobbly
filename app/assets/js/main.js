const API_URL = 'API_Ops.php';

document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    toggleLoader(true);
    loadJobs().finally(() => toggleLoader(false));
    
    // Sync new jobs from APIs in the background
    syncFetchedJobsToDatabase().then(() => {
        console.log('Background sync complete, refreshing jobs...');
        loadJobs();
    });
});

function bindEvents() {
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    if (searchInput) {
        searchInput.addEventListener('keyup', (event) => {
            if (event.key === 'Enter') {
                searchJobs();
            }
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchJobs);
    }
}

function toggleLoader(show) {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = show ? 'flex' : 'none';
}

async function loadJobs(search = '') {
    const container = document.getElementById('jobsContainer');
    if (search) toggleLoader(true);

    const jobs = await fetchJobs(search);
    if (search) toggleLoader(false);

    if (!jobs.length) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No saved jobs found</h3>
                <p>Try searching with a different keyword.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = '';
    jobs.forEach((job) => container.appendChild(createJobCard(job)));
}

async function fetchJobs(search = '') {
    try {
        const endpoint = search
            ? `${API_URL}?action=read&search=${encodeURIComponent(search)}`
            : `${API_URL}?action=read`;

        const response = await fetch(endpoint);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        if (!payload.success || !Array.isArray(payload.jobs)) {
            return [];
        }

        return payload.jobs;
    } catch (error) {
        console.error('Error reading saved jobs:', error);
        return [];
    }
}

async function searchJobs() {
    const searchInput = document.getElementById('searchInput');
    const value = searchInput ? searchInput.value.trim() : '';
    await loadJobs(value);
}

function createJobCard(job) {
    const card = document.createElement('article');
    card.className = 'job-row group';

    const safeTitle = escapeHtml(job.title || 'Untitled Role');
    const safeCompany = escapeHtml(job.company || 'Unknown Company');
    const safeLocation = escapeHtml(job.location || 'Unknown');
    const safeType = escapeHtml(job.jobType || 'N/A');
    const safeSalary = escapeHtml(job.salary || 'N/A');

    const isSaved = job.isSaved || false;

    card.innerHTML = `
        <div class="job-main">
            <div class="job-logo">${safeCompany.charAt(0).toUpperCase()}</div>
            <div>
                <h3>${safeTitle}</h3>
                <div class="job-meta">
                    <span><span class="material-symbols-outlined">business</span>${safeCompany}</span>
                    <span><span class="material-symbols-outlined">location_on</span>${safeLocation}</span>
                    <span><span class="material-symbols-outlined">work</span>${safeType}</span>
                </div>
            </div>
        </div>
        <div class="job-side">
            <span class="badge">${safeSalary}</span>
            <button class="save-btn ${isSaved ? 'saved' : ''}" type="button" aria-label="save job">
                <span class="material-symbols-outlined">favorite</span>
            </button>
            <button class="view-btn" type="button">View</button>
        </div>
    `;

    const saveBtn = card.querySelector('.save-btn');
    saveBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleSaveJob(job.id, saveBtn);
    });

    return card;
}

async function toggleSaveJob(jobId, btn) {
    const isCurrentlySaved = btn.classList.contains('saved');
    const action = isCurrentlySaved ? 'unsave_job' : 'save_job';
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, job_id: jobId })
        });
        const data = await response.json();
        if (data.success) {
            btn.classList.toggle('saved');
        } else {
            alert(data.message || 'Please login to save jobs');
            if (data.message === 'Login required') window.location.href = 'login.php';
        }
    } catch (err) {
        console.error(err);
    }
}

async function syncFetchedJobsToDatabase() {
    try {
        await fetch(`${API_URL}?action=sync`);
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}