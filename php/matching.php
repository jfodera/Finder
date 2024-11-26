<?php

require_once '../db/db_connect.php';







try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



    function areWordsSimilar($word1, $word2, $levenshteinWeight = 0.4, $soundexWeight = 0.3, $similarTextWeight = 0.3) {
        // Calculate Levenshtein distance (normalized to a score)
        $levenshteinDistance = levenshtein($word1, $word2);
        $maxLen = max(strlen($word1), strlen($word2));
        $levenshteinScore = $maxLen > 0 ? (1 - ($levenshteinDistance / $maxLen)) : 0; // Normalize to a score between 0 and 1
        
        // Check phonetic similarity using Soundex
        $soundexScore = (soundex($word1) === soundex($word2)) ? 1 : 0; // 1 if they sound the same, otherwise 0
        
        // Calculate string similarity percentage
        similar_text($word1, $word2, $percentage);
        $similarTextScore = $percentage / 100; // Normalize to a score between 0 and 1
    
        // Calculate the weighted similarity score
        $similarity = 
            ($levenshteinWeight * $levenshteinScore) +
            ($soundexWeight * $soundexScore) +
            ($similarTextWeight * $similarTextScore);
    
        // Return the overall similarity score
        return $similarity;
    }
    
    function findMatchesForLostItems($pdo) {
        try {
            // Attribute weights
            $weights = [
                // 'type' => 0.5,
                // 'color' => 0.1,
                // // 'location' => 0.3,
                // 'date' => 0.1
                'type' => 0.5,
                'color' => 0.2,
                // 'location' => 0.3,
                'date' => 0.2
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

                // $All_locations = $pdo->query("
                //     SELECT * FROM item_locations
                // ")->fetchAll(PDO::FETCH_ASSOC);

                // $lostLocation = $lostItem['location'];
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
                    // $foundLocation = $foundItem['location'];
                    $foundDate = $foundItem['found_date'];
    
                    // Calculate similarity scores
                    // $typeScore = (strcasecmp($lostType, $foundType) === 0) ? 1 : 0;
                    $typeScore = areWordsSimilar(strtolower($lostType),strtolower($foundType));
                    $colorScore = (strcasecmp($lostColor, $foundColor) === 0) ? 1 : 0;
                    // $locationScore = ($lostLocation === $foundLocation) ? 1 : 0;
    
                    $dateDiff = abs((strtotime($foundDate) - strtotime($lostDate)) / (60 * 60 * 24));
                    $dateScore = ($dateDiff <= 7) ? 1 - ($dateDiff / 7) : 0;
    
                    // Weighted similarity
                    $similarityScore = 
                        $weights['type'] * $typeScore +
                        $weights['color'] * $colorScore +
                        // $weights['location'] * $locationScore +
                        $weights['date'] * $dateScore;
    
                
                
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





$conn->close();
?>
