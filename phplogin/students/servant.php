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
    <title>Servant Home</title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link href="../style.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js" defer></script>
    <style>
        
        .dark-mode .welcome-section h3 {
            background: linear-gradient(135deg, rgba(25, 59, 26, 0.6), rgba(15, 51, 82, 0.6));
            backdrop-filter: blur(4px);

        }

        .button-box {
            text-align: center;
            border-radius: 15px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            background: rgba(15, 183, 216, 0.72);
            width: 200px;
            height: 120px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin: 10px;
        }

        
        .button-box:hover {
            height: 200px;
            backdrop-filter: blur(4px);
        }

        .a:hover {
            background: rgba(201, 76, 76, 0.9);
        }

        .b:hover {
            background: rgba(50, 76, 200, 0.9);
        }

        .c:hover {
            background: rgba(90, 80, 70, 0.9);
        }

        .d:hover {
            background: rgba(20, 120, 20, 0.9);
        }

        .w3-row-padding {
            max-width: 1060px;
        }

        .w3-row-padding .alert h3 {
            color: rgba(149, 255, 0, 1);
        }

        .w3-third {
            float: left;
            width: 33.3%;
            text-align: center;
        }
        @media screen and (max-width: 768px) {
            .w3-third {width: 100%;}
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
                <h3>Welcome Back, Servant: <?= htmlspecialchars($_SESSION['account_name'], ENT_QUOTES) ?>!</h3>
            </div>
        </div>

        <div class="w3-third">
            <div class="button-box b" onclick="location.href='../servants/attendance.php'">
                <h2>Attendance</h2>
            </div>
            <br><br>
            <div class="button-box b" onclick="location.href='../servants/sermon-edit.php'">
                <h2>Sermon</h2>
            </div>
            <br><br><br><br>
            <div class="button-box d" onclick="location.href='../servants/lesson_upload.php'">
                <h2>Lesson</h2>
            </div>
        </div>

        <div class="w3-third">
            <div class="button-box a" onclick="location.href='../servants/saint-edit.php'">
                <h2>Saint Story</h2>
            </div>
            <br><br>
            <div class="button-box b" onclick="location.href='../servants/hymn-edit.php'">
                <h2>Hymns</h2>
            </div>
            <br><br>

            <div class="button-box c" onclick="location.href='home.php'">
                <h2>Normal Website View</h2>
            </div>
            <div class="alert">
                <h3> ! Alert ! <br></br>If you view the normal website you must log out and login again to return to
                    servants' website</h3>
            </div>
            <div class="button-box d" onclick="location.href='../Church/churchmeetings.html'">
                <h2>_TEST_ ADMIN VIEW</h2>
            </div>
        </div>

        <div class="w3-third">
            <br><br>

            <br><br>
            <div class="button-box d" onclick="location.href='../servants/students.php'">
                <h2>Student Managment</h2>
            </div>
        </div>

        <div class="w3-third">
            <br><br><br>
            <div class="button-box d" onclick="location.href='../servants/bible_upload.php'">
                <h2>Bible Study</h2>
            </div>
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
    </script>
</body>

</html>