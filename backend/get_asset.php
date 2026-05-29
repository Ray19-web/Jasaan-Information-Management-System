<?php
header('Content-Type: application/json');
require_once "db.php";

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT a.*,
            s.status_code AS asset_status,
            GROUP_CONCAT(DISTINCT t.type_id ORDER BY t.type_name SEPARATOR ',') AS type_ids,
            GROUP_CONCAT(DISTINCT t.type_name ORDER BY t.type_name SEPARATOR ', ') AS type_name,
            ati.transportation,
            ati.nearby_stay,
            ati.travel_tips,
            ati.estimated_cost,
            ati.travel_time,
            ati.best_time,
            ati.difficulty,
            ROUND(IFNULL((
                SELECT AVG(rating)
                FROM feedbacks
                WHERE asset_id = a.asset_id
                  AND is_hidden = 0
                  AND deleted_at IS NULL
            ), 0), 1) AS avg_rating
     FROM assets a
     LEFT JOIN asset_type_assignments ata ON ata.asset_id = a.asset_id
     LEFT JOIN asset_types t ON t.type_id = ata.type_id AND t.deleted_at IS NULL
     LEFT JOIN asset_statuses s ON s.status_id = a.status_id
     LEFT JOIN asset_travel_info ati ON ati.asset_id = a.asset_id
     WHERE a.asset_id = ?
       AND a.deleted_at IS NULL
     GROUP BY a.asset_id"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo json_encode([]);
    exit;
}

$socialStmt = $conn->prepare(
    "SELECT sp.platform_code AS platform, asl.url
     FROM asset_social_links asl
     JOIN social_platforms sp ON sp.platform_id = asl.platform_id
     WHERE asl.asset_id = ?"
);
$socialStmt->bind_param("i", $id);
$socialStmt->execute();
$socials = $socialStmt->get_result();
while ($row = $socials->fetch_assoc()) {
    $data[strtolower($row['platform'])] = $row['url'];
}
$socialStmt->close();

$images = [];
$imgStmt = $conn->prepare("SELECT image_path FROM asset_images WHERE asset_id = ? ORDER BY image_id ASC");
$imgStmt->bind_param("i", $id);
$imgStmt->execute();
$imgQuery = $imgStmt->get_result();
while ($img = $imgQuery->fetch_assoc()) {
    $images[] = $img['image_path'];
}
$imgStmt->close();

$data['images'] = $images;

echo json_encode($data);
