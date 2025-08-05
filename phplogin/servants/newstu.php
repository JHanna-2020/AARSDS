<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Redirect if not logged in
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../servant.php');
    exit;
}

// Connect to database
$con = new mysqli('localhost', 'root', '', 'phplogin');
if ($con->connect_error) {
    exit('Failed to connect to MySQL: ' . $con->connect_error);
}

$message = $_GET['message'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $username = $con->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $con->real_escape_string($_POST['email'] ?? '');
    $sex = $con->real_escape_string($_POST['sex']);
    $grade = $con->real_escape_string($_POST['grade']);
    $phone = $con->real_escape_string($_POST['phone'] ?? '');
    $servant = 0;
    $father_of_confession = $con->real_escape_string($_POST['father_of_confession']);
    $lesson_score = $con->real_escape_string($_POST['lesson_score']);
    $bible_score = $con->real_escape_string($_POST['bible_score']);
    $lesson_is_submitted = 0;
    $bible_is_submitted = 0;
    $attendance_score=0;
    $school_grade = $con->real_escape_string($_POST['school_grade']);
    $birthdate = $con->real_escape_string($_POST['birthdate']);
    $address = $con->real_escape_string($_POST['address']);
    $home_phone_num = $con->real_escape_string($_POST['home_phone_num'] ?? '');
    $fr_phone_num = $con->real_escape_string($_POST['fr_phone_num'] ?? '');
    $mr_phone_num = $con->real_escape_string($_POST['mr_phone_num'] ?? '');
    $last_confession = $con->real_escape_string($_POST['last_confession'] ?? '');
    $email_fr = $con->real_escape_string($_POST['email_fr'] ?? '');
    $email_mr = $con->real_escape_string($_POST['email_mr'] ?? '');
    $name_fr = $con->real_escape_string($_POST['name_fr']);
    $name_mr = $con->real_escape_string($_POST['name_mr']);

    // Prepared statement to insert
    $stmt = $con->prepare("INSERT INTO accounts 
        (username, password, email, sex, grade, phone, is_servant, father_of_confession, lesson_score, bible_score, attendance_score, lesson_is_submitted, bible_is_submitted, school_grade, birthdate, address, home_phone_num, fr_phone_num, mr_phone_num, last_confession, email_fr, email_mr, name_fr, name_mr)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssiisisiiiiiissssssssss",
        $username, $password, $email, $sex, $grade, $phone, $servant, $father_of_confession,
        $lesson_score, $bible_score, $attendance_score, $lesson_is_submitted, $bible_is_submitted, $school_grade, $birthdate, $address, $home_phone_num,
        $fr_phone_num, $mr_phone_num, $last_confession, $email_fr, $email_mr, $name_fr, $name_mr
    );

    if ($stmt->execute()) {
        header("Location: newstu.php?message=" . urlencode(":) Student added successfully."));
        exit;
    } else {
        header("Location: newstu.php?message=" . urlencode("Failed: " . $stmt->error));
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Student</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            backdrop-filter: blur(10px);
        }

        .login {
            padding: 1%;
        }


    </style>
</head>

<body>

    <h1>Add New Student</h1>

    <button onclick="location.href='students.php'">Back to Student List</button><br><br>


    <div class="login">
        
        <form method="POST">
            <div class="profile-detail">
                <strong>Name</strong><br>
                <input type="text" name="username" required>
            </div>

            <div class="profile-detail">
                <strong>Password</strong><br>
                <input type="text" name="password" required>
            </div>

            <div class="profile-detail">
                <strong>Student Email (Optional)</strong><br>
                <input type="email" name="email">
            </div>

            <div class="profile-detail">
                <strong>Gender</strong><br>
                <select name="sex" required>
                    <option value="">-- Select --</option>
                    <option value="0">Male</option>
                    <option value="1">Female</option>
                </select>
            </div>

            <div class="profile-detail">
                <strong>Student Grade</strong><br>
                <input type="text" name="grade" required>
            </div>

            <div class="profile-detail">
                <strong>Phone # (Optional)</strong><br>
                <input type="tel" name="phone" placeholder="(123)456-7890" pattern="\(\d{3}\)\d{3}-\d{4}">
            </div>

            <div class="profile-detail">
                <strong>Father of Confession</strong><br>
                <select name="father_of_confession" required>
                    <option value="">-- Select --</option>
                    <optgroup label="Archangel Raphael Church:">
                        <option value="Fr. Athanasius Kaldas">Fr. Athanasius Kaldas</option>
                        <option value="Fr. Tobia Manoli">Fr. Tobia Manoli</option>
                    </optgroup>
                    <optgroup label="St. Mark Church:">
                        <option value="Fr. Bishoy George">Fr. Bishoy George</option>
                        <option value="Fr. Makary Boutros">Fr. Makary Boutros</option>
                        <option value="Fr. Polycarpus Shoukry">Fr. Polycarpus Shoukry</option>
                        <option value="Fr. Younan William">Fr. Younan William</option>
                    </optgroup>
                    <optgroup label="St. Stephen Church:">
                        <option value="Fr. Abraam Kamal">Fr. Abraam Kamal</option>
                    </optgroup>
                    <optgroup label="St. Paul American Church:">
                        <option value="Fr. Matthias Shehad">Fr. Matthias Shehad</option>
                    </optgroup>
                    <optgroup label="St. Mary & Archangel Michael Church:">
                        <option value="Fr. James Gendi">Fr. James Gendi</option>
                    </optgroup>
                    <optgroup label="St. George Church:">
                        <option value="Fr. George Salama">Fr. George Salama</option>
                    </optgroup>
                </select>
            </div>

            <div class="profile-detail">
                <strong>Lesson Taio (score)</strong><br>
                <input type="text" name="lesson_score" required>
            </div>

            <div class="profile-detail">
                <strong>Bible Taio</strong><br>
                <input type="text" name="bible_score" required>
            </div>

            <div class="profile-detail">
                <strong>School Grade</strong><br>
                <input type="text" name="school_grade" required>
            </div>

            <div class="profile-detail">
                <strong>Birthdate</strong><br>
                <input type="date" name="birthdate" required>
            </div>

            <div class="profile-detail">
                <strong>Address</strong><br>
                <input type="text" name="address" required>
            </div>

            <div class="profile-detail">
                <strong>Father's Name</strong><br>
                <input type="text" name="name_fr" required>
            </div>

            <div class="profile-detail">
                <strong>Mother's Name</strong><br>
                <input type="text" name="name_mr" required>
            </div>

            <div class="profile-detail">
                <strong>Home Phone (Optional)</strong><br>
                <input type="tel" name="home_phone_num" placeholder="(123)456-7890" pattern="\(\d{3}\)\d{3}-\d{4}">
            </div>

            <div class="profile-detail">
                <strong>Father Phone (Optional)</strong><br>
                <input type="tel" name="fr_phone_num" placeholder="(123)456-7890" pattern="\(\d{3}\)\d{3}-\d{4}">
            </div>

            <div class="profile-detail">
                <strong>Mother Phone (Optional)</strong><br>
                <input type="tel" name="mr_phone_num" placeholder="(123)456-7890" pattern="\(\d{3}\)\d{3}-\d{4}">
            </div>

            <div class="profile-detail">
                <strong>Last Confession (Optional)</strong><br>
                <input type="date" name="last_confession">
            </div>

            <div class="profile-detail">
                <strong>Father Email (Optional)</strong><br>
                <input type="email" name="email_fr">
            </div>

            <div class="profile-detail">
                <strong>Mother Email (Optional)</strong><br>
                <input type="email" name="email_mr">
            </div>

            <button type="submit">ADD STUDENT</button>
        </form>
    </div>
</body>

</html>
