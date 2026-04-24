<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
?>

<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">

<nav class="top-nav">
    <div class="top-nav-inner">
        <div class="brand-group">
            <a href="index.php" class="brand" style="display: flex; align-items: center; gap: 8px; text-decoration: none;">
                <span class="material-symbols-outlined" style="font-size: 32px; color: var(--primary);">rocket_launch</span>
                <span>Jobbly</span>
            </a>
            <div class="menu-links" style="margin-left: 20px;">
                <a class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    Explore
                </a>
                <?php if ($isLoggedIn): ?>
                    <a class="menu-link <?php echo ($_GET['view'] ?? '') === 'saved' ? 'active' : ''; ?>" href="index.php?view=saved">
                        <!--                        <span class="material-symbols-outlined" style="font-size: 18px; margin-right: 4px;">favorite</span>-->
                        Saved
                    </a>
                    <a class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                        Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="account-controls">
            <?php if ($isLoggedIn): ?>
                <div class="user-pill" style="background: var(--primary-soft); padding: 6px 16px; border-radius: 99px; display: flex; align-items: center; gap: 10px;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: var(--primary);">account_circle</span>
                    <span style="font-weight: 600; font-size: 0.9rem; color: var(--primary);"><?php echo htmlspecialchars($userName); ?></span>
                </div>
                <div class="divider" style="margin: 0 12px; height: 16px;"></div>
                <button onclick="logout()" class="account-btn" style="opacity: 0.7; font-size: 0.85rem;">Sign out</button>
            <?php else: ?>
                <a href="login.php" class="account-btn" style="text-decoration: none;">Login</a>
                <a href="signup.php" class="btn-primary" style="text-decoration: none; padding: 10px 24px; font-size: 0.9rem; margin-left: 12px;">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    function showTestEndpoints() {
        window.location.href = 'test_sources.php';
    }

    async function logout() {
        try {
            const res = await fetch('API_Ops.php?action=logout');
            const data = await res.json();
            if (data.success) {
                window.location.href = 'index.php';
            }
        } catch (e) {
            console.error('Logout failed', e);
            window.location.href = 'index.php';
        }
    }
</script>