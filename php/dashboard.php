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
    <title>Finder - Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <h1 class="dashboard-heading">Welcome to your Dashboard</h1>
        
        <?php if ($is_recorder): ?>
            <div class="action-buttons">
                <a href="item_form_recorder.php" class="button">Add Found Item</a>
                <div class="dashboard-tabs">
                    <button class="tab-button active" data-tab="matches">Matches Overview</button>
                    <button class="tab-button" data-tab="lost">Lost Items</button>
                    <button class="tab-button" data-tab="found">Found Items</button>
                </div>
            </div>

            <div id="matchesGrid" class="items-grid tab-content active">
                <div class="match-flow">
                    <div class="loading">Loading matches overview...</div>
                </div>
            </div>
            <div id="lostItemsGrid" class="items-grid tab-content">
                <div class="loading">Loading lost items...</div>
            </div>
            <div id="foundItemsGrid" class="items-grid tab-content">
                <div class="loading">Loading found items...</div>
            </div>
        <?php else: ?>
            <div class="user-dashboard">
                <div class="action-buttons">
                    <a href="item_form_user.php" class="button">Report Lost Item</a>
                    <div class="dashboard-tabs">
                        <button class="tab-button active" data-tab="items">My Lost Items</button>
                        <button class="tab-button" data-tab="matches">Potential Matches</button>
                    </div>
                </div>

                <div id="itemsGrid" class="items-grid tab-content active">
                    <div class="loading">Loading your items...</div>
                </div>
                <div id="userMatchesGrid" class="items-grid tab-content">
                    <div class="loading">Loading potential matches...</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        window.isRecorder = <?php echo $is_recorder ? 'true' : 'false'; ?>;
    </script>
    <script src="../script.js"></script>
</body>
</html>