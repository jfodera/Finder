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

// Fetch all found items from the database
try {
    $stmt = $pdo->prepare("
        SELECT 
            fi.*,
            GROUP_CONCAT(il.location) as locations,
            CONCAT(u.first_name, ' ', u.last_name) as recorder_name
        FROM found_items fi
        LEFT JOIN item_locations il ON fi.item_id = il.item_id AND il.item_type = 'found'
        LEFT JOIN users u ON fi.recorder_id = u.user_id
        GROUP BY fi.item_id
        ORDER BY fi.created_at DESC
    ");
    
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the items array to include only the necessary fields
    $formattedItems = array_map(function($item) {
        return [
            'item_id' => $item['item_id'],
            'item_type' => $item['item_type'],
            'brand' => $item['brand'] ?? 'N/A',
            'color' => $item['color'] ?? 'N/A',
            'additional_info' => $item['additional_info'] ?? '',
            'found_time' => $item['found_time'],
            'status' => $item['status'],
            'image_url' => $item['image_url'] ?? './../assets/placeholderImg.svg',
            'locations' => $item['locations'] ? explode(',', $item['locations']) : [],
            'created_at' => $item['created_at'],
            'recorder_name' => $item['recorder_name'] ?? 'Unknown'
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
    error_log("Database error in getFoundItems.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch items',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in getFoundItems.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}