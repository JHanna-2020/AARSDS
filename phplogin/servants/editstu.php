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

// Get student ID from URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0)
    exit("Invalid ID.");

// Fetch student data
$result = $con->query("SELECT * FROM accounts WHERE id = $id");
if ($result->num_rows === 0)
    exit("Student not found.");
$student = $result->fetch_assoc();

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Safely get POST values with null coalescing
    $username = $con->real_escape_string($_POST['username'] ?? '');
    $email = $con->real_escape_string($_POST['email'] ?? '');
    $phone = $con->real_escape_string($_POST['phone'] ?? '');
    $sex = $con->real_escape_string($_POST['sex'] ?? '');
    $grade = $con->real_escape_string($_POST['grade'] ?? '');
    $father_of_confession = $con->real_escape_string($_POST['father_of_confession'] ?? '');
    $lesson_score = $con->real_escape_string($_POST['lesson_score'] ?? '');
    $bible_score = $con->real_escape_string($_POST['bible_score'] ?? '');
    $school_grade = $con->real_escape_string($_POST['school_grade'] ?? '');
    $birthdate = $con->real_escape_string($_POST['birthdate'] ?? '');
    $address = $con->real_escape_string($_POST['address'] ?? '');
    $home_phone_num = $con->real_escape_string($_POST['home_phone_num'] ?? '');
    $fr_phone_num = $con->real_escape_string($_POST['fr_phone_num'] ?? '');
    $mr_phone_num = $con->real_escape_string($_POST['mr_phone_num'] ?? '');
    $last_confession = $con->real_escape_string($_POST['last_confession'] ?? '');
    $email_fr = $con->real_escape_string($_POST['email_fr'] ?? '');
    $email_mr = $con->real_escape_string($_POST['email_mr'] ?? '');
    $name_fr = $con->real_escape_string($_POST['name_fr'] ?? '');
    $name_mr = $con->real_escape_string($_POST['name_mr'] ?? '');

    $sql = "UPDATE accounts SET 
                username = '$username',
                email = '$email',
                phone = '$phone',
                sex = '$sex',
                grade = '$grade',
                father_of_confession = '$father_of_confession',
                lesson_score = '$lesson_score',
                bible_score = '$bible_score',
                school_grade = '$school_grade', 
                birthdate = '$birthdate', 
                address = '$address', 
                home_phone_num = '$home_phone_num', 
                fr_phone_num = '$fr_phone_num',
                mr_phone_num = '$mr_phone_num', 
                last_confession = '$last_confession', 
                email_fr = '$email_fr', 
                email_mr = '$email_mr', 
                name_fr = '$name_fr', 
                name_mr = '$name_mr'";

    // If change_password checkbox was checked
    if (!empty($_POST['change_password']) && !empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql .= ", password = '$password'";
    }

    $sql .= " WHERE id = $id";

    if ($con->query($sql)) {
        $message = "Student updated successfully";
        $result = $con->query("SELECT * FROM accounts WHERE id = $id");
        $student = $result->fetch_assoc();
    } else {
        $message = "Update failed: " . $con->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <h2>Edit Student: <?= htmlspecialchars($student['username']) ?></h2>

        <?php if ($message): ?>
            <p><?= $message ?></p>
        <?php endif; ?>

        <form method="POST" action="editstu.php?id=<?= $id ?>">
            <label for="username">Name:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($student['username']) ?>" required>

            <label>
                <input type="checkbox" id="change_password_checkbox" name="change_password"
                    onchange="togglePasswordField()">click the checkbox to change password
            </label>

            <label for="password">New Password:</label>
            <input type="text" name="password" id="password" disabled>

            <label for="email">Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>">

            <label for="phone">Phone:</label>
            <input type="tel" name="phone" pattern="\(\d{3}\)\d{3}-\d{4}" placeholder="(123)456-7890"
                value="<?= htmlspecialchars($student['phone']) ?>">
            <small>Use format: (123)456-7890</small>

            <label for="sex">Gender:</label>
            <select name="sex" required>
                <option value="0" <?= $student['sex'] == '0' ? 'selected' : '' ?>>Male</option>
                <option value="1" <?= $student['sex'] == '1' ? 'selected' : '' ?>>Female</option>
            </select>

            <label for="grade">Grade:</label>
            <input type="number" name="grade" min="1" max="12" value="<?= htmlspecialchars($student['grade']) ?>" required>

            <div class="profile-detail">
                <label for="father_of_confession">Father of Confession:</label>
                <select name="father_of_confession" id="father_of_confession" required>
                    <optgroup label="Archangel Raphael Church:">
                        <option value="Fr. Athanasius Kaldas" <?= $student['father_of_confession'] == 'Fr. Athanasius Kaldas' ? 'selected' : '' ?>>Fr. Athanasius Kaldas</option>
                        <option value="Fr. Tobia Manoli" <?= $student['father_of_confession'] == 'Fr. Tobia Manoli' ? 'selected' : '' ?>>Fr. Tobia Manoli</option>
                    </optgroup>
                    <optgroup label="St. Mark Church:">
                        <option value="Fr. Bishoy George" <?= $student['father_of_confession'] == 'Fr. Bishoy George' ? 'selected' : '' ?>>Fr. Bishoy George</option>
                        <option value="Fr. Makary Boutros" <?= $student['father_of_confession'] == 'Fr. Makary Boutros' ? 'selected' : '' ?>>Fr. Makary Boutros</option>
                        <option value="Fr. Polycarpus Shoukry" <?= $student['father_of_confession'] == 'Fr. Polycarpus Shoukry' ? 'selected' : '' ?>>Fr. Polycarpus Shoukry</option>
                        <option value="Fr. Younan William" <?= $student['father_of_confession'] == 'Fr. Younan William' ? 'selected' : '' ?>>Fr. Younan William</option>
                    </optgroup>
                    <optgroup label="St. Stephen Church:">
                        <option value="Fr. Abraam Kamal" <?= $student['father_of_confession'] == 'Fr. Abraam Kamal' ? 'selected' : '' ?>>Fr. Abraam Kamal</option>
                    </optgroup>
                    <optgroup label="St. Paul American Church:">
                        <option value="Fr. Matthias Shehad" <?= $student['father_of_confession'] == 'Fr. Matthias Shehad' ? 'selected' : '' ?>>Fr. Matthias Shehad</option>
                    </optgroup>
                    <optgroup label="St. Mary & Archangel Michael Church:">
                        <option value="Fr. James Gendi" <?= $student['father_of_confession'] == 'Fr. James Gendi' ? 'selected' : '' ?>>Fr. James Gendi</option>
                    </optgroup>
                    <optgroup label="St. George Church:">
                        <option value="Fr. George Salama" <?= $student['father_of_confession'] == 'Fr. George Salama' ? 'selected' : '' ?>>Fr. George Salama</option>
                    </optgroup>
                </select>
            </div>

            <label for="lesson_score">Lesson Taio (score):</label>
            <input type="text" name="lesson_score" value="<?= htmlspecialchars($student['lesson_score']) ?>" required>

            <label for="bible_score">Bible Taio:</label>
            <input type="text" name="bible_score" value="<?= htmlspecialchars($student['bible_score']) ?>" required>

            <label for="school_grade">School Grade:</label>
            <input type="text" name="school_grade" value="<?= htmlspecialchars($student['school_grade']) ?>" required>

            <label for="birthdate">Birthdate:</label>
            <input type="date" name="birthdate" value="<?= htmlspecialchars($student['birthdate']) ?>" required>

            <label for="address">Address:</label>
            <input type="text" name="address" value="<?= htmlspecialchars($student['address']) ?>" required>

            <label for="home_phone_num">Home Phone:</label>
            <input type="tel" name="home_phone_num" pattern="\(\d{3}\)\d{3}-\d{4}" placeholder="(123)456-7890"
                value="<?= htmlspecialchars($student['home_phone_num']) ?>">
            <small>Use format: (123)456-7890</small>

            <label for="last_confession">Last Confession Date: (Optional)</label>
            <input type="date" name="last_confession" value="<?= htmlspecialchars($student['last_confession']) ?>">

            <br><br><br><br><br><br>
            
            <label for="name_fr">Father Name:</label>
            <input type="text" name="name_fr" value="<?= htmlspecialchars($student['name_fr']) ?>" required>

            <label for="name_mr">Mother Name:</label>
            <input type="text" name="name_mr" value="<?= htmlspecialchars($student['name_mr']) ?>" required>

            <label for="fr_phone_num">Father Phone: (Optional)</label>
            <input type="tel" name="fr_phone_num" pattern="\(\d{3}\)\d{3}-\d{4}" placeholder="(123)456-7890"
                value="<?= htmlspecialchars($student['fr_phone_num']) ?>">
            <small>Use format: (123)456-7890</small>

            <label for="mr_phone_num">Mother Phone: (Optional)</label>
            <input type="tel" name="mr_phone_num" pattern="\(\d{3}\)\d{3}-\d{4}" placeholder="(123)456-7890"
                value="<?= htmlspecialchars($student['mr_phone_num']) ?>">
            <small>Use format: (123)456-7890</small>

            <label for="email_fr">Father Email: (Optional)</label>
            <input type="email" name="email_fr" value="<?= htmlspecialchars($student['email_fr']) ?>">

            <label for="email_mr">Mother Email: (Optional)</label>
            <input type="email" name="email_mr" value="<?= htmlspecialchars($student['email_mr']) ?>">

            <button type="submit">Update Student</button>
        </form>
    </div>
    
    <bckbtn onclick="location.href='../servants/students.php'" id="bckbtn" style="vertical-align:middle">
        <span>Back</span>
    </bckbtn>
</body>
</html>

<script>
    function togglePasswordField() {
        const checkbox = document.getElementById('change_password_checkbox');
        const passwordField = document.getElementById('password');
        passwordField.disabled = !checkbox.checked;
        if (!checkbox.checked) passwordField.value = ''; // clear if disabled
    }
</script>