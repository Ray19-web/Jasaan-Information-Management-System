<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "db.php";

header("Content-Type: application/json");


$full_name = $_POST['full_name'];
$email = $_POST['email'];
$username = $_POST['username'];
$password = $_POST['password'];


$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
$stmt->bind_param("ss", $email, $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "User already exists"]);
    exit;
}


$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


$roleStmt = $conn->prepare("SELECT role_id FROM user_roles WHERE role_code = 'tourist' LIMIT 1");
$roleStmt->execute();
$role = $roleStmt->get_result()->fetch_assoc();
$roleStmt->close();
$roleId = (int) ($role['role_id'] ?? 0);

if ($roleId <= 0) {
    echo json_encode(["success" => false, "message" => "Signup failed"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssssi", $username, $full_name, $email, $hashedPassword, $roleId);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Account created successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Signup failed"]);
}

$stmt->close();
$conn->close();
?>
