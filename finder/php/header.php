<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="/finder/style.css">
    
</head>
<body>
<header class="global-header">
    <div class="header-content">
       
        <img class="logo-image" src="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? '/finder/assets/logo.svg' : '../assets/logo.svg'); ?>" alt="Finder Logo">
        
        <div class="hamburger" id="hamburger">
            &#9776; <!-- Hamburger icon -->
        </div>
        <nav id="nav-menu" class="nav-menu">
            <ul>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'index.php' : '../index.php'; ?>">Home</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/about.php' : 'about.php'; ?>">About</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/contact.php' : 'contact.php'; ?>">Contact</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/login.php' : 'login.php'; ?>">Login</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/user_type.php' : 'user_type.php'; ?>">Register</a></li>
            </ul>
        </nav>
    </div>
</header>
<!-- ... other content ... -->
</body>
</html>