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

$session_score = isset($_SESSION['bible_score']) ? $_SESSION['bible_score'] : 0;

$stmt = $con->prepare('SELECT grade, bible_score, bible_is_submitted FROM accounts WHERE id = ?'); 
$stmt->bind_param('i', $_SESSION['account_id']); 
$stmt->execute(); 
$stmt->bind_result($grade, $current_bible_score, $bible_is_submitted); 
$stmt->fetch(); 
$stmt->close();  

$questions = json_decode(file_get_contents("grade_".$grade.".json"), true); 
$total = count($questions);  

//process if we have a session score and haven't submitted yet
if ($session_score > 0 && !$bible_is_submitted) {
    $new_score = $current_bible_score + $session_score;

    $updateStmt = $con->prepare('UPDATE accounts SET bible_score = ?, bible_is_submitted = 1 WHERE id = ?'); 
    $updateStmt->bind_param('ii', $new_score, $_SESSION['account_id']); 
    $updateStmt->execute(); 
    $updateStmt->close();
    
    // Clear session score after successful update
    unset($_SESSION['bible_score']);
} else {
    // Already submitted or no session score, use current database score
    $new_score = $current_bible_score;
}

mysqli_close($con); 
?>  

<!DOCTYPE html> 
<html>  
<head>   
    <meta charset="UTF-8">   
    <title>Quiz Complete</title>   
    <script src="https://cdn.tailwindcss.com"></script> 
</head> 
<body class="bg-gradient-to-br from-purple-600 to-indigo-900 text-white min-h-screen flex items-center justify-center">   
    <div class="text-center">     
        <h1 class="text-4xl font-bold mb-4">Quiz Complete!</h1>     
        <?php if ($session_score > 0 && !$bible_is_submitted): ?>
            <p class="text-xl mb-8">You answered <span class="font-bold"><?= $session_score ?> out of <?= $total ?> questions correct.</span></p>     
        <?php else: ?>
            <p class="text-xl mb-8">You cannot answer Bible trivia now.</p>
        <?php endif; ?>
        <p class="text-2xl font-bold">Your Total Score is now: <?php echo $new_score; ?></p>   
    </div> 
</body> 
</html>