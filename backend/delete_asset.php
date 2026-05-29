<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid asset."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE assets SET deleted_at = NOW(), deleted_by = ? WHERE asset_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $adminId, $id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode([
        "status" => "success",
        "message" => "Asset moved to Recycle Bin."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Asset was not found or is already in the Recycle Bin."
    ]);
}

$stmt->close();
