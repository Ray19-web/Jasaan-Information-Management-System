<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$allowedStatuses = ['open', 'temporarily_closed', 'permanently_closed', 'abandoned', 'under_renovation'];

$typeIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['type_ids'] ?? [])))));
$legacyTypeId = intval($_POST['type_id'] ?? 0);
$name = trim($_POST['asset_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$phone = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$lat = trim($_POST['latitude'] ?? '');
$lng = trim($_POST['longitude'] ?? '');
$transportation = trim($_POST['transportation'] ?? '');
$nearbyStay = trim($_POST['nearby_stay'] ?? '');
$travelTips = trim($_POST['travel_tips'] ?? '');
$estimatedCost = trim($_POST['estimated_cost'] ?? '');
$travelTime = trim($_POST['travel_time'] ?? '');
$bestTime = trim($_POST['best_time'] ?? '');
$difficulty = trim($_POST['difficulty'] ?? '');
$assetStatus = trim((string) ($_POST['asset_status'] ?? 'open'));
$statusNote = trim((string) ($_POST['status_note'] ?? ''));

if (!in_array($assetStatus, $allowedStatuses, true)) {
    $assetStatus = 'open';
}

if (strlen($statusNote) > 255) {
    $statusNote = substr($statusNote, 0, 255);
}

if (empty($typeIds) && $legacyTypeId > 0) {
    $typeIds = [$legacyTypeId];
}

if (empty($typeIds)) {
    echo json_encode([
        "status" => "error",
        "message" => "Please select at least one classification."
    ]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($typeIds), '?'));
$typeCheck = $conn->prepare("SELECT type_id FROM asset_types WHERE type_id IN ($placeholders) AND deleted_at IS NULL");
$bindTypes = str_repeat('i', count($typeIds));
$typeCheck->bind_param($bindTypes, ...$typeIds);
$typeCheck->execute();
$typeCheck->store_result();

if ($typeCheck->num_rows !== count($typeIds)) {
    echo json_encode([
        "status" => "error",
        "message" => "One or more selected classifications do not exist."
    ]);
    exit;
}

$typeCheck->close();

$uploadDir = __DIR__ . "/../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$thumbnail = '';
if (!empty($_FILES['thumbnail']['name'])) {
    $thumbnail = time() . '_' . $_FILES['thumbnail']['name'];
    move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $thumbnail);
}

try {
    $conn->begin_transaction();

    $statusStmt = $conn->prepare("SELECT status_id FROM asset_statuses WHERE status_code = ? LIMIT 1");
    $statusStmt->bind_param("s", $assetStatus);
    $statusStmt->execute();
    $statusRow = $statusStmt->get_result()->fetch_assoc();
    $statusStmt->close();
    $statusId = (int) ($statusRow['status_id'] ?? 0);

    if ($statusId <= 0) {
        throw new RuntimeException("Selected status does not exist.");
    }

    $stmt = $conn->prepare(
        "INSERT INTO assets (
            asset_name,
            location,
            description,
            thumbnail,
            status_id,
            status_note,
            phone_number,
            email,
            latitude,
            longitude
        ) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, NULLIF(?, ''), NULLIF(?, ''))"
    );
    $stmt->bind_param(
        "ssssisssss",
        $name,
        $location,
        $description,
        $thumbnail,
        $statusId,
        $statusNote,
        $phone,
        $email,
        $lat,
        $lng
    );

    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error ?: $conn->error);
    }

    $asset_id = $conn->insert_id;
    $stmt->close();

    $assignmentStmt = $conn->prepare("INSERT IGNORE INTO asset_type_assignments (asset_id, type_id) VALUES (?, ?)");
    foreach ($typeIds as $nextTypeId) {
        $assignmentStmt->bind_param("ii", $asset_id, $nextTypeId);
        if (!$assignmentStmt->execute()) {
            throw new RuntimeException($assignmentStmt->error ?: $conn->error);
        }
    }
    $assignmentStmt->close();

    $travelStmt = $conn->prepare(
        "INSERT INTO asset_travel_info (
            asset_id,
            transportation,
            nearby_stay,
            travel_tips,
            estimated_cost,
            travel_time,
            best_time,
            difficulty
        ) VALUES (
            ?,
            NULLIF(?, ''),
            NULLIF(?, ''),
            NULLIF(?, ''),
            NULLIF(?, ''),
            NULLIF(?, ''),
            NULLIF(?, ''),
            NULLIF(?, '')
        )"
    );
    $travelStmt->bind_param(
        "isssssss",
        $asset_id,
        $transportation,
        $nearbyStay,
        $travelTips,
        $estimatedCost,
        $travelTime,
        $bestTime,
        $difficulty
    );

    if (!$travelStmt->execute()) {
        throw new RuntimeException($travelStmt->error ?: $conn->error);
    }

    $travelStmt->close();

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
            if (empty($_FILES['images']['name'][$key])) {
                continue;
            }

            $imgName = time() . '_' . $_FILES['images']['name'][$key];
            move_uploaded_file($tmp, $uploadDir . $imgName);

            $imgStmt = $conn->prepare("INSERT INTO asset_images (asset_id, image_path) VALUES (?, ?)");
            $imgStmt->bind_param("is", $asset_id, $imgName);

            if (!$imgStmt->execute()) {
                throw new RuntimeException($imgStmt->error ?: $conn->error);
            }

            $imgStmt->close();
        }
    }

    $socials = [
        "facebook" => $_POST['facebook'] ?? '',
        "instagram" => $_POST['instagram'] ?? '',
        "twitter" => $_POST['twitter'] ?? '',
        "tiktok" => $_POST['tiktok'] ?? ''
    ];

    foreach ($socials as $platform => $url) {
        $url = trim($url);

        if ($url === '') {
            continue;
        }

        $platformStmt = $conn->prepare("SELECT platform_id FROM social_platforms WHERE platform_code = ? LIMIT 1");
        $platformStmt->bind_param("s", $platform);
        $platformStmt->execute();
        $platformRow = $platformStmt->get_result()->fetch_assoc();
        $platformStmt->close();
        $platformId = (int) ($platformRow['platform_id'] ?? 0);

        if ($platformId <= 0) {
            continue;
        }

        $socialStmt = $conn->prepare("INSERT INTO asset_social_links (asset_id, platform_id, url) VALUES (?, ?, ?)");
        $socialStmt->bind_param("iis", $asset_id, $platformId, $url);

        if (!$socialStmt->execute()) {
            throw new RuntimeException($socialStmt->error ?: $conn->error);
        }

        $socialStmt->close();
    }

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}

$typeQuery = $conn->prepare("
    SELECT GROUP_CONCAT(type_name ORDER BY type_name SEPARATOR ', ') AS type_name
    FROM asset_types
    WHERE type_id IN ($placeholders)
      AND deleted_at IS NULL
");
$typeQuery->bind_param($bindTypes, ...$typeIds);
$typeQuery->execute();
$typeResult = $typeQuery->get_result();
$typeRow = $typeResult->fetch_assoc();
$typeQuery->close();

echo json_encode([
    "status" => "success",
    "message" => "Asset added successfully!",
    "data" => [
        "asset_id" => $asset_id,
        "name" => $name,
        "type_name" => $typeRow['type_name'] ?? '',
        "asset_status" => $assetStatus,
        "status_note" => $statusNote,
        "location" => $location,
        "thumbnail" => $thumbnail
    ]
]);
