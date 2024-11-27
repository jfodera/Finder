<?php
require_once '../db/db_connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        error_log(print_r([
            'message' => $message,
            'data' => $data,
            'time' => date('Y-m-d H:i:s')
        ], true));
    }
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    function convertToPlural($word) {
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
        if ($word1 === $word2) return 1;
        if (convertToPlural($word1) === $word2) return 1;
        if ($word1 === convertToPlural($word2)) return 1;

        try {
            $apiUrl = "https://api.datamuse.com/words?rel_syn=" . urlencode($word1);
            $context = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response === false) {
                debug_log("API call failed for word comparison");
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
            debug_log("Word comparison error: " . $e->getMessage());
            return 0;
        }
        return 0;
    }

    function findMatchesForLostItems($pdo, $specificItemId = null) {
        debug_log("Starting findMatchesForLostItems", ['specificItemId' => $specificItemId]);
        
        try {
            $weights = [
                'type' => 0.5,
                'color' => 0.2,
                'brand' => 0.2,
                'date' => 0.1
            ];
            
            $newMatches = [];

            $query = $specificItemId 
                ? "SELECT * FROM lost_items WHERE status = 'lost' AND item_id = ?"
                : "SELECT * FROM lost_items WHERE status = 'lost'";
            
            $stmt = $pdo->prepare($query);
            if ($specificItemId) {
                $stmt->execute([$specificItemId]);
            } else {
                $stmt->execute();
            }
            
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            debug_log("Found lost items", ['count' => count($lostItems)]);

            if (empty($lostItems)) {
                debug_log("No lost items found to match");
                return [];
            }

            foreach ($lostItems as $lostItem) {
                debug_log("Processing lost item", [
                    'item_id' => $lostItem['item_id'],
                    'type' => $lostItem['item_type']
                ]);

                $foundStmt = $pdo->query("SELECT * FROM found_items WHERE status = 'available'");
                $foundItems = $foundStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($foundItems as $foundItem) {
                    $checkStmt = $pdo->prepare("SELECT * FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
                    $checkStmt->execute([$lostItem['item_id'], $foundItem['item_id']]);
                    
                    if (!$checkStmt->fetch()) {
                        $typeScore = areWordsSimilar(
                            strtolower($lostItem['item_type']), 
                            strtolower($foundItem['item_type'])
                        );
                        $brandScore = (strcasecmp(
                            trim(strtolower($lostItem['brand'])), 
                            trim(strtolower($foundItem['brand']))
                        ) === 0) ? 1 : 0;
                        $colorScore = areWordsSimilar(
                            strtolower($lostItem['color']), 
                            strtolower($foundItem['color'])
                        );
                        $dateScore = (strtotime($lostItem['lost_time']) >= strtotime($foundItem['found_time'])) ? 0 : 1;

                        $similarityScore = 
                            $weights['type'] * $typeScore +
                            $weights['color'] * $colorScore +
                            $weights['brand'] * $brandScore +
                            $weights['date'] * $dateScore;
                        
                        debug_log("Calculated similarity", [
                            'lost_id' => $lostItem['item_id'],
                            'found_id' => $foundItem['item_id'],
                            'score' => $similarityScore
                        ]);

                        try {
                            $insertStmt = $pdo->prepare("
                            INSERT INTO matches (
                                lost_item_id,
                                found_item_id, 
                                user_id,
                                similarity_score,
                                match_time,
                                status
                            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 'pending')
                            ");
                            
                            $insertStmt->execute([
                                $lostItem['item_id'],
                                $foundItem['item_id'],
                                $lostItem['user_id'],
                                $similarityScore
                            ]);
                            
                            $newMatchId = $pdo->lastInsertId();
                            debug_log("Match created successfully", ['match_id' => $newMatchId]);
                            
                            $newMatches[] = [
                                'match_id' => $newMatchId,
                                'lost_item_id' => $lostItem['item_id'],
                                'found_item_id' => $foundItem['item_id'],
                                'similarity_score' => $similarityScore
                            ];
                        } catch (PDOException $e) {
                            debug_log("Failed to insert match", ['error' => $e->getMessage()]);
                            continue;
                        }
                    }
                }
            }
            
            debug_log("Matching complete", ['new_matches_count' => count($newMatches)]);
            return $newMatches;

        } catch (PDOException $e) {
            debug_log("Error in findMatchesForLostItems: " . $e->getMessage());
            throw $e;
        }
    }

    if (!isset($includeOnly)) {
        $matches = findMatchesForLostItems($pdo);
        echo json_encode(['success' => true, 'matches' => $matches]);
    }

} catch (PDOException $e) {
    debug_log("Connection failed: " . $e->getMessage());
    if (!isset($includeOnly)) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    }
    throw $e;
}