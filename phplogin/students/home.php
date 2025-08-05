<?php
session_start();
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1">
    <title>Home</title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link href="../theme.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js" defer></script>
    <style>
        body {
            padding-top: 80px;
        }

        .w3-row-padding {
            max-width: 1060px;
        }

        .w3-third {
            float: left;
            width: 33.3%;
            text-align: center;
        }

        @media screen and (max-width: 768px) {
            .w3-third {
                width: 100%;
            }
        }

        footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            color: black;
            text-align: center;
            backdrop-filter: blur(12px);
        }
    </style>
</head>

<body>
    <header class="header">
        <h1>Archangel Raphael Coptic Orthodox Church</h1>
        <div class="hamburger" onclick="toggleMenu()">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <nav class="menu" id="navMenu">
            <a href="home.php"><i class="fas fa-home"></i> Home</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a onclick="toggleDarkMode()"><i class="fas fa-moon"></i> Dark Mode</a>
            <a href="../logout.php">
                <svg width="14" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path fill="currentColor"
                        d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.5 0 32 14.3 32 32s-14.3 32-32 32z" />
                </svg>
                Logout
            </a>
        </nav>
    </header>

    <div class="w3-row-padding">
        <div class="page-title">
            <div class="welcome-section">
                <h3>Welcome Back, <?= htmlspecialchars($_SESSION['account_name'], ENT_QUOTES) ?>!</h3>
            </div>
        </div>

        <div class="w3-third">
            <br><br><br><br>
            <div class="button-box b" onclick="location.href='story.php'">
                <h2>Saint Story</h2>
            </div>
        </div>

        <div class="w3-third">
            <div class="button-box a" onclick="location.href='lesson.php'">
                <h2>Lesson</h2>
            </div>
            <br><br>
            <div class="button-box b" onclick="location.href='bible.php'">
                <h2>Bible Trivia</h2>
            </div>
            <br><br>

            <div class="button-box c" onclick="location.href='hymns.php'">
                <h2>Hymns</h2>
            </div>
            <br><br>
            <div class="button-box d" onclick="location.href='sermon.php'">
                <h2>Sermons</h2>
            </div>
        </div>

        <div class="w3-third">
            <br><br><br><br>
            <div class="button-box c" onclick="location.href='question.php'">
                <h2>Send a Question</h2>
            </div>
        </div>
        <div class="security-badge" title="Secure connection">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            <span>Secure</span>
        </div>

        <script>
            // Security: Content Security Policy compliance
            'use strict';

            // Enhanced menu toggle with accessibility
            function toggleMenu() {
                const menu = document.getElementById("navMenu");
                const hamburger = document.querySelector(".hamburger");
                const isOpen = menu.classList.contains("open");

                menu.classList.toggle("open");
                hamburger.setAttribute("aria-expanded", !isOpen);

                // Focus management for accessibility
                if (!isOpen) {
                    menu.querySelector("a").focus();
                }
            }

            // Enhanced dark mode with persistence
            function toggleDarkMode() {
                document.body.classList.toggle("dark-mode");
                const isDark = document.body.classList.contains("dark-mode");

                // Save preference securely
                try {
                    sessionStorage.setItem("darkMode", isDark);
                } catch (e) {
                    console.warn("Unable to save dark mode preference");
                }

                // Update icon
                const icon = document.querySelector('[onclick="toggleDarkMode()"] i');
                if (icon) {
                    icon.className = isDark ? "fas fa-sun" : "fas fa-moon";
                }
            }



            // Session timeout warning
            let sessionTimeout;
            function resetSessionTimeout() {
                clearTimeout(sessionTimeout);
                sessionTimeout = setTimeout(() => {
                    if (confirm("Your session expired.")) {
                        window.location.href = "../logout.php";
                    } else {
                        window.location.href = "../logout.php";
                    }
                }, 25 * 60 * 1000); // 25 minutes
            }

            // Reset timeout on user activity
            ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
                document.addEventListener(event, resetSessionTimeout, true);
            });

            resetSessionTimeout();
        </script>
        <footer>
            <strong>
                <p>© 2025 Archangel Raphael Coptic Orthodox Church Sunday School</title>
                </p>
        </footer>
</body>

</html>