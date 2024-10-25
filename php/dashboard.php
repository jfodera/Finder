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
        
        <div class="action-buttons">
            <a href="item_form_user.php" class="button">Report Lost Item</a>
        </div>

        <h2>Your Lost Items</h2>
        <div class="items-grid" id="itemsGrid">
            <div class="loading">Loading your items...</div>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>