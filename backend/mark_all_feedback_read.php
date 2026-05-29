<?php
header('Content-Type: application/json');
require_once 'check_admin_api.php';
require_once 'db.php';

$hasReadColumn = $conn->query("SHOW COLUMNS FROM feedbacks LIKE 'is_read'");
if ($hasReadColumn && $hasReadColumn->num_rows === 0) {
    $conn->query("ALTER TABLE feedbacks ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
}

$result = $conn->query("UPDATE feedbacks SET is_read = 1 WHERE is_read = 0 AND deleted_at IS NULL");
if ($result !== false) {
    echo json_encode(['success' => true, 'updated' => $conn->affected_rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to update feedback status']);
}
