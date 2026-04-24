<?php
$current_year = date('Y');
?>
<footer class="footer-shell" style="padding: 80px 0 40px; background: var(--surface); border-top: 1px solid var(--border-soft);">
    <div class="footer-shell-inner" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 60px; max-width: 1120px; margin: 0 auto; padding: 0 24px;">
        
        <div class="footer-brand-column">
            <a href="index.php" class="brand" style="display: flex; align-items: center; gap: 8px; text-decoration: none; margin-bottom: 24px; font-size: 1.8rem;">
                <span class="material-symbols-outlined" style="font-size: 36px; color: var(--primary);">rocket_launch</span>
                <span>Jobbly</span>
            </a>
            <p style="color: var(--text-muted); line-height: 1.6; max-width: 320px; font-size: 0.95rem;">
                The world's leading job aggregation platform. We connect high-quality talent with the best opportunities globally.
            </p>
        </div>

        <div class="footer-nav-column">
            <h4 style="margin-bottom: 24px; font-size: 1.1rem;">Platform</h4>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                <li><a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Browse Jobs</a></li>
                <li><a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Companies</a></li>
                <li><a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Salaries</a></li>
            </ul>
        </div>

        <div class="footer-nav-column">
            <h4 style="margin-bottom: 24px; font-size: 1.1rem;">Community</h4>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                <li><a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Help Center</a></li>
                <li><a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Safety</a></li>
                <li><a href="#" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; transition: color 0.3s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Privacy</a></li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom-bar" style="max-width: 1120px; margin: 60px auto 0; padding: 30px 24px 0; border-top: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center;">
        <p style="color: var(--text-muted); font-size: 0.85rem;">&copy; <?php echo $current_year; ?> Jobbly Inc. All rights reserved.</p>
        <div class="social-icons" style="display: flex; gap: 20px;">
             <span class="material-symbols-outlined" style="color: var(--text-muted); font-size: 20px; cursor: pointer;">language</span>
             <span class="material-symbols-outlined" style="color: var(--text-muted); font-size: 20px; cursor: pointer;">mail</span>
        </div>
    </div>
</footer>
</body>
</html>