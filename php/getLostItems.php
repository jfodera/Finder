<?php
session_start();
require_once '../db/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is a recorder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_recorder']) || !$_SESSION['is_recorder']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            li.*,
            GROUP_CONCAT(il.location) as locations,
            CONCAT(u.first_name, ' ', u.last_name) as reporter_name
        FROM lost_items li
        LEFT JOIN item_locations il ON li.item_id = il.item_id AND il.item_type = 'lost'
        LEFT JOIN users u ON li.user_id = u.user_id
        GROUP BY li.item_id
        ORDER BY li.created_at DESC
    ");
    
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the items
    $formattedItems = array_map(function($item) {
        return [
            'item_id' => $item['item_id'],
            'item_type' => $item['item_type'],
            'brand' => $item['brand'] ?? 'N/A',
            'color' => $item['color'] ?? 'N/A',
            'additional_info' => $item['additional_info'] ?? '',
            'lost_time' => $item['lost_time'],
            'status' => $item['status'],
            'image_url' => $item['image_url'] ?? '../default_image.png',
            'locations' => $item['locations'] ? explode(',', $item['locations']) : [],
            'created_at' => $item['created_at'],
            'reporter_name' => $item['reporter_name'] ?? 'Unknown'
        ];
    }, $items);

    $response = [
        'success' => true,
        'items' => $formattedItems
    ];

    $jsonResponse = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($jsonResponse === false) {
        throw new Exception('JSON encoding failed: ' . json_last_error_msg());
    }

    echo $jsonResponse;

} catch (PDOException $e) {
    error_log("Database error in getLostItems.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch items',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in getLostItems.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}