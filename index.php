<?php
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
session_start(); 
include "../base/main.php";


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
$error = "";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require '/var/www/house-778/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_confirmation_email($email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->SMTPDebug = 0;
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'coworange9@gmail.com';
        $mail->Password = getenv('GMAIL_APP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('coworange9@gmail.com', 'TheWordleAnswers');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Confirm your subscription';
        $mail->Body    = "Please confirm your email by clicking on the following link: 
                          <a href='https://wordle.house-778.theorangecow.org/confirm.php?token=$token'>Confirm Subscription</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}


function wordle_answer($date) {
    $url = "https://www.nytimes.com/svc/wordle/v2/{$date}.json";
    try {
        $response = @file_get_contents($url);
        if ($response === FALSE) {
            throw new Exception("Error fetching the Wordle answer");
        }
        $data = json_decode($response, true);
        return isset($data['solution']) ? $data['solution'] : null;
    } catch (Exception $e) {
        echo $e->getMessage();
        return null;
    }
}

$yesterday_date = date("Y-m-d", strtotime("-1 day"));
$today_date = date("Y-m-d");
$tomorrow_date = date("Y-m-d", strtotime("+1 day"));

$yesterday_answer = wordle_answer($yesterday_date);
$today_answer = wordle_answer($today_date);
$tomorrow_answer = wordle_answer($tomorrow_date);

$yesterday_answer = $yesterday_answer ?: "Couldn't find answer";
$today_answer = $today_answer ?: "Couldn't find answer";
$tomorrow_answer = $tomorrow_answer ?: "Couldn't find answer";

if (isset($_POST['email'])) {
    $new_email = $_POST['email'];

    $query = "SELECT * FROM emails WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $new_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['confirmed']) {
            $error = "You are already subscribed.";
        } else {
            $token = $row['token'];
            if (send_confirmation_email($new_email, $token)) {
                $error = "A confirmation email has been resent. Please check your inbox.";
            } else {
                $error = "There was an error resending the confirmation email.";
            }
        }
    } else {
        $token = bin2hex(random_bytes(16));
        $query = "INSERT INTO emails (email, token, confirmed) VALUES (?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $new_email, $token);
        if ($stmt->execute()) {
            if (send_confirmation_email($new_email, $token)) {
                $error = "A confirmation email has been sent. Please check your inbox.";
            } else {
                $error = "There was an error sending the confirmation email.";
            }
        } else {
            $error = "There was an error saving your email.";
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
        <link rel="stylesheet" href="https://house-778.theorangecow.org/base/style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">
        <link rel="icon" href="https://house-778.theorangecow.org/base/icon.ico" type="image/x-icon">
    </head>
    <body>
        <canvas class="back" id="canvas"></canvas>
        <?php include '../base/sidebar.php'; ?>
        <div class= "con">
            <button class="circle-btn" onclick = "openNav()">☰</button>  
            <button class="home" onclick="window.location.href = 'https://house-778.theorangecow.org'">Home</button>
            <h1>Welcome, <?php echo $_SESSION['username']; ?> to wordle answers</h1>
            <h2>
                Yesterday's answer:
            </h2>
            <h3>
                <?php echo $yesterday_answer?>
            </h3>
            <h2>
                Today's answer:
            </h2>
            <h3>
                <?php echo $today_answer?>
            </h3>
            <h2>
                Tomorrow's answer:
            </h2>
            <h3>
                <?php echo $tomorrow_answer?>
            </h3>
            <h2>We can send you this info every day just add your email</h2>
            <form method="post" action="index.php">
                <label for="email">Enter your email to subscribe:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Subscribe</button>
                <p><?php echo $error ?></p>
            </form>
        </div>
    </body>
    <script src="https://theme.house-778.theorangecow.org/background.js"></script>
    <script src="https://house-778.theorangecow.org/base/main.js"></script>
    <script src="https://auth.house-778.theorangecow.org/account/track.js"></script>
    <script src="https://house-778.theorangecow.org/base/sidebar.js"></script>
</html>

<?php
$conn->close();
?>
