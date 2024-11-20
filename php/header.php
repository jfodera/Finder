<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="global-header">
    <div class="header-content">
        <!-- Uses php for the source so that this header can be used on multiple pages -->
         <!-- directly acter questionmark is if evaluates to true, after colon is false -->
        <a id="logoLin" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'index.php' : '../index.php'); ?>">
            <img class="logo-image" src="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'assets/logo.svg' : '../assets/logo.svg'); ?>" alt="Finder Logo">
        </a>
        
        <div class="hamburger" id="hamburger">
            &#9776;
        </div>
        <nav id="nav-menu" class="nav-menu">
            <ul>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'index.php' : '../index.php'; ?>">Home</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/about.php' : 'about.php'; ?>">About</a></li>
                <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/contact.php' : 'contact.php'; ?>">Contact</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Links for logged in users -->
                    <?php if ($_SESSION['is_recorder']): ?>
                        <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/found_item_form.php' : 'found_item_form.php'; ?>">Add Found Item</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/item_form_user.php' : 'item_form_user.php'; ?>">Report Lost Item</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/dashboard.php' : 'dashboard.php'; ?>">Dashboard</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/logout.php' : 'logout.php'; ?>">Logout</a></li>
                <?php else: ?>
                    <!-- Links for guests -->
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/login.php' : 'login.php'; ?>">Login</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/user_type.php' : 'user_type.php'; ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
</body>
</html>