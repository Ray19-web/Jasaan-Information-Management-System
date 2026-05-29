<?php
require_once "check_admin_api.php";
require_once "db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['role'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);
    exit;
}

$user_id = intval($data['user_id']);
$role = trim($data['role']);
$roleCode = strtolower($role);
$allowed_roles = ["admin", "tourist"];

if (!in_array($roleCode, $allowed_roles, true)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid role"
    ]);
    exit;
}

$roleStmt = $conn->prepare("SELECT role_id FROM user_roles WHERE role_code = ? LIMIT 1");
$roleStmt->bind_param("s", $roleCode);
$roleStmt->execute();
$roleRow = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();
$roleId = (int) ($roleRow['role_id'] ?? 0);

if ($roleId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid role"
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE user_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $roleId, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Role updated successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update role"
    ]);
}

$stmt->close();
$conn->close();
?>
