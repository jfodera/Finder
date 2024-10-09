<?php
session_start();
include 'header.php';
require_once '../db/db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'finderitws@gmail.com';
        $mail->Password   = 'finderDaGoat5';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your_gmail@gmail.com', 'Finder App');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify your email for Finder App';
        $mail->Body    = "Please click the following link to verify your email: <a href='http://yourwebsite.com/verify.php?email=$email&token=$token'>Verify Email</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: user_register.php");
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9._%+-]+@rpi\.edu$/', $email)) {
        $_SESSION['error'] = "Email must be an @rpi.edu address";
        header("Location: user_register.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: user_register.php");
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists";
            header("Location: user_register.php");
            exit();
        }
        
        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        $verification_token = bin2hex(random_bytes(16));
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder, is_verified, verification_token) VALUES (?, ?, ?, ?, FALSE, FALSE, ?)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        
        if (sendVerificationEmail($email, $verification_token)) {
            $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Registration successful, but failed to send verification email. Please contact support.";
            header("Location: login.php");
            exit();
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: user_register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - User Register</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo">Finder</div>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error">' . $_SESSION['error'] . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="user_register.php" method="post"> 
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit" class="button button-account">Sign Up</button>
            </form>
            <div class="switch">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>