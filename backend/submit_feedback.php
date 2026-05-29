<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit;
}

$userId = (int) $_SESSION['user_id'];
$assetId = (int) ($_POST['asset_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

if ($assetId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    echo "invalid";
    exit;
}

$activeStmt = $conn->prepare("
    SELECT a.asset_id
    FROM assets a
    JOIN users u ON u.user_id = ?
    WHERE a.asset_id = ?
      AND a.deleted_at IS NULL
      AND u.deleted_at IS NULL
    LIMIT 1
");
$activeStmt->bind_param("ii", $userId, $assetId);
$activeStmt->execute();
$activeStmt->store_result();

if ($activeStmt->num_rows === 0) {
    echo "invalid";
    exit;
}

$activeStmt->close();

$stmt = $conn->prepare("
INSERT INTO feedbacks(user_id,asset_id,rating,comment,created_at)
VALUES(?,?,?,?,NOW())
");

$stmt->bind_param(
    "iiis",
    $userId,
    $assetId,
    $rating,
    $comment
);

$stmt->execute();

echo "success";
