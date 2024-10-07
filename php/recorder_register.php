<?php
session_start();
require_once '../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $code = $_POST['code'];
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($code)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: recorder_register.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: recorder_register.php");
        exit();
    }
    
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
        
        // Insert new user
        $pdo->beginTransaction();
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Split full name into first and last name
        $name_parts = explode(" ", $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : "";
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, is_recorder) VALUES (?, ?, ?, ?, TRUE)");
        $stmt->execute([$email, $hashed_password, $first_name, $last_name]);
        $user_id = $pdo->lastInsertId();
        
        // Update recorder code
        $stmt = $pdo->prepare("UPDATE recorder_codes SET user_id = ? WHERE code = ?");
        $stmt->execute([$user_id, $code]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Registration successful! Please log in.";
        header("Location: login.php");
        exit();
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
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
    <div class="container">
        <div class="form-container">
            <div class="logo">Finder</div>
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