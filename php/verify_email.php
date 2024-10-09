<?php
session_start();
include 'header.php';
require_once '../db/db_connect.php';

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND verification_token = ? AND is_verified = FALSE");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);

        $_SESSION['success'] = "Your email has been verified. You can now log in.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid or expired verification link.";
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid verification link.";
    header("Location: login.php");
    exit();
}
?>