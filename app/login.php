<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Jobbly</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="bg-surface">
    <?php include 'header.php'; ?>

    <main class="main-layout">
        <div class="auth-container">
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to access your saved jobs and profile.</p>
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" class="form-control" placeholder="name@company.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary">Login to Account</button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign up free</a>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('API_Ops.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', email, password })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = 'jobs.php';
                } else {
                    showToast(data.message || 'Invalid email or password.', 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    </script>
<script src="assets/js/main.js"></script>
</body>
</html>

