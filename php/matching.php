<?php
session_start();
require_once '../db/db_connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

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

        try {
            $apiUrl = "https://api.datamuse.com/words?rel_syn=" . urlencode($word1);
            $context = stream_context_create(['http' => ['timeout' => 2]]); // 2 second timeout
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                error_log("API call failed for word comparison");
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
        } catch (Exception $e) {
            error_log("Word comparison error: " . $e->getMessage());
            return 0;
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
            
            $newMatches = [];
    
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
            if (empty($lostItems)) {
                return [];
            }
    
            foreach ($lostItems as $lostItem) {
                $lostItemId = $lostItem['item_id'];
                $lostType = $lostItem['item_type'];
                $lostColor = $lostItem['color'];
                $lostBrand = $lostItem['brand'];
                $lostTime = $lostItem['lost_time'];
                $user_id = $lostItem['user_id'];

                $foundStmt = $pdo->query("
                    SELECT * FROM found_items
                    WHERE status = 'found'
                ");
                
                if (!$foundStmt) {
                    error_log("Failed to query found items");
                    continue;
                }

                $foundItems = $foundStmt->fetchAll(PDO::FETCH_ASSOC);
    
                foreach ($foundItems as $foundItem) {
                    $foundItemId = $foundItem['item_id'];
                    
                    // Check if match already exists
                    $checkStmt = $pdo->prepare("
                        SELECT * FROM matches
                        WHERE lost_item_id = ? AND found_item_id = ?
                    ");
                    $checkStmt->execute([$lostItemId, $foundItemId]);
                    
                    if (!$checkStmt->fetch()) {
                        $typeScore = areWordsSimilar(strtolower($lostType), strtolower($foundItem['item_type']));
                        $brandScore = (strcasecmp(trim(strtolower($lostBrand)), trim(strtolower($foundItem['brand']))) === 0) ? 1 : 0;
                        $colorScore = areWordsSimilar(strtolower($lostColor), strtolower($foundItem['color']));
                        $dateScore = (strtotime($lostTime) >= strtotime($foundItem['found_time'])) ? 0 : 1;
        
                        $similarityScore = 
                            $weights['type'] * $typeScore +
                            $weights['color'] * $colorScore +
                            $weights['brand'] * $brandScore +
                            $weights['date'] * $dateScore;
        
                        if ($similarityScore >= 0.7) {
                            try {
                                $insertStmt = $pdo->prepare("
                                    INSERT INTO matches (lost_item_id, found_item_id, user_id, match_time, status)
                                    VALUES (?, ?, ?, ?, 'pending')
                                ");
                                $match_time = date('Y-m-d H:i:s');
                                $insertStmt->execute([$lostItemId, $foundItemId, $user_id, $match_time]);
                                
                                $newMatches[] = [
                                    'match_id' => $pdo->lastInsertId(),
                                    'lost_item_id' => $lostItemId,
                                    'found_item_id' => $foundItemId,
                                    'similarity_score' => $similarityScore
                                ];
                            } catch (PDOException $e) {
                                error_log("Failed to insert match: " . $e->getMessage());
                                continue;
                            }
                        }
                    }
                }
            }
            
            return $newMatches;
    
        } catch (PDOException $e) {
            error_log("Error in findMatchesForLostItems: " . $e->getMessage());
            return [];
        }
    }

    // Only run the matching algorithm if called directly
    if (isset($runMatching) && $runMatching === true) {
        $matches = findMatchesForLostItems($pdo);
        echo json_encode(['success' => true, 'matches' => $matches]);
    }
    
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    if (isset($runMatching) && $runMatching === true) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    }
}
?>