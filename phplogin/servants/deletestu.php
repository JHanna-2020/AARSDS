<?php
session_start();

if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    die('No ID specified.');
}

$con = new mysqli('localhost', 'root', '', 'phplogin');
if ($con->connect_error) {
    die('Connection failed: ' . $con->connect_error);
}

$id = (int) $_GET['id'];
$stmt = $con->prepare("DELETE FROM accounts WHERE id = ? AND is_servant = 0");
$stmt->bind_param('i', $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header('Location: students.php');
} else {
    echo "❌ Failed to delete or record not found.";
}

$stmt->close();
$con->close();
?>