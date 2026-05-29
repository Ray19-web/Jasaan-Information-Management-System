<?php
header('Content-Type: application/json');
require_once 'check_admin_api.php';
require_once 'db.php';

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit;
}

$hasReadColumn = $conn->query("SHOW COLUMNS FROM feedbacks LIKE 'is_read'");
if ($hasReadColumn && $hasReadColumn->num_rows === 0) {
    $conn->query("ALTER TABLE feedbacks ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

$stmt = $conn->prepare("UPDATE feedbacks SET is_read = 1 WHERE feedback_id = ? AND deleted_at IS NULL");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

if ($conn->affected_rows >= 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update feedback status']);
}
