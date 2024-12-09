<?php
session_start();
require_once '../db/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$is_recorder = isset($_SESSION['is_recorder']) && $_SESSION['is_recorder'];

try {
    if ($is_recorder) {
        $query = "
            SELECT 
                m.match_id,
                m.status AS status,
                m.match_time,
                l.item_id AS lost_item_id, 
                l.item_type AS lost_type,
                l.brand AS lost_brand,
                l.color AS lost_color,
                l.additional_info AS lost_info,
                l.lost_time,
                l.image_url AS lost_image_url,
                f.item_id AS found_item_id,
                f.item_type AS found_type,
                f.brand AS found_brand,
                f.color AS found_color,
                f.additional_info AS found_info,
                f.found_time,
                f.image_url AS found_image_url,
                GROUP_CONCAT(DISTINCT il_lost.location) as lost_locations,
                GROUP_CONCAT(DISTINCT il_found.location) as found_locations,
                CONCAT(u_lost.first_name, ' ', u_lost.last_name) as reporter_name,
                CONCAT(u_found.first_name, ' ', u_found.last_name) as finder_name
            FROM matches m
            JOIN lost_items l ON m.lost_item_id = l.item_id
            JOIN found_items f ON m.found_item_id = f.item_id
            LEFT JOIN item_locations il_lost ON l.item_id = il_lost.item_id AND il_lost.item_type = 'lost'
            LEFT JOIN item_locations il_found ON f.item_id = il_found.item_id AND il_found.item_type = 'found'
            JOIN users u_lost ON l.user_id = u_lost.user_id
            JOIN users u_found ON f.recorder_id = u_found.user_id
            GROUP BY m.match_id
            ORDER BY m.match_time DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
    } else {
        $query = "
            SELECT 
                m.match_id,
                m.status AS status,
                m.match_time,
                l.item_id AS lost_item_id, 
                l.item_type AS lost_type,
                l.brand AS lost_brand,
                l.color AS lost_color,
                l.additional_info AS lost_info,
                l.lost_time,
                l.image_url AS lost_image_url,
                f.item_id AS found_item_id,
                f.item_type AS found_type,
                f.brand AS found_brand,
                f.color AS found_color,
                f.additional_info AS found_info,
                f.found_time,
                f.image_url AS found_image_url,
                GROUP_CONCAT(DISTINCT il_lost.location) as lost_locations,
                GROUP_CONCAT(DISTINCT il_found.location) as found_locations,
                CONCAT(u_found.first_name, ' ', u_found.last_name) as finder_name
            FROM matches m
            JOIN lost_items l ON m.lost_item_id = l.item_id
            JOIN found_items f ON m.found_item_id = f.item_id
            LEFT JOIN item_locations il_lost ON l.item_id = il_lost.item_id AND il_lost.item_type = 'lost'
            LEFT JOIN item_locations il_found ON f.item_id = il_found.item_id AND il_found.item_type = 'found'
            JOIN users u_found ON f.recorder_id = u_found.user_id
            WHERE m.user_id = ?
            GROUP BY m.match_id
            ORDER BY m.match_time DESC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
    }

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedMatches = array_map(function($match) {
        return [
            'match_id' => $match['match_id'],
            'status' => $match['status'],
            'match_time' => $match['match_time'],
            'lost_item' => [
                'item_id' => $match['lost_item_id'],
                'item_type' => $match['lost_type'],
                'brand' => $match['lost_brand'] ?? 'N/A',
                'color' => $match['lost_color'] ?? 'N/A',
                'additional_info' => $match['lost_info'] ?? '',
                'lost_time' => $match['lost_time'],
                'locations' => $match['lost_locations'] ? explode(',', $match['lost_locations']) : [],
                'image_url' => $match['lost_image_url'] ?? './../assets/placeholderImg.svg',
                'reporter_name' => $match['reporter_name'] ?? 'Unknown'
            ],
            'found_item' => [
                'item_id' => $match['found_item_id'],
                'item_type' => $match['found_type'],
                'brand' => $match['found_brand'] ?? 'N/A',
                'color' => $match['found_color'] ?? 'N/A',
                'additional_info' => $match['found_info'] ?? '',
                'found_time' => $match['found_time'],
                'locations' => $match['found_locations'] ? explode(',', $match['found_locations']) : [],
                'image_url' => $match['found_image_url'] ?? './../assets/placeholderImg.svg',
                'finder_name' => $match['finder_name'] ?? 'Unknown'
            ]
        ];
    }, $matches);

    echo json_encode([
        'success' => true,
        'items' => $formattedMatches
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    error_log("Database error in getMatches.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error', 'error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in getMatches.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred', 'error' => $e->getMessage()]);
}