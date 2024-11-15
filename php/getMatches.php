<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_recorder = isset($_SESSION['is_recorder']) && $_SESSION['is_recorder'];

try {
    if ($is_recorder) {
        // Recorders see all matches with details
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   l.item_type as lost_item_type, l.brand as lost_brand, l.color as lost_color,
                   l.additional_info as lost_info, l.lost_time, l.image_url as lost_image,
                   l.user_id as lost_user_id, u.first_name as lost_user_first_name, 
                   u.last_name as lost_user_last_name,
                   f.item_type as found_item_type, f.brand as found_brand, f.color as found_color,
                   f.additional_info as found_info, f.found_time, f.image_url as found_image
            FROM matches m
            JOIN lost_items l ON m.lost_item_id = l.item_id
            JOIN found_items f ON m.found_item_id = f.item_id
            JOIN users u ON l.user_id = u.user_id
            ORDER BY m.match_time DESC
        ");
    } else {
        // Users only see their own matches
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   l.item_type as lost_item_type, l.brand as lost_brand, l.color as lost_color,
                   l.additional_info as lost_info, l.lost_time, l.image_url as lost_image,
                   f.item_type as found_item_type, f.brand as found_brand, f.color as found_color,
                   f.additional_info as found_info, f.found_time, f.image_url as found_image
            FROM matches m
            JOIN lost_items l ON m.lost_item_id = l.item_id
            JOIN found_items f ON m.found_item_id = f.item_id
            WHERE m.user_id = ?
            ORDER BY m.match_time DESC
        ");
        $stmt->execute([$user_id]);
    }
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format matches for frontend
    $formatted_matches = array_map(function($match) {
        return [
            'match_id' => $match['match_id'],
            'status' => $match['status'],
            'lost_item' => [
                'item_id' => $match['lost_item_id'],
                'item_type' => $match['lost_item_type'],
                'brand' => $match['lost_brand'],
                'color' => $match['lost_color'],
                'additional_info' => $match['lost_info'],
                'lost_time' => $match['lost_time'],
                'image_url' => $match['lost_image'],
                'user_name' => isset($match['lost_user_first_name']) ? 
                    $match['lost_user_first_name'] . ' ' . $match['lost_user_last_name'] : null
            ],
            'found_item' => [
                'item_id' => $match['found_item_id'],
                'item_type' => $match['found_item_type'],
                'brand' => $match['found_brand'],
                'color' => $match['found_color'],
                'additional_info' => $match['found_info'],
                'found_time' => $match['found_time'],
                'image_url' => $match['found_image']
            ]
        ];
    }, $matches);

    header('Content-Type: application/json');
    echo json_encode(['items' => $formatted_matches]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
?>