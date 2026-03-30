<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '/var/www/house-778/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists( "/var/www/house-778/wordle/.env")) {
    foreach (file("/var/www/house-778/wordle/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv($line);
    }
}


$host = "127.0.0.1:3306";
$user = getenv('db_user');
$pass = getenv('db_pass');
$db = "wordle";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

$query = "SELECT email FROM emails WHERE confirmed = 1";
$result = $conn->query($query);

$emails = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['email'];
    }
}

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

    foreach ($emails as $email) {
        $unsubscribe_link = "http://wordle.house-778.org/unsubscribe.php?email=" . urlencode($email);

        $mail->addAddress($email);

        $mail->Body = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Template</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <div style="text-align: center; width: 100%; max-width: 600px; margin: 30px auto; border-radius: 10px; background-color: #ffffff; padding: 20px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
        <div style="padding: 20px; line-height: 1.6; color: #333333;">
            <h1 style="font-size: 24px; margin-bottom: 20px;">Wordle Answer</h1>
            <p style="font-size: 16px; margin-bottom: 20px;">Dear {$email},</p>
            <p style="font-size: 16px; margin-bottom: 20px;">Yesterday's answer was:</p>
            <h2>{$yesterday_answer}</h2>
            <p style="font-size: 16px; margin-bottom: 20px;">Today's answer is:</p>
            <h2>{$today_answer}</h2>
            <p style="font-size: 16px; margin-bottom: 20px;">Tomorrow's answer will be:</p>
            <h2>{$tomorrow_answer}</h2>
            <p style="font-size: 16px; margin-bottom: 20px;">Best regards,</p>
            <p style="font-size: 16px; margin-bottom: 20px;">The Wordle Team</p>
            <p style="font-size: 12px; margin-top: 20px; color: #777777;">This is an automated email. Please do not reply.</p>
        </div>
        <div style="text-align: center; padding: 10px 0; font-size: 14px; color: #888888;">
            <p>&copy; 2024 House-778. All rights reserved.</p>
            <p><a href="{$unsubscribe_link}" style="color: #888888; text-decoration: none;">Unsubscribe</a></p>
        </div>
    </div>
</body>
</html>
EOT;

        $mail->AltBody = 'Hi, here are the Wordle answers for yesterday, today, and tomorrow.';
        $mail->send();
        $mail->clearAddresses();
    }

    echo 'Messages have been sent';
} catch (Exception $e) {
    echo "Messages could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

$conn->close();
?>
