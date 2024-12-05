<?php 
session_start();
include 'header.php';
require_once '../db/db_connect.php';

// Security helper functions
function validateAndSanitizePath($path) {
    $path = str_replace('..', '', $path);
    $realPath = realpath($path);
    $baseDir = realpath($_SERVER['DOCUMENT_ROOT']);
    if ($realPath === false || strpos($realPath, $baseDir) !== 0) {
        throw new Exception('Invalid path detected');
    }
    return $realPath;
}

function validateRPIEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && 
           preg_match('/^[a-zA-Z0-9._%+-]+@rpi\.edu$/', $email);
}

function sendVerificationEmail($email, $token) {
    if (!validateRPIEmail($email)) {
        throw new Exception('Invalid email format');
    }
    
    try {
        $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
            ->setUsername($_ENV['SMTP_USER'])
            ->setPassword($_ENV['SMTP_PASS']); 

        $mailer = new Swift_Mailer($transport);

        $domain = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_STRING);
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        
        $basePath = validateAndSanitizePath($_ENV['URL'] . "/php/verify_email.php");
        $verificationLink = $protocol . $domain . $basePath . 
            "?email=" . urlencode($email) . 
            "&token=" . urlencode($token);

        $message = (new Swift_Message('Verify your Finder account'))
            ->setFrom([$_ENV['SMTP_USER'] => 'Finder'])
            ->setTo([$email])
            ->setBody(
                '<html>' .
                '<body>' .
                '<h1>Welcome to Finder!</h1>' .
                '<p>Please click the link below to verify your account:</p>' .
                '<p><a href="' . htmlspecialchars($verificationLink) . '">Verify Account</a></p>' .
                '</body>' .
                '</html>',
                'text/html'
            );

        return $mailer->send($message);
    } catch (Exception $e) {
        throw new Exception("Failed to send verification email: " . $e->getMessage());
    }
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // CSRF check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        // Validate and sanitize inputs
        $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);
        
        // Input validation
        if (empty($full_name) || empty($email) || empty($password) || 
            empty($confirm_password) || empty($code)) {
            throw new Exception('All fields are required');
        }

        if (!validateRPIEmail($email)) {
            throw new Exception('Email must be an @rpi.edu address');
        }

        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }
        
        // Database operations
        $pdo->beginTransaction();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        
        // Validate recorder code
        $stmt = $pdo->prepare("SELECT code_id FROM recorder_codes WHERE code = ? AND user_id IS NULL");
        $stmt->execute([$code]);
        $recorder_code = $stmt->fetch();
        
        if (!$recorder_code) {
            throw new Exception('Invalid or used recorder code');
        }
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(32));
        
        // Split full name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, verification_token, is_recorder) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        $user_id = $pdo->lastInsertId();
        
        // Update recorder code
        $stmt = $pdo->prepare("UPDATE recorder_codes SET user_id = ? WHERE code = ?");
        $stmt->execute([$user_id, $code]);
        
        // Send verification email
        sendVerificationEmail($email, $verification_token);
        
        $pdo->commit();

        // Create resend verification form
        $_SESSION['mess'] = "Verification Email Sent! Must verify before logging in!
            <form method='post' style='display:inline;'>
                <input type='hidden' name='email' value='" . htmlspecialchars($email) . "'>
                <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['csrf_token']) . "'>
                <input type='hidden' name='resend_verification' value='1'>
                <button type='submit' style='background:none;border:none;color:white;text-decoration:underline;cursor:pointer;'>
                    Resend verification email
                </button>
            </form>";
        
        header("Location: login.php");
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: recorder_register.php");
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
    <title>Finder - Recorder Register</title>
    <link rel="stylesheet" href="../style.css">
    <?php
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; frame-ancestors 'none'");
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    ?>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <div class="logo">Finder</div>
            <?php
            if (isset($_SESSION['error'])) {
                echo '<div class="error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="recorder_register.php" method="post">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <input type="text" name="code" placeholder="Recorder Code" required>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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