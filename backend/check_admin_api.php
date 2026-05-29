<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Authentication required."
    ]);
    exit;
}

if (!isset($_SESSION['role']) || strtolower((string) $_SESSION['role']) !== "admin") {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Admin access only."
    ]);
    exit;
}
