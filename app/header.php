<?php
// **================================================**
// ** File: header.php                               **
// ** Responsibility: Site header                    **
// ** - Add logo / app name                          **
// ** - Add navigation links                         **
// **================================================**
?>

<header class="header">
    <div class="header-container">

        <!-- logo -->
        <div class="logo-section">
            <h1 class="logo">💼 Jobbly</h1>
            <p class="tagline">Smart Job Finder & Manager</p>
        </div>

        <!-- navigation -->
        <nav class="nav">
            <ul>
                <li>
                    <a href="#"
                       class="nav-link"
                       data-page="home"
                       onclick="navigateTo('home'); return false;">
                        Home
                    </a>
                </li>

                <li>
                    <a href="#"
                       class="nav-link"
                       data-page="saved"
                       onclick="navigateTo('saved'); return false;">
                        Saved
                    </a>
                </li>

                <li>
                    <a href="#"
                       class="nav-link"
                       data-page="about"
                       onclick="navigateTo('about'); return false;">
                        About us
                    </a>
                </li>
            </ul>
        </nav>

        <!-- profile button -->
        <div class="profile-section">
            <a href="#"
               class="btn btn-profile nav-link"
               data-page="profile"
               onclick="navigateTo('profile'); return false;">
                👤 Profile
            </a>
        </div>
    </div>
</header>