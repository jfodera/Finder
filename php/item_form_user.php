<?php 
session_start();
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/db_connect.php';
//calls lost form handler

// Fetch locations from database
$stmt = $pdo->query("SELECT * FROM locations ORDER BY category, name");
$locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group locations by category
$grouped_locations = [];
foreach ($locations as $location) {
    $grouped_locations[$location['category']][] = $location;
}





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Finder - Report Lost Item</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <div class="container">
        <section class="question">
            <h1>Tell us more to help us help you:</h1>
        </section>
        <div class="item_form_container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?php echo $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?php echo $_SESSION['success']; ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form id="infoForm" action="lostFormHandler.php" method="post" enctype="multipart/form-data">
                <div class="page page-1 index active">
                    <h2>What is the item you lost?</h2>
                    <div class="form_group">
                        <input type="text" 
                               name="type" 
                               placeholder="Type (e.g., Phone, Wallet, Keys)" 
                               required>
                        <div class="error-message">Please enter the item type</div>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="brand" 
                               placeholder="Brand (e.g., Apple, Nike, Samsung)" 
                               required>
                        <div class="error-message">Please enter the brand</div>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="color" 
                               placeholder="Color (e.g., Black, Red, Blue)" 
                               required>
                        <div class="error-message">Please enter the color</div>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="addInfo" 
                               placeholder="Additional Information (optional)">
                    </div>
                    <button type="button" class="next-btn">Continue</button>
                </div>

                <div class="page page-2">
                    <h2>When did you lose the item?</h2>
                    <h3>*Required - please provide approximate time*</h3>
                    <div class="form_group">
                        <input type="datetime-local" 
                               name="date" 
                               max="<?php echo date('Y-m-d\TH:i'); ?>"
                               required>
                        <div class="error-message">Please select a valid date and time</div>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="prev-btn">Go Back</button>
                        <button type="button" class="next-btn">Continue</button>
                    </div>
                </div>

                <div class="page page-3">
                    <h2>Do you have an image of the item?</h2>
                    <h3>*Optional but recommended for better identification*</h3>
                    <img id="upload_image" src="../assets/placeholderImg.svg" alt="image of the item">
                    <label id="item_img" for="input-file">upload image</label>
                    <div class="form_group">
                        <input id="input-file" type="file" name="image" accept="image/jpeg,image/png,image/jpg">
                        <div class="error-message">Please upload a valid image file (JPG or PNG, max 5MB)</div>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="prev-btn">Go Back</button>
                        <button type="button" class="next-btn">Continue</button>
                    </div>
                </div>

                <div class="page page-4">
                    <h2>Where did you lose it?</h2>
                    <h3>*Can select multiple locations*</h3>
                    
                    <input type="text" 
                        class="location-search" 
                        placeholder="Search locations..." 
                        id="locationSearch">
                    
                    <div class="location-section">
                        <div class="locations-main">
                            <div class="locations-container">
                                <?php foreach ($grouped_locations as $category => $locs): ?>
                                    <div class="location-group">
                                        <h3><?php echo htmlspecialchars($category); ?></h3>
                                        <div class="location-checkboxes">
                                            <?php foreach ($locs as $location): ?>
                                                <div class="location-checkbox">
                                                    <input type="checkbox" 
                                                        name="locations[]" 
                                                        id="loc<?php echo $location['location_id']; ?>" 
                                                        value="<?php echo htmlspecialchars($location['name']); ?>">
                                                    <label for="loc<?php echo $location['location_id']; ?>">
                                                        <?php echo htmlspecialchars($location['name']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="locations-selected">
                            <h3>Selected Locations (<span id="selectedCount">0</span>)</h3>
                            <div class="selected-list" id="selectedList"></div>
                            <div class="error-message">Please select at least one location</div>
                        </div>
                    </div>
                    <div class="form-buttons">                              
                        <button type="button" class="prev-btn">Go Back</button>
                        <button type="submit" class="submit-btn" disabled>
                            Submit
                            <span class="loading-indicator">⭕</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php include 'background-under.php'; ?>
    <script src="../script.js"></script>
    <script src="../jquery-3.6.1.min.js"></script>
</body>
</html>