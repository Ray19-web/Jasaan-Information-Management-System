<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$typeName = trim((string) ($_POST['type_name'] ?? ''));
$typeName = preg_replace('/\s+/', ' ', $typeName);

if ($typeName === '') {
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

$checkStmt = $conn->prepare("SELECT type_id, type_name, deleted_at FROM asset_types WHERE LOWER(type_name) = LOWER(?) LIMIT 1");
$checkStmt->bind_param("s", $typeName);
$checkStmt->execute();
$existing = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($existing) {
    if (!empty($existing['deleted_at'])) {
        echo json_encode([
            "status" => "error",
            "message" => "This classification is in the Recycle Bin. Restore it or permanently delete it first."
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Classification already exists.",
        "data" => [
            "type_id" => (int) $existing['type_id'],
            "type_name" => $existing['type_name']
        ]
    ]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO asset_types (type_name) VALUES (?)");
$stmt->bind_param("s", $typeName);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save classification."
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Classification added successfully.",
    "data" => [
        "type_id" => $stmt->insert_id,
        "type_name" => $typeName
    ]
]);

$stmt->close();
$conn->close();
?>
