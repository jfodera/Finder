<?php
require_once '../db/db_connect.php';
require_once '../vendor/autoload.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// function to log debug messages
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        error_log(print_r([
            'message' => $message,
            'data' => $data,
            'time' => date('Y-m-d H:i:s')
        ], true));
    }
}

// try the matching process
// there are two parts to this process
// 1st: traditional matching (type, color, brand, date) 40% of the weight
// 2nd: grok matching (using x.ai's grok api) 60% of the weight
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // function to convert singular to plural 
    function convertToPlural($word) {
        // check if word is already plural
        if (preg_match('/(s|x|z|sh|ch)$/', $word)) return $word . 'es';
        // check if word ends in 'y' and is not preceded by a vowel
        if (preg_match('/[aeiou]y$/', $word) === 0 && substr($word, -1) === 'y')  return substr($word, 0, -1) . 'ies';
        // check if word ends in 'f' or 'fe'
        if (substr($word, -1) === 'y' && preg_match('/[aeiou]y$/', $word)) return $word . 's';
        // check if word ends in 'f' or 'fe'
        if (substr($word, -1) === 'f') {
            if (substr($word, -2) === 'fe') {
                return substr($word, 0, -2) . 'ves';
            }
            return substr($word, 0, -1) . 'ves';
        }
        return $word . 's';
    }
    
    // api call to get similar words
    function areWordsSimilar($word1, $word2) {
        if ($word1 === $word2) return 1;
        if (convertToPlural($word1) === $word2) return 1;
        if ($word1 === convertToPlural($word2)) return 1;

        // calls data muse api to get similar words
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

    // function to get grok score
    function getGrokScore($lostItem, $foundItem) {
        // calls x.ai grok api to get similarity score
        try {
            $ch = curl_init('https://api.x.ai/v1/chat/completions');
            
            // the prompt to send to the grok api
            $data = [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI system specialized in matching lost and found items. Compare items and rate their similarity from 0 to 1.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Compare these items and return only a number between 0 and 1:
                            Lost Item: {$lostItem['item_type']}, {$lostItem['brand']}, {$lostItem['color']}
                            Found Item: {$foundItem['item_type']}, {$foundItem['brand']}, {$foundItem['color']}
                            
                            Consider:
                            1. Core matching (40%): Same type of item?
                            2. Brand/Model (25%): Exact or similar brands?
                            3. Physical traits (20%): Color match?
                            4. Time/Location (15%): Found after lost?
                            
                            Return only a number between 0 and 1."
                    ]
                ],
                'model' => 'grok-beta',
                'stream' => false,
                'temperature' => 0.2
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $_ENV['XAI_API_KEY']
                ],
                CURLOPT_POSTFIELDS => json_encode($data)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                debug_log("Grok API error", ['http_code' => $httpCode, 'response' => $response]);
                return null;
            }

            if ($response) {
                $result = json_decode($response, true);
                $score = isset($result['choices'][0]['message']['content']) ? 
                    floatval($result['choices'][0]['message']['content']) : null;
                
                debug_log("Grok score calculated", [
                    'lost_item' => $lostItem['item_type'],
                    'found_item' => $foundItem['item_type'],
                    'score' => $score
                ]);
                
                return $score;
            }
        } catch (Exception $e) {
            debug_log("Grok API error", ['error' => $e->getMessage()]);
        }
        return null;
    }

    function sendNotification($email, $token) {
        $transport = (new Swift_SmtpTransport($_ENV['SMTP_HOST'], $_ENV['SMTP_PORT'], 'tls'))
            ->setUsername($_ENV['SMTP_USER'])
            ->setPassword($_ENV['SMTP_PASS']); 
    
        $mailer = new Swift_Mailer($transport);
    
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'yourdomain.com';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        
        $verificationLink = $protocol . $domain . "/" . $_ENV['URL'] . "/php/verify_email.php?email=" . urlencode($email) . "&token=" . $token;
    
        $message = (new Swift_Message('Verify your Finder account'))
            ->setFrom([$_ENV['SMTP_USER'] => 'Finder'])
            ->setTo([$email])
            ->setBody(
                '<html>' .
                '<body>' .
                '<h1>Welcome to Finder!</h1>' .
                '<p>Please click the link below to verify your account:</p>' .
                '<p><a href="' . $verificationLink . '">Verify Account</a></p>' .
                '</body>' .
                '</html>',
                'text/html'
            );
    
        try {
            $result = $mailer->send($message);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }

    // function to find matches for lost items -> think of as main 
    function findMatchesForLostItems($pdo, $specificItemId = null) {
        debug_log("Starting findMatchesForLostItems", ['specificItemId' => $specificItemId]);
        

        // weights for traditional matching
        // type is the most important, followed by color, brand, and date
        // logic behind this:
        // 1. type is the most important because it's the most unique identifier
        // 2. color and brand is the next most important because it's a common identifier
        // 3. date is the least important because it's not always accurate (you might not know the exact time)
        
        try {
            $weights = [
                'type' => 0.5,
                'color' => 0.2,
                'brand' => 0.2,
                'date' => 0.1
            ];
            
            $newMatches = [];

            // query to get all lost items
            $query = $specificItemId 
                ? "SELECT * FROM lost_items WHERE status = 'lost' AND item_id = ?"
                : "SELECT * FROM lost_items WHERE status = 'lost'";
            
            $stmt = $pdo->prepare($query);
            if ($specificItemId) {
                $stmt->execute([$specificItemId]);
            } else {
                $stmt->execute();
            }
            
            // get all lost items
            $lostItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            debug_log("Found lost items", ['count' => count($lostItems)]);

            if (empty($lostItems)) {
                debug_log("No lost items found to match");
                return [];
            }

            // get all found items that are available
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
                    
                    // check if match already exists
                    if (!$checkStmt->fetch()) {
                        // Calculate traditional score
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
                        
                        //Calculate traditional score
                        $traditionalScore = 
                            $weights['type'] * $typeScore +
                            $weights['color'] * $colorScore +
                            $weights['brand'] * $brandScore +
                            $weights['date'] * $dateScore;

                        // Get Grok score
                        $grokScore = getGrokScore($lostItem, $foundItem);

                        // Calculate final similarity score
                        $similarityScore = $grokScore !== null 
                            ? ($grokScore * 0.6 + $traditionalScore * 0.4)  // 60% Grok, 40% traditional
                            : $traditionalScore;  // Fallback to traditional if Grok fails
                        
                        debug_log("Calculated similarity", [
                            'lost_id' => $lostItem['item_id'],
                            'found_id' => $foundItem['item_id'],
                            'traditional_score' => $traditionalScore,
                            'grok_score' => $grokScore,
                            'final_score' => $similarityScore
                        ]);

                        // if similarity score is greater than 0.5, create a match, updates DB Accordingly 
                        if ($similarityScore >= 0.5) {
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
            }
            
            //send email descripbing which matches were found if any 

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
