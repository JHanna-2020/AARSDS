<?php
session_start();

// Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['png', 'jpg', 'jpeg']);
define('UPLOAD_DIR', '../servants/bible/');
define('QUESTIONS_DIR', '../servants/bible/questions/');

// Security: Check if user is logged in and has proper permissions
if (!isset($_SESSION['account_loggedin']) || !isset($_SESSION['account_id'])) {
    header('Location: ../servant.php');
    exit;
}

// Database configuration - consider moving to separate config file
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';

// Database connection with error handling
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

// Get user grade with prepared statement
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

// Get current Bible images for a grade
function getCurrentImages($grade)
{
    $patterns = [
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.png",
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.jpg",
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.jpeg"
    ];

    $files = [];
    foreach ($patterns as $pattern) {
        $files = array_merge($files, glob($pattern));
    }

    if (empty($files)) {
        return [];
    }

    // Sort by modification time (newest first)
    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    return $files;
}

// Validate file upload
function validateFile($file)
{
    $errors = [];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error occurred.";
        return $errors;
    }

    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        $errors[] = "Only PNG, JPG, and JPEG files are allowed.";
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "File size exceeds 10MB limit.";
    }

    // Additional security: Check actual file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        $errors[] = "File is not a valid PNG, JPG, or JPEG image.";
    }

    return $errors;
}

// Sanitize filename
function sanitizeFilename($filename)
{
    return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
}

// Clear old images for a specific grade
function clearOldImages($grade)
{
    $clearedFiles = [];
    $patterns = [
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.png",
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.jpg",
        UPLOAD_DIR . "grade_" . intval($grade) . "_*.jpeg"
    ];

    foreach ($patterns as $pattern) {
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $clearedFiles[] = basename($file);
                }
            }
        }
    }

    return $clearedFiles;
}


function notifyAdmin($con, $grade, $clearedFiles, $newFiles, $userId)
{
    try {

        $stmt = $con->prepare('SELECT is_servant FROM accounts WHERE id = ?'); //restricted to is_servant =1
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $username = $user ? $user['username'] : 'Unknown User';

        // Create notification message
        $timestamp = date('Y-m-d H:i:s');
        $message = "Bible Quiz Update - Grade {$grade}\n";

        if (!empty($clearedFiles)) {
            $message .= "Cleared Files (" . count($clearedFiles) . "):\n";
            foreach ($clearedFiles as $file) {
                $message .= "- {$file}\n";
            }
            $message .= "\n";
        }

        if (!empty($newFiles)) {
            $message .= "New Files (" . count($newFiles) . "):\n";
            foreach ($newFiles as $file) {
                $message .= "- {$file}\n";
            }
            $message .= "\n";
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to send admin notification: " . $e->getMessage());
        return false;
    }
}

// Handle file uploads
function handleFileUploads($con, $grade, $userId)
{
    $statusMessages = [];
    $uploadSuccess = false;
    $newFiles = [];

    if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'])) {
        return ['messages' => ['No files selected.'], 'success' => false];
    }

    $clearedFiles = clearOldImages($grade);
    if (!empty($clearedFiles)) {
        $statusMessages[] = ";] Cleared " . count($clearedFiles) . " old image(s) for grade {$grade}.";
    }

    $uploadCount = count($_FILES['images']['name']);

    for ($i = 0; $i < $uploadCount; $i++) {
        $file = [
            'name' => $_FILES['images']['name'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'size' => $_FILES['images']['size'][$i],
            'error' => $_FILES['images']['error'][$i]
        ];

        $errors = validateFile($file);

        if (!empty($errors)) {
            $statusMessages[] = ";[ File {$file['name']}: " . implode(', ', $errors);
            continue;
        }

        $safeGrade = "grade_" . intval($grade);
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newFileName = "{$safeGrade}_" . ($i + 1) . ".{$fileExt}";
        $targetFile = UPLOAD_DIR . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $uploadSuccess = true;
            $newFiles[] = $newFileName;
            $statusMessages[] = ";] File {$file['name']} uploaded successfully as {$newFileName}.";

            $stmt = $con->prepare("UPDATE accounts SET bible_is_submitted = 0 WHERE is_servant = 0 AND grade = ?");
            if ($stmt) {
                $stmt->bind_param('s', $grade);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $statusMessages[] = ";[ Failed to upload {$file['name']}.";
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

    if (!is_dir(QUESTIONS_DIR)) {
        mkdir(QUESTIONS_DIR, 0755, true);
    }

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

// Main execution
try {
    $grade = getUserGrade($con, $_SESSION['account_id']);
    if (!$grade) {
        throw new Exception('Unable to determine user grade.');
    }

    $statusMessage = '';
    $uploadSuccess = false;
    $questionErrors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
        $uploadResult = handleFileUploads($con, $grade, $_SESSION['account_id']);
        $statusMessage = implode('<br>', $uploadResult['messages']);
        $uploadSuccess = $uploadResult['success'];
    }

    $questions = loadQuestions($grade);

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

    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $deleteId = intval($_GET['delete']);
        $questions = array_filter($questions, fn($q) => $q['id'] !== $deleteId);

        if (saveQuestions($grade, array_values($questions))) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

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

    // Handle delete all screenshots
    if (isset($_GET['delete_all']) && $_GET['delete_all'] === 'confirm') {
        $deletedFiles = clearOldImages($grade);
        if (!empty($deletedFiles)) {
            $statusMessage = ";] Successfully deleted " . count($deletedFiles) . " screenshot(s) for Grade " . htmlspecialchars($grade) . ".";
            $uploadSuccess = true;
        } else {
            $statusMessage = "ℹ️ No screenshots found to delete for Grade " . htmlspecialchars($grade) . ".";
            $uploadSuccess = false;
        }
        // Redirect to prevent accidental re-deletion
        header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
        exit;
    }

    // Get current images
    $currentImages = getCurrentImages($grade);

} catch (Exception $e) {
    error_log($e->getMessage());
    die('An error occurred. Please try again later.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allow_quiz'])) {
    $stmt = $con->prepare('UPDATE accounts SET bible_is_submitted = 0 WHERE grade = ?');
    $stmt->bind_param('i', $grade);
    $stmt->execute();
    $stmt->close();
    $statusMessage = ";] Your students are now allowed to take the bible trivia quiz. (grade $grade)";
    $uploadSuccess = true;
}

// Handle success message from deletion redirect
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $statusMessage = ";] Screenshots have been successfully deleted.";
    $uploadSuccess = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bible Quiz Management - Grade <?= htmlspecialchars($grade) ?></title>
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

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .image-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .image-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #bbb;
        }
    </style>
</head>

<body>
    <div class="hymns-container">

        <!-- Header -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">
                📖 Bible Quiz Management System
            </h1>
            <p class="text-center text-gray-600">Grade <?= htmlspecialchars($grade) ?></p>
        </div>

        <!-- Current Images Preview -->
        <?php if (!empty($currentImages)): ?>
            <div class="bg-white shadow-lg rounded-xl p-6 mb-8 fade-in">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-semibold text-gray-800">Current Bible Images (screenshots)</h2>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-500">
                            <?= count($currentImages) ?> image(s) uploaded
                        </span>
                        <button onclick="confirmDeleteAll()"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                            🗑️ Delete All Images
                        </button>
                    </div>
                </div>

                <div class="image-grid">
                    <?php foreach ($currentImages as $index => $imagePath): ?>
                        <div class="image-card">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="Bible Screenshot <?= $index + 1 ?>"
                                onclick="openModal('<?= htmlspecialchars($imagePath) ?>', 'Bible Screenshot <?= $index + 1 ?>')">
                            <div class="p-3 bg-gray-50">
                                <p class="text-sm text-gray-600 truncate" title="<?= htmlspecialchars(basename($imagePath)) ?>">
                                    📄 <?= htmlspecialchars(basename($imagePath)) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p class="text-sm text-gray-500 mt-4 text-center">
                    💡 Click on any image to view it in full size
                </p>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4"> Current Bible Images (screenshots)</h2>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <p class="text-yellow-800">No Bible screenshots uploaded yet for Grade <?= htmlspecialchars($grade) ?>.
                    </p>
                    <p class="text-yellow-700 text-sm mt-2">Upload PNG, JPG, or JPEG images below to get started.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Question Form -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8 fade-in">
            <h2 class="text-2xl font-semibold mb-6 text-gray-800">
                <?= $editQuestion ? 'Edit Question' : 'Add New Question' ?>
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
                            <option value="<?= $letter ?>" <?= ($editQuestion['correct'] ?? '') === $letter ? 'selected' : '' ?>>
                                Option <?= $letter ?>
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
            <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
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

        <!-- File Upload -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800">Upload Bible Images (screenshots)</h2>

            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6">
                <h3 class="text-orange-800 font-semibold mb-2">⚠️ Important Notes:</h3>
                <ul class="text-orange-800 space-y-1">
                    <li>• All existing images for Grade <?= htmlspecialchars($grade) ?> will be automatically deleted
                        when you upload new ones</li>
                    <li>• Supported formats: PNG, JPG, JPEG (Max 10MB each)</li>
                    <li>• Students will be able to answer new questions after pressing the "Allow Quiz" button below
                    </li>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Select Images (PNG, JPG, JPEG - Max 10MB each)
                    </label>
                    <input type="file" name="images[]" multiple required accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    📤 Upload Images
                </button>
            </form>
        </div>

        <bckbtn onclick="location.href='../students/servant.php'" class="btn btn-home" aria-label="Return to home page">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Home</span>
        </bckbtn>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <div id="caption"
            style="margin: auto; display: block; width: 80%; max-width: 700px; text-align: center; color: #ccc; padding: 10px 0;">
        </div>
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

        // Modal functions
        function openModal(imageSrc, caption) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const captionText = document.getElementById('caption');

            modal.style.display = "block";
            modalImg.src = imageSrc;
            captionText.innerHTML = caption;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }

        // Delete all screenshots confirmation
        function confirmDeleteAll() {
            const imageCount = <?= count($currentImages) ?>;
            if (imageCount === 0) {
                alert('No images to delete.');
                return;
            }

            const confirmed = confirm(
                `⚠️ Are you sure you want to delete ALL ${imageCount} image(s) for Grade <?= htmlspecialchars($grade) ?>?\n\n` +
                'This action cannot be undone!'
            );

            if (confirmed) {
                const doubleConfirm = confirm(
                    'This will permanently delete all images. Are you absolutely sure?'
                );

                if (doubleConfirm) {
                    window.location.href = '?delete_all=confirm';
                }
            }
        }

        // Close modal when clicking outside the image
        window.onclick = function (event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>

</html>

<?php $con->close(); ?>