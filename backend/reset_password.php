<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

header("Content-Type: application/json");

$email = $_POST['email'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$email || !$new_password || !$confirm) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

if ($new_password !== $confirm) {
    echo json_encode(["success" => false, "message" => "Passwords do not match"]);
    exit;
}


$hashed = password_hash($new_password, PASSWORD_DEFAULT);


$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Password updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed"]);
}
