<?php
session_start();


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
// We don't have the email or registered info stored in sessions so instead we can get the results from the database
$stmt = $con->prepare('SELECT grade,bible_score,bible_is_submitted FROM accounts WHERE id = ?');
// In this case, we can use the account ID to get the account info
$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade,$bible_score,$bible_is_submitted);
$stmt->fetch();
$stmt->close();

if (!isset($_SESSION['bible_score'])) {
    $_SESSION['bible_score'] = 0;
}

$question_id = $_POST['question_id'];
$selected = $_POST['answer'];
$correct = $_POST['correct'];
$page = $_POST['page'];

if ($selected === $correct) {
    $_SESSION['bible_score'] = ($_SESSION['bible_score'] ?? 0) + 1;
}

// Redirect back with answered flag
header("Location: quiz.php?page=$page&answered=1");
exit;
