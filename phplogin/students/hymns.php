<?php
session_start();

if (!isset($_SESSION['account_loggedin'])) {
    header('Location: index.php');
    exit;
}

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$stmt = $con->prepare('SELECT grade FROM accounts WHERE id = ?');
$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade);
$stmt->fetch();
$stmt->close();
mysqli_close($con);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Hymns - Archangel Raphael Coptic Orthodox Church</title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js" defer></script>

    <style>

        .grade-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            margin: 10px 0;
        }

        
        .dark-mode h3 {
            background-color: rgba(17, 81, 158, 0.7);
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

    <div class="topnav">
        <h3>
            <?php
            if ($grade > 0 && $grade < 6) {
                echo "Elementary";
            } elseif ($grade >= 6 && $grade < 9) {
                echo "Middle";
            } elseif ($grade >= 9 && $grade < 13) {
                echo "High";
            } else {
                echo "General";
            }
            ?>
            Weekly Hymns Class
        </h3>
    </div>

    <div class="hymns-container">
        <div class="grade-badge">
            Grade Level:
            <?php
            if ($grade > 0 && $grade < 6) {
                echo "Elementary School (Grades 1-5)";
            } elseif ($grade >= 6 && $grade < 9) {
                echo "Middle School (Grades 6-8)";
            } elseif ($grade >= 9 && $grade < 13) {
                echo "High School (Grades 9-12)";
            } else {
                echo "General Studies";
            }
            ?>
        </div>

        <div class="hymn-info">
            <?php
            $videoPath = "../servants/hymn/grade_$grade.html";

            $videoId = '';
            $hymnName = 'Error in retrieing hymn name';
            $hymnInfo = 'No info available.';

            if (file_exists($videoPath)) {
                $lines = file($videoPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_starts_with($line, '<!--HYMN_NAME-->')) {
                        $hymnName = substr($line, strlen('<!--HYMN_NAME-->'));
                    } elseif (str_starts_with($line, '<!--HYMN_INFO-->')) {
                        $hymnInfo = substr($line, strlen('<!--HYMN_INFO-->'));
                    } elseif (!str_starts_with($line, '<!--') && !$videoId) {
                        $videoId = trim($line);
                    }
                }
            }
            ?>
            <h4><i class="fas fa-music"></i> "<?= htmlspecialchars($hymnName) ?>" Hymn</h4>
        </div>

        <div class="video-container">
            <div class="video-loading" id="videoLoading">
                <i class="fas fa-spinner"></i> Loading hymn...
            </div>


            <iframe id="sermon" width="800" height="450"
                src="https://www.youtube.com/embed/<?= htmlspecialchars($videoId) ?>" title="Coptic Orthodox Sermon"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin" allowfullscreen onload="hideLoading()"
                onerror="showError()">
            </iframe>
        </div>

        <div class="hymn-info">
            <h4><i class="fas fa-info-circle"></i> About This Hymn</h4>
            <p><?= nl2br(htmlspecialchars($hymnInfo)) ?></p><!-- interesting -->

        </div>
        <br>
        <bckbtn onclick="location.href='home.php'" class="btn btn-home" aria-label="Return to home page">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Home</span>
        </bckbtn>
    </div>

      <div class="security-badge" title="Secure connection">
        <i class="fas fa-shield-alt" aria-hidden="true"></i>
        <span>Secure</span>
    </div> 
     

    <script>
        // Show loading animation
        document.addEventListener('DOMContentLoaded', function () {
            const loading = document.getElementById('videoLoading');
            if (loading) {
                loading.style.display = 'block';
            }
        });

        // Hide loading animation when video loads
        function hideLoading() {
            const loading = document.getElementById('videoLoading');
            if (loading) {
                loading.style.display = 'none';
            }
        }

        // Show error message if video fails to load
        function showError() {
            const loading = document.getElementById('videoLoading');
            if (loading) {
                loading.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error loading video. Please refresh the page.';
                loading.style.color = '#e74c3c';
            }
        }

        // Auto-hide loading after 5 seconds as fallback
        setTimeout(function () {
            hideLoading();
        }, 5000);
    </script>

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