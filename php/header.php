<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<body>
<header class="global-header">
    <div class="header-content">
        <!-- Uses php for the source so that this header can be used on multiple pages -->
         <!-- directly acter questionmark is if evaluates to true, after colon is false -->
        <!-- <a id="logoLin" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'index.php' : '../index.php'); ?>">
            <img class="logo-image" src="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'assets/logo.svg' : '../assets/logo.svg'); ?>" alt="Finder Logo">
        </a> -->
        
        <div class="hamburger" id="hamburger">
            &#9776;
        </div>
        <nav id="nav-menu" class="nav-menu">
            <a id="logoLin" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'index.php' : '../index.php'); ?>">
                <img class="logo-image" src="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'assets/logo.svg' : '../assets/logo.svg'); ?>" alt="Finder Logo">
            </a>
            <ul>
                <li><a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''); ?>" href="dashboard.php">Home</a></li>
                <li><a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''); ?>" href="about.php">About</a></li>
                <li><a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''); ?>" href="contact.php">Contact</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Links for logged in users -->
                    <?php if ($_SESSION['is_recorder']): ?>
                        <li><a href="found_item_form.php">Add Found Item</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Links for guests -->
                    <li><a href="login.php">Login</a></li>
                    <li><a href="user_type.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div id="account-name"><a id="account-name-a" href="logout.php"><?php echo $_SESSION['name'] ?></a></div>
    </div>
</header>
</body>
<script>
    const nameElement = document.querySelector('#account-name');
    const nameElementInner = document.querySelector('#account-name-a');
    const header = document.querySelector('.global-header')

    nameElement.addEventListener("mouseover", () => {
        nameElementInner.classList.add("logout-visible")
        setTimeout(function() {nameElementInner.innerHTML = "Logout"}, 100);
        
    });
    nameElement.addEventListener("mouseout", () => {
        nameElementInner.classList.remove("logout-visible")
        setTimeout(function() {nameElementInner.innerHTML = "<?php echo $_SESSION['name'] ?>" }, 100);
    });
    document.addEventListener("scroll", (event) => {
        const Yoffset = window.scrollY;
        // console.log(Yoffset);
        if (Yoffset > 150) {
            header.classList.remove('transparent')
        } else {
            header.classList.add('transparent')
        }
    });
</script>
</html>