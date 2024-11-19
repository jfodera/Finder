<?php
header('Content-Type: application/json');

$requestUri = $_SERVER['REQUEST_URI'];

if ($requestUri === '/itws/4500') {
    $courseInfo = [
        "course" => "Web Science",
        "number" => "ITWS 4500",
        "Description" => "A course about Web Science"
    ];

    echo json_encode($courseInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else http_response_code(404);
