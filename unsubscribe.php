<?php
include "../base/main.php";
include "../base/chech.php";
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


if (isset($_GET['email'])) {
    $email_to_remove = $_GET['email'];

    $stmt = $pdo->prepare("SELECT * FROM emails WHERE email = :email");
    $stmt->execute(['email' => $email_to_remove]);
    $email = $stmt->fetch();

    if ($email) {
        $stmt = $pdo->prepare("DELETE FROM emails WHERE email = :email");
        $stmt->execute(['email' => $email_to_remove]);
        echo "You have been unsubscribed.";
    } else {
        header("Location: index.html");
        exit;
    }
} else {
    header("Location: index.html");
    exit;
}
?>
