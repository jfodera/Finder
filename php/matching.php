<?php 
// session_start();
// include 'header.php';

// require_once '../db/db_connect.php';

// // Query to fetch all items from the `item` table
// $item_query = "SELECT * FROM lost_items";
// $item_result = $conn->query($item_query);

// if ($item_result->num_rows > 0) {
//     // Loop through each item
//     while ($item_row = $item_result->fetch_assoc()) {
//         $item_type = $item_row['item_type'];
//         $brand = $item_row['brand'];
//         $color = $item_row['color'];
        
//         echo "Checking for matches for Item: Type: $item_type, Brand: $brand, Color: $color<br>";

//         // Query to find matching items in the `found` table
//         $found_query = "SELECT * FROM found_items 
//                         WHERE item_type = ? AND brand = ? AND color = ?";
//         $found_stmt = $conn->prepare($found_query);
//         $found_stmt->bind_param("sss", $item_type, $brand, $color);
//         $found_stmt->execute();
//         $found_result = $found_stmt->get_result();

//         // Check if any matches are found
//         if ($found_result->num_rows > 0) {
//             while ($found_row = $found_result->fetch_assoc()) {
//                 echo "Match Found: Found ID: " . $found_row['recorder_id']. ", Color: " . $found_row['color']  . ", Additional Info: " . $found_row['additional_info'] . ", Time: " . $found_row['found_time'] . "<br>";
//             }
//         } else {
//             echo "No match found.<br>";
//         }

//         $found_stmt->close();
//     }
// } else {
//     echo "No items found in the `items` table.<br>";
// }

// 


require_once '../db/db_connect.php';

function findMatchesForLostItems($pdo) {
    try {
        // Fetch all unmatched lost items
        $lostItems = $pdo->query("
            SELECT * FROM lost_items
                WHERE status = 'lost'
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($lostItems as $lostItem) {
            $lostItemId = $lostItem['item_id'];
            $lostCategory = $lostItem['category'];
            $lostLocation = $lostItem['location'];
            $lostDate = $lostItem['lost_date'];

            // Find potential matches in found items
            $stmt = $pdo->prepare("
                SELECT * FROM found_items
                WHERE status = 'found'
                  AND category = ?
                  AND location = ?
                  AND ABS(DATEDIFF(found_date, ?)) <= 7
            ");
            $stmt->execute([$lostCategory, $lostLocation, $lostDate]);
            $foundItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($foundItems as $foundItem) {
                $foundItemId = $foundItem['item_id'];

                // Check if the match already exists
                $checkStmt = $pdo->prepare("
                    SELECT * FROM matches
                    WHERE lost_item_id = ? AND found_item_id = ?
                ");
                $checkStmt->execute([$lostItemId, $foundItemId]);

                if (!$checkStmt->fetch()) {
                    // Create a new match
                    $insertStmt = $pdo->prepare("
                        INSERT INTO matches (lost_item_id, found_item_id, status)
                        VALUES (?, ?, 'pending')
                    ");
                    $insertStmt->execute([$lostItemId, $foundItemId]);

                    echo "Match created for Lost Item #$lostItemId and Found Item #$foundItemId\n";
                }
            }
        }

        echo "Matching process completed.\n";

    } catch (PDOException $e) {
        error_log("Error in findMatchesForLostItems: " . $e->getMessage());
        echo "An error occurred while matching items.";
    }
}

// Run the matching algorithm
findMatchesForLostItems($pdo);
 

?>


