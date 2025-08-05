<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>Home</title>
        <link href="../style.css" rel="stylesheet" type="text/css">
        <script src="../dark.js" defer></script>


        <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            padding-top: 80px; /* space for fixed navbar */
        }

        /* Header */
        .header {
            background-color: #333;
            color: white;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 999;
            padding: 10px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: clamp(1rem, 2.5vw, 1.5rem);
        }

        /* Menu */
        .menu {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 15px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            padding: 6px 10px;
        }

        .menu a:hover {
            background-color: #575757;
            border-radius: 4px;
        }

        /* Hamburger menu */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .hamburger span {
            height: 3px;
            width: 25px;
            background-color: white;
            margin: 4px 0;
        }

        .mobile-nav {
            display: none;
            flex-direction: column;
            background-color: #333;
            padding: 10px;
        }

        .mobile-nav a {
            padding: 10px 0;
            color: white;
            text-decoration: none;
        }

        .mobile-nav a:hover {
            background-color: #575757;
        }

        /* Buttons */
        h2 { color: #fff; }
        buttons {
            text-align: center;
            border-radius: 10%;
            display: inline-block;
            justify-content: center;
            align-items: center;
            text-decoration-style: center;
            background: rgba(50,50,50,0.6);
            width: 200px;
            height: 120px;
            transition: all 0.3s ease;
        }

        buttons:hover.a { background: rgba(201, 76, 76, 0.9); height: 200px; }
        buttons:hover.b { background: rgba(50, 76, 200, 0.9); height: 200px; }
        buttons:hover.c { background: rgba(90, 80, 70, 0.9); height: 200px; }
        buttons:hover.d { background: rgba(20, 120, 20, 0.9); height: 200px; }

        .w3-third {
            float: left;
            width: 33.33%;
            text-align: center;
            align-items: auto;
        }

        .page-title {
            text-align: center;
            margin: 20px;
        }

        @media screen and (max-width: 768px) {
            .menu {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .w3-third {
                width: 100%;
                margin-bottom: 20px;
            }
        }
    </style>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    </head>