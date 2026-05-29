<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$itemType = strtolower(trim((string) ($_POST['item_type'] ?? '')));
$itemId = (int) ($_POST['item_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$items = [
    'asset' => [
        'table' => 'assets',
        'id' => 'asset_id',
        'label' => 'Asset',
    ],
    'classification' => [
        'table' => 'asset_types',
        'id' => 'type_id',
        'label' => 'Classification',
    ],
    'user' => [
        'table' => 'users',
        'id' => 'user_id',
        'label' => 'User',
    ],
    'feedback' => [
        'table' => 'feedbacks',
        'id' => 'feedback_id',
        'label' => 'Feedback',
    ],
];

if (!isset($items[$itemType]) || $itemId <= 0 || !in_array($action, ['restore', 'delete'], true)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Recycle Bin request."
    ]);
    exit;
}

if ($itemType === 'user' && $itemId === $adminId) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot change your own account from the Recycle Bin."
    ]);
    exit;
}

$item = $items[$itemType];
$table = $item['table'];
$idColumn = $item['id'];
$label = $item['label'];

try {
    if ($action === 'restore') {
        $stmt = $conn->prepare("UPDATE `{$table}` SET deleted_at = NULL, deleted_by = NULL WHERE `{$idColumn}` = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();

        echo json_encode([
            "status" => $stmt->affected_rows > 0 ? "success" : "error",
            "message" => $stmt->affected_rows > 0
                ? "{$label} restored successfully."
                : "{$label} was not found in the Recycle Bin."
        ]);
        $stmt->close();
        exit;
    }

    $conn->begin_transaction();

    if ($itemType === 'asset') {
        $feedbackStmt = $conn->prepare("DELETE FROM feedbacks WHERE asset_id = ?");
        $feedbackStmt->bind_param("i", $itemId);
        $feedbackStmt->execute();
        $feedbackStmt->close();

        foreach (['asset_travel_info', 'asset_images', 'asset_social_links', 'asset_type_assignments'] as $relatedTable) {
            $relatedStmt = $conn->prepare("DELETE FROM `{$relatedTable}` WHERE asset_id = ?");
            $relatedStmt->bind_param("i", $itemId);
            $relatedStmt->execute();
            $relatedStmt->close();
        }
    } elseif ($itemType === 'user') {
        $feedbackStmt = $conn->prepare("DELETE FROM feedbacks WHERE user_id = ?");
        $feedbackStmt->bind_param("i", $itemId);
        $feedbackStmt->execute();
        $feedbackStmt->close();
    } elseif ($itemType === 'classification') {
        $assignmentStmt = $conn->prepare("DELETE FROM asset_type_assignments WHERE type_id = ?");
        $assignmentStmt->bind_param("i", $itemId);
        $assignmentStmt->execute();
        $assignmentStmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM `{$table}` WHERE `{$idColumn}` = ? AND deleted_at IS NOT NULL");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected <= 0) {
        throw new RuntimeException("{$label} was not found in the Recycle Bin.");
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "{$label} permanently deleted."
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage() ?: "Recycle Bin action failed."
    ]);
}
