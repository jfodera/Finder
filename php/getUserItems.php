<?php
// get_user_items.php
session_start();
require_once '../db/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
    exit();
}

try {
    // Fetch all items from the database
    $stmt = $pdo->prepare("
        SELECT 
            li.*,
            GROUP_CONCAT(il.location) as locations
        FROM lost_items li
        LEFT JOIN item_locations il ON li.item_id = il.item_id AND il.item_type = 'lost'
        WHERE li.user_id = ?
        GROUP BY li.item_id
        ORDER BY li.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the items array to include only the necessary fields
    $formattedItems = array_map(function($item) {
        return [
            'item_id' => $item['item_id'],
            'item_type' => $item['item_type'],
            'brand' => $item['brand'],
            'color' => $item['color'],
            'additional_info' => $item['additional_info'],
            'lost_time' => $item['lost_time'],
            'status' => $item['status'],
            'image_url' => $item['image_url'],
            'locations' => explode(',', $item['locations']), 
            'created_at' => $item['created_at']
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
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch items',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}