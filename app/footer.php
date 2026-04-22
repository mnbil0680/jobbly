<?php
$current_year = date('Y');

$team_members = [
    "Mohamed Nabil",
    "Abdulrahman Ahmed",
    "Ziad El-Sayed",
    "Hazem Mostafa",
    "Omar Soliman",
    "Nourhan Ahmed",
    "Habiba Mohamed",
    "Basmala Esmail"
];

$team_left = array_slice($team_members, 0, 4);
$team_right = array_slice($team_members, 4, 4);
?>

<footer class="footer">

    <div class="footer-container">

        <!-- JOBBLY -->
        <div class="footer-section">
            <h3>💼 Jobbly</h3>

            <p>
                A modern job search & aggregation platform that unifies listings from multiple sources.
            </p>

            <small>IS333 • Spring 2026</small>
        </div>

        <!-- HEADER LINKS -->
        <div class="footer-section">
            <h3>🔗 Quick Access</h3>

            <div class="footer-links">
                <a href="../docs/START_HERE.md">Getting Started</a>
                <a href="../docs/QUICK_START.md">Quick Start Guide</a>
            </div>
        </div>

        <!-- TEAM -->
        <div class="footer-section">
            <h3>👥 Team</h3>

            <div class="team-columns">

                <div class="team-col">
                    <?php foreach ($team_left as $member): ?>
                        <span class="team-tag">
                            <?php echo htmlspecialchars($member); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

                <div class="team-col">
                    <?php foreach ($team_right as $member): ?>
                        <span class="team-tag">
                            <?php echo htmlspecialchars($member); ?>
                        </span>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>

    </div>

    <!-- BOTTOM BAR -->
    <div class="footer-bottom">
        <p>&copy; <?php echo $current_year; ?> Jobbly. All rights reserved.</p>
        <p>Built with ❤️ by FCAI-CU Students</p>
    </div>

</footer>