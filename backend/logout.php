<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');


$_SESSION = [];
session_destroy();

echo json_encode([
    'status' => 'success',
    'message' => 'Logged out'
]);
exit;