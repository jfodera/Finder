<?php
// Connect to the database (replace with your actual credentials)
try {

    // Retrieve all lost items
    $lostStmt = $pdo->prepare("SELECT * FROM lost_items");
    $lostStmt->execute();
    $lostItems = $lostStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lostItems as $lostItem) {
        echo "Lost Item ID: " . $lostItem['item_id'] . "<br>";
        echo "Lost Item Type: " . $lostItem['item_type'] . "<br>";
        echo "Lost Item Brand: " . $lostItem['brand'] . "<br>";
        echo "Lost Item Color: " . $lostItem['color'] . "<br><br>";

        // Retrieve all found items that might match the lost item
        $foundStmt = $pdo->prepare("
            SELECT * FROM found_items
            WHERE item_type = :item_type AND brand = :brand AND color = :color
        ");
        
        // Bind parameters from the lost item to search in the found items
        $foundStmt->execute([
            ':item_type' => $lostItem['item_type'],
            ':brand' => $lostItem['brand'],
            ':color' => $lostItem['color']
        ]);

        $foundItems = $foundStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($foundItems) > 0) {
            echo "Potential matches in found items:<br>";
            foreach ($foundItems as $foundItem) {
                echo "- Found Item ID: " . $foundItem['item_id'] . "<br>";

                // Insert the match into the matching table
                $insertStmt = $pdo->prepare("
                    INSERT INTO matching_table (lost_item_id, found_item_id)
                    VALUES (:lost_item_id, :found_item_id)
                ");

                $insertStmt->execute([
                    ':lost_item_id' => $lostItem['item_id'],
                    ':found_item_id' => $foundItem['item_id']
                ]);

                echo "Match inserted into matching table.<br><br>";
            }
        } else {
            echo "No matches found for this lost item.<br><br>";
        }

        echo "<hr>"; // Separator between each lost item
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$pdo = null;
?>
