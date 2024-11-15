<?php
// getAllItems.php
session_start();
require_once '../db/db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // For regular users, only get their own lost items
    if (!$_SESSION['is_recorder']) {
        $stmt = $pdo->prepare("
            SELECT l.*, u.email as reporter_email 
            FROM lost_items l 
            LEFT JOIN users u ON l.user_id = u.user_id 
            WHERE l.user_id = ?
            ORDER BY l.date_lost DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $lost_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $formatted_items = [
            'lost_items' => array_map(function($item) {
                return [
                    'item_type' => $item['item_name'],
                    'brand' => $item['brand'] ?? 'N/A',
                    'color' => $item['color'] ?? 'N/A',
                    'additional_info' => $item['description'],
                    'lost_time' => $item['date_lost'],
                    'status' => $item['status'],
                    'locations' => $item['location'],
                    'image_url' => $item['image_url'] ?? '../default_image.png'
                ];
            }, $lost_items),
            'found_items' => []
        ];
    } 
    // For recorders, get all lost and found items
    else {
        // Get all lost items
        $stmt = $pdo->prepare("
            SELECT l.*, u.email as reporter_email 
            FROM lost_items l 
            LEFT JOIN users u ON l.user_id = u.user_id 
            ORDER BY l.date_lost DESC
        ");
        $stmt->execute();
        $lost_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get all found items
        $stmt = $pdo->prepare("
            SELECT f.*, u.email as recorder_email 
            FROM found_items f 
            LEFT JOIN users u ON f.recorder_id = u.user_id 
            ORDER BY f.date_found DESC
        ");
        $stmt->execute();
        $found_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted_items = [
            'lost_items' => array_map(function($item) {
                return [
                    'item_type' => $item['item_name'],
                    'brand' => $item['brand'] ?? 'N/A',
                    'color' => $item['color'] ?? 'N/A',
                    'additional_info' => $item['description'],
                    'lost_time' => $item['date_lost'],
                    'status' => $item['status'],
                    'locations' => $item['location'],
                    'image_url' => $item['image_url'] ?? '../default_image.png',
                    'reporter_email' => $item['reporter_email']
                ];
            }, $lost_items),
            'found_items' => array_map(function($item) {
                return [
                    'item_type' => $item['item_name'],
                    'brand' => $item['brand'] ?? 'N/A',
                    'color' => $item['color'] ?? 'N/A',
                    'additional_info' => $item['description'],
                    'lost_time' => $item['date_found'],
                    'status' => $item['status'],
                    'locations' => $item['location_found'],
                    'image_url' => $item['image_url'] ?? '../default_image.png',
                    'recorder_email' => $item['recorder_email']
                ];
            }, $found_items)
        ];
    }

    echo json_encode($formatted_items);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>