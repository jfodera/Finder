<?php
// <!-- NEVER USED BUT KEPT FOR FUTURE IMPLEMENTATION -->
session_start();
require_once '../db/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle match confirmation/rejection
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['match_id']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$match_id = $data['match_id'];
$user_id = $_SESSION['user_id'];
$action = $data['action'];
$status = ($action === 'confirm') ? 'confirmed' : 'rejected';

try {
    // Verify the match belongs to the user
    $stmt = $pdo->prepare("
        SELECT m.* FROM matches m
        JOIN lost_items l ON m.lost_item_id = l.item_id
        WHERE m.match_id = ? AND l.user_id = ?
    ");
    $stmt->execute([$match_id, $user_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Match not found or unauthorized']);
        exit();
    }

    // Update match status
    $stmt = $pdo->prepare("
        UPDATE matches 
        SET status = ?
        WHERE match_id = ?
    ");
    $stmt->execute([$status, $match_id]);

    if ($status === 'confirmed') {
        // Update lost and found item statuses
        $stmt = $pdo->prepare("
            UPDATE lost_items l
            JOIN matches m ON l.item_id = m.lost_item_id
            SET l.status = 'found'
            WHERE m.match_id = ?
        ");
        $stmt->execute([$match_id]);

        $stmt = $pdo->prepare("
            UPDATE found_items f
            JOIN matches m ON f.item_id = m.found_item_id
            SET f.status = 'claimed'
            WHERE m.match_id = ?
        ");
        $stmt->execute([$match_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Match updated successfully']);

} catch (PDOException $e) {
    error_log("Database error in handleUserMatch.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update match',
        'error' => $e->getMessage()
    ]);
}