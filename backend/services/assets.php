<?php

function jt_bind_dynamic_params(mysqli_stmt $stmt, string $types, array $values): void
{
    if ($types === '' || $values === []) {
        return;
    }

    $refs = [];

    foreach ($values as $index => $value) {
        $refs[$index] = &$values[$index];
    }

    $stmt->bind_param($types, ...$refs);
}

function jt_fetch_assets(mysqli $conn, string $search = '', ?string $typeFilter = null, string $typeMode = 'exact'): array
{
    $sql = "
        SELECT
            a.asset_id,
            a.asset_name,
            a.location,
            s.status_code AS asset_status,
            a.status_note,
            GROUP_CONCAT(DISTINCT t.type_name ORDER BY t.type_name SEPARATOR ', ') AS type_name,
            a.thumbnail AS image_path,
            (
                SELECT IFNULL(AVG(rating), 0)
                FROM feedbacks
                WHERE asset_id = a.asset_id
                  AND is_hidden = 0
                  AND deleted_at IS NULL
            ) AS avg_rating
        FROM assets a
        LEFT JOIN asset_type_assignments ata ON ata.asset_id = a.asset_id
        LEFT JOIN asset_types t ON ata.type_id = t.type_id AND t.deleted_at IS NULL
        LEFT JOIN asset_statuses s ON s.status_id = a.status_id
    ";

    $conditions = ["a.deleted_at IS NULL"];
    $types = '';
    $values = [];

    if ($typeFilter !== null && $typeFilter !== '') {
        if ($typeMode === 'like') {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM asset_type_assignments ata_filter
                JOIN asset_types t_filter ON t_filter.type_id = ata_filter.type_id AND t_filter.deleted_at IS NULL
                WHERE ata_filter.asset_id = a.asset_id
                  AND LOWER(t_filter.type_name) LIKE ?
            )";
            $values[] = strtolower($typeFilter);
        } else {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM asset_type_assignments ata_filter
                JOIN asset_types t_filter ON t_filter.type_id = ata_filter.type_id AND t_filter.deleted_at IS NULL
                WHERE ata_filter.asset_id = a.asset_id
                  AND LOWER(t_filter.type_name) = ?
            )";
            $values[] = strtolower($typeFilter);
        }

        $types .= 's';
    }

    if ($search !== '') {
        $conditions[] = "a.asset_name LIKE ?";
        $values[] = '%' . $search . '%';
        $types .= 's';
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' GROUP BY a.asset_id ORDER BY a.asset_id DESC';

    $stmt = $conn->prepare($sql);
    jt_bind_dynamic_params($stmt, $types, $values);
    $stmt->execute();

    $assets = [];
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $assets[] = $row;
    }

    $stmt->close();

    return $assets;
}

function jt_fetch_hero_images(mysqli $conn, int $assetId, int $limit = 5): array
{
    $stmt = $conn->prepare("
        SELECT image_path
        FROM asset_images
        WHERE asset_id = ?
        ORDER BY image_id DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $assetId, $limit);
    $stmt->execute();

    $images = [];
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $images[] = $row['image_path'];
    }

    $stmt->close();

    return $images;
}
