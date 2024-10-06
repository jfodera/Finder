<?php include 'header.php'; ?>

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
    <div class="container">
        <div class="form-container">
            <div class="logo">Finder</div>
            <h2>Login</h2>
            <form action="login.php" method="post">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="button button-account">Login</button>
            </form>
            <div class="switch">
                Don't have an account? <a href="user_type.php">Sign up</a>
            </div>
        </div>
    </div>
    <script src="../script.js"></script>
</body>

</html>