<?php

// We need to use sessions, so you should always initialize sessions using the below function
session_start();
// If the user is logged in, redirect to the home page

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
attendance_score,
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
    $attendance_score,
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

$destination = ($servant == 0) ? 'home.php' : 'servant.php';

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Hymns - Archangel Raphael Coptic Orthodox Church</title>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../dark.js"></script>

    <style>


        /* Responsive design */
        @media screen and (max-width: 768px) {

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

        .login {
            width: 100%;
            max-width: 460px;
            margin: 20px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.78);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: list-item;
             font-size: clamp(.6rem, 2.5vw, 1.2rem);
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

        <div class="login">
            <div class="page-title">
                <div class="wrap">
                    <h4> <strong>Profile</strong></h4>
                    <br>
                    <h4>View your profile details below.</h4>
                </div>
            </div>
            <div class="profile-detail">
                <h4> <strong>Username</strong></h4>
                <?php if ($servant == 1) {
                    echo "Servant: ";
                }
                echo $_SESSION['account_name']; ?>

            </div>
            <div class="profile-detail">
                <h4><strong>Grade / School grade</strong></h4>
                <?php if ($servant == 1) {
                    echo "Servant on grade: " . $grade . " / ------";
                } else {
                    echo $grade . " / " . $school_grade;
                } ?>
            </div>
            <div class="profile-detail">
                <h4><strong>Male/Female</strong></h4>
                <?php if ($sex == 0) {
                    echo "Male";
                } elseif ($sex == 1) {
                    echo "Female";
                } else {
                    echo "error, sorry, send us to correct (send a question). ";
                } ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>School Stage</strong></h4>
                <?php
                if ($servant == 1) {
                    echo "Serving: ";
                }
                if ($grade < 6 && $grade > 0) {
                    echo "Elementary";
                } elseif ($grade < 9 && $grade > 5) {
                    echo "Middle";
                } elseif ($grade < 13 && $grade > 8) {
                    echo "High";
                } else {
                    echo 'error, sorry, send us to correct (send a question). ';
                }
                echo " School"; ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>Email</strong></h4>
                <?= htmlspecialchars($email) ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>Phone #</strong></h4>
                <?= htmlspecialchars($phone) ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>Your father of confession</strong></h4>
                <?= htmlspecialchars($father_of_confession) ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>Taio (score)</strong></h4>
                <?php
                if ($servant == 0) {
                    echo 'lesson: '.$lesson_score . '. bible: '.$bible_score.' correct answers.';
                } else {
                    echo "Not valid for servants";
                }
                ?>
            </div>
            <div class="profile-detail">
                 <h4><strong>Attendance</strong></h4>
                <?php
                if ($servant == 0) {
                    echo 'Attendance: '.$attendance_score.' points.';
                } else {
                    echo "Not valid for servants";
                }
                ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Age today</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {
                    echo calculate_age($birthdate) . " old.";
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Address</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {

                    echo htmlspecialchars($address);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Father</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {

                    echo htmlspecialchars($name_fr);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Mother</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {

                    echo htmlspecialchars($name_mr);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Home Phone Number</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {

                    echo htmlspecialchars($home_phone_num);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Father Phone Number</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {

                    echo htmlspecialchars($fr_phone_num);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Mother Phone Number</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {
                    echo htmlspecialchars($mr_phone_num);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Last Confession Date</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {
                    echo htmlspecialchars($last_confession);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Father Email</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {
                    echo htmlspecialchars($email_fr);
                } ?>
            </div>

            <div class="profile-detail">
                 <h4><strong>Mother Email</strong></h4>
                <?php if ($servant == 1) {
                    echo "Not valid for servants";
                } else {
                    echo htmlspecialchars($email_mr);
                } ?>
            </div>
            <bckbtn onclick="location.href='<?= $destination ?>'" class="btn btn-home" aria-label="Return to home page">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Home</span>
            </bckbtn>
        
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
                if (confirm("Your session will expire soon. Would you like to stay logged in?")) {
                    // Ping server to keep session alive
                    fetch("../ping.php", { method: "POST" });
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

</body>

</html>