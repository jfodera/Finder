<?php
session_start();
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to your Dashboard</h1>
        
        <?php if ($_SESSION['is_recorder']): ?>
            <h2>Your Found Items</h2>
        <?php else: ?>
            <h2>Your Lost Items</h2>
        <?php endif; ?>

        
        <h1>Your Lost Items</h1>
        <div class="items-grid" id="itemsGrid"></div>

        
        
    </div>
    <script src="../script.js"></script>
</body>
</html>