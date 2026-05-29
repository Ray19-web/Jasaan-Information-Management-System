<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "db.php";

header("Content-Type: application/json");

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';


if (empty($username) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields"
    ]);
    exit;
}


$stmt = $conn->prepare(
    "SELECT u.user_id, u.username, u.email, u.password, r.role_label AS role
     FROM users u
     JOIN user_roles r ON r.role_id = u.role_id
     WHERE (u.username = ? OR u.email = ?)
       AND u.deleted_at IS NULL"
);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();



if (password_verify($password, $user['password'])) {

    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "role" => $user['role'] 
    ]);

} else {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect password"
    ]);
}

$stmt->close();
$conn->close();
?>
