<?php 
// new session when go to this page 
session_start();

$baseDir = dirname(__DIR__); 
$headerPath = $baseDir . '/php/header.php'; 
$realHeaderPath = realpath($headerPath); 

if ($realHeaderPath === false || !is_file($realHeaderPath) || 
    strpos($realHeaderPath, realpath($baseDir)) !== 0) {
    die('Invalid header path');
}

require_once $realHeaderPath; 
require_once $baseDir . '/db/db_connect.php';
require_once $baseDir . '/vendor/autoload.php';

/* 

Thought process:
only users should need to verify their emails to confirm they go to RPI as the recorders technically
don't need verification because we give them a unique recorder code

How it works:
the sendVerificationEmail sends email when u register and when you click on the link it auto verifies email

*/
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendVerificationEmail($email, $token) {
    // last arg is encryption protocol
    $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
        ->setUsername($_ENV['SMTP_USER'])
        ->setPassword($_ENV['SMTP_PASS']); 

    $mailer = new Swift_Mailer($transport);

    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'yourdomain.com';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
    // makes the link to send to our emails
    $verificationLink = $protocol . $domain . "/" . $_ENV['URL'] . "/php/verify_email.php?email=" . urlencode($email) . "&token=" . $token;

    $message = (new Swift_Message('Verify your Finder account'))
        ->setFrom([$_ENV['SMTP_USER'] => 'Finder'])
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
        $_SESSION['error'] ="Failed to send verification email: " . $e->getMessage();
        header("Location: user_register.php");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate full name
    $full_name = filter_var(trim($_POST['full_name']), FILTER_SANITIZE_STRING);
    if (empty($full_name)) {
        $_SESSION['error'] = "Full name is required";
        header("Location: user_register.php");
        exit();
    }

    // Sanitize and validate email input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: user_register.php");
        exit();
    }

    // Sanitize and validate password
    $password = trim($_POST['password']);
    if (empty($password) || strlen($password) < 8) { // Example: minimum length of 8
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: user_register.php");
        exit();
    }

    // Sanitize and validate confirm password
    $confirm_password = trim($_POST['confirm_password']);
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
        
        $verification_token = bin2hex(random_bytes(32));
        
        // Encrypt password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Split full name into first and last name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder, verification_token) VALUES (?, ?, ?, ?, FALSE, ?)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        
        sendVerificationEmail($email, $verification_token);

        // Creates form with submit button hidden, appears as a link and sets $_POST['resend_verification'] to 1
        $_SESSION['mess'] = "Verification Email Sent! Must verify before logging in!
        <form method='post' style='display:inline;'>
            <input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>
            <input type='hidden' name='password' value='" . htmlspecialchars($password) . "'>
            <input type='hidden' name='resend_verification' value='1'>
            <button type='submit' style='background:none;border:none;color:white;text-decoration:underline;cursor:pointer;'>
                Resend verification email
            </button>
        </form>";
        
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
    <div class="container login-register">
        <div class="form-container">
            <h2>Register</h2>
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
                <button type="submit" class="button button-account">Register</button>
            </form>
            <div class="switch">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
    <?php include 'background-under.php'; ?>
    <script src="../script.js"></script>
</body>

</html>