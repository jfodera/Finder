<?php
require_once '../db/db_connect.php';

function findMatchesForLostItems($pdo, $specificItemId = null) {
    try {
        // Attribute weights
        $weights = [
            'type' => 0.5,
            'color' => 0.1,
            'location' => 0.3,
            'date' => 0.1
        ];

        // Modify query based on whether we're checking a specific item or all items
        if ($specificItemId) {
            $query = "SELECT * FROM lost_items WHERE status = 'lost' AND item_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$specificItemId]);
        } else {
            $query = "SELECT * FROM lost_items WHERE status = 'lost'";
            $stmt = $pdo->query($query);
        }
        
        $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $newMatches = [];

        foreach ($lostItems as $lostItem) {
            $lostItemId = $lostItem['item_id'];
            $lostType = $lostItem['type'];
            $lostColor = $lostItem['color'];
            $lostDate = $lostItem['lost_time'];

            // Fetch potential matches from found items
            $foundItems = $pdo->query("
                SELECT * FROM found_items
                WHERE status = 'found'
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($foundItems as $foundItem) {
                $foundItemId = $foundItem['item_id'];
                
                // Check if match already exists
                $checkStmt = $pdo->prepare("
                    SELECT * FROM matches
                    WHERE lost_item_id = ? AND found_item_id = ?
                ");
                $checkStmt->execute([$lostItemId, $foundItemId]);
                
                if (!$checkStmt->fetch()) {
                    // Calculate similarity scores
                    $typeScore = (strtolower($lostType) === strtolower($foundItem['type'])) ? 1 : 0;
                    $colorScore = (strtolower($lostColor) === strtolower($foundItem['color'])) ? 1 : 0;
                    
                    // Location comparison
                    $locationScore = 0;
                    // Get lost item locations
                    $locStmt = $pdo->prepare("SELECT location FROM item_locations WHERE item_id = ? AND item_type = 'lost'");
                    $locStmt->execute([$lostItemId]);
                    $lostLocations = $locStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Get found item locations
                    $locStmt->execute([$foundItemId]);
                    $foundLocations = $locStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Check for any matching locations
                    $commonLocations = array_intersect($lostLocations, $foundLocations);
                    if (!empty($commonLocations)) {
                        $locationScore = 1;
                    }

                    // Calculate date score
                    $dateDiff = abs((strtotime($foundItem['found_time']) - strtotime($lostDate)) / (60 * 60 * 24));
                    $dateScore = ($dateDiff <= 7) ? 1 - ($dateDiff / 7) : 0;

                    // Calculate weighted similarity
                    $similarityScore = 
                        $weights['type'] * $typeScore +
                        $weights['color'] * $colorScore +
                        $weights['location'] * $locationScore +
                        $weights['date'] * $dateScore;

                    // Create match if similarity is high enough
                    if ($similarityScore >= 0.6) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO matches (lost_item_id, found_item_id, similarity_score, status)
                            VALUES (?, ?, ?, 'pending')
                        ");
                        $insertStmt->execute([$lostItemId, $foundItemId, $similarityScore]);
                        
                        $newMatches[] = [
                            'match_id' => $pdo->lastInsertId(),
                            'similarity_score' => $similarityScore
                        ];
                    }
                }
            }
        }

        return $newMatches;

    } catch (PDOException $e) {
        error_log("Error in findMatchesForLostItems: " . $e->getMessage());
        throw $e;
    }
}

// Allow the function to be called directly or via include
if (isset($runMatching) || php_sapi_name() === 'cli') {
    findMatchesForLostItems($pdo);
}
?>