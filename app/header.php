<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>

<nav class="top-nav">
    <div class="top-nav-inner">
        <div class="brand-group">
            <a href="index.php" class="brand" style="text-decoration: none;">Jobbly</a>
            <div class="menu-links">
                <a class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                <?php if ($isLoggedIn): ?>
                    <a class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">Profile</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="account-controls">
            <?php if ($isLoggedIn): ?>
                <span class="user-greeting">Hi, <?php echo htmlspecialchars($userName); ?></span>
                <div class="divider"></div>
                <button onclick="logout()" class="account-btn">Logout</button>
            <?php else: ?>
                <a href="login.php" class="account-btn" style="text-decoration: none;">Login</a>
                <div class="divider"></div>
                <a href="signup.php" class="account-btn" style="text-decoration: none;">Signup</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
async function logout() {
    try {
        const res = await fetch('API_Ops.php?action=logout');
        const data = await res.json();
        if (data.success) {
            window.location.href = 'index.php';
        }
    } catch (e) {
        console.error('Logout failed', e);
        // Fallback
        window.location.href = 'index.php';
    }
}
</script>