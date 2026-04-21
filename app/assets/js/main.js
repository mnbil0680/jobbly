// **================================================**
// ** File: main.js                                  **
// ** Responsibility: Main application logic         **
// ** - Load and display jobs                        **
// ** - Handle form submissions (Create/Update)      **
// ** - Handle search and filtering                  **
// ** - Handle delete operations                     **
// **================================================**

// Store current job being edited
let currentEditId = null;

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, calling loadJobs()');
    loadJobs();
});

/**
 * Load and display all jobs
 */
async function loadJobs() {
    const jobsContainer = document.getElementById('jobsContainer');
    jobsContainer.innerHTML = '<p class="loading">Loading jobs...</p>';

    console.log('Fetching jobs from API...');
    const jobs = await fetchJobs();
    console.log('Jobs received:', jobs);

    if (jobs.length === 0) {
        jobsContainer.innerHTML = `
            <div class="empty-state">
                <h3>No jobs found</h3>
                <p>Add a new job using the form above to get started.</p>
            </div>
        `;
        return;
    }

    jobsContainer.innerHTML = '';
    jobs.forEach(job => {
        const jobCard = createJobCard(job);
        jobsContainer.appendChild(jobCard);
    });
}

/**
 * Create a job card DOM element
 */
function createJobCard(job) {
    const card = document.createElement('div');
    card.className = 'job-card';
    card.innerHTML = `
        <h3>${escapeHtml(job.title || 'Untitled')}</h3>
        <p class="company">${escapeHtml(job.company || 'Company Unknown')}</p>
        <div class="meta">
            <span class="meta-item">📍 ${escapeHtml(job.location || 'Location Not Specified')}</span>
            <span class="meta-item">💼 ${escapeHtml(job.jobType || 'Type Not Specified')}</span>
        </div>
        <p class="description">${escapeHtml(job.description || '').substring(0, 150)}...</p>
        ${job.salary ? `<div class="salary">💰 ${escapeHtml(job.salary)}</div>` : ''}
        <div class="actions">
            <button class="btn btn-primary btn-small" onclick="editJob(${job.id})">Edit</button>
            <button class="btn btn-danger btn-small" onclick="deleteJobConfirm(${job.id})">Delete</button>
        </div>
    `;
    return card;
}

/**
 * Handle form submission (Create or Update)
 */
async function submitJobForm(event) {
    event.preventDefault();

    const jobData = {
        title: document.getElementById('jobTitle').value,
        company: document.getElementById('company').value,
        location: document.getElementById('location').value,
        jobType: document.getElementById('jobType').value,
        description: document.getElementById('description').value,
        salary: document.getElementById('salary').value
    };

    let success = false;

    if (currentEditId) {
        // Update existing job
        success = await updateJob(currentEditId, jobData);
    } else {
        // Create new job
        success = await createJob(jobData);
    }

    if (success) {
        resetForm();
        await loadJobs();
    }
}

/**
 * Load job data into form for editing
 */
async function editJob(jobId) {
    const jobs = await fetchJobs();
    const job = jobs.find(j => j.id === jobId);

    if (!job) {
        showAlert('Job not found', 'danger');
        return;
    }

    // Populate form fields
    document.getElementById('jobId').value = job.id;
    document.getElementById('jobTitle').value = job.title || '';
    document.getElementById('company').value = job.company || '';
    document.getElementById('location').value = job.location || '';
    document.getElementById('jobType').value = job.jobType || '';
    document.getElementById('description').value = job.description || '';
    document.getElementById('salary').value = job.salary || '';

    // Update form UI
    document.getElementById('formTitle').textContent = 'Edit Job';
    document.getElementById('submitBtn').textContent = 'Update Job';
    currentEditId = jobId;

    // Scroll to form
    document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Delete job with confirmation
 */
function deleteJobConfirm(jobId) {
    if (confirm('Are you sure you want to delete this job?')) {
        deleteJobAction(jobId);
    }
}

/**
 * Actually delete the job
 */
async function deleteJobAction(jobId) {
    const success = await deleteJob(jobId);
    if (success) {
        await loadJobs();
    }
}

/**
 * Reset the form to initial state
 */
function resetForm() {
    document.getElementById('jobForm').reset();
    document.getElementById('jobId').value = '';
    document.getElementById('formTitle').textContent = 'Add New Job';
    document.getElementById('submitBtn').textContent = 'Add Job';
    currentEditId = null;
}

/**
 * Search jobs based on input
 */
async function searchJobs() {
    const searchTerm = document.getElementById('searchInput').value;

    if (!searchTerm.trim()) {
        await loadJobs();
        return;
    }

    const jobsContainer = document.getElementById('jobsContainer');
    const jobs = await fetchJobs({ search: searchTerm });

    if (jobs.length === 0) {
        jobsContainer.innerHTML = `
            <div class="empty-state">
                <h3>No jobs found</h3>
                <p>Try a different search term.</p>
            </div>
        `;
        return;
    }

    jobsContainer.innerHTML = '';
    jobs.forEach(job => {
        const jobCard = createJobCard(job);
        jobsContainer.appendChild(jobCard);
    });
}

/**
 * Reset search and reload all jobs
 */
async function resetSearch() {
    document.getElementById('searchInput').value = '';
    await loadJobs();
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
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
 * Show jobs page
 */
function showJobsPage() {
    console.log('Showing jobs page...');

    // Hide all other containers
    document.getElementById('testEndpointsContainer').style.display = 'none';
    document.getElementById('testDetailsContainer').style.display = 'none';

    // Show jobs page
    const jobsPage = document.getElementById('jobsPage');
    if (jobsPage) {
        jobsPage.style.display = 'block';
    }

    // Load jobs
    loadJobs();
}

/**
 * Load a page (for future navigation)
 */
function loadPage(page) {
    console.log('Loading page:', page);

    if (page === 'jobs') {
        showJobsPage();
    }
    // Placeholder for future multi-page navigation
}
