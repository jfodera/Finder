<?php

session_start();
include 'header.php';
require_once '../db/db_connect.php';

// Check if email and token are in get request
if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
    $token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);
    
    try {
        // prepare statement to select user_id from users table where email, verification_token and is_verified are as provided
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND verification_token = ? AND is_verified = 0");
        $stmt->execute([$email, $token]);
        
        // if user is found update is_verified field to true and NULL the verification_token
        if ($user = $stmt->fetch()) {
            $updateStmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error occurred.";
        error_log($e->getMessage());
    }
    
    header("Location: login.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}

?>