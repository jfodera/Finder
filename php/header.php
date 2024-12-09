<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<body>
<header class="global-header transparent">
    <div class="header-content">
        <div class="hamburger" id="hamburger">
            &#9776;
        </div>
        <nav id="nav-menu" class="nav-menu">
            <a id="logoLin" href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'index.php' : '../index.php'); ?>">
                <img class="logo-image" src="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'assets/logo_offwhite.svg' : '../assets/logo.svg'); ?> " alt="Finder Logo">
            </a>
            <ul>
                <li style="<?php echo (!$_SESSION['email'] ? 'display: none;' : ''); ?>">
                    <a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''); ?>" href="dashboard.php">Home</a>
                </li>
                <li><a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''); ?>" 
                href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'php/about.php' : 'about.php'); ?>">About</a></li>
                <li><a class="<?php echo (basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''); ?>" 
                href="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'php/contact.php' : 'contact.php'); ?>">Contact</a></li>
            </ul>
        </nav>
        <div class="nav-account-buttons" style="<?php echo (!$_SESSION['email'] ? 'display: none;' : ''); ?>">
            <div class="main-form-button-container">
                <?php if ($_SESSION['is_recorder']): ?>
                    <a href="found_item_form.php" class="button main-form found-from">Add Found Item</a>
                <?php else: ?>
                    <a href="item_form_user.php" class="button main-form lost-from">Report Lost Item</a>
                <?php endif; ?>
            </div>
            <div id="account-name"><a id="account-name-a" href="logout.php"><?php echo $_SESSION['name'] ?></a></div>
        </div>
        
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
        if (Yoffset > 150) {
            header.classList.remove('transparent')
        } else {
            header.classList.add('transparent')
        }
    });
</script>
</html>