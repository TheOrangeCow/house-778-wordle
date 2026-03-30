<?php

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '.theorangecow.org', 
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header('Access-Control-Allow-Origin: https://wordle.house-778.theorangecow.org');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

echo json_encode(['username' => $_SESSION['username']]);
?>
