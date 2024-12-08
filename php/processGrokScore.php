<?php
session_start();
require_once '../db/db_connect.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['grok_score']) || !isset($data['lost_item_id']) || !isset($data['found_item_id'])) {
        throw new Exception('Missing required data');
    }

    // Store the score in session for matching.php to use
    $_SESSION['grok_scores'][$data['lost_item_id']][$data['found_item_id']] = $data['grok_score'];
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}