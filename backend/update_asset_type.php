<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$typeId = (int) ($_POST['type_id'] ?? 0);
$typeName = trim((string) ($_POST['type_name'] ?? ''));
$typeName = preg_replace('/\s+/', ' ', $typeName);

if ($typeId <= 0 || $typeName === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Classification name is required."
    ]);
    exit;
}

if (strlen($typeName) > 100) {
    echo json_encode([
        "status" => "error",
        "message" => "Classification name must be 100 characters or fewer."
    ]);
    exit;
}

$duplicateStmt = $conn->prepare("SELECT type_id FROM asset_types WHERE LOWER(type_name) = LOWER(?) AND type_id <> ? AND deleted_at IS NULL LIMIT 1");
$duplicateStmt->bind_param("si", $typeName, $typeId);
$duplicateStmt->execute();
$duplicateStmt->store_result();

if ($duplicateStmt->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Another classification already uses that name."
    ]);
    exit;
}

$duplicateStmt->close();

$stmt = $conn->prepare("UPDATE asset_types SET type_name = ? WHERE type_id = ? AND deleted_at IS NULL");
$stmt->bind_param("si", $typeName, $typeId);

if (!$stmt->execute() || $stmt->affected_rows < 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update classification."
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Classification updated successfully.",
    "data" => [
        "type_id" => $typeId,
        "type_name" => $typeName
    ]
]);

$stmt->close();
$conn->close();
?>
