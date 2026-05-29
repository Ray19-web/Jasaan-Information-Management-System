<?php
require_once "check_admin_api.php";
require_once "db.php";

$id = intval($_POST['id'] ?? 0);
$hidden = intval($_POST['hidden'] ?? -1);

if ($id <= 0 || ($hidden !== 0 && $hidden !== 1)) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Invalid feedback visibility request."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE feedbacks SET is_hidden = ?, is_read = 1 WHERE feedback_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $hidden, $id);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to update feedback visibility."
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "hidden" => $hidden === 1,
    "is_read" => true
]);
