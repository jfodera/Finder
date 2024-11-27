<?php

require_once '../db/db_connect.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    function findMatchesForLostItems($pdo) {
        try {
            // Attribute weights
            $weights = [
                'type' => 0.5,
                'color' => 0.1,
                'location' => 0.3,
                'date' => 0.1
            ];
    
            // Fetch all unmatched lost items
            $lostItems = $pdo->query("
                SELECT * FROM lost_items
                WHERE status = 'lost'
            ")->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($lostItems as $lostItem) {
                $lostItemId = $lostItem['item_id'];
                $lostType = $lostItem['type'];
                $lostColor = $lostItem['color'];
                $lostLocation = $lostItem['location'];
                $lostDate = $lostItem['lost_date'];
    
                // Fetch potential matches in found items
                $foundItems = $pdo->query("
                    SELECT * FROM found_items
                    WHERE status = 'found'
                ")->fetchAll(PDO::FETCH_ASSOC);
    
                foreach ($foundItems as $foundItem) {
                    $foundItemId = $foundItem['item_id'];
                    $foundType = $foundItem['type'];
                    $foundColor = $foundItem['color'];
                    $foundLocation = $foundItem['location'];
                    $foundDate = $foundItem['found_date'];
    
                    // Calculate similarity scores
                    $typeScore = ($lostType === $foundType) ? 1 : 0;
                    $colorScore = ($lostColor === $foundColor) ? 1 : 0;
                    $locationScore = ($lostLocation === $foundLocation) ? 1 : 0;
    
                    $dateDiff = abs((strtotime($foundDate) - strtotime($lostDate)) / (60 * 60 * 24));
                    $dateScore = ($dateDiff <= 7) ? 1 - ($dateDiff / 7) : 0;
    
                    // Weighted similarity
                    $similarityScore = 
                        $weights['type'] * $typeScore +
                        $weights['color'] * $colorScore +
                        $weights['location'] * $locationScore +
                        $weights['date'] * $dateScore;
    
                    // Match threshold
                   
                        // Check if the match already exists
                        $checkStmt = $pdo->prepare("
                            SELECT * FROM matches
                            WHERE lost_item_id = ? AND found_item_id = ?
                        ");
                        $checkStmt->execute([$lostItemId, $foundItemId]);
    
                        if (!$checkStmt->fetch()) {
                            // Create a new match
                            $insertStmt = $pdo->prepare("
                                INSERT INTO matches (lost_item_id, found_item_id, similarity_score, status)
                                VALUES (?, ?, ?, 'pending')
                            ");
                            $insertStmt->execute([$lostItemId, $foundItemId, $similarityScore]);
    
                            echo "Match created for Lost Item #$lostItemId and Found Item #$foundItemId with similarity score: $similarityScore\n";
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


?>
