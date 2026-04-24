<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <?php include 'header.php'; ?>
    <?php
        if (!$isLoggedIn) {
            header('Location: login.php');
            exit;
        }
        if (empty($_GET['id'])) {
            header('Location: manage_jobs.php');
            exit;
        }
        
        require_once 'DB_Ops.php';
        $db = new JobsDatabase();
        $jobId = (int)$_GET['id'];
        $job = $db->getJobById($jobId);
        
        // Ensure user owns this job
        if (!$job || $job['poster_id'] !== 'user_' . $_SESSION['user_id']) {
            echo "<div style='text-align:center; padding: 50px;'><h3>Unauthorized or Job not found</h3><a href='manage_jobs.php'>Go back</a></div>";
            exit;
        }
    ?>

    <main class="main-layout">
        <div class="auth-container" style="max-width: 600px;">
            <h2>Edit Job Listing</h2>
            <form id="editJobForm">
                <input type="hidden" name="id" value="<?php echo $job['id']; ?>">
                <div class="form-group">
                    <label for="title">Job Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($job['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" value="<?php echo htmlspecialchars($job['company_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category ID</label>
                    <input type="number" id="category_id" name="category_id" class="form-control" value="<?php echo htmlspecialchars($job['category_id']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($job['location']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="job_type">Job Type</label>
                    <input type="text" id="job_type" name="job_type" class="form-control" value="<?php echo htmlspecialchars($job['job_type']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Job Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="salary_min">Minimum Salary</label>
                        <input type="number" id="salary_min" name="salary_min" class="form-control" value="<?php echo htmlspecialchars($job['salary_min']); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="salary_max">Maximum Salary</label>
                        <input type="number" id="salary_max" name="salary_max" class="form-control" value="<?php echo htmlspecialchars($job['salary_max']); ?>">
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label for="currency">Currency</label>
                        <input type="text" id="currency" name="currency" class="form-control" value="<?php echo htmlspecialchars($job['currency']); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;">Update Job</button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('editJobForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_job';
            
            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const text = await res.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (err) {
                    throw new Error('Server returned invalid response: ' + text.substring(0, 100));
                }
                
                if (result.success) {
                    if (typeof showToast !== 'undefined') showToast('Job updated successfully!', 'success');
                    else alert('Job updated successfully!');
                    setTimeout(() => { window.location.href = 'manage_jobs.php'; }, 1500);
                } else {
                    if (typeof showToast !== 'undefined') showToast(result.message || 'Failed to update job.', 'error');
                    else alert(result.message || 'Failed to update job.');
                }
            } catch (err) {
                console.error(err);
                if (typeof showToast !== 'undefined') showToast('Error: ' + err.message, 'error');
                else alert('Error: ' + err.message);
            }
        });
    </script>
<script src="assets/js/main.js"></script>
</body>
</html>
