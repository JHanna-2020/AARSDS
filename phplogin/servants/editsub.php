<?php
session_start();

if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../servant.php');
    exit;
}

$con = new mysqli('localhost', 'root', '', 'phplogin');
if ($con->connect_error) {
    exit(' DB connection failed: ' . $con->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$submission = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $new_score = (int)$_POST['score'];

    $stmt = $con->prepare("UPDATE submissions SET score = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_score, $id);

    if ($stmt->execute()) {
        header("Location: stugrades.php");
        exit;
    } else {
        $message = "Failed to update score: " . $stmt->error;
    }
}

// Fetch submission info
$stmt = $con->prepare("SELECT * FROM submissions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$submission = $result->fetch_assoc();

if (!$submission) {
    exit(" Not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Submission Taio (score)</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container { max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ccc; background: #f9f9f9; }
        label { display: block; margin-top: 10px; }
        input[type=number], input[type=text] { width: 100%; padding: 8px; }
        button { margin-top: 20px; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; }
        button:hover { background: #0056b3; }
        .message { margin-top: 15px; color: red; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Edit Taio (score) for <?= htmlspecialchars($submission['user_identifier']) ?></h2>

    <form method="POST">
        <input type="hidden" name="id" value="<?= $submission['id'] ?>">

        <label>Student Name</label>
        <input type="text" value="<?= htmlspecialchars($submission['user_identifier']) ?>" readonly>

        <label>Submission Time</label>
        <input type="text" value="<?= htmlspecialchars($submission['submission_time']) ?>" readonly>

        <label>Total Taio score</label>
        <input type="number" name="score" min="0" max="100" value="<?= $submission['score'] ?>" required>

        <button type="submit">Update Taio Score</button>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
    </form>
</div>
<bckbtn onclick="location.href='../stugrades.php'" id="bckbtn" style="vertical-align:middle">
            <span>Back</span>
        </bckbtn>

</body>
</html>
