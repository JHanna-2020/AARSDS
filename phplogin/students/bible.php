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

try {
    $pdo = new PDO("mysql:host=$DATABASE_HOST;dbname=$DATABASE_NAME", $DATABASE_USER, $DATABASE_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Bible Trivia - Archangel Raphael Coptic Orthodox Church</title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js" defer></script>
    <style>
        .slide {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .navigation-buttons {
            margin-top: 15px;
        }

        .quiz-embed {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 15px;
        }

        .dark-mode .quiz-container {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
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
        <h1>Weekly Bible Trivia</h1>
        <h2><i class="fas fa-graduation-cap"></i>Grade <?= htmlspecialchars($grade) ?>
            <?php
            if ($grade > 0 && $grade < 6) {
                echo "Elementary School";
            } elseif ($grade >= 6 && $grade < 9) {
                echo "Middle School";
            } elseif ($grade >= 9 && $grade < 13) {
                echo "High School";
            } else {
                echo "ERROR";
            }
            ?>
        </h2>
    </div>

    <div class="hymns-container">
        <?php
        $bibleDir = "../servants/bible/";
        $gradePrefix = "grade_" . intval($grade) . "_*";
        $images = array_merge(
            glob($bibleDir . $gradePrefix . ".png"),
            glob($bibleDir . $gradePrefix . ".jpg"),
            glob($bibleDir . $gradePrefix . ".jpeg")
        );

        if (!empty($images)) {
            usort($images, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });

            echo '<div id="bibleSlides">';
            foreach ($images as $index => $imgPath) {
                $display = $index === 0 ? 'block' : 'none';
                $imgName = basename($imgPath);
                echo "<img src='$imgPath' alt='Bible Trivia Lesson $index' style='display:$display;' class='slide' data-index='$index'>";
            }

            if (count($images) > 1) {
                echo '<div class="navigation-buttons">';
                echo '<button onclick="prevSlide()" aria-label="Previous slide">&#8592; Previous</button>';
                echo '<span id="slideCounter">1 / ' . count($images) . '</span>';
                echo '<button onclick="nextSlide()" aria-label="Next slide">Next &#8594;</button>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo "<div class='content-text'>";
            echo "<p>No Bible study materials have been uploaded yet for Grade $grade. Please check back later or contact your teacher.</p>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="hymns-container">
        <h1>Bible Study Questions</h1>
        <h2>You will only have ONE chance to answer each question</h2>

        <?php
        // Check if quiz file exists
        $quizPath = "../servants/bible/questions/quiz.php";
        if (file_exists($quizPath)) {
            echo "<iframe src='$quizPath' class='quiz-embed' title='Bible Study Quiz'></iframe>";
        } else {
            echo "<div class='content-text'>";
            echo "<p>Quiz is not available at this time. Please check back later.</p>";
            echo "</div>";
        }
        ?>
    </div>

    <div style="text-align: center; margin: 20px 0;">
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
        let slideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;

        function showSlide(index) {
            if (totalSlides === 0) return;

            // Hide all slides
            slides.forEach((img) => {
                img.style.display = 'none';
            });

            // Show current slide
            if (slides[index]) {
                slides[index].style.display = 'block';
            }

            // Update counter
            const counter = document.getElementById('slideCounter');
            if (counter && totalSlides > 1) {
                counter.textContent = `${index + 1} / ${totalSlides}`;
            }
        }

        function nextSlide() {
            if (totalSlides <= 1) return;
            slideIndex = (slideIndex + 1) % totalSlides;
            showSlide(slideIndex);
        }

        function prevSlide() {
            if (totalSlides <= 1) return;
            slideIndex = (slideIndex - 1 + totalSlides) % totalSlides;
            showSlide(slideIndex);
        }

        // Keyboard navigation
        document.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                prevSlide();
            } else if (event.key === 'ArrowRight') {
                nextSlide();
            }
        });

        // Initialize slideshow
        document.addEventListener('DOMContentLoaded', function () {
            if (totalSlides > 0) {
                showSlide(0);
            }
        });
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