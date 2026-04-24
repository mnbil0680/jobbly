<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job | Jobbly</title>
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
    ?>

    <main class="main-layout">
        <div class="auth-container" style="max-width: 600px;">
            <h2>Create a New Job Listing</h2>
            <p class="auth-subtitle">Fill in the details below to post a new job opportunity.</p>
            <form id="createJobForm">
                <div class="form-group">
                    <label for="title">Job Title</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Software Engineer" required>
                </div>
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" class="form-control" placeholder="e.g. Tech Corp" required>
                </div>
                <div class="form-group">
                    <label for="category_id">Category ID</label>
                    <input type="number" id="category_id" name="category_id" class="form-control" placeholder="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Remote, New York, etc." required>
                </div>
                <div class="form-group">
                    <label for="job_type">Job Type</label>
                    <input type="text" id="job_type" name="job_type" class="form-control" placeholder="e.g. Full-time, Contract" required>
                </div>
                <div class="form-group">
                    <label for="description">Job Description</label>
                    <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe the role..." required></textarea>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="salary_min">Minimum Salary</label>
                        <input type="number" id="salary_min" name="salary_min" class="form-control" placeholder="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="salary_max">Maximum Salary</label>
                        <input type="number" id="salary_max" name="salary_max" class="form-control" placeholder="0">
                    </div>
                    <div class="form-group" style="flex: 0.5;">
                        <label for="currency">Currency</label>
                        <input type="text" id="currency" name="currency" class="form-control" placeholder="USD" value="USD">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 15px;">Create Job</button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('createJobForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create_job';
            data.status = 'open'; // Default status
            
            // Client-side validation
            if (data.title.trim() === '' || data.company_name.trim() === '') {
                if (typeof showToast !== 'undefined') showToast('Title and Company Name are required.', 'error');
                else alert('Title and Company Name are required.');
                return;
            }
            
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
                    if (typeof showToast !== 'undefined') showToast('Job created successfully!', 'success');
                    else alert('Job created successfully!');
                    setTimeout(() => { window.location.href = 'index.php'; }, 1500);
                } else {
                    if (typeof showToast !== 'undefined') showToast(result.message || 'Failed to create job.', 'error');
                    else alert(result.message || 'Failed to create job.');
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
