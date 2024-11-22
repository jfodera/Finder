<?php

require_once '../db/db_connect.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    function findMatchesForLostItems($pdo) {
        try {
            // Fetch all unmatched lost items
            $lostItems = $pdo->query("
                SELECT * FROM lost_items
                    WHERE status = 'lost'
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($lostItems as $lostItem) {
                $lostItemId = $lostItem['item_id'];
                $losttype = $lostItem['type'];
                $lostLocation = $lostItem['location'];
                $lostDate = $lostItem['lost_date'];

                // Find potential matches in found items
                $stmt = $pdo->prepare("
                    SELECT * FROM found_items
                    WHERE status = 'found'
                      AND type = ?
                      AND location = ?
                      AND ABS(DATEDIFF(found_date, ?)) <= 7
                ");
                $stmt->execute([$losttype, $lostLocation, $lostDate]);
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

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}





$conn->close();
?>
