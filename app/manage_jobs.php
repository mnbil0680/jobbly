<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        .jobs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .jobs-table th, .jobs-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        .jobs-table th {
            color: var(--text-muted);
            font-weight: 500;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background: var(--primary);
            color: white;
            margin-right: 5px;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
    </style>
</head>
<body class="bg-surface">
    <?php include 'header.php'; ?>
    <?php
        if (!$isLoggedIn) {
            header('Location: login.php');
            exit;
        }
        require_once 'DB_Ops.php';
        $db = new JobsDatabase();
        $myJobs = $db->getJobsByPosterId('user_' . $_SESSION['user_id']);
    ?>

    <main class="main-layout">
        <div class="auth-container" style="max-width: 900px; padding: 30px;">
            <h2>Manage Your Job Listings</h2>
            <p class="auth-subtitle">View, edit or delete jobs you have posted manually.</p>
            
            <?php if (empty($myJobs)): ?>
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <p>You haven't posted any jobs yet.</p>
                    <a href="create_job.php" class="btn-primary" style="display: inline-block; margin-top: 10px;">Create a Job</a>
                </div>
            <?php else: ?>
                <table class="jobs-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Posted On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myJobs as $job): ?>
                            <tr id="job-row-<?php echo $job['id']; ?>">
                                <td><strong><?php echo htmlspecialchars($job['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn-sm btn-edit">Edit</a>
                                    <button class="btn-sm btn-delete" onclick="deleteJob(<?php echo $job['id']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script>
        async function deleteJob(id) {
            if (!confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                return;
            }
            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete_job', id: id })
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof showToast !== 'undefined') showToast('Job deleted successfully', 'success');
                    else alert('Job deleted successfully');
                    
                    const row = document.getElementById('job-row-' + id);
                    if (row) row.remove();
                } else {
                    if (typeof showToast !== 'undefined') showToast(data.message || 'Failed to delete job', 'error');
                    else alert(data.message || 'Failed to delete job');
                }
            } catch (err) {
                console.error(err);
                if (typeof showToast !== 'undefined') showToast('Error: ' + err.message, 'error');
                else alert('Error: ' + err.message);
            }
        }
    </script>
<script src="assets/js/main.js"></script>
</body>
</html>
