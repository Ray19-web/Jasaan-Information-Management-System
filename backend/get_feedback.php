<?php
header('Content-Type: application/json');
require_once "db.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        f.rating, 
        f.comment, 
        f.created_at,
        u.username,
        u.full_name,
        u.profile_picture
    FROM feedbacks f
    LEFT JOIN users u ON f.user_id = u.user_id
    WHERE f.asset_id = ?
      AND f.is_hidden = 0
      AND f.deleted_at IS NULL
      AND u.deleted_at IS NULL
    ORDER BY f.created_at DESC
");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = [
        'rating' => intval($row['rating']),
        'comment' => $row['comment'],
        'created_at' => $row['created_at'],
        'username' => $row['username'],
        'full_name' => $row['full_name'] ?: $row['username'],
        'profile_picture' => $row['profile_picture']
    ];
}

echo json_encode($feedbacks);
