<?php 
session_start();
include 'header.php';
require_once '../db/db_connect.php';
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function sendVerificationEmail($email, $token) {
    $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
        ->setUsername($_ENV['SMTP_USER'])
        ->setPassword($_ENV['SMTP_PASS']); 

    $mailer = new Swift_Mailer($transport);

    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'yourdomain.com';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
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
        error_log("Failed to send verification email: " . $e->getMessage());
        return false;
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, first_name, email, password, is_recorder, is_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                //is in DB 
                if ($user['is_verified']) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['first_name'];
                    $_SESSION['is_recorder'] = $user['is_recorder'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    //User is not verified 
                    // Resend verification email if requested
                    if (isset($_POST['resend_verification'])) {
                        // Generate new verification token
                        $new_verification_token = bin2hex(random_bytes(32));
                        
                        // Update the token in database
                        $update_stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE email = ?");
                        $update_stmt->execute([$new_verification_token, $email]);
                        
                        sendVerificationEmail($email, $new_verification_token);
                        
                        $_SESSION['mess'] = "Verification Email Sent! Must verify before logging in!
                        <form method='post' style='display:inline;'>
                            <input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>
                            <input type='hidden' name='password' value='" . htmlspecialchars($password) . "'>
                            <input type='hidden' name='resend_verification' value='1'>
                            <button type='submit' style='background:none;border:none;color:white;text-decoration:underline;cursor:pointer;'>
                                Resend verification email
                            </button>
                        </form>";
                    } else {
                        $_SESSION['error'] = "Please verify your email before logging in. 
                                <form method='post' style='display:inline;'>
                                    <input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>
                                    <input type='hidden' name='password' value='" . htmlspecialchars($password) . "'>
                                    <input type='hidden' name='resend_verification' value='1'>
                                    <button type='submit' style='background:none;border:none;color:white;text-decoration:underline;cursor:pointer;'>
                                        Resend verification email
                                    </button>
                                </form>";
                    }
                }
            } else {
                $_SESSION['error'] = "Invalid email or password";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Login</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container login-register">
        <div class="form-container">
            <h2>Login</h2>
            <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="error">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
                //so they can see verif message
                if (isset($_SESSION['mess'])) {
                    echo '<div class="error">' . $_SESSION['mess'] . '</div>';
                    unset($_SESSION['mess']);
                }
            ?>
            <form action="login.php" method="post">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="button button-account">Continue</button>
            </form>
            <div class="switch">
                Don't have an account? <a href="user_type.php">Sign up</a>
            </div>
        </div>
    </div>
    <?php include 'background-under.php'; ?>
    <script src="../script.js"></script>
</body>
</html>