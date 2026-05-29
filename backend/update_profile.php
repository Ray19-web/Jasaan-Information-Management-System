<?php
require_once "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}


$username = $_POST['username'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';




$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . "/../uploads/profile_pictures/";
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
    $fileName = basename($_FILES['profile_picture']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = "profile_{$user_id}." . $ext;
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $profile_picture_path = "uploads/profile_pictures/" . $newFileName;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit;
    }
}


$updateFields = "username=?, full_name=?, email=?";
$params = [$username, $full_name, $email];
$types = "sss";

if ($password !== '') {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $updateFields .= ", password=?";
    $params[] = $hashed_password;
    $types .= "s";
}

if ($profile_picture_path !== null) {
    $updateFields .= ", profile_picture=?";
    $params[] = $profile_picture_path;
    $types .= "s";
}

$params[] = $user_id;
$types .= "i";

$sql = "UPDATE users SET $updateFields WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Profile updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
}
$stmt->close();
$conn->close();
?>