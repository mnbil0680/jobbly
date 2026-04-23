<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .jobs-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .jobs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .job-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .job-info {
            flex: 1;
        }
        .job-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        .job-company {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }
        .job-date {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .job-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: var(--primary);
            color: white;
        }
        .btn-small:hover {
            opacity: 0.9;
        }
        .btn-danger-small {
            background-color: #dc3545;
        }
        .btn-outline-small {
            background: white;
            border: 1px solid var(--border);
            color: var(--text);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }
    </style>
</head>
<body class="bg-surface">
    <?php include 'header.php'; ?>

    <main class="jobs-container">
        <div class="jobs-header">
            <h1>My Jobs</h1>
            <button class="btn-primary" onclick="showCreateModal()">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 0.5rem;">add</span>Create Job
            </button>
        </div>

        <div id="jobsList"></div>

        <!-- Create/Edit Job Modal -->
        <div id="jobModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Create New Job</h2>
                    <button class="modal-close" onclick="hideJobModal()">&times;</button>
                </div>
                <form id="jobForm">
                    <div class="form-group">
                        <label for="jobTitle">Job Title *</label>
                        <input type="text" id="jobTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jobCompany">Company Name *</label>
                        <input type="text" id="jobCompany" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="jobCategory">Category *</label>
                        <select id="jobCategory" class="form-control" required></select>
                    </div>
                    <div class="form-group">
                        <label for="jobLocation">Location</label>
                        <input type="text" id="jobLocation" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="jobType">Job Type</label>
                        <select id="jobType" class="form-control">
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Freelance">Freelance</option>
                            <option value="Temporary">Temporary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="jobDescription">Description</label>
                        <textarea id="jobDescription" class="form-control" rows="4"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="jobSalaryMin">Salary Min</label>
                            <input type="number" id="jobSalaryMin" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="jobSalaryMax">Salary Max</label>
                            <input type="number" id="jobSalaryMax" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="jobCurrency">Currency</label>
                        <input type="text" id="jobCurrency" class="form-control" value="USD">
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="button" class="btn-outline" onclick="hideJobModal()" style="padding: 0.75rem 1.5rem; border: 1px solid var(--border); background: white; cursor: pointer; border-radius: 4px;">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary" style="padding: 0.75rem 1.5rem; cursor: pointer; border: none; border-radius: 4px;">
                            Save Job
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content" style="max-width: 400px;">
                <h3>Delete Job</h3>
                <p>Are you sure you want to delete this job? This action cannot be undone.</p>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                    <button type="button" class="btn-outline" onclick="hideDeleteModal()" style="padding: 0.75rem 1.5rem; border: 1px solid var(--border); background: white; cursor: pointer; border-radius: 4px;">
                        Cancel
                    </button>
                    <button type="button" onclick="confirmDelete()" style="padding: 0.75rem 1.5rem; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        let categories = [];
        let currentJob = null;
        let jobToDelete = null;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadCategories();
            await loadJobs();
        });

        async function loadCategories() {
            try {
                const res = await fetch('API_Ops.php?action=read');
                const data = await res.json();
                // Try to get categories from a categories endpoint if available
                const categorySelect = document.getElementById('jobCategory');
                // For now, use common categories
                const commonCategories = [
                    { id: 1, name: 'Software Engineering' },
                    { id: 2, name: 'Marketing' },
                    { id: 3, name: 'Healthcare' },
                    { id: 4, name: 'Sales' }
                ];
                commonCategories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    categorySelect.appendChild(option);
                });
            } catch (err) {
                console.error(err);
            }
        }

        async function loadJobs() {
            try {
                // Fetch all jobs - in a real app, you'd want to filter by creator
                const res = await fetch('API_Ops.php?action=read');
                const data = await res.json();

                if (data.success && data.jobs && data.jobs.length > 0) {
                    displayJobs(data.jobs);
                } else {
                    document.getElementById('jobsList').innerHTML = `
                        <div class="empty-state">
                            <p>You haven't created any jobs yet.</p>
                            <p>Click "Create Job" to get started!</p>
                        </div>
                    `;
                }
            } catch (err) {
                console.error(err);
                document.getElementById('jobsList').innerHTML = '<p style="color: red;">Error loading jobs</p>';
            }
        }

        function displayJobs(jobs) {
            const jobsList = document.getElementById('jobsList');

            if (!jobs || jobs.length === 0) {
                jobsList.innerHTML = `
                    <div class="empty-state">
                        <p>You haven't created any jobs yet.</p>
                        <p>Click "Create Job" to get started!</p>
                    </div>
                `;
                return;
            }

            jobsList.innerHTML = jobs.map(job => `
                <div class="job-card">
                    <div class="job-info">
                        <h3 class="job-title">${job.title}</h3>
                        <p class="job-company">${job.company}</p>
                        <p class="job-date">Created: ${new Date(job.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="job-actions">
                        <button class="btn-small btn-outline-small" onclick="editJob(${job.id})">Edit</button>
                        <button class="btn-small btn-danger-small" onclick="showDeleteModal(${job.id})">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        function showCreateModal() {
            currentJob = null;
            document.getElementById('modalTitle').textContent = 'Create New Job';
            document.getElementById('jobForm').reset();
            document.getElementById('jobModal').style.display = 'flex';
        }

        async function editJob(jobId) {
            try {
                // In a real app, you'd fetch the job details
                // For now, we'll show a modal to edit
                currentJob = jobId;
                document.getElementById('modalTitle').textContent = 'Edit Job';
                document.getElementById('jobModal').style.display = 'flex';
            } catch (err) {
                console.error(err);
                alert('Error loading job');
            }
        }

        function hideJobModal() {
            document.getElementById('jobModal').style.display = 'none';
            document.getElementById('jobForm').reset();
            currentJob = null;
        }

        function showDeleteModal(jobId) {
            jobToDelete = jobId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            jobToDelete = null;
        }

        document.getElementById('jobForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const jobData = {
                title: document.getElementById('jobTitle').value,
                company_name: document.getElementById('jobCompany').value,
                category_id: document.getElementById('jobCategory').value,
                location: document.getElementById('jobLocation').value,
                job_type: document.getElementById('jobType').value,
                description: document.getElementById('jobDescription').value,
                salary_min: parseFloat(document.getElementById('jobSalaryMin').value) || 0,
                salary_max: parseFloat(document.getElementById('jobSalaryMax').value) || 0,
                currency: document.getElementById('jobCurrency').value,
                status: 'active'
            };

            try {
                const action = currentJob ? 'update_job' : 'create_job';
                if (currentJob) {
                    jobData.id = currentJob;
                }

                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, ...jobData })
                });
                const data = await res.json();

                if (data.success) {
                    alert(currentJob ? 'Job updated successfully!' : 'Job created successfully!');
                    hideJobModal();
                    await loadJobs();
                } else {
                    alert(data.message || 'Error saving job');
                }
            } catch (err) {
                console.error(err);
                alert('Error saving job');
            }
        });

        async function confirmDelete() {
            if (!jobToDelete) return;

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_job', id: jobToDelete })
                });
                const data = await res.json();

                if (data.success) {
                    alert('Job deleted successfully!');
                    hideDeleteModal();
                    await loadJobs();
                } else {
                    alert(data.message || 'Error deleting job');
                    hideDeleteModal();
                }
            } catch (err) {
                console.error(err);
                alert('Error deleting job');
                hideDeleteModal();
            }
        }
    </script>
</body>
</html>
