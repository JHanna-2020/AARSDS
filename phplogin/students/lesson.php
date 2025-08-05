<?php
// Enhanced security and session management
session_start();

// Security: Regenerate session ID to prevent session fixation
if (!isset($_SESSION['regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['regenerated'] = true;
}

// Enhanced authentication check
if (!isset($_SESSION['account_loggedin']) || $_SESSION['account_loggedin'] !== true) {
    header('Location: index.php');
    exit;
}

// Security: Add CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database configuration - move to separate config file in production
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

// Enhanced database connection with error handling
try {
    $con = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

    // Security: Set charset to prevent SQL injection via charset confusion
    $con->set_charset("utf8mb4");

    if ($con->connect_error) {
        throw new Exception("Connection failed: " . $con->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

// Enhanced prepared statement with better error handling
$stmt = $con->prepare('SELECT grade, email, phone, sex, is_servant FROM accounts WHERE id = ? LIMIT 1');

if (!$stmt) {
    error_log("Prepare failed: " . $con->error);
    die("Database query failed. Please try again later.");
}

$stmt->bind_param('i', $_SESSION['account_id']);

if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die("Database query failed. Please try again later.");
}

$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$grade = $user_data['grade'];
$email = $user_data['email'];
$phone = $user_data['phone'];
$sex = $user_data['sex'];
$servant = $user_data['is_servant'];

$stmt->close();
$con->close();

// Security: Validate grade to prevent path traversal
if (!is_numeric($grade) || $grade < 1 || $grade > 12) {
    die("Invalid grade specified.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sunday School Lesson - Grade <?= htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sunday School Lesson for Archangel Raphael Coptic Orthodox Church">

    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">

    <link rel="preload" href="../style.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" as="style">

    <link href="../style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw=="
        crossorigin="anonymous" referrerpolicy="no-referrer">

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
            --bg-light: #ffffff;
            --bg-dark: #34495e;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.2);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        .bckbtn {
            bottom: 30px;
            right: 30px;
            background: var(--accent-color);
            border-radius: 50px;
            padding: 15px 25px;
            z-index: 999;
            box-shadow: var(--shadow-hover);
        }

        .bckbtn:hover {
            background-color: #222;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .dark-mode .header h1,
        .dark-mode .menu a {
            color: black;
        }


        .header {
            background-color: rgba(30, 30, 30, 0.2);
            backdrop-filter: blur(5px);
            color: white;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 999;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: "Times New Roman", Times, serif;
        }

        .dark-mode .header {
            background-color: rgba(30, 30, 30, 0.8);
            color: white;
        }

        .header h1 {
            margin: 0;
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            color: white;
            font-family: "Times New Roman", Times, serif;
            font-weight: bold;
        }

        .menu.open {
            display: flex;
            flex-direction: column;
            position: absolute;
            top: 60px;
            right: 20px;
            background-color: #444;
            padding: 10px;
            border-radius: 5px;
        }

        .menu {
            display: flex;
            gap: 15px;
        }

        .menu a {
            color: white;
            text-decoration: none;
        }

        .dark-mode .header h1,
        .dark-mode .menu a {
            color: black;
        }


        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .hamburger:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .bar {
            width: 25px;
            height: 3px;
            background-color: var(--text-light);
            margin: 3px 0;
            transition: var(--transition);
            border-radius: 2px;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            backdrop-filter: blur(4px);
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-light);
        }

        .page-title h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .grade-badge {
            display: inline-block;
            background: var(--success-color);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 1.2rem;
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 600;
        }

        .section-title i {
            color: var(--secondary-color);
            font-size: 2rem;
        }

        /* PDF Container */
        .pdf-container {
            position: relative;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            background: #f8f9fa;
            min-height: 600px;
        }

        .pdf-embed {
            width: 100%;
            height: 800px;
            border: none;
            border-radius: var(--border-radius);
        }

        .quiz-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: var(--border-radius);
            padding: 20px;
            color: white;
            text-align: center;
        }

        .quiz-embed {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: var(--border-radius);
            background: white;
            box-shadow: var(--shadow);
        }

        /* Enhanced Buttons */
        .btn {
            background: var(--secondary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: var(--shadow);
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Dark Mode */
        .dark-mode {
            --text-light: #2c3e50;
            --text-dark: #ecf0f1;
            --bg-light: #2c3e50;
            --bg-dark: #ecf0f1;
        }

        .dark-mode body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        .dark-mode .section-title {
            color: var(--text-light);
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .header {
                padding: 15px 20px;
            }

            .hamburger {
                display: flex;
            }

            .menu {
                display: none;
                position: absolute;
                top: 70px;
                right: 20px;
                background: var(--primary-color);
                flex-direction: column;
                padding: 20px;
                border-radius: var(--border-radius);
                box-shadow: var(--shadow-hover);
                min-width: 200px;
            }

            .menu.open {
                display: flex;
            }

            .container {
                padding: 15px;
            }

            .content-section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .bckbtn {
                bottom: 20px;
                right: 20px;
                padding: 12px 20px;
            }

            .pdf-embed {
                height: 500px;
            }

            .quiz-embed {
                height: 400px;
            }
        }

        @media screen and (max-width: 480px) {
            .content-section {
                padding: 15px;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .section-title i {
                font-size: 1.8rem;
            }

            .pdf-embed {
                height: 400px;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Security indicator */
        .security-badge {
            position: fixed;
            bottom: 100px;
            left: 30px;
            background: var(--success-color);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 999;
            box-shadow: var(--shadow);
            background-color: #27ae60;
        }

        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* Focus styles for accessibility */
        .btn:focus,
        .menu a:focus {
            outline: 2px solid var(--warning-color);
            outline-offset: 2px;
        }
    </style>
</head>

<body>
    <header class="header" role="banner">
        <h1>Archangel Raphael Coptic Orthodox Church</h1>
        <div class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation menu" role="button" tabindex="0">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <nav class="menu" id="navMenu" role="navigation">
            <a href="home.php" aria-label="Go to home page">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Home</span>
            </a>
            <a href="profile.php" aria-label="View profile">
                <i class="fas fa-user" aria-hidden="true"></i>
                <span>Profile</span>
            </a>
            <a onclick="toggleDarkMode()" role="button" tabindex="0" aria-label="Toggle dark mode">
                <i class="fas fa-moon" aria-hidden="true"></i>
                <span>Dark Mode</span>
            </a>
            <a href="../logout.php" aria-label="Logout">
                <svg width="14" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true">
                    <path fill="currentColor"
                        d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.5 0 32 14.3 32 32s-14.3 32-32 32z" />
                </svg>
                <span>Logout</span>
            </a>
        </nav>
    </header>

    <main class="content-section" role="main">
        <div class="page-title">
            <h1>Sunday School Lesson</h1>
            <div class="grade-badge">
                <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                Grade <?= htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <section class="hymns-container" aria-labelledby="lesson-title">
            <h2 class="section-title" id="lesson-title">
                Lesson:
            </h2>
            <div class="pdf-container">
                <?php
                $lesson_pdf = "../servants/lesson/grade_" . intval($grade) . ".pdf";
                // Security: Validate file exists and is within expected directory
                if (file_exists($lesson_pdf) && strpos(realpath($lesson_pdf), realpath("../servants/lesson/")) === 0) {
                    echo '<embed src="' . htmlspecialchars($lesson_pdf, ENT_QUOTES, 'UTF-8') . '" 
                          class="pdf-embed" 
                          type="application/pdf"
                          aria-label="Sunday school lesson PDF for grade ' . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . '">';
                } else {
                    echo '<div style="text-align: center; padding: 50px; color: #666;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px;"></i>
                            <p>Lesson material is currently unavailable. Please contact your teacher.</p>
                          </div>';
                }
                ?>
            </div>
        </section>

        <section class="hymns-container" aria-labelledby="quiz-title">
            <h2>Multiple Choice Questions</h2>
            <br>

            <iframe
                src="../servants/lesson/questions/quiz.php?grade=<?= urlencode($grade) ?>&token=<?= urlencode($_SESSION['csrf_token']) ?>"
                class="quiz-embed"
                title="Interactive quiz for grade <?= htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') ?>"
                sandbox="allow-scripts allow-forms allow-same-origin">
                <p>Your browser does not support iframes. Please <a href="../servants/lesson/questions/quiz.php"
                        target="_blank">click here</a> to access the quiz.</p>
            </iframe>

        </section>
        <bckbtn onclick="location.href='home.php'" class="btn btn-home" aria-label="Return to home page">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Home</span>
        </bckbtn>

    </main>

    <div class="security-badge" title="Secure connection">
        <i class="fas fa-shield-alt" aria-hidden="true"></i>
        <span>Secure</span>
    </div> 

    <!-- Enhanced JavaScript -->
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

        // Load dark mode preference
        document.addEventListener("DOMContentLoaded", function () {
            try {
                const darkMode = sessionStorage.getItem("darkMode") === "true";
                if (darkMode) {
                    document.body.classList.add("dark-mode");
                    const icon = document.querySelector('[onclick="toggleDarkMode()"] i');
                    if (icon) {
                        icon.className = "fas fa-sun";
                    }
                }
            } catch (e) {
                console.warn("Unable to load dark mode preference");
            }
        });

        // Enhanced keyboard navigation
        document.addEventListener("keydown", function (e) {
            // ESC to close menu
            if (e.key === "Escape") {
                const menu = document.getElementById("navMenu");
                if (menu.classList.contains("open")) {
                    toggleMenu();
                }
            }

            // Enter/Space for hamburger button
            if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains("hamburger")) {
                e.preventDefault();
                toggleMenu();
            }
        });

        // Security: Prevent iframe clickjacking
        if (window.top !== window.self) {
            window.top.location = window.self.location;
        }

        // Performance: Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Error handling for embedded content
        document.querySelectorAll('embed, iframe').forEach(element => {
            element.addEventListener('error', function () {
                const errorMsg = document.createElement('div');
                errorMsg.innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #666; background: #f8f9fa; border-radius: 12px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px; color: #f39c12;"></i>
                        <p>Content could not be loaded. Please refresh the page or contact support.</p>
                        <button onclick="location.reload()" class="btn" style="margin-top: 15px;">
                            <i class="fas fa-refresh"></i> Refresh Page
                        </button>
                    </div>
                `;
                this.parentNode.replaceChild(errorMsg, this);
            });
        });
    </script>

    <!-- Load external scripts asynchronously -->
    <script src="../dark.js" defer></script>
</body>

</html>