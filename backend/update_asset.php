<?php
header('Content-Type: application/json');
require_once "check_admin_api.php";
require_once "db.php";

$allowedStatuses = ['open', 'temporarily_closed', 'permanently_closed', 'abandoned', 'under_renovation'];

$id = intval($_POST['asset_id'] ?? 0);
$typeIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['type_ids'] ?? [])))));
$legacyTypeId = intval($_POST['type_id'] ?? 0);
$name = trim($_POST['asset_name'] ?? '');
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$phone = trim($_POST['phone_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$transportation = trim($_POST['transportation'] ?? '');
$nearbyStay = trim($_POST['nearby_stay'] ?? '');
$travelTips = trim($_POST['travel_tips'] ?? '');
$estimatedCost = trim($_POST['estimated_cost'] ?? '');
$travelTime = trim($_POST['travel_time'] ?? '');
$bestTime = trim($_POST['best_time'] ?? '');
$difficulty = trim($_POST['difficulty'] ?? '');
$assetStatus = trim((string) ($_POST['asset_status'] ?? 'open'));
$statusNote = trim((string) ($_POST['status_note'] ?? ''));
$removedImages = array_values(array_filter(array_map(
    static fn($image) => basename(trim((string) $image)),
    (array) ($_POST['removed_images'] ?? [])
)));

if (!in_array($assetStatus, $allowedStatuses, true)) {
    $assetStatus = 'open';
}

if (strlen($statusNote) > 255) {
    $statusNote = substr($statusNote, 0, 255);
}

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid asset."
    ]);
    exit;
}

$assetCheck = $conn->prepare("SELECT asset_id FROM assets WHERE asset_id = ? AND deleted_at IS NULL LIMIT 1");
$assetCheck->bind_param("i", $id);
$assetCheck->execute();
$assetCheck->store_result();

if ($assetCheck->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Asset was not found or is in the Recycle Bin."
    ]);
    exit;
}

$assetCheck->close();

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
$bindTypes = str_repeat('i', count($typeIds));
$typeCheck = $conn->prepare("SELECT type_id FROM asset_types WHERE type_id IN ($placeholders) AND deleted_at IS NULL");
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

try {
    $conn->begin_transaction();
    $filesToDelete = [];

    $statusStmt = $conn->prepare("SELECT status_id FROM asset_statuses WHERE status_code = ? LIMIT 1");
    $statusStmt->bind_param("s", $assetStatus);
    $statusStmt->execute();
    $statusRow = $statusStmt->get_result()->fetch_assoc();
    $statusStmt->close();
    $statusId = (int) ($statusRow['status_id'] ?? 0);

    if ($statusId <= 0) {
        throw new RuntimeException("Selected status does not exist.");
    }

    if (!empty($removedImages)) {
        $deleteImageStmt = $conn->prepare("DELETE FROM asset_images WHERE asset_id = ? AND image_path = ?");

        foreach (array_unique($removedImages) as $imagePath) {
            $deleteImageStmt->bind_param("is", $id, $imagePath);

            if (!$deleteImageStmt->execute()) {
                throw new RuntimeException($deleteImageStmt->error ?: $conn->error);
            }

            if ($deleteImageStmt->affected_rows > 0) {
                $filesToDelete[] = $uploadDir . $imagePath;
            }
        }

        $deleteImageStmt->close();
    }

    if (!empty($_FILES['thumbnail']['name'])) {
        $thumbName = time() . "_" . $_FILES['thumbnail']['name'];
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $thumbName);

        $thumbStmt = $conn->prepare("UPDATE assets SET thumbnail = ? WHERE asset_id = ?");
        $thumbStmt->bind_param("si", $thumbName, $id);

        if (!$thumbStmt->execute()) {
            throw new RuntimeException($thumbStmt->error ?: $conn->error);
        }

        $thumbStmt->close();
    }

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] !== 0) {
                continue;
            }

            $fileName = uniqid() . "_" . $_FILES['images']['name'][$key];
            $target = $uploadDir . $fileName;

            if (!move_uploaded_file($tmpName, $target)) {
                continue;
            }

            $imgStmt = $conn->prepare("INSERT INTO asset_images (asset_id, image_path) VALUES (?, ?)");
            $imgStmt->bind_param("is", $id, $fileName);

            if (!$imgStmt->execute()) {
                throw new RuntimeException($imgStmt->error ?: $conn->error);
            }

            $imgStmt->close();
        }
    }

    $stmt = $conn->prepare(
        "UPDATE assets
         SET asset_name = ?,
             location = ?,
             description = ?,
             status_id = ?,
             status_note = NULLIF(?, ''),
             phone_number = ?,
             email = ?,
             latitude = NULLIF(?, ''),
             longitude = NULLIF(?, '')
         WHERE asset_id = ?"
    );
    $stmt->bind_param(
        "sssisssssi",
        $name,
        $location,
        $description,
        $statusId,
        $statusNote,
        $phone,
        $email,
        $latitude,
        $longitude,
        $id
    );

    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error ?: $conn->error);
    }

    $stmt->close();

    $deleteAssignments = $conn->prepare("DELETE FROM asset_type_assignments WHERE asset_id = ?");
    $deleteAssignments->bind_param("i", $id);
    if (!$deleteAssignments->execute()) {
        throw new RuntimeException($deleteAssignments->error ?: $conn->error);
    }
    $deleteAssignments->close();

    $assignmentStmt = $conn->prepare("INSERT IGNORE INTO asset_type_assignments (asset_id, type_id) VALUES (?, ?)");
    foreach ($typeIds as $nextTypeId) {
        $assignmentStmt->bind_param("ii", $id, $nextTypeId);
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
        )
        ON DUPLICATE KEY UPDATE
            transportation = VALUES(transportation),
            nearby_stay = VALUES(nearby_stay),
            travel_tips = VALUES(travel_tips),
            estimated_cost = VALUES(estimated_cost),
            travel_time = VALUES(travel_time),
            best_time = VALUES(best_time),
            difficulty = VALUES(difficulty)"
    );
    $travelStmt->bind_param(
        "isssssss",
        $id,
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

    $deleteSocials = $conn->prepare("DELETE FROM asset_social_links WHERE asset_id = ?");
    $deleteSocials->bind_param("i", $id);

    if (!$deleteSocials->execute()) {
        throw new RuntimeException($deleteSocials->error ?: $conn->error);
    }

    $deleteSocials->close();

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
        $socialStmt->bind_param("iis", $id, $platformId, $url);

        if (!$socialStmt->execute()) {
            throw new RuntimeException($socialStmt->error ?: $conn->error);
        }

        $socialStmt->close();
    }

    $conn->commit();

    foreach ($filesToDelete as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Asset updated successfully!"
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
