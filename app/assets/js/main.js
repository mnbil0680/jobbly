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
 * fetch jobs
 */
async function fetchJobs(filters = {}) {
    try {
        const res = await fetch('/jobbly/src/fetch_sources.php');

        if (!res.ok) {
            throw new Error("HTTP error " + res.status);
        }

        const data = await res.json();

        console.log("API response:", data);

        const jobs = [];

        data.results.forEach(source => {
            if (source.sample_job) {
                jobs.push({
                    id: source.source_id,
                    title: source.sample_job.title,
                    company: source.sample_job.company,
                    location: source.sample_job.location,
                    jobType: source.sample_job.job_type || "N/A",
                    description: "",
                    salary: "",
                    apply_url: source.sample_job.apply_url || source.sample_job.link
                });
            }
        });

        return jobs;

    } catch (error) {
        console.error("Error fetching jobs:", error);
        return [];
    }
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
 * create card
 */
function createJobCard(job) {
    const card = document.createElement("div");
    card.className = "job-card";

    card.innerHTML = `
        <h3>${job.title}</h3>
        <p class="company">${job.company}</p>

        <div class="meta">
            <span class="meta-item">📍 ${job.location}</span>
            <span class="meta-item">💼 ${job.jobType}</span>
        </div>

        <p class="description">${job.description?.substring(0, 120) ?? ""}...</p>

        ${job.salary ? `<div class="salary">💰 ${job.salary}</div>` : ""}
    `;

    return card;
}

/**
 * header navigation to a specific section
 */
function navigateTo(page) {
    const homeSection = document.querySelector(".search-section");
    const jobsSection = document.querySelector(".display-section");
    const aboutSection = document.getElementById("aboutSection");

    setActivePage(page);

    if (page === "home") {
        homeSection.style.display = "block";
        jobsSection.style.display = "block";
        aboutSection.style.display = "none";

        loadJobs();
    }

    if (page === "about") {
        homeSection.style.display = "none";
        jobsSection.style.display = "none";
        aboutSection.style.display = "flex";
    }

    if (page === "saved") {
        homeSection.style.display = "none";
        jobsSection.style.display = "block";
        aboutSection.style.display = "none";
    }

    if (page === "profile") {
        homeSection.style.display = "none";
        jobsSection.style.display = "none";
        aboutSection.style.display = "none";
    }
}

/**
 * setting the current active page
 */
function setActivePage(page) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');

        if (link.dataset.page === page) {
            link.classList.add('active');
        }
    });
}

/**
 * initial active page is the 'home page'
 */
document.addEventListener("DOMContentLoaded", () => {
    setActivePage("home");
});

/**
 * Search
 */
async function searchJobs() {
    const value = document.getElementById("searchInput").value;

    const jobs = await fetchJobs({ search: value });

    const container = document.getElementById("jobsContainer");

    if (!jobs.length) {
        container.innerHTML = `
            <div class="empty-state">
                <h3>No results found</h3>
            </div>
        `;
        return;
    }

    container.innerHTML = "";
    jobs.forEach(job => container.appendChild(createJobCard(job)));
}