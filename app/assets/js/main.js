/**
 * main.js
 * Responsibility: General UI logic, modals, toasts, and shared interactions
 */

const API_URL = 'API_Ops.php';

// ─── TOAST NOTIFICATIONS ────────────────────────────────────
function showToast(message, type = 'info', duration = 3000) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const iconMap = { success: 'check_circle', error: 'error', info: 'info' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined">${iconMap[type] || 'info'}</span>
        <span>${message}</span>
    `;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => toast.remove());
    }, duration);
}

// ─── LOGIN MODAL ─────────────────────────────────────────────
function showLoginModal() {
    if (document.querySelector('.modal-overlay')) return;

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
        <div class="modal-box">
            <span class="material-symbols-outlined">lock</span>
            <h3>Sign in to save jobs</h3>
            <p>Create a free account to save your favourite jobs and track applications.</p>
            <div class="modal-actions">
                <button class="btn-ghost" onclick="this.closest('.modal-overlay').remove()">Maybe later</button>
                <a href="login.php" class="btn-primary" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">Sign in</a>
            </div>
        </div>
    `;
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
}

// ─── SAVE / UNSAVE JOB (AJAX) ────────────────────────────────
async function toggleSavePost(jobId, btn) {
    const isSaved = btn.classList.contains('saved');
    const action = isSaved ? 'unsave_job' : 'save_job';

    btn.style.pointerEvents = 'none';
    btn.style.opacity = '0.5';

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, job_id: jobId })
        });
        const data = await res.json();

        if (data.success) {
            btn.classList.toggle('saved');
            showToast(isSaved ? 'Job removed from saved' : 'Job saved!', isSaved ? 'info' : 'success');

            // If in saved view and unsaving, handle the DOM removal gracefully
            if (isSaved && typeof currentView !== 'undefined' && currentView === 'saved') {
                const row = btn.closest('.job-row');
                if (row) {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(16px)';
                    setTimeout(() => row.remove(), 350);
                }
            }
        } else if (data.message === 'Unauthorized' || data.message === 'Login required') {
            showLoginModal();
        } else {
            showToast(data.message || 'Something went wrong', 'error');
        }
    } catch (e) {
        console.error('Save failed', e);
        showToast('Network error. Please try again.', 'error');
    } finally {
        btn.style.pointerEvents = '';
        btn.style.opacity = '';
    }
}

/**
 * Utility: HTML Escaping to prevent XSS (Requirement: Good Practices)
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ─── KEYBOARD: ESC CLOSES MODAL ──────────────────────────────
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const overlay = document.querySelector('.modal-overlay');
        if (overlay) overlay.remove();
    }
});
