<?php
require_once '../db/db_connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

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

    // Word pluralization helper
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

    // Color similarity comparison
    function AreColorSimilar($word1, $word2) {
        $color1 = preg_replace('/\s+/', '', $word1);
        $color2 = preg_replace('/\s+/', '', $word2);

        try {
            $apiUrlColor1 = "https://www.csscolorsapi.com/api/colors/" . urlencode($color1);
            $apiUrlColor2 = "https://www.csscolorsapi.com/api/colors/" . urlencode($color2);

            $context = stream_context_create(['http' => ['timeout' => 2]]);

            $response1 = @file_get_contents($apiUrlColor1, false, $context);
            $response2 = @file_get_contents($apiUrlColor2, false, $context);

            if ($response1 === false || $response2 === false) {
                debug_log("API call failed for color comparison");
                return 0;
            }

            $data1 = json_decode($response1, true);
            $data2 = json_decode($response2, true);

            if (!isset($data1['data']['group']) || !isset($data2['data']['group'])) {
                debug_log("Color group data is missing in the API response");
                return 0;
            }
      
            if ($data1['data']['group'] === $data2['data']['group']) {
                return 0.9;
            } else {
                return 0; 
            }
        } catch (Exception $e) {
            debug_log("Color comparison error: " . $e->getMessage());
            return 0;
        }
    }

    // Word similarity comparison
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

    // Grok AI comparison
    function compareItemsWithGrok($item1Details, $item2Details) {
        if (!isset($_ENV['GROK_API_KEY'])) {
            debug_log("Grok API key not found");
            return null;
        }

        $item1Location = !empty($item1Details['location']) ? $item1Details['location'] : 'No location specified';
        $item2Location = !empty($item2Details['location']) ? $item2Details['location'] : 'No location specified';
        
        $item1Time = !empty($item1Details['lost_time']) ? date('Y-m-d', strtotime($item1Details['lost_time'])) : 'No date specified';
        $item2Time = !empty($item2Details['found_time']) ? date('Y-m-d', strtotime($item2Details['found_time'])) : 'No date specified';

        $prompt = <<<EOT
You are a specialized lost and found matching system. Analyze these two items and determine if they could be the same item. Rate their similarity from 0 to 1, where 1 means definitely the same item and 0 means definitely different items.

ITEM 1 (Lost Item):
- Type: {$item1Details['item_type']}
- Brand: {$item1Details['brand']}
- Color: {$item1Details['color']}
- Location Lost: {$item1Location}
- Date Lost: {$item1Time}
- Description: {$item1Details['description']}

ITEM 2 (Found Item):
- Type: {$item2Details['item_type']}
- Brand: {$item2Details['brand']}
- Color: {$item2Details['color']}
- Location Found: {$item2Location}
- Date Found: {$item2Time}
- Description: {$item2Details['description']}

Consider these factors in order of importance:
1. Core Item Match (40% weight):
   - Is it the same category of item?
   - Do the specific item types match or are they very similar?
   - Are there any unique identifying features mentioned in both descriptions?

2. Brand and Model (25% weight):
   - Is it the same brand?
   - If brand is mentioned in one but not the other, look for brand indicators in the description
   - Consider similar or commonly confused brand names

3. Physical Attributes (20% weight):
   - Color match or similar colors
   - Size if mentioned
   - Material if mentioned
   - Condition descriptions

4. Temporal and Spatial Logic (15% weight):
   - Was the item found after it was lost?
   - Are the locations reasonably close or along a logical path?
   - Is the time difference reasonable?

Additional Considerations:
- Look for unique identifying marks or features mentioned in descriptions
- Consider common misspellings or similar-sounding words
- Account for subjective color descriptions
- Consider partial brand name matches

Return only a number between 0 and 1, rounded to two decimal places, representing the probability these are the same item.
EOT;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.grok.ai/v1/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $_ENV['GROK_API_KEY'],
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'prompt' => $prompt,
                'max_tokens' => 10,
                'temperature' => 0.2,
                'top_p' => 0.9,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0
            ])
        ]);

        try {
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                debug_log("Grok API call failed", curl_error($ch));
                return null;
            }

            $result = json_decode($response, true);
            if (isset($result['choices'][0]['text'])) {
                $score = floatval(trim($result['choices'][0]['text']));
                return max(0, min(1, round($score, 2)));
            }
            
            debug_log("Unexpected Grok API response", $result);
            return null;
        } catch (Exception $e) {
            debug_log("Error in Grok comparison: " . $e->getMessage());
            return null;
        } finally {
            curl_close($ch);
        }
    }

    // Main matching function
    function findMatchesForLostItems($pdo, $specificItemId = null) {
        debug_log("Starting findMatchesForLostItems", ['specificItemId' => $specificItemId]);
        
        try {
            $weights = [
                'traditional' => 0.5,  // 50% traditional matching
                'grok' => 0.5         // 50% Grok AI matching
            ];
            
            // Weights for traditional matching components
            $traditionalWeights = [
                'type' => 0.5,   // 50% of traditional score
                'color' => 0.2,  // 20% of traditional score
                'brand' => 0.2,  // 20% of traditional score
                'date' => 0.1    // 10% of traditional score
            ];
            
            $newMatches = [];

            // Query preparation
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
            debug_log("Processing lost items", ['count' => count($lostItems)]);

            // Process each lost item
            foreach ($lostItems as $lostItem) {
                $foundStmt = $pdo->query("SELECT * FROM found_items WHERE status = 'available'");
                $foundItems = $foundStmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($foundItems as $foundItem) {
                    // Check if match already exists
                    $checkStmt = $pdo->prepare("SELECT * FROM matches WHERE lost_item_id = ? AND found_item_id = ?");
                    $checkStmt->execute([$lostItem['item_id'], $foundItem['item_id']]);
                    
                    if (!$checkStmt->fetch()) {
                        // Calculate traditional similarity score
                        $typeScore = areWordsSimilar(
                            strtolower($lostItem['item_type']), 
                            strtolower($foundItem['item_type'])
                        );
                        $brandScore = (strcasecmp(
                            trim(strtolower($lostItem['brand'])), 
                            trim(strtolower($foundItem['brand']))
                        ) === 0) ? 1 : 0;
                        $colorScore = AreColorSimilar(
                            strtolower($lostItem['color']), 
                            strtolower($foundItem['color'])
                        );
                        $dateScore = (strtotime($lostItem['lost_time']) >= strtotime($foundItem['found_time'])) ? 0 : 1;

                        $traditionalScore = 
                            $traditionalWeights['type'] * $typeScore +
                            $traditionalWeights['color'] * $colorScore +
                            $traditionalWeights['brand'] * $brandScore +
                            $traditionalWeights['date'] * $dateScore;

                        // Get Grok AI similarity score
                        $grokScore = compareItemsWithGrok($lostItem, $foundItem);

                        // Calculate final similarity score
                        $similarityScore = $grokScore !== null 
                            ? ($weights['grok'] * $grokScore + $weights['traditional'] * $traditionalScore)
                            : $traditionalScore;

                        debug_log("Match analysis", [
                            'lost_id' => $lostItem['item_id'],
                            'found_id' => $foundItem['item_id'],
                            'traditional_score' => $traditionalScore,
                            'grok_score' => $grokScore,
                            'final_score' => $similarityScore,
                            'weights_used' => $weights
                        ]);

                        // If similarity score meets threshold, create match
                        if ($similarityScore) {
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
                                debug_log("Created new match", ['match_id' => $newMatchId, 'score' => $similarityScore]);
                                
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
            }
            
            return $newMatches;

        } catch (PDOException $e) {
            debug_log("Error in findMatchesForLostItems: " . $e->getMessage());
            throw $e;
        }
    }

    // Execute matching if not included as a module
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