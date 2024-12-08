<?php
// getapikey.php
require_once '../db/db_connect.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');
echo json_encode(['key' => $_ENV['XAI_API_KEY']]);