<?php
require_once "check_admin_api.php";
require_once "db.php";

header("Content-Type: application/json");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user ID."
    ]);
    exit;
}

$user_id = (int) $_GET['id'];
$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($user_id === $adminId) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot move your own account to the Recycle Bin."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET deleted_at = NOW(), deleted_by = ? WHERE user_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $adminId, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "User moved to Recycle Bin."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "User was not found or is already in the Recycle Bin."
    ]);
}

$stmt->close();
$conn->close();
?>
