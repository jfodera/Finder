<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');

    $courseInfo = [
        "course" => "Web Science",
        "number" => "ITWS 4500",
        "Description" => "A course about Web Science"
    ];

    echo json_encode($courseInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>