<?php

function jt_fetch_current_user(mysqli $conn): ?array
{
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    if ($userId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT u.*, r.role_label AS role
         FROM users u
         LEFT JOIN user_roles r ON r.role_id = u.role_id
         WHERE u.user_id = ?
           AND u.deleted_at IS NULL"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $user;
}
