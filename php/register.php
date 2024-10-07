<?php
session_start();
require_once '../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $is_recorder = isset($_POST['code']);
    
    // Determine which page to redirect back to in case of error
    $redirect_page = $is_recorder ? 'recorder_register.php' : 'user_register.php';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: $redirect_page");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: $redirect_page");
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists";
            header("Location: $redirect_page");
            exit();
        }
        
        // If recorder, validate recorder code
        if ($is_recorder) {
            $code = $_POST['code'];
            $stmt = $pdo->prepare("SELECT code_id FROM recorder_codes WHERE code = ? AND user_id IS NULL");
            $stmt->execute([$code]);
            $recorder_code = $stmt->fetch();
            
            if (!$recorder_code) {
                $_SESSION['error'] = "Invalid or used recorder code";
                header("Location: recorder_register.php");
                exit();
            }
        }
        
        // Insert new user
        $pdo->beginTransaction();
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Split full name into first and last name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $is_recorder]);
        $user_id = $pdo->lastInsertId();
        
        // If recorder, update recorder code
        if ($is_recorder) {
            $stmt = $pdo->prepare("UPDATE recorder_codes SET user_id = ? WHERE code = ?");
            $stmt->execute([$user_id, $code]);
        }
        
        $pdo->commit();
        
        $_SESSION['success'] = "Registration successful! Please log in.";
        header("Location: login.php");
        exit();
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: $redirect_page");
        exit();
    }
}
?>