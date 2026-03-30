<?php
include "../base/main.php";
session_start();
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv($line);
    }
}

$host = "127.0.0.1:3306";
$username = getenv('db_user');
$password = getenv('db_pass');
$dbname = "wordle";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $dbname :" . $e->getMessage());
}

include "../base/main.php";
session_start();

$responce = '';
$home = '';

if (isset($_POST['sub'])) {
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        // Check if the token exists in the database and if the email is unconfirmed
        $stmt = $pdo->prepare("SELECT * FROM emails WHERE token = :token AND confirmed = 0");
        $stmt->execute(['token' => $token]);
        $email = $stmt->fetch();

        if ($email) {
            // Update email as confirmed
            $stmt = $pdo->prepare("UPDATE emails SET confirmed = 1 WHERE token = :token");
            $stmt->execute(['token' => $token]);

            $responce = "Your email has been confirmed. Thank you for subscribing!";
            $home = '<a href="index.php">Home</a>';
        } else {
            $responce = "Invalid or expired token.";
            $home = '<a href="index.php">Home</a>';
        }
    }
}

if (isset($_POST['desub'])) {
    if (isset($_GET['token'])) {
        $token = $_GET['token'];

        // Check if the token exists in the database
        $stmt = $pdo->prepare("SELECT * FROM emails WHERE token = :token");
        $stmt->execute(['token' => $token]);
        $email = $stmt->fetch();

        if ($email) {
            // Remove the email from the database
            $stmt = $pdo->prepare("DELETE FROM emails WHERE token = :token");
            $stmt->execute(['token' => $token]);

            $responce = "You have declined the subscription. Your email has been removed.";
            $home = '<a href="index.php">Home</a>';
        } else {
            $responce = "Invalid or expired token.";
            $home = '<a href="index.php">Home</a>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wordle</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://house-778.org/base/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://house-778.org/base/icon.ico" type="image/x-icon">
</head>
<body>
    <canvas class="back" id="canvas"></canvas>
    <?php include '../base/sidebar.php'; ?>
    <div class="con">
        <button class="circle-btn" onclick="openNav()">☰</button>  
        <h1>Subscribe to get emails of the Wordle answers</h1>
        <p>This will be an email between 1 and 2 am every day with yesterday's, today's, and tomorrow's Wordle answer.</p>
        <form method="post" action="confirm.php?token=<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
            <button type="submit" name="sub" id="sub">Subscribe</button>
        </form>
        <br>
        <form method="post" action="confirm.php?token=<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
            <button type="submit" name="desub" id="desub">Unsubscribe</button>
        </form>
        
        <p><?php echo $responce; ?></p>
        <?php echo $home; ?>
    </div>
</body>
<script src="https://theme.house-778.org/background.js"></script>
<script src="https://house-778.org/base/main.js"></script>
<script src="https://auth.house-778.org/account/track.js"></script>
<script src="https://house-778.org/base/sidebar.js"></script>
</html>
