<?php session_start();
if (!isset($_SESSION['account_loggedin'])) {
    header('Location: ../students/servant.php');
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

$videoFile = "../servants/story/grade_$grade.html";

$savedVideoId = '';
$savedName = '';
$savedInfo = '';
$savedUrl = '';

if (file_exists($videoFile)) {
    $lines = file($videoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, '<!--SAINT_NAME-->')) {
            $savedName = substr($line, strlen('<!--SAINT_NAME-->'));
        } elseif (str_starts_with($line, '<!--SAINT_INFO-->')) {
            $savedInfo = substr($line, strlen('<!--SAINT_INFO-->'));
        } elseif (str_starts_with($line, '<!--YOUTUBE_URL-->')) {
            $savedUrl = substr($line, strlen('<!--YOUTUBE_URL-->'));
        } elseif (!str_starts_with($line, '<!--')) {
            $savedVideoId = trim($line);
        }
    }
}

$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['youtube_url']);
    $name = trim($_POST['saint_name']);
    $info = trim($_POST['saint_info']);

    // Parse YouTube video ID
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
    $videoId = $params['v'] ?? '';
    if (!$videoId && str_contains($url, 'youtu.be')) {
        $videoId = ltrim(parse_url($url, PHP_URL_PATH), '/');
    }

    if ($videoId) {
        $info = str_replace(["\n"], '', $info);//convert multiple lines in saint info into one (causes errors when reading in saints.php)

        $data = implode("\n", [
            $videoId,
            "<!--SAINT_NAME-->" . $name,
            "<!--SAINT_INFO-->" . $info,
            "<!--YOUTUBE_URL-->" . $url
        ]);
        file_put_contents($videoFile, $data);
        $status = "Saint info saved successfully!";
    } else {
        $status = "Invalid YouTube URL.";
    }
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Sunday School Saint Story</title>
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

        .bckbtn {
            bottom: 30px;
            right: 30px;
            background: var(--accent-color);
            border-radius: 50px;
            padding: 15px 25px;
            z-index: 999;
            box-shadow: var(--shadow-hover);
        }

        .bckbtn:hover {
            background-color: #222;
        }
    </style>
</head>

<body>

    <h2>Saint Story for grade <?= htmlspecialchars($grade) ?></h2><br>

    <form method="POST">
        <div class="login">
            <label for="youtube_url">YouTube URL:</label>
            <input type="url" name="youtube_url" id="youtube_url" required
                value="<?= htmlspecialchars($savedUrl ?: 'https://www.youtube.com/watch?v=' . $savedVideoId) ?>">

            <label for="saint_name">Saint:</label>
            <input type="text" name="saint_name" id="saint_name" required value="<?= htmlspecialchars($savedName) ?>">

            <label for="saint_info">About this saint:</label>
            <textarea name="saint_info" id="saint_info" rows="4" required><?= htmlspecialchars($savedInfo) ?></textarea>

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