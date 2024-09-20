<header class="global-header">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    
    <div class="header-content">
        <div class="left-nav">
            <nav>
                <ul>
                    <li><a>Logo Here</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'index.php' : '../index.php'; ?>">Home</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/about.php' : 'about.php'; ?>">About</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/contact.php' : 'contact.php'; ?>">Contact</a></li>
                </ul>
            </nav>
        </div>
        <div class="right-nav">
            <nav>
                <ul>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/login.php' : 'login.php'; ?>">Login</a></li>
                    <li><a href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'php/register.php' : 'register.php'; ?>">Register</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>