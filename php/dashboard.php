<?php
session_start();
require_once 'auth_middleware.php';
include 'header.php';
require_once '../db/db_connect.php';

requireLogin();
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
        <h1 class="dashboard-heading">Welcome to your Dashboard</h1>
        
        <div class="action-buttons">
            <?php if ($_SESSION['is_recorder']): ?>
                <a href="item_form_recorder.php" class="button">Add Found Item</a>
            <?php else: ?>
                <a href="item_form_user.php" class="button">Report Lost Item</a>
            <?php endif; ?>
        </div>

        <?php if ($_SESSION['is_recorder']): ?>
            <div class="tab-container">
                <button class="tab-button active" onclick="showTab('lost')">Lost Items</button>
                <button class="tab-button" onclick="showTab('found')">Found Items</button>
            </div>
        <?php endif; ?>

        <div id="lost-items" class="tab-content active">
            <h2>Lost Items</h2>
            <div class="items-grid" id="lostItemsGrid">
                <div class="loading">Loading lost items...</div>
            </div>
        </div>

        <?php if ($_SESSION['is_recorder']): ?>
            <div id="found-items" class="tab-content">
                <h2>Found Items</h2>
                <div class="items-grid" id="foundItemsGrid">
                    <div class="loading">Loading found items...</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="../script.js"></script>
</body>
</html>