<?php
session_start();
require_once '../db/db_connect.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function convertToPlural($word){
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
        return $word . 's';
    }

    function areWordsSimilar($word1, $word2) {
        if ($word1 === $word2) {
            return 1;
        }

        if (convertToPlural($word1) === $word2) {
            return 1; 
        } else if($word1 === convertToPlural($word2)){
            return 1;
        }

        $apiUrl = "https://api.datamuse.com/words?rel_syn=" . urlencode($word1);
        $response = file_get_contents($apiUrl);
    
        if ($response === false) {
            return 0;
        }
    
        $data = json_decode($response, true);
    
        if (is_array($data)) {
            foreach ($data as $item) {
                if (isset($item['word']) && strtolower($item['word']) === strtolower($word2)) {
                    return 0.8;
                }
            }
        }
    
        return 0;
    }
    
    function findMatchesForLostItems($pdo, $specificItemId = null) {
        try {
            $weights = [
                'type' => 0.5,
                'color' => 0.2,
                'brand' => 0.2,
                'date' => 0.1
            ];
            
            $newMatches = []; // Array to track new matches
    
            // Modify query based on whether we're checking a specific item
            if ($specificItemId) {
                $query = "SELECT * FROM lost_items WHERE status = 'lost' AND item_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$specificItemId]);
            } else {
                $query = "SELECT * FROM lost_items WHERE status = 'lost'";
                $stmt = $pdo->query($query);
            }
            
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            foreach ($lostItems as $lostItem) {
                $lostItemId = $lostItem['item_id'];
                $lostType = $lostItem['item_type'];
                $lostColor = $lostItem['color'];
                $lostBrand = $lostItem['brand'];
                $lostTime = $lostItem['lost_time'];
                $user_id = $lostItem['user_id'];

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
    
                    $typeScore = areWordsSimilar(strtolower($lostType), strtolower($foundType));
                    $brandScore = (strcasecmp(trim(strtolower($lostBrand)), trim(strtolower($foundBrand))) === 0) ? 1 : 0;
                    $colorScore = areWordsSimilar(strtolower($lostColor), strtolower($foundColor));
                    $dateScore = (strtotime($lostTime) >= strtotime($foundTime)) ? 0 : 1;
    
                    $similarityScore = 
                        $weights['type'] * $typeScore +
                        $weights['color'] * $colorScore +
                        $weights['brand'] * $brandScore +
                        $weights['date'] * $dateScore;
    
                    $checkStmt = $pdo->prepare("
                        SELECT * FROM matches
                        WHERE lost_item_id = ? AND found_item_id = ?
                    ");
                    $checkStmt->execute([$lostItemId, $foundItemId]);
                     
                    if ($similarityScore >= 0.7 && !$checkStmt->fetch()) {
                        $insertStmt = $pdo->prepare("
                            INSERT INTO matches (lost_item_id, found_item_id, user_id, match_time, status, similarity_score)
                            VALUES (?, ?, ?, ?, 'pending', ?)
                        ");
                        $match_time = date('Y-m-d H:i:s');
                        $insertStmt->execute([$lostItemId, $foundItemId, $user_id, $match_time, $similarityScore]);
                        
                        // Add to new matches array
                        $newMatches[] = [
                            'match_id' => $pdo->lastInsertId(),
                            'lost_item_id' => $lostItemId,
                            'found_item_id' => $foundItemId,
                            'similarity_score' => $similarityScore
                        ];
                    }
                }
            }
            
            return $newMatches; // Return array of new matches
    
        } catch (PDOException $e) {
            error_log("Error in findMatchesForLostItems: " . $e->getMessage());
            throw $e;
        }
    }

    // Only run the matching algorithm if called directly (not through inclusion)
    if (!isset($includeOnly)) {
        findMatchesForLostItems($pdo);
    }
    
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    throw $e;
}
?>