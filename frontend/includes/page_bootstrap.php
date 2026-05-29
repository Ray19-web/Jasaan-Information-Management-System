<?php
require_once __DIR__ . '/../../backend/optional_session.php';
require_once __DIR__ . '/../../backend/db.php';
require_once __DIR__ . '/../../backend/services/users.php';
require_once __DIR__ . '/../../backend/services/assets.php';

$BASE_URL = '/jasaan-tourism';
$isAdmin = isset($_SESSION['role']) && strtolower((string) $_SESSION['role']) === 'admin';

// Admin users should stay in the admin area instead of opening public visitor pages.
$currentPathForAccess = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
$currentPathForAccess = rtrim($currentPathForAccess, '/') ?: $BASE_URL;
if ($isAdmin && $currentPathForAccess !== $BASE_URL . '/admin') {
    header('Location: ' . $BASE_URL . '/admin?page=overview');
    exit;
}

$navbarInclude = $isAdmin
    ? __DIR__ . '/admin_navbar.php'
    : __DIR__ . '/user_navbar.php';
$user = jt_fetch_current_user($conn);
