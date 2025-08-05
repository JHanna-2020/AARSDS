<?php
session_start();

// Database credentials
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

// Connect to MySQL
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

// Validate form input
if (!isset($_POST['username'], $_POST['password'])) {
    exit('Please fill both the username and password fields!');
}

// Prepare and execute query
if ($stmt = $con->prepare('SELECT id, password FROM accounts WHERE username = ?')) {
    $stmt->bind_param('s', $_POST['username']);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        // Verify hashed password
        if (password_verify($_POST['password'], $hashed_password)) {
            // Password is correct
            session_regenerate_id();
            $_SESSION['account_loggedin'] = TRUE;
            $_SESSION['account_name'] = $_POST['username'];
            $_SESSION['account_id'] = $id;

            header('Location: ../phplogin/students/loading.php');
            exit;
        } else {
            // Wrong password
            echo '<script>
                window.location.href = "../phplogin/index.php";
                alert("Login failed. Invalid username or password!");
            </script>';
        }
    } else {
        // Wrong username
        echo '<script>
            window.location.href = "../phplogin/index.php";
            alert("Login failed. Invalid username or password!");
        </script>';
    }
    $stmt->close();
}

?>
