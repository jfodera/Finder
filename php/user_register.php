<?php 
session_start();
include 'header.php';
require_once '../db/db_connect.php';
require_once '../vendor/autoload.php';

function sendVerificationEmail($email, $token) {
    // Create the Transport
    $transport = (new Swift_SmtpTransport('smtp.gmail.com', 587, 'tls'))
        ->setUsername('finderitws@gmail.com')
        ->setPassword('nrfknktpwopyxznz'); 

    $mailer = new Swift_Mailer($transport);

    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'yourdomain.com';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
    $verificationLink = $protocol . $domain . "php/verify_email.php?email=" . urlencode($email) . "&token=" . $token;

    $message = (new Swift_Message('Verify your Finder account'))
        ->setFrom(['finderitws@gmail.com' => 'Finder'])
        ->setTo([$email])
        ->setBody(
            '<html>' .
            '<body>' .
            '<h1>Welcome to Finder!</h1>' .
            '<p>Please click the link below to verify your account:</p>' .
            '<p><a href="' . $verificationLink . '">Verify Account</a></p>' .
            '</body>' .
            '</html>',
            'text/html'
        );

    try {
        $result = $mailer->send($message);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send verification email: " . $e->getMessage());
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

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: user_register.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists";
            header("Location: user_register.php");
            exit();
        }
        
        $verification_token = bin2hex(random_bytes(32));
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder, verification_token) VALUES (?, ?, ?, ?, FALSE, ?)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        
        if (sendVerificationEmail($email, $verification_token)) {
            $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
        } else {
            $_SESSION['warning'] = "Account created, but verification email could not be sent. Please contact support.";
        }
        
        header("Location: login.php");
        exit();
        
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
            if (isset($_SESSION['warning'])) {
                echo '<div class="warning">' . $_SESSION['warning'] . '</div>';
                unset($_SESSION['warning']);
            }
            ?>
            <form action="user_register.php" method="post"> 
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email (@rpi.edu)" required>
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