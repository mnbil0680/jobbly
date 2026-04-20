// **================================================**
// ** File: API_Ops.js                               **
// ** Responsibility: AJAX calls for CRUD operations **
// ** - Send fetch requests to API_Ops.php           **
// ** - Handle success responses and update UI       **
// ** - Handle errors and show user-friendly messages**
// **================================================**

const API_BASE_URL = '../../API_Ops.php';

/**
 * Fetch all jobs from the API
 */
async function fetchJobs(filters = {}) {
    try {
        const params = new URLSearchParams({
            action: 'read',
            ...filters
        });
        
        const url = `${API_BASE_URL}?${params}`;
        console.log('Fetching from URL:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('API Response:', data);
        return data.success ? data.jobs : [];
    } catch (error) {
        console.error('Error fetching jobs:', error);
        showAlert('Failed to load jobs. Please try again.', 'danger');
        return [];
    }
}

/**
 * Create a new job
 */
async function createJob(jobData) {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create',
                ...jobData
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('Job added successfully!', 'success');
            return true;
        } else {
            showAlert(data.message || 'Failed to add job', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Error creating job:', error);
        showAlert('Error adding job. Please try again.', 'danger');
        return false;
    }
}

/**
 * Update an existing job
 */
async function updateJob(jobId, jobData) {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update',
                id: jobId,
                ...jobData
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('Job updated successfully!', 'success');
            return true;
        } else {
            showAlert(data.message || 'Failed to update job', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Error updating job:', error);
        showAlert('Error updating job. Please try again.', 'danger');
        return false;
    }
}

/**
 * Delete a job
 */
async function deleteJob(jobId) {
    try {
        const response = await fetch(API_BASE_URL, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                id: jobId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('Job deleted successfully!', 'success');
            return true;
        } else {
            showAlert(data.message || 'Failed to delete job', 'danger');
            return false;
        }
    } catch (error) {
        console.error('Error deleting job:', error);
        showAlert('Error deleting job. Please try again.', 'danger');
        return false;
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
