<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$typeId = (int) ($_POST['type_id'] ?? 0);
$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($typeId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid classification."
    ]);
    exit;
}

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS asset_count
    FROM asset_type_assignments ata
    JOIN assets a ON a.asset_id = ata.asset_id
    WHERE ata.type_id = ?
      AND a.deleted_at IS NULL
");
$countStmt->bind_param("i", $typeId);
$countStmt->execute();
$assetCount = (int) ($countStmt->get_result()->fetch_assoc()['asset_count'] ?? 0);
$countStmt->close();

if ($assetCount > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "This classification is used by {$assetCount} asset" . ($assetCount === 1 ? "" : "s") . ". Reassign those assets before deleting it."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE asset_types SET deleted_at = NOW(), deleted_by = ? WHERE type_id = ? AND deleted_at IS NULL");
$stmt->bind_param("ii", $adminId, $typeId);

if (!$stmt->execute() || $stmt->affected_rows <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Classification was not found or is already in the Recycle Bin."
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Classification moved to Recycle Bin."
]);

$stmt->close();
$conn->close();
?>
