<?php session_start();
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: servant.php');
    exit;
}
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'phplogin';
// Try and connect using the info above
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
// Ensure there are no connection errors
if (mysqli_connect_errno()) {
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}
// We don't have the email or registered info stored in sessions so instead we can get the results from the database
$stmt = $con->prepare('SELECT grade, email, phone, sex, is_servant FROM accounts WHERE id = ?');
// In this case, we can use the account ID to get the account info
$stmt->bind_param('i', $_SESSION['account_id']);
$stmt->execute();
$stmt->bind_result($grade, $email, $phone, $sex, $servant);
$stmt->fetch();

$videoFile = "../servants/sermon/grade_$grade.html";

// Initialize variables
$savedVideoId = '';
$savedName = '';
$savedInfo = '';
$savedUrl = '';
$status = '';

// Load from file
if (file_exists($videoFile)) {
    $lines = file($videoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, '<!--SERMON_NAME-->')) {
            $savedName = substr($line, strlen('<!--SERMON_NAME-->'));
        } elseif (str_starts_with($line, '<!--SERMON_INFO-->')) {
            $savedInfo = substr($line, strlen('<!--SERMON_INFO-->'));
        } elseif (str_starts_with($line, '<!--YOUTUBE_URL-->')) {
            $savedUrl = substr($line, strlen('<!--YOUTUBE_URL-->'));
        } elseif (!str_starts_with($line, '<!--')) {
            $savedVideoId = trim($line);
        }
    }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['youtube_url']);
    $name = trim($_POST['sermon_name']);
    $info = trim($_POST['sermon_info']);

    // Extract YouTube video ID
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
    $videoId = $params['v'] ?? '';

    if (!$videoId && str_contains($url, 'youtu.be')) {
        $path = parse_url($url, PHP_URL_PATH);
        $videoId = ltrim($path, '/');
    }

    if ($videoId) {

        $info = str_replace(["\n"], '', $info);//convert multiple lines in sermon info into one (causes errors when reading in sermons.php)

        $data = implode("\n", [
            $videoId,
            "<!--SERMON_NAME-->" . $name,
            "<!--SERMON_INFO-->" . $info,
            "<!--YOUTUBE_URL-->" . $url
        ]);
        file_put_contents($videoFile, $data);
        $savedVideoId = $videoId;
        $savedName = $name;
        $savedInfo = $info;
        $savedUrl = $url;
        $status = "sermon saved successfully!";
    } else {
        $status = "Invalid YouTube URL.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Sunday School Sermon</title>
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

        .profile-detail {
            margin: 10px 0;
        }

        body {
            font-family: Arial;
            padding: 40px;
        }

        input {
            border-radius: 20px;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            max-width: 600px;
            margin-bottom: 20px;
        }

        button {
            border-radius: 20px;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            max-width: 600px;
            margin-bottom: 20px;
            background-color: green;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        iframe {
            max-width: 100%;
            border: 5px solid #ffb300;
            border-radius: 30px;
        }

        .status {
            font-weight: bold;
            color:
                <?= str_contains($status, '') ? 'green' : 'red' ?>
            ;
        }

        body {
            backdrop-filter: blur(4px);
        }

        .login {
            width: 800px;
            padding: 10px auto;
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
    <h2>Sermon for grade <?= htmlspecialchars($grade) ?></h2><br>

    <form method="POST">
        <div class="login">
            <label for="youtube_url">YouTube URL:</label>
            <input type="url" name="youtube_url" id="youtube_url" required
                placeholder="<?= htmlspecialchars($savedUrl ?: 'https://www.youtube.com/watch?v=' . $savedVideoId) ?>">

            <label for="sermon_name">Sermon Name:</label>
            <input type="text" name="sermon_name" id="sermon_name" required value="<?= htmlspecialchars($savedName) ?>">

            <label for="sermon_info">Sermon Info:</label>
            <textarea name="sermon_info" id="sermon_info" rows="4"
                required><?= htmlspecialchars($savedInfo) ?></textarea>

            <button type="submit">Save</button>


            <?php if ($status): ?>
                <p class="status"><?= $status ?></p>
            <?php endif; ?>

        </div>
    </form>

    <?php if ($savedVideoId): ?>
        <div class="login">
            <h2>Video Preview:</h2>
            <iframe width="800" height="400" src="https://www.youtube.com/embed/<?= htmlspecialchars($savedVideoId) ?>"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>

            <br>

            <bckbtn onclick="location.href='../students/servant.php'" class="btn btn-home" aria-label="Return to home page">
                <i class="fas fa-home" aria-hidden="true"></i>
                <span>Home</span>
            </bckbtn>
        </div>
    <?php endif; ?>

</body>

</html>


<script>
    const input = document.getElementById("youtube_url");
    const iframe = document.querySelector("iframe");

    input.addEventListener("input", () => {
        const embed = convertToEmbedUrl(input.value);
        if (embed && iframe) iframe.src = embed;
    });

    function convertToEmbedUrl(url) {
        try {
            const u = new URL(url);
            if (u.hostname.includes("youtube.com")) {
                const id = u.searchParams.get("v");
                return id ? `https://www.youtube.com/embed/${id}` : "";
            }
            if (u.hostname.includes("youtu.be")) {
                return `https://www.youtube.com/embed/${u.pathname.slice(1)}`;
            }
        } catch (e) { }
        return "";
    }
</script>