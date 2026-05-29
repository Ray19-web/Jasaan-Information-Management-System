<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "jasaan_tourism";

// Load the rate limiter before database work so repeated requests are stopped early.
require_once __DIR__ . "/rate_limit.php";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

require_once __DIR__ . "/schema.php";
ensureJasaanTourismSchema($conn);
