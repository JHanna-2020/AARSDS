<?php
session_start();
if (isset($_SESSION['account_loggedin'])) {
    header('Location: ../students/loading.php');
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Hymns - Archangel Raphael Coptic Orthodox Church</title>
    <link href="style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="dark.js" defer></script>
    <title>Login</title>
    <style>
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        body {
            background-image: url('AAR.png');
            backdrop-filter: blur(15px);
            padding-top: 0px;
        }

        .dark-mode .form .form-label {
            color: #c4c9cf;
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            color: #4CAF50;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
        }

        .form-input {
            width: 10%;
            height: 3px;
            box-sizing: border-box;
            border: 3px solid #ccc;
            border-radius: 5px;
            background-color: white;
            background-position: 10px 10px;
            background-repeat: no-repeat;
            padding: 8px 12px 8px 40px;
            transition: width 0.4s ease-in-out;
        }

        .form-input:hover {
            width: 160%;
        }

        h2 {
            background-color: grey;
        }

        .login {
            width: 500px;
        }

        iframe {
    border: 5px solid grey;
    border-radius: 10px;
}
    </style>
</head>

<body>
    <div class="login">
        <h1>AAR Login</h1>
        <form action="authenticate.php" method="post" class="form login-form">
            <div class="form-group">
                <label class="form-label" for="username">Username:</label>
                <svg class="form-icon-right" width="14" height="14" xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 448 512">
                    <path
                        d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z" />
                </svg>
                <input class="form-input" type="text" name="username" placeholder="USERNAME" id="username" required>
            </div>
            <div class="form-group ">

                <label class="form-label" for="password">Password: </label>
                <svg class="form-icon-right" xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                    viewBox="0 0 448 512">
                    <!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                    <path
                        d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z" />
                </svg>
                <input class="form-input" type="password" name="password" placeholder="PASSWORD" id="password" required>

            </div>

            <div class="form-group mar-bot-5">
                <strong><label for="show"> Show Your Password</label></strong>
                <input type="checkbox" name="show" id="show" onclick="showPassword()">
            </div>
            <button class="btn blue" type="submit">Login</button>
        </form>
        <br><br>
        <div class="hymns-container">
        <h2>Church Calendar</h2>
        <br>
        <div class="video-loading" id="videoLoading">
            <i class="fas fa-spinner"></i> Loading calendar...
        </div>

        <iframe src="https://archangelraphael.org/monthly-calendar" onload="hideLoading()"
        width="100%" height="450">
        </iframe>
        </div>


</body>

</html>

<script>
    //self explanatory
    function showPassword() {
        var passwordField = document.getElementById("password");
        var showButton = document.getElementById("show");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            showButton.value = "Hide Password";
        } else {
            passwordField.type = "password";
            showButton.value = "Show your Password";
        }
    }
    //loading 
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