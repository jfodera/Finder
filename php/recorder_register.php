<?php 
session_start();

$baseDir = realpath(__DIR__ . '/..');
$headerPath = realpath($baseDir . '/php/header.php');

if ($headerPath === false || !is_file($headerPath)) {
    die('Invalid header path');
}

if (strpos($headerPath, $baseDir) !== 0) {
    die('Invalid file location');
}

require_once $headerPath;
require_once realpath($baseDir . '/db/db_connect.php');


//verification email functionality
function sendVerificationEmail($email, $token) {
    //last arg is encryption protocol
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

//Form submission for recorder
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate full name
    $full_name = filter_var(trim($_POST['full_name']), FILTER_SANITIZE_STRING);
    if (empty($full_name)) {
        $_SESSION['error'] = "Full name is required";
        header("Location: recorder_register.php");
        exit();
    }

    // Sanitize and validate email input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: recorder_register.php");
        exit();
    }

    // Sanitize and validate password
    $password = filter_var(trim($_POST['password']), FILTER_SANITIZE_STRING);
    if (empty($password) || strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || // At least one uppercase letter
        !preg_match('/[a-z]/', $password) || // At least one lowercase letter
        !preg_match('/[0-9]/', $password) || // At least one digit
        !preg_match('/[\W_]/', $password)) { // At least one special character
        $_SESSION['error'] = "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one digit, and one special character.";
        header("Location: recorder_register.php");
        exit();
    }

    // Sanitize and validate confirm password
    $confirm_password = filter_var(trim($_POST['confirm_password']), FILTER_SANITIZE_STRING);
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: recorder_register.php");
        exit();
    }

    // Sanitize and validate recorder code
    $code = filter_var(trim($_POST['code']), FILTER_SANITIZE_STRING);
    if (empty($code)) {
        $_SESSION['error'] = "Recorder code is required";
        header("Location: recorder_register.php");
        exit();
    }
    
    //only rpi users with a @rpi.edu email can register
    // if (!preg_match('/^[a-zA-Z0-9._%+-]+@rpi\.edu$/', $email)) {
    //     $_SESSION['error'] = "Email must be an @rpi.edu address";
    //     header("Location: recorder_register.php");
    //     exit();
    // }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already exists";
            header("Location: recorder_register.php");
            exit();
        }
        
        // Validate recorder code
        $stmt = $pdo->prepare("SELECT code_id FROM recorder_codes WHERE code = ? AND user_id IS NULL");
        $stmt->execute([$code]);
        $recorder_code = $stmt->fetch();
        
        if (!$recorder_code) {
            $_SESSION['error'] = "Invalid or used recorder code";
            header("Location: recorder_register.php");
            exit();
        }

        // Insert new user, making multiple sql statement so put in commit block
        $pdo->beginTransaction();
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $verification_token = bin2hex(random_bytes(32));
        // Split full name into first and last name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, verification_token, is_recorder) VALUES (?, ?, ?, ?, ?, TRUE)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
        $user_id = $pdo->lastInsertId();
        
        // Update recorder code
        $stmt = $pdo->prepare("UPDATE recorder_codes SET user_id = ? WHERE code = ?");
        $stmt->execute([$user_id, $code]);
        
        $pdo->commit();

        //send verif email 
        sendVerificationEmail($email, $verification_token);

        //creates form with submit button hiden, appears as a link and sets $_POST['resend_verification'] to 1
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
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); //sql insertion failed
        }
        $_SESSION['error'] = "Database error: " . $e->getMessage();
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
            ?>
            <form action="recorder_register.php" method="post">
                <input type="text" name="full_name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <input type="text" name="code" placeholder="Recorder Code" required>
                <button type="submit" class="button button-account">Register</button>
            </form>
            <div class="switch">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
    <script src="../script.js"></script>
</body>

</html>