const API_URL = 'API_Ops.php';

document.addEventListener('DOMContentLoaded', () => {
    // Standard form submission is now preferred for pagination support
});

async function performSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    const query = searchInput ? searchInput.value.trim() : '';
    const container = document.getElementById('jobsContainer');
    
    // Show loading state
    container.style.opacity = '0.5';

    try {
        const response = await fetch(`${API_URL}?action=read&search=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.success) {
            renderJobs(data.jobs);
        }
    } catch (e) {
        console.error('Search failed', e);
    } finally {
        container.style.opacity = '1';
    }
}

function renderJobs(jobs) {
    const container = document.getElementById('jobsContainer');
    if (jobs.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No jobs found</h3>
                <p>Try searching with a different keyword.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = jobs.map(job => `
        <article class="job-row group">
            <div class="job-main">
                <div class="job-logo">${job.company.charAt(0).toUpperCase()}</div>
                <div>
                    <h3>${escapeHtml(job.title)}</h3>
                    <div class="job-meta">
                        <span><span class="material-symbols-outlined">business</span>${escapeHtml(job.company)}</span>
                        <span><span class="material-symbols-outlined">location_on</span>${escapeHtml(job.location || 'Remote')}</span>
                        <span><span class="material-symbols-outlined">work</span>${escapeHtml(job.jobType || 'N/A')}</span>
                    </div>
                </div>
            </div>
            <div class="job-side">
                <span class="badge">${escapeHtml(job.salary || 'N/A')}</span>
                <button class="save-btn ${job.isSaved ? 'saved' : ''}" onclick="toggleSavePost(${job.id}, this)">
                    <span class="material-symbols-outlined">favorite</span>
                </button>
                <button class="view-btn" type="button">View</button>
            </div>
        </article>
    `).join('');
}

async function toggleSavePost(jobId, btn) {
    const isSaved = btn.classList.contains('saved');
    const action = isSaved ? 'unsave_job' : 'save_job';
    
    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, job_id: jobId })
        });
        const data = await res.json();
        if (data.success) {
            btn.classList.toggle('saved');
        } else if (data.message === 'Login required') {
            window.location.href = 'login.php';
        }
    } catch (e) {
        console.error('Save failed', e);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
