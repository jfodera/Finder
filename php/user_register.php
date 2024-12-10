<?php 
declare(strict_types=1);

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");

//new session when go to this page 
session_start();

// Use absolute paths for includes
$headerPath = __DIR__ . '/header.php';
require_once $headerPath;
require_once __DIR__ . '/../db/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

/* 
Thought process:
only users should need to verify their emails to confirm they go to RPI as the recorders technically
don't need verification because we give them a unique recorder code

How it works:
the sendVerificationEmail sends email when u register and when you click on the link it auto verifies email
*/

// Load environment variables safely
$dotenvPath = __DIR__ . '/../.env';
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname($dotenvPath));
    $dotenv->load();
}

function sendVerificationEmail($email, $token) {
    // Validate email and token before processing
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
        throw new Exception('Invalid token format');
    }

    //last arg is encryption protocol
    $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
        ->setUsername($_ENV['SMTP_USER'])
        ->setPassword($_ENV['SMTP_PASS']); 

    $mailer = new Swift_Mailer($transport);

    // Validate domain
    $domain = '';
    if (isset($_SERVER['HTTP_HOST']) && preg_match('/^[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/', $_SERVER['HTTP_HOST'])) {
        $domain = $_SERVER['HTTP_HOST'];
    } else {
        $domain = 'yourdomain.com';
    }
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
    // makes the link to send to our emails with proper encoding
    $verificationLink = sprintf(
        '%s%s/%s/php/verify_email.php?email=%s&token=%s',
        $protocol,
        htmlspecialchars($domain),
        $_ENV['URL'],
        urlencode($email),
        urlencode($token)
    );

    // Secure HTML template
    $emailTemplate = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your Finder account</title>
</head>
<body>
    <h1>Welcome to Finder!</h1>
    <p>Please click the link below to verify your account:</p>
    <p><a href="%s">Verify Account</a></p>
</body>
</html>
EOT;

    $message = (new Swift_Message('Verify your Finder account'))
        ->setFrom([$_ENV['SMTP_USER'] => 'Finder'])
        ->setTo([$email])
        ->setBody(
            sprintf($emailTemplate, htmlspecialchars($verificationLink)),
            'text/html'
        );

    try {
        $result = $mailer->send($message);
        return true;
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to send verification email: " . htmlspecialchars($e->getMessage());
        header("Location: user_register.php");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Enhanced input validation
    $full_name = trim(filter_var($_POST['full_name'] ?? '', FILTER_SANITIZE_STRING));
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: user_register.php");
        exit();
    }

    // Only RPI users with a @rpi.edu email can register
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
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists";
            header("Location: user_register.php");
            exit();
        }
        
        $verification_token = bin2hex(random_bytes(32));
        
        //encrypts so cannot be seen in database 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Split name into first and last name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder, verification_token) VALUES (?, ?, ?, ?, FALSE, ?)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        
        sendVerificationEmail($email, $verification_token);

        //creates form with submit button hidden, appears as a link and sets $_POST['resend_verification'] to 1
        $_SESSION['mess'] = sprintf(
            "Verification Email Sent! Must verify before logging in!
            <form method='post' style='display:inline;'>
                <input type='hidden' name='email' value='%s'>
                <input type='hidden' name='resend_verification' value='1'>
                <button type='submit' style='background:none;border:none;color:white;text-decoration:underline;cursor:pointer;'>
                    Resend verification email
                </button>
            </form>",
            htmlspecialchars($email)
        );
        
        header("Location: login.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . htmlspecialchars($e->getMessage());
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
                echo '<div class="error">' . htmlspecialchars($_SESSION['error']) . '</div>';
                unset($_SESSION['error']);
            }
            if (isset($_SESSION['warning'])) {
                echo '<div class="warning">' . htmlspecialchars($_SESSION['warning']) . '</div>';
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