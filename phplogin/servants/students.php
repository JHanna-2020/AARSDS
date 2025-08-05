<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../servant.php');
    exit;
}

$con = new mysqli('localhost', 'root', '', 'phplogin');
if ($con->connect_error) {
    exit(' Failed to connect to MySQL: ' . $con->connect_error);
}

$search = '';
$accounts = [];
$message = '';
$view_type = $_GET['view'] ?? 'students'; // Default to students view

// Determine which accounts to show based on view type
$is_servant_value = ($view_type === 'servants') ? 1 : 0;
$sql = "SELECT * FROM accounts WHERE is_servant = $is_servant_value ORDER BY username";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search']) && !empty($_POST['search'])) {
        $search = $con->real_escape_string($_POST['search']);
        $sql = "SELECT * FROM accounts 
                WHERE is_servant = $is_servant_value AND username LIKE '%$search%' 
                ORDER BY username ASC";
    }
}

$result = $con->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
} else {
    echo "Error: " . $con->error;
}

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

?>

<!DOCTYPE html>
<html>

<head>
    <title><?= $view_type === 'servants' ? 'Servant' : 'Student' ?> List</title>
    <link rel="stylesheet" href="../style.css">
    <style>
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
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
            width: 200px;
        }


        .view-toggle {
            background: #19150bff;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            display: inline-block;
        }

        .view-toggle:hover {
            background: #007BFF;
        }

        .view-toggle.active {
            background: #ffc107;
            color: #212529;
        }

        body {
            backdrop-filter: blur(10px);
        }

        .login {
            margin: auto;
            padding: 1%;
        }


        .view-controls {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>

    <h1><?= $view_type === 'servants' ? 'Servant' : 'Student' ?> Management</h1>

    <bckbtn onclick="location.href='../students/servant.php'" class="btn btn-home" aria-label="Return to home page">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Home</span>
            </bckbtn>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- View Toggle Controls -->
    <div class="view-controls">
        <a href="students.php?view=students" class="view-toggle <?= $view_type === 'students' ? 'active' : '' ?>">
            View Students (<?= $view_type === 'students' ? count($accounts) : '?' ?>)
        </a>
        <a href="students.php?view=servants" class="view-toggle <?= $view_type === 'servants' ? 'active' : '' ?>">
            View Servants (<?= $view_type === 'servants' ? count($accounts) : '?' ?>)
        </a>
    </div>

    <div class="login">
                        <span class="student-count">
                    Total Students: <?= count($accounts) ?>
                </span>
                
        <?php if ($view_type === 'students'): ?>
            <button onclick="location.href='newstu.php'">ADD A STUDENT HERE</button>
        <?php else: ?>
            <p><strong>Note:</strong> Servants are edited only by contacting IT or Uncle George.</p>
        <?php endif; ?>
    </div>

    <!-- Search -->
    <br><br>
    <div class="login" style="width:100%;">
        <form method="POST" class="search-box">
            <label><strong>Search by Name:</strong></label><br>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Enter name...">
            <button class="edit-btn" type="submit">Search</button>
            <a href="students.php?view=<?= $view_type ?>" class="edit-btn">Refresh</a><br><br>
            <label><strong>You can scroll right/left and up/down to view full table</strong></label>
        </form>

        <!-- Accounts Table -->
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <?php if ($view_type === 'students'): ?>
                            <th>Edit</th>
                            <th>Delete</th>
                        <?php endif; ?>
                        <th>Name</th>
                        <th>Hashed Password</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Grade</th>
                        <th>Phone</th>
                        <?php if ($view_type === 'students'): ?>
                            <th>Father of Confession</th>
                            <th>Lesson Taio</th>
                            <th>Bible Taio</th>
                            <th>School Grade</th>
                            <th>Age Today</th>
                            <th>Address</th>
                            <th>Last Confession</th>
                            <th> --- </th>
                            <th>Home Phone</th>
                            <th>Father</th>
                            <th>Father Phone</th>
                            <th>Email Father</th>
                            <th>Mother</th>
                            <th>Mother Phone</th>
                            <th>Email Mother</th>
                        <?php else: ?>
                            <th>Account Type</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $i => $account): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <?php if ($view_type === 'students'): ?>
                                    <td><a class="edit-btn" href="editstu.php?id=<?= $account['id'] ?>">Edit</a></td>
                                    <td><a class="edit-btn" href="deletestu.php?id=<?= $account['id'] ?>"
                                            onclick="return confirm('Are you sure you want to DELETE this student?');">Delete</a>
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($account['username']) ?></td>
                                <td><?= htmlspecialchars($account['password']) ?></td>
                                <td><?= htmlspecialchars($account['email']) ?></td>
                                <td><?= $account['sex'] == 0 ? 'Male' : 'Female' ?></td>
                                <td><?= htmlspecialchars($account['grade']) ?></td>
                                <td><?= htmlspecialchars($account['phone']) ?></td>

                                <?php if ($view_type === 'students'): ?>
                                    <!-- Student-specific columns -->
                                    <td><?= htmlspecialchars($account['father_of_confession']) ?></td>
                                    <td><?= htmlspecialchars($account['lesson_score']) ?></td>
                                    <td><?= htmlspecialchars($account['bible_score']) ?></td>
                                    <td><?= htmlspecialchars($account['attendance_score']) ?></td>
                                    <td><?= htmlspecialchars($account['school_grade']) ?></td>
                                    <td><?= calculate_age($account['birthdate']) ?></td>
                                    <td><?= htmlspecialchars($account['address']) ?></td>
                                    <td><?= htmlspecialchars($account['last_confession']) ?></td>
                                    <td></td>
                                    <td><?= htmlspecialchars($account['home_phone_num']) ?></td>
                                    <td><?= htmlspecialchars($account['name_fr']) ?></td>
                                    <td><?= htmlspecialchars($account['fr_phone_num']) ?></td>
                                    <td><?= htmlspecialchars($account['email_fr']) ?></td>
                                    <td><?= htmlspecialchars($account['name_mr']) ?></td>
                                    <td><?= htmlspecialchars($account['mr_phone_num']) ?></td>
                                    <td><?= htmlspecialchars($account['email_mr']) ?></td>
                                <?php else: ?>
                                    <td>Servant</td> <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $view_type === 'students' ? '23' : '11' ?>">
                                No <?= $view_type === 'servants' ? 'servants' : 'students' ?> found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>