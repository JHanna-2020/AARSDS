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

$stmt = $con->prepare('SELECT grade,lesson_score,lesson_is_submitted FROM accounts WHERE id = ?');

$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade,$lesson_score,$lesson_is_submitted);
$stmt->fetch();
$stmt->close();

if ($lesson_is_submitted == 1) {
    header('Location: leaderboard.php');
    exit;
}

$questions = json_decode(file_get_contents("grade_".$grade.".json"), true);

$limit = 1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = $page - 1;

$total = count($questions);
$total_pages = ceil($total / $limit);



if (!isset($questions[$offset])) {
    
    echo "<h2 class='text-center mt-10 text-2xl font-semibold'>;] Completed!</h2>";
    echo "<div class='text-center mt-4'><a href='leaderboard.php' class='text-blue-600 underline'>View Correct Answers</a></div>";
    exit;
}

$q = $questions[$offset];

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lesson Quiz</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-[rgba(168,85,247,0.8)] to-[rgba(49,46,129,0.8)] min-h-screen flex items-center justify-center text-white ">
  <div class="w-full max-w-3xl p-6 bg-white/10 backdrop-blur-md shadow-xl text-center rounded-[15px]">
    <h2 class="text-2xl font-bold mb-4">Question <?= $page ?></h2>
    <p class="text-xl mb-8"><?= htmlspecialchars($q['question']) ?></p>
    <form action="answer.php" method="POST" class="grid grid-cols-2 gap-4 text-lg font-semibold">
      <input type="hidden" name="question_id" value="<?= $page ?>">
      <input type="hidden" name="correct" value="<?= $q['correct'] ?>">
      <input type="hidden" name="page" value="<?= $page ?>">

      <button name="answer" value="A" class="bg-red-500 hover:bg-red-600 transition-colors p-6 rounded-2xl w-full">A) <?= htmlspecialchars($q['option_a']) ?></button>
      <button name="answer" value="B" class="bg-blue-500 hover:bg-blue-600 transition-colors p-6 rounded-2xl w-full">B) <?= htmlspecialchars($q['option_b']) ?></button>
      <button name="answer" value="C" class="bg-yellow-400 hover:bg-yellow-500 transition-colors p-6 rounded-2xl w-full">C) <?= htmlspecialchars($q['option_c']) ?></button>
      <button name="answer" value="D" class="bg-green-500 hover:bg-green-600 transition-colors p-6 rounded-2xl w-full">D) <?= htmlspecialchars($q['option_d']) ?></button>
    </form>

    <?php if (isset($_GET['answered'])): ?>
    <div class="mt-6">
      <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="inline-block bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-xl font-semibold">Next Question</a>
      <?php else:  ?>
        <a href="leaderboard.php" class="inline-block bg-green-500 hover:bg-green-600 px-6 py-3 rounded-xl font-semibold">View Correct Answers</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>
