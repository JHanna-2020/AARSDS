<?php
session_start();

define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['pdf']);
define('UPLOAD_DIR', '../servants/lesson/');
define('QUESTIONS_DIR', '../servants/lesson/questions/');


if (!isset($_SESSION['account_loggedin']) || !isset($_SESSION['account_id'])) {
    header('Location: ../servant.php');
    exit;
}

$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

try {
    $con = new mysqli($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
    if ($con->connect_error) {
        throw new Exception('Database connection failed: ' . $con->connect_error);
    }
    $con->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log($e->getMessage());
    die('Database connection error. Please try again later.');
}

function getUserGrade($con, $userId)
{
    $stmt = $con->prepare('SELECT grade FROM accounts WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $con->error);
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? $row['grade'] : null;
}

function getCurrentLesson($grade) {
    $pattern = UPLOAD_DIR . "grade_" . intval($grade) . ".pdf";
    $files = glob($pattern);
    
    if (empty($files)) {
        return null;
    }
    
    // Return the most recent file (by timestamp in filename)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    return $files[0];
}

function validateFile($file)
{
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error occurred.";
        return $errors;
    }

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        $errors[] = "Only PDF files are allowed.";
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "File size exceeds 10MB limit.";
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/pdf') {
        $errors[] = "File is not a valid PDF.";
    }
    return $errors;
}

function sanitizeFilename($filename)
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

function clearOldImages($grade)
{
    $clearedFiles = [];
    $pattern = UPLOAD_DIR . "grade_" . intval($grade) . ".pdf";
    $files = glob($pattern);

    foreach ($files as $file) {
        if (is_file($file)) {
            if (unlink($file)) {
                $clearedFiles[] = basename($file);
            }
        }
    }

    return $clearedFiles;
}
function handleFileUploads($con, $grade, $userId)
{
    $statusMessages = [];
    $uploadSuccess = false;
    $newFiles = [];

    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        return ['messages' => ['No file selected.'], 'success' => false];
    }

    // Clear old files for this grade
    $clearedFiles = clearOldImages($grade);
    if (!empty($clearedFiles)) {
        $statusMessages[] = ";] Cleared " . count($clearedFiles) . " old file(s) for grade {$grade}.";
    }

    $file = $_FILES['pdf'];
    $errors = validateFile($file);

    if (!empty($errors)) {
        $statusMessages[] = " File {$file['name']}: " . implode(', ', $errors);
    } else {
        
        $targetFile = UPLOAD_DIR . "grade_" . intval($grade) . ".pdf";

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $uploadSuccess = true;
            $newFiles[] = $targetFile;
            $statusMessages[] = ";] File {$file['name']} uploaded successfully as {$targetFile}.";

            // Reset student submission state
            $stmt = $con->prepare("UPDATE accounts SET lesson_is_submitted = 0 WHERE is_servant = 0 AND grade = ?");
            if ($stmt) {
                $stmt->bind_param('s', $grade);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $statusMessages[] = " Failed to upload {$file['name']}.";
        }
    }

    return ['messages' => $statusMessages, 'success' => $uploadSuccess];
}


function loadQuestions($grade)
{
    $file = QUESTIONS_DIR . 'grade_' . intval($grade) . '.json';

    if (!file_exists($file)) {
        return [];
    }

    $content = file_get_contents($file);
    $questions = json_decode($content, true);

    return is_array($questions) ? $questions : [];
}

function saveQuestions($grade, $questions)
{
    $file = QUESTIONS_DIR . 'grade_' . intval($grade) . '.json';

    return file_put_contents($file, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function validateQuestionData($data)
{
    $errors = [];

    if (empty(trim($data['question']))) {
        $errors[] = "Question text is required.";
    }

    foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $option) {
        if (empty(trim($data[$option]))) {
            $errors[] = "All options must be filled.";
            break;
        }
    }

    if (!in_array($data['correct'], ['A', 'B', 'C', 'D'])) {
        $errors[] = "Please select a correct answer.";
    }

    return $errors;
}

try {
    $grade = getUserGrade($con, $_SESSION['account_id']);
    if (!$grade) {
        throw new Exception('Unable to determine user grade.');
    }

    $statusMessage = '';
    $uploadSuccess = false;
    $questionErrors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf'])) {
        $uploadResult = handleFileUploads($con, $grade, $_SESSION['account_id']);
        $statusMessage = implode('<br>', $uploadResult['messages']);
        $uploadSuccess = $uploadResult['success'];
    }

    // Load existing questions
    $questions = loadQuestions($grade);

    // Handle question form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
        $questionData = [
            'id' => !empty($_POST['id']) ? intval($_POST['id']) : time(),
            'question' => trim($_POST['question']),
            'option_a' => trim($_POST['option_a']),
            'option_b' => trim($_POST['option_b']),
            'option_c' => trim($_POST['option_c']),
            'option_d' => trim($_POST['option_d']),
            'correct' => $_POST['correct'] ?? ''
        ];

        $questionErrors = validateQuestionData($questionData);

        if (empty($questionErrors)) {
            // Find and update existing question or add new one
            $found = false;
            foreach ($questions as &$q) {
                if ($q['id'] === $questionData['id']) {
                    $q = $questionData;
                    $found = true;
                    break;
                }
            }
            unset($q);

            if (!$found) {
                $questions[] = $questionData;
            }

            if (saveQuestions($grade, $questions)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $questionErrors[] = "Failed to save question.";
            }
        }
    }

    // Handle question deletion
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $deleteId = intval($_GET['delete']);
        $questions = array_filter($questions, fn($q) => $q['id'] !== $deleteId);

        if (saveQuestions($grade, array_values($questions))) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // Get question data for editing
    $editQuestion = null;
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $editId = intval($_GET['edit']);
        foreach ($questions as $q) {
            if ($q['id'] === $editId) {
                $editQuestion = $q;
                break;
            }
        }
    }

    // Get current lesson file
    $currentLesson = getCurrentLesson($grade);

} catch (Exception $e) {
    error_log($e->getMessage());
    die('An error occurred. Please try again later.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allow_quiz'])) {
    $stmt = $con->prepare('UPDATE accounts SET lesson_is_submitted = 0 WHERE grade = ?');
    $stmt->bind_param('i', $grade);
    $stmt->execute();
    $stmt->close();
    $statusMessage = ";] Your students are now allowed to take the lesson quiz. (grade $grade)";
    $uploadSuccess = true;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lesson Quiz Management - Grade <?= htmlspecialchars($grade) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link href="../theme.css" rel="stylesheet" type="text/css">
    <script src="../dark.js" defer></script>
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .status-success {
            color: #10b981;
        }

        .status-error {
            color: #ef4444;
        }

        label {
            background-color: rgba(255, 255, 255, .3);
        }

        .pdf-viewer {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .pdf-viewer iframe {
            border: none;
            background: white;
        }
    </style>
</head>

<body>
    <div class="hymns-container">

        <!-- Header -->
        <div class=" shadow-lg rounded-xl p-6 mb-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">
                Lesson Quiz Management System
            </h1>
            <p class="text-center text-gray-600">Grade <?= htmlspecialchars($grade) ?></p>
        </div>

        <!-- Current Lesson Viewer -->
        <?php if ($currentLesson): ?>
            <div class="shadow-lg rounded-xl p-6 mb-8 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">📖 Current Lesson</h2>
                    <div class="flex gap-2">
                        <span class="text-sm text-gray-500">
                            <?= htmlspecialchars(basename($currentLesson)) ?>
                        </span>
                        <a href="<?= htmlspecialchars($currentLesson) ?>" target="_blank" 
                           class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                            Open in New Tab
                        </a>
                    </div>
                </div>
                
                <div class="pdf-viewer">
                    <iframe src="<?= htmlspecialchars($currentLesson) ?>" 
                            width="100%" 
                            height="600px"
                            title="Current Lesson PDF">
                        <p class="p-4 text-center text-gray-600">
                            Your browser doesn't support PDF viewing. 
                            <a href="<?= htmlspecialchars($currentLesson) ?>" target="_blank" 
                               class="text-blue-500 hover:underline">Click here to download the PDF</a>.
                        </p>
                    </iframe>
                </div>
            </div>
        <?php else: ?>
            <div class="shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">📖 Current Lesson</h2>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-yellow-800">No lesson uploaded yet for Grade <?= htmlspecialchars($grade) ?>.</p>
                    <p class="text-yellow-700 text-sm mt-2">Upload a PDF lesson (if you did please REFRESH page).</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Question Form -->
        <div class=" shadow-lg rounded-xl p-6 mb-8 fade-in">
            <h2 class="text-2xl font-semibold mb-6 text-gray-800">
                <?= $editQuestion ? 'x Edit Question' : '+ Add New Question' ?>
            </h2>

            <?php if (!empty($questionErrors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h3 class="text-red-800 font-semibold mb-2">Please fix the following errors:</h3>
                    <ul class="text-red-700 list-disc list-inside">
                        <?php foreach ($questionErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <input type="hidden" name="id" value="<?= $editQuestion['id'] ?? '' ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
                    <textarea name="question" rows="3" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter your question here..."><?= htmlspecialchars($editQuestion['question'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Option <?= $letter ?></label>
                            <input type="text" name="option_<?= strtolower($letter) ?>" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Enter option <?= $letter ?>"
                                value="<?= htmlspecialchars($editQuestion['option_' . strtolower($letter)] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Correct Answer</label>
                    <select name="correct" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select the correct option</option>
                        <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                            <option value="<?= $letter ?>" <?= ($editQuestion['correct'] ?? '') === $letter ? 'selected' : '' ?>>Option <?= $letter ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-4">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                        <?= $editQuestion ? 'Update Question' : 'Add Question' ?>
                    </button>
                    <?php if ($editQuestion): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                            Cancel Edit
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Questions List -->
        <?php if (!empty($questions)): ?>
            <div class=" shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-6 text-gray-800">📝 Existing Questions (<?= count($questions) ?>)</h2>

                <div class="space-y-6">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-lg font-semibold text-gray-800">Question <?= $index + 1 ?></h3>
                                <div class="flex gap-2">
                                    <a href="?edit=<?= $q['id'] ?>"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        Edit
                                    </a>
                                    <a href="?delete=<?= $q['id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this question?')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        Delete
                                    </a>
                                </div>
                            </div>

                            <p class="text-gray-800 mb-3 font-medium"><?= htmlspecialchars($q['question']) ?></p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
                                <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                                    <div
                                        class="flex items-center p-2 rounded <?= $q['correct'] === $letter ? 'bg-green-100 border border-green-300' : 'bg-gray-50' ?>">
                                        <span class="font-semibold text-gray-700 mr-2"><?= $letter ?>)</span>
                                        <span
                                            class="text-gray-800"><?= htmlspecialchars($q['option_' . strtolower($letter)]) ?></span>
                                        <?php if ($q['correct'] === $letter): ?>
                                            <span class="ml-auto text-green-600 font-semibold">✓ Correct</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- File Upload Section -->
        <div class=" shadow-lg rounded-xl p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">📁 Upload New Lesson</h2>

            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <h3 class="text-orange-800 font-semibold mb-2">⚠️ Important Notes:</h3>
                <ul class="text-orange-800 space-y-1">
                    <li>• Existing lesson for Grade <?= htmlspecialchars($grade) ?> will be automatically deleted when you upload a new lesson</li>
                    <li>• Students will be able to answer new questions after pressing the "Allow Quiz" button below</li>
                </ul>
                
                <div class="mt-4">
                    <form method="post" class="inline">
                        <input type="hidden" name="allow_quiz" value="1">
                        <button type="submit" 
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors">
                             Allow Students to Answer (Start Quiz)
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($statusMessage)): ?>
                <div
                    class="mb-6 p-4 rounded-lg <?= $uploadSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
                    <p class="<?= $uploadSuccess ? 'status-success' : 'status-error' ?> font-medium">
                        <?= $statusMessage ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block font-medium text-center mb-2">
                        Select PDF File
                    </label>
                    <input type="file" name="pdf" required accept="application/pdf"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    📤 Upload Lesson (PDF)
                </button>
            </form>
        </div>

        <bckbtn onclick="location.href='../students/servant.php'" class="btn btn-home" aria-label="Return to home page">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Home</span>
        </bckbtn>
    </div>

    <script>
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const successMessages = document.querySelectorAll('.status-success');
            successMessages.forEach(function (message) {
                setTimeout(function () {
                    message.style.transition = 'opacity 0.5s';
                    message.style.opacity = '0';
                }, 5000);
            });
        });
    </script>
</body>

</html>

<?php $con->close(); ?>