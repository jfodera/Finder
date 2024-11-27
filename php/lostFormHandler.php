<?php

ob_start();

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db_connect.php';
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Debug log function
function debug_log($message, $data = null) {
    error_log(print_r([
        'message' => $message,
        'data' => $data,
        'time' => date('Y-m-d H:i:s')
    ], true));
}

debug_log("Handler started", [
    'POST' => $_POST,
    'FILES' => $_FILES,
    'SESSION' => $_SESSION
]);

// Configure Cloudinary
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

try {
    Configuration::instance([
        'cloud' => [
            'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
            'api_key' => $_ENV['CLOUDINARY_API_KEY'],
            'api_secret' => $_ENV['CLOUDINARY_SECRET']
        ],
        'url' => [
            'secure' => true
        ]
    ]);

    debug_log("Cloudinary configured successfully");
} catch (Exception $e) {
    debug_log("Cloudinary configuration error", $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Failed to configure image upload service",
        'error' => $e->getMessage()
    ]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => "Please log in to continue",
        'redirect' => 'login.php'
    ]);
    exit();
}

function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}


function checkSubmissionCooldown($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT last_submission FROM submission_cooldowns WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cooldown = $stmt->fetch();
        
        if ($cooldown) {
            $last_submission = strtotime($cooldown['last_submission']);
            $current_time = time();
            $cooldown_period = 300; 
            
            if (($current_time - $last_submission) < $cooldown_period) {
                return false;
            }
        }
        return true;
    } catch (PDOException $e) {
        debug_log("Cooldown check error", $e->getMessage());
        return true;
    }
}

// Update cooldown timestamp
function updateCooldown($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO submission_cooldowns (user_id, last_submission) 
                              VALUES (?, CURRENT_TIMESTAMP) 
                              ON DUPLICATE KEY UPDATE last_submission = CURRENT_TIMESTAMP");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        debug_log("Update cooldown error", $e->getMessage());
        return false;
    }
}

// Validate and sanitize locations
function validateLocations($locations, $pdo) {
    if (empty($locations)) return false;
    
    $valid_locations = [];
    try {
        $stmt = $pdo->prepare("SELECT name FROM locations WHERE name = ?");
        
        foreach ($locations as $location) {
            $stmt->execute([trim($location)]);
            if ($stmt->rowCount() > 0) {
                $valid_locations[] = trim($location);
            }
        }
        
        return !empty($valid_locations) ? $valid_locations : false;
    } catch (PDOException $e) {
        debug_log("Location validation error", $e->getMessage());
        return false;
    }
}

// Handle image upload to Cloudinary
function handleCloudinaryUpload($image) {
    //checks if can support image
    if ($image['error'] !== UPLOAD_ERR_OK) {
        debug_log("Image upload error", $image['error']);
        return [null, null];
    }

    //checks if actual image (png, jpeg, jpg extension)
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($image['type'], $allowed_types)) {
        debug_log("Invalid image type", $image['type']);
        return [null, null];
    }

    try {
        //UploadAPI instance from the library on our server. 
        $upload = new UploadApi();
        $result = $upload->upload($image['tmp_name'], [
            'folder' => 'lost_items',
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);
        
        debug_log("Image uploaded successfully", $result);
        return [$result['secure_url'], $result['public_id']];
    } catch (Exception $e) {
        debug_log("Cloudinary upload error", $e->getMessage());
        return [null, null];
    }
}

//when recieves
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check cooldown
        if (!checkSubmissionCooldown($pdo, $_SESSION['user_id'])) {
            throw new Exception("Please wait 5 minutes between submissions.");
        }

        // Validate and sanitize input
        $item_type = sanitize_input($_POST['type']);
        $brand = sanitize_input($_POST['brand']);
        $color = sanitize_input($_POST['color']);
        $additional_info = sanitize_input($_POST['addInfo']);
        $lost_time = $_POST['date'];
        $locations = $_POST['locations'] ?? [];

        debug_log("Received form data", [
            'item_type' => $item_type,
            'brand' => $brand,
            'color' => $color,
            'lost_time' => $lost_time,
            'locations' => $locations
        ]);

        // Validation
        if (empty($item_type) || empty($brand) || empty($color) || empty($lost_time)) {
            throw new Exception("All required fields must be filled out.");
        }

        // Validate locations
        $valid_locations = validateLocations($locations, $pdo);
        if (!$valid_locations) {
            throw new Exception("Please select valid locations.");
        }

        $pdo->beginTransaction();
        debug_log("Transaction started");

        // Handle image upload if present
        $image_url = null;
        $image_public_id = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            list($image_url, $image_public_id) = handleCloudinaryUpload($_FILES['image']);
            
            if (!$image_url || !$image_public_id) {
                throw new Exception("Failed to upload image. Please try again.");
            }
        }

        // Insert lost item
        
        $stmt = $pdo->prepare("INSERT INTO lost_items (
            item_type, brand, color, additional_info, lost_time, 
            user_id, image_url, image_public_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $item_type, $brand, $color, $additional_info, $lost_time,
            $_SESSION['user_id'], $image_url, $image_public_id
        ]);

        //last insert from that specific connection, therefore, no need for constraints 
        $item_id = $pdo->lastInsertId();
        debug_log("Item inserted", ["item_id" => $item_id]);

        // Insert locations
        $stmt = $pdo->prepare("INSERT INTO item_locations (item_id, item_type, location) VALUES (?, 'lost', ?)");
        try {
            foreach ($valid_locations as $location) {
                $stmt->execute([$item_id, $location]);
                debug_log("Location inserted", [
                    'item_id' => $item_id,
                    'location' => $location
                ]);
            }
        } catch (PDOException $e) {
            debug_log("Location insertion error", [
                'error' => $e->getMessage(),
                'item_id' => $item_id,
                'locations' => $valid_locations
            ]);
            throw $e;
        }

        // Update cooldown
        updateCooldown($pdo, $_SESSION['user_id']);

        $pdo->commit();
        debug_log("Transaction completed successfully");
        
        // Run matching algorithm
        require_once 'matching.php';
        try {
            debug_log("Starting matching algorithm for new lost item", ["item_id" => $item_id]);
            $newMatches = findMatchesForLostItems($pdo, $item_id);
            
            if (!empty($newMatches)) {
                $_SESSION['new_matches'] = count($newMatches);
                debug_log("Matches found", [
                    "count" => count($newMatches),
                    "matches" => $newMatches
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => "Item successfully reported as lost! " . count($newMatches) . " potential matches found!",
                    'redirect' => 'dashboard.php?matches=new',
                    'item_id' => $item_id
                ]);
            } else {
                debug_log("No matches found for new lost item");
                echo json_encode([
                    'success' => true,
                    'message' => "Item successfully reported as lost!",
                    'redirect' => 'dashboard.php',
                    'item_id' => $item_id
                ]);
            }
        } catch (Exception $matchingError) {
            debug_log("Matching algorithm error", [
                'error' => $matchingError->getMessage(),
                'item_id' => $item_id
            ]);
            // Still return success since the item was saved
            echo json_encode([
                'success' => true,
                'message' => "Item successfully reported as lost!",
                'redirect' => 'dashboard.php',
                'item_id' => $item_id
            ]);
        }
        exit();

    } catch (Exception $e) {
        debug_log("Error occurred", [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        if (isset($image_public_id)) {
            try {
                $cloudinary->uploadApi()->destroy($image_public_id);
            } catch (Exception $cleanupError) {
                debug_log("Failed to cleanup Cloudinary image", $cleanupError->getMessage());
            }
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'error_details' => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => "Invalid request method"
    ]);
    exit();
}