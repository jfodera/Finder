<?php
session_start();
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$is_recorder = isset($_SESSION['is_recorder']) && $_SESSION['is_recorder'];
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
            <?php if ($is_recorder): ?>
                <a href="item_form_recorder.php" class="button">Add Found Item</a>
                <div class="dashboard-tabs">
                    <button class="tab-button active" data-tab="lost">Lost Items</button>
                    <button class="tab-button" data-tab="found">Found Items</button>
                </div>
            <?php else: ?>
                <a href="item_form_user.php" class="button">Report Lost Item</a>
            <?php endif; ?>
        </div>

        <?php if ($is_recorder): ?>
            <div id="lostItemsGrid" class="items-grid tab-content active">
                <div class="loading">Loading lost items...</div>
            </div>
            <div id="foundItemsGrid" class="items-grid tab-content">
                <div class="loading">Loading found items...</div>
            </div>
        <?php else: ?>
            <h2>Your Lost Items</h2>
            <div class="items-grid" id="itemsGrid">
                <div class="loading">Loading your items...</div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Pass user type to JavaScript
        window.isRecorder = <?php echo $is_recorder ? 'true' : 'false'; ?>;
    </script>
    <script src="../script.js"></script>
</body>
</html>