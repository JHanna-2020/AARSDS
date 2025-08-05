<?php
session_start();

if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../index.php');
    exit;
}

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}

$search = $_POST['search'] ?? '';
$accounts = [];
$grade = 0;
$message = '';

// Get current user's grade first
$stmt = $con->prepare('SELECT grade FROM accounts WHERE id = ? ');
$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade);
$stmt->fetch();
$stmt->close();

// Handle attendance update for multiple students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $selected_students = $_POST['students'] ?? [];
    $updated_count = 0;

    if (!empty($selected_students)) {
        foreach ($selected_students as $student_id) {
            $student_id = (int) $student_id;
            if ($student_id > 0) {
                $updateStmt = $con->prepare('UPDATE accounts SET attendance_score = attendance_score + 1 WHERE id = ? AND grade = ? AND is_servant=0');
                $updateStmt->bind_param('ii', $student_id, $grade);
                if ($updateStmt->execute()) {
                    $updated_count++;
                }
                $updateStmt->close();
            }
        }

        if ($updated_count > 0) {
            $message = "Attendance updated for $updated_count student(s)!";
        } else {
            $message = "No students were updated.";
        }
    } else {
        $message = "Please select at least one student.";
    }
}

// Get accounts list
if (!empty($search)) {
    $stmt = $con->prepare('SELECT id, username, attendance_score FROM accounts WHERE username LIKE CONCAT("%", ?, "%") AND grade = ? AND is_servant=0 ORDER BY username');
    $stmt->bind_param('si', $search, $grade);
} else {
    $stmt = $con->prepare('SELECT id, username, attendance_score FROM accounts WHERE grade = ? AND is_servant=0 ORDER BY username');
    $stmt->bind_param('i', $grade);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
$stmt->close();

mysqli_close($con);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Sunday School Attendance</title>
    <link rel="stylesheet" type="text/css" href="../style.css">
    <style>
        h1 {
            background-color: rgba(0, 0, 0, 0.6);
            color: #fff;
            font-size: clamp(0.6rem, 2.5vw, 1rem);
        }

        h2 {
            text-align: center;
        }

        body {
            font-family: Arial;
            padding: 40px;
            backdrop-filter: blur(4px);
        }

        input,
        textarea,
        button {
            border-radius: 20px;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            max-width: 600px;
            margin-bottom: 20px;
            display: block;
        }

        button {
            background-color: green;
            color: white;
            cursor: pointer;
        }

        .status {
            font-weight: bold;
            color:
                <?= str_contains($status, 'success') ? 'green' : 'red' ?>
            ;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #aaa;
            padding: 8px;
            text-align: left;
            border-radius: 5px;
        }

        th {
            background-color: white;
            color: black;
        }

        tr {
            background-color: black;
            color: white;
        }

        td:hover {
            background-color: coral;
        }


        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .checkbox-cell {
            text-align: center;
            width: 50px;
        }

        .checkbox-cell input[type="checkbox"] {
            transform: scale(1.2);
            cursor: pointer;
        }

        .attendance-form {
            background: rgba(121, 119, 119, 0.5);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .attendance-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
            width: 200px;
        }

        .form-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .student-count {
            background: #e3f2fd;
            color: #1565c0;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
        }

        #bckbtn {
            margin: 20px auto;
            display: block;
            padding: 10px 20px;
            background-color: #444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        #bckbtn:hover {
            background-color: #222;
        }


    </style>
</head>

<body>
    <div class="attendance-form">
        <h3>Students Attendance for Grade <?php echo htmlspecialchars($grade) ?></h3>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'Please') !== false ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <bckbtn onclick="location.href='../students/servant.php'" class="edit-btn" aria-label="Return to home page">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Go to Home</span>
        </bckbtn>
        <!-- Search Form -->
        <form method="POST" class="attendance-form">
            <label><strong>Search by Name:</strong></label><br><br>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter name...">
            <a class="edit-btn" type="submit">Search</a>
            <a href="attendance.php" class="edit-btn">Clear Search</a>
        </form>

        <!-- Attendance Form -->
        <form method="POST" class="hymns-container">
            <div class="form-controls">
                <a type="button" class="edit-btn" onclick="selectAll()">Select All</a>
                <a type="button" class="edit-btn" onclick="selectNone()">Select None</a>
                <a type="submit" name="update_attendance" class="edit-btn" onclick="return confirmUpdate()">
                    Update Attendance
                </a>
                <span class="student-count">
                    Total Students: <?= count($accounts) ?>
                </span>
                <span id="selected-count" class="student-count" style="background: #fff3cd; color: #856404;">
                    Selected: 0
                </span>
            </div>

            <!-- Students Table -->
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>----</th>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Total Attendance</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($accounts)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    No students found for grade <?= htmlspecialchars($grade) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($accounts as $i => $account): ?>
                                <tr>
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="students[]" value="<?= $account['id'] ?>"
                                            class="student-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($account['username']) ?></td>
                                    <td><?= htmlspecialchars($account['attendance_score']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = true);
            document.getElementById('select-all-header').checked = true;
            updateSelectedCount();
        }

        function selectNone() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = false);
            document.getElementById('select-all-header').checked = false;
            updateSelectedCount();
        }

        function toggleAll(headerCheckbox) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = headerCheckbox.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const count = checkboxes.length;
            document.getElementById('selected-count').textContent = `Selected: ${count}`;

            // Update header checkbox state
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            const headerCheckbox = document.getElementById('select-all-header');

            if (count === 0) {
                headerCheckbox.indeterminate = false;
                headerCheckbox.checked = false;
            } else if (count === allCheckboxes.length) {
                headerCheckbox.indeterminate = false;
                headerCheckbox.checked = true;
            } else {
                headerCheckbox.indeterminate = true;
            }
        }

        function confirmUpdate() {
            const selected = document.querySelectorAll('.student-checkbox:checked');
            if (selected.length === 0) {
                alert('Please select at least one student.');
                return false;
            }

            return confirm(`Are you sure you want to add attendance for ${selected.length} student(s)?`);
        }

        // Initialize count on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateSelectedCount();
        });
    </script>
</body>

</html>