<?php
require_once "db.php";

$result = $conn->query("SELECT asset_id, location, latitude, longitude FROM assets WHERE deleted_at IS NULL");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "id" => $row["asset_id"],
        "name" => $row["location"],
        "location" => $row["location"],
        "latitude" => $row["latitude"],
        "longitude" => $row["longitude"]
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
