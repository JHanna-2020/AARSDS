<?php
// We need to use sessions, so you should always initialize sessions using the below function
session_start();
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../index.php');
    exit;
}
// Change the below variables to reflect your MySQL database details
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';
// Try and connect using the info above
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
// Ensure there are no connection errors
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$stmt = $con->prepare('SELECT grade, 
email, 
phone, 
sex, 
is_servant,
father_of_confession,
lesson_score,
bible_score,
school_grade,
birthdate,
address,
home_phone_num,
fr_phone_num,
mr_phone_num,
last_confession,
email_fr,
email_mr,
name_fr,
name_mr 
FROM accounts WHERE id = ?');

$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result(
    $grade,
    $email,
    $phone,
    $sex,
    $servant,
    $father_of_confession,
    $lesson_score,
    $bible_score,
    $school_grade,
    $birthdate,
    $address,
    $home_phone_num,
    $fr_phone_num,
    $mr_phone_num,
    $last_confession,
    $email_fr,
    $email_mr,
    $name_fr,
    $name_mr
);
$stmt->fetch();
$stmt->close();

function calculate_age($birthdate)
{
    if (!$birthdate)
        return '';

    try {
        $dob = new DateTime($birthdate);
        $today = new DateTime('today');
        $diff = $dob->diff($today);

        $years = $diff->y;
        $months = $diff->m;
        $days = $diff->d;

        return "{$years} yrs, {$months} mos, {$days} days";
    } catch (Exception $e) {
        return 'Invalid date';
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Questions </title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js" defer></script>

    <style>
        
        @media screen and (max-width: 768px) {

            .hymns-container {
                width: 95%;
                margin: 10px auto;
                padding: 15px;
            }

            #hymn {
                width: 100%;
                height: auto;
                aspect-ratio: 16/9;
            }

            .bckbtn {
                bottom: 20px;
                right: 20px;
                padding: 12px 20px;
            }
        }

        @media screen and (max-width: 480px) {
            .hymns-container {
                margin: 5px;
                padding: 10px;
            }

            .hymn-info {
                padding: 15px;
            }

            .hymn-info h4 {
                font-size: 1.1rem;
            }

            .hymn-info p {
                font-size: 0.9rem;
            }
        }

        .hymns-container {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.4);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        #hymn {
            max-width: 100%;
            border: 5px solid #ffb300;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .hymn-info {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hymn-info h4 {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .hymn-info p {
            color: #34495e;
            font-size: 1rem;
            line-height: 1.6;
            margin: 10px 0;
        }

        .dark-mode .hymns-container {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
        }

        .dark-mode .hymn-info {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .dark-mode .hymn-info h4 {
            color: #ecf0f1;
        }

        .dark-mode .hymn-info p {
            color: #bdc3c7;
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


    <div class="hymns-container">
         <h3>
            You can ask your questions here
        </h3>
        <br>
        <form action="https://api.web3forms.com/submit" method="POST">
            <h2>The question is sent to your servants and Abouna, and will be answered SOOOOON</h2>
            <br>
            <hr>
            <input type="hidden" name="Student name" value="<?= htmlspecialchars($_SESSION['account_name']) ?>">
            <input type="hidden" name="Sunday School grade" value="<?= htmlspecialchars($grade) ?>">
            <input type="hidden" name="Actual School Grade" value="<?= htmlspecialchars($school_grade) ?>">
            <input type="hidden" name="Stage" value="<?php if ($grade < 6 && $grade > 0) {
                echo "Elementary";
            } else if ($grade < 9 && $grade > 5) {
                echo "Middle";
            } else if ($grade < 13 && $grade > 8) {
                echo "High";
            }
            echo " School"; ?>">
            <input type="hidden" name="Student email" value="<?= htmlspecialchars($email) ?>">
            <input type="hidden" name="Father of Confession" value="<?= htmlspecialchars($father_of_confession) ?>">
            <input type="hidden" name="Lesson score" value="<?= htmlspecialchars($lesson_score) ?>">
            <input type="hidden" name="Bible score" value="<?= htmlspecialchars($bible_score) ?>">
            <input type="hidden" name="Address" value="<?= htmlspecialchars($address) ?>">
            <input type="hidden" name="Age today" value="<?php echo calculate_age($birthdate) . " old." ?>">

            <input type="hidden" name="Father Info"
                value="<?= htmlspecialchars($name_fr . " \nNumber: " . $fr_phone_num . "\nEmail: " . $email_fr) ?>">
            <input type="hidden" name="Mother Info"
                value="<?= htmlspecialchars($name_mr) . " \nNumber: " . htmlspecialchars($mr_phone_num) . " \nEmail: " . htmlspecialchars($email_mr) ?>">

            <input type="hidden" name="access_key" value="c8332c18-b864-43a9-9bae-36f78d2ece82">
            <h4>Type your question below</h4><br>
            <textarea placeholder="My Question is..." name="Question:"> </textarea>
            <br><br>
            <button class="btn" type="submit">Send</button>
        </form>

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