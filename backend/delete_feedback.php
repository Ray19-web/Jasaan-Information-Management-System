<?php
require_once "check_admin_api.php";
require_once "db.php";

$id = (int) ($_POST['id'] ?? 0);
$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid feedback."]);
    exit;
}

$stmt = $conn->prepare("UPDATE feedbacks SET deleted_at = NOW(), deleted_by = ? WHERE feedback_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $adminId, $id);
$stmt->execute();

echo json_encode([
    "success" => $stmt->affected_rows > 0,
    "message" => $stmt->affected_rows > 0
        ? "Feedback moved to Recycle Bin."
        : "Feedback was not found or is already in the Recycle Bin."
]);
