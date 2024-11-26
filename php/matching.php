<?php
session_start();
require_once '../db/db_connect.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    function convertToPlural($word){
        //convert to plural words using grammar rules

        if (preg_match('/(s|x|z|sh|ch)$/', $word)) {
            return $word . 'es';
        }
        if (preg_match('/[aeiou]y$/', $word) === 0 && substr($word, -1) === 'y') {
            return substr($word, 0, -1) . 'ies';
        }
        if (substr($word, -1) === 'y' && preg_match('/[aeiou]y$/', $word)) {
            return $word . 's';
        }
    
        if (substr($word, -1) === 'f') {
            if (substr($word, -2) === 'fe') {
                return substr($word, 0, -2) . 'ves';
            }
            return substr($word, 0, -1) . 'ves';
        }
        // For regular nouns, just add -s
        return $word . 's';
    }

    function areWordsSimilar($word1, $word2) {
        if ($word1 === $word2) {
            return 1; // Words are identical
        }

        if (convertToPlural($word1) === $word2) {
            return 1; 
        }else if($word1 === convertToPlural($word2)){
            return 1;
        }
        

    
        $apiUrl = "https://api.datamuse.com/words?rel_syn=" . urlencode($word1); //API for synonyms
        $response = file_get_contents($apiUrl);
    
        if ($response === false) {
            return 0; // API call failed
        }
    
        $data = json_decode($response, true);
    
        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['word']) && strtolower($item['word']) === strtolower($word2)) {
                    return 0.8; // Found a match
                }
            }
        }
    
        return 0; // No match found
    }
    
    function findMatchesForLostItems($pdo) {
        try {
            // Attribute weights
            $weights = [
                'type' => 0.5,
                'color' => 0.2,
                'brand' => 0.2,
                'date' => 0.1
            ];
    
            // Fetch all unmatched lost items
            $lostItems = $pdo->query("
                SELECT * FROM lost_items
                WHERE status = 'lost'
            ")->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($lostItems as $lostItem) {
                $lostItemId = $lostItem['item_id'];
                $lostType = $lostItem['item_type'];
                $lostColor = $lostItem['color'];
                $lostBrand = $lostItem['brand'];
                $lostTime = $lostItem['lost_time'];
                $user_id= $lostItem['user_id'];
                $foundItems = $pdo->query("
                    SELECT * FROM found_items
                    WHERE status = 'found'
                ")->fetchAll(PDO::FETCH_ASSOC);
    
                foreach ($foundItems as $foundItem) {
                    $foundItemId = $foundItem['item_id'];
                    $foundType = $foundItem['item_type'];
                    $foundColor = $foundItem['color'];
                    $foundBrand = $foundItem['brand'];
                    $foundTime = $foundItem['found_time'];
    
                    $typeScore = areWordsSimilar(strtolower($lostType),strtolower($foundType));
                    $brandScore = (strcasecmp(trim(strtolower($lostBrand)), trim(strtolower($foundBrand))) === 0) ? 1 : 0;
                    $colorScore = areWordsSimilar(strtolower($lostColor),strtolower($foundColor));
    
                    if (strtotime($lostTime) >= strtotime($foundTime)) {
                       
                        $dateScore = 0; // Set score to 0 or handle as needed
                    } else {
                        $dateScore = 1; 
                    }
                    
    
                    // Weighted similarity
                    $similarityScore = 
                        $weights['type'] * $typeScore +
                        $weights['color'] * $colorScore +
                        $weights['brand'] * $brandScore +
                        $weights['date'] * $dateScore;
    
                
                
                        // Check if the match already exists
                    $checkStmt = $pdo->prepare("
                        SELECT * FROM matches
                        WHERE lost_item_id = ? AND found_item_id = ?
                    ");
                    $checkStmt->execute([$lostItemId, $foundItemId]);
                     
                    if ($similarityScore >= 0.7) {

                        if (!$checkStmt->fetch()) {
                            // Create a new match
                            $insertStmt = $pdo->prepare("
                               
                                INSERT INTO matches (lost_item_id, found_item_id, user_id, match_time,status)
                                VALUES (?, ?, ?, ?, 'pending')
                            ");
                            $match_time = date('Y-m-d H:i:s');
                            $insertStmt->execute([$lostItemId, $foundItemId, $user_id, $match_time]);
                           

                            // echo "Type Score: $typeScore and Color Score: $colorScore \n";

                            // echo "Match created for Lost Item #$lostItemId and Found Item #$foundItemId with similarity score: $similarityScore\n";
                        }
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
