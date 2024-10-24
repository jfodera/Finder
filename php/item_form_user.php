<?php 
session_start();
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $item_type = trim(filter_var($_POST['type'], FILTER_SANITIZE_STRING));
    $brand = trim(filter_var($_POST['brand'], FILTER_SANITIZE_STRING));
    $color = trim(filter_var($_POST['color'], FILTER_SANITIZE_STRING));
    $additional_info = trim(filter_var($_POST['addInfo'], FILTER_SANITIZE_STRING));
    $lost_time = $_POST['date'];
    $locations = $_POST['locations'] ?? [];

    $errors = [];

    // Validate required fields
    if (empty($item_type)) $errors[] = "Item type is required";
    if (empty($brand)) $errors[] = "Brand is required";
    if (empty($color)) $errors[] = "Color is required";
    if (empty($lost_time)) $errors[] = "Lost time is required";
    if (empty($locations)) $errors[] = "At least one location must be selected";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert into lost_items
            $stmt = $pdo->prepare("INSERT INTO lost_items (item_type, brand, color, additional_info, lost_time, user_id) 
                                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$item_type, $brand, $color, $additional_info, $lost_time, $_SESSION['user_id']]);
            
            $item_id = $pdo->lastInsertId();
            
            // Insert locations
            if (!empty($locations)) {
                $stmt = $pdo->prepare("INSERT INTO item_locations (item_id, item_type, location) VALUES (?, 'lost', ?)");
                foreach ($locations as $location) {
                    $stmt->execute([$item_id, $location]);
                }
            }
            
            // Handle image upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image = $_FILES['image'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                
                if (in_array($image['type'], $allowed_types)) {
                    // Add your image upload logic here
                    // Update the lost_items table with image_url and image_public_id
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Item successfully reported as lost!";
            header("Location: dashboard.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

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
    <link rel="stylesheet" href="../css/info.css">
</head>

<body>
    <div class="container">
        <section class="question">
            <h1>Describe Your Lost Item Below:</h1>
        </section>
        <div class="item_form_container">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?php echo $_SESSION['success']; ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form id="infoForm" action="item_form_user.php" method="post" enctype="multipart/form-data">
                <div class="page page-1 index active">
                    <h2>What type of item was it?</h2>
                    <div class="form_group">
                        <input type="text" 
                               name="type" 
                               placeholder="Type (e.g., Phone, Wallet, Keys)" 
                               value="<?php echo isset($_POST['type']) ? htmlspecialchars($_POST['type']) : ''; ?>" 
                               required>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="brand" 
                               placeholder="Brand (e.g., Apple, Nike, Samsung)" 
                               value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>" 
                               required>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="color" 
                               placeholder="Color (e.g., Black, Red, Blue)" 
                               value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : ''; ?>" 
                               required>
                    </div>
                    <div class="form_group">
                        <input type="text" 
                               name="addInfo" 
                               placeholder="Additional Information (optional)" 
                               value="<?php echo isset($_POST['addInfo']) ? htmlspecialchars($_POST['addInfo']) : ''; ?>">
                    </div>
                    <button type="button" class="next-btn">Continue</button>
                </div>

                <div class="page page-2">
                    <h2>When did you lose it?</h2>
                    <h3>*Required - please provide approximate time*</h3>
                    <div class="form_group">
                        <input type="datetime-local" 
                               name="date" 
                               value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>" 
                               required>
                    </div>
                    <button type="button" class="prev-btn">Go Back</button>
                    <button type="button" class="next-btn">Continue</button>
                </div>

                <div class="page page-3">
                    <h2>Upload Item Image</h2>
                    <h3>*Optional but recommended for better identification*</h3>
                    <img id="upload_image" src="../default_image.png" alt="image of the item">
                    <label id="item_img" for="input-file">upload image</label>
                    <div class="form_group">
                        <input id="input-file" 
                               type="file" 
                               name="image" 
                               accept="image/jpeg,image/png,image/jpg">
                    </div>
                    <button type="button" class="prev-btn">Go Back</button>
                    <button type="button" class="next-btn">Continue</button>
                </div>

                <div class="page page-4">
                    <h2>Where did you lose it?</h2>
                    <h3>*Required - select at least one location*</h3>
                    
                    <input type="text" 
                           class="location-search" 
                           placeholder="Search locations..." 
                           id="locationSearch">
                    
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

                    <div class="selected-locations">
                        Selected locations: <span id="selectedCount">0</span>
                    </div>

                    <button type="button" class="prev-btn">Go Back</button>
                    <button type="submit" class="submit-btn" disabled>Submit</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../script.js"></script>
</body>
</html>