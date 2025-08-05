<?php
// We need to use sessions, so you should always initialize sessions using the below function
session_start();
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: home.php');
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
// We don't have the email or registered info stored in sessions so instead we can get the results from the database
$stmt = $con->prepare('SELECT grade, email, phone, is_servant FROM accounts WHERE id = ?');
// In this case, we can use the account ID to get the account info
$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade, $email, $phone, $servant);
$stmt->fetch();
$stmt->close();

if($servant == 1) {
    echo '<script>
        setTimeout(function() {
            window.location.href = "servant.php";
        }, 2000);
        // Show welcome message after a brief delay
        setTimeout(function() {
            document.getElementById("welcome-message").style.opacity = "1";
        }, 500);
    </script>';
} else {
    echo '<script>
        setTimeout(function() {
            window.location.href = "home.php";
        }, 3000);
        // Show welcome message after a brief delay
        setTimeout(function() {
            document.getElementById("welcome-message").style.opacity = "1";
        }, 500);
    </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading - Archangel Raphael Church</title>
    <link href="../AAR-Sunday-School/css/theme.css" rel="stylesheet" type="text/css">
    <link href="css/theme.css" rel="stylesheet" type="text/css">
    <style>

        body {
            background: linear-gradient(135deg, #3c12aeff 0%, #16d342ff 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }

        /* Background pattern overlay for texture */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 75% 75%, rgba(255,255,255,0.5) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
        }

        .loading-container {
            text-align: center;
            z-index: 10;
            position: relative;
            padding-top: 100px;
        }

        .church-logo {
            width: 250px;
            height: 250px;
            margin: 0 auto 30px;
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: logoGlow 2s ease-in-out infinite alternate;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center; 
            overflow: hidden;
        }

        .church-logo img {
            width: 220px;
            height: 220px;
            object-fit: contain;
            border-radius: 50%;
        }

        .church-name {
            color: white;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 0.5s forwards;
        }

        .loading-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            margin-bottom: 30px;
            opacity: 0;
            animation: fadeInUp 1s ease-out 1s forwards;
        }

        .welcome-message {
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.9rem, 2vw, 1rem);
            margin-bottom: 30px;
            opacity: 0;
            transition: opacity 0.5s ease;
            max-width: 400px;
            line-height: 1.5;
        }

        /* Loading spinner */
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            opacity: 0;
            animation: spin 1s linear infinite, fadeIn 0.5s ease-out 1.5s forwards;
        }

        /* Progress bar */
        .progress-container {
            width: 300px;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            margin: 20px auto 0;
            overflow: hidden;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 2s forwards;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            border-radius: 3px;
            width: 0%;
            animation: loadProgress 3s ease-out forwards;
        }

        /* Floating particles */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            pointer-events: none;
            animation: floatUp 4s ease-out infinite;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; width: 4px; height: 4px; }
        .particle:nth-child(2) { left: 20%; animation-delay: 1s; width: 6px; height: 6px; }
        .particle:nth-child(3) { left: 30%; animation-delay: 2s; width: 3px; height: 3px; }
        .particle:nth-child(4) { left: 80%; animation-delay: 0.5s; width: 5px; height: 5px; }
        .particle:nth-child(5) { left: 90%; animation-delay: 1.5s; width: 4px; height: 4px; }

        /* Animations */
        @keyframes logoGlow {
            0% { 
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3), 0 0 20px rgba(255, 255, 255, 0.1);
                transform: scale(1);
            }
            100% { 
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(255, 255, 255, 0.2);
                transform: scale(1.05);
            }
        }

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

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes loadProgress {
            0% { width: 0%; }
            50% { width: 60%; }
            100% { width: 100%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        @keyframes floatUp {
            0% {
                opacity: 0;
                transform: translateY(100vh) scale(0);
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: translateY(-100vh) scale(1);
            }
        }

        @media (max-width: 768px) {
            .church-logo {
                width: 120px;
                height: 120px;
            }
            
            .church-logo img {
                width: 100px;
                height: 100px;
            }
            
            .progress-container {
                width: 250px;
            }
        }

        @media (max-width: 480px) {
            .loading-container {
                padding: 0 20px;
            }
            
            .progress-container {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>
    <div class="particle"></div>

    <div class="loading-container">
        <div class="church-logo">
            <img src="loading.gif" alt="Archangel Raphael Church Logo">
        </div>
        
        <h1 class="church-name">Archangel Raphael Coptic Orthodox Church</h1>
        <h2 class="church-name" style="font-size: clamp(1rem, 3vw, 1.5rem); animation-delay: 2.0s;">Clear Lake</h2>
        
        
        <div id="welcome-message" class="welcome-message">
            <?php if($servant == 1): ?>
                Welcome, Servant! Preparing your administrative dashboard...
            <?php else: ?>
                Welcome back! Loading AAR Sunday School portal...
            <?php endif; ?>
        </div>
        
        <div class="spinner"></div>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
    </div>
</body>
</html>