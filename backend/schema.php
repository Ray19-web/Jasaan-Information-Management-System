<?php

function jasaanColumnExists(mysqli $conn, string $table, string $column): bool
{
    $tableName = $conn->real_escape_string($table);
    $columnName = $conn->real_escape_string($column);

    try {
        $result = $conn->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
    } catch (mysqli_sql_exception) {
        return false;
    }

    if (!$result) {
        return false;
    }

    $exists = $result->num_rows > 0;
    $result->free();

    return $exists;
}

function jasaanEnsureColumn(mysqli $conn, string $table, string $column, string $definition): void
{
    if (jasaanColumnExists($conn, $table, $column)) {
        return;
    }

    $tableName = $conn->real_escape_string($table);
    $columnName = $conn->real_escape_string($column);
    try {
        $conn->query("ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}");
    } catch (mysqli_sql_exception) {
        return;
    }
}

function jasaanIndexExists(mysqli $conn, string $table, string $index): bool
{
    $tableName = $conn->real_escape_string($table);
    $indexName = $conn->real_escape_string($index);
    try {
        $result = $conn->query("SHOW INDEX FROM `{$tableName}` WHERE Key_name = '{$indexName}'");
    } catch (mysqli_sql_exception) {
        return false;
    }

    if (!$result) {
        return false;
    }

    $exists = $result->num_rows > 0;
    $result->free();

    return $exists;
}

function jasaanTableExists(mysqli $conn, string $table): bool
{
    $tableName = $conn->real_escape_string($table);
    try {
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    } catch (mysqli_sql_exception) {
        return false;
    }

    if (!$result) {
        return false;
    }

    $exists = $result->num_rows > 0;
    $result->free();

    return $exists;
}

function jasaanForeignKeyExists(mysqli $conn, string $table, string $constraint): bool
{
    $tableName = $conn->real_escape_string($table);
    $constraintName = $conn->real_escape_string($constraint);
    $schema = $conn->real_escape_string((string) $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);

    $result = $conn->query(
        "SELECT CONSTRAINT_NAME
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = '{$schema}'
           AND TABLE_NAME = '{$tableName}'
           AND CONSTRAINT_NAME = '{$constraintName}'
           AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
    );

    if (!$result) {
        return false;
    }

    $exists = $result->num_rows > 0;
    $result->free();

    return $exists;
}

function jasaanDropForeignKeyIfExists(mysqli $conn, string $table, string $constraint): void
{
    if (!jasaanForeignKeyExists($conn, $table, $constraint)) {
        return;
    }

    $tableName = $conn->real_escape_string($table);
    $constraintName = $conn->real_escape_string($constraint);
    $conn->query("ALTER TABLE `{$tableName}` DROP FOREIGN KEY `{$constraintName}`");
}

function jasaanDropIndexIfExists(mysqli $conn, string $table, string $index): void
{
    if (!jasaanIndexExists($conn, $table, $index)) {
        return;
    }

    $tableName = $conn->real_escape_string($table);
    $indexName = $conn->real_escape_string($index);
    $conn->query("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
}

function jasaanDropColumnIfExists(mysqli $conn, string $table, string $column): void
{
    if (!jasaanColumnExists($conn, $table, $column)) {
        return;
    }

    $tableName = $conn->real_escape_string($table);
    $columnName = $conn->real_escape_string($column);
    $conn->query("ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`");
}

function jasaanEnsureTable(mysqli $conn, string $table, string $createSql): void
{
    try {
        $conn->query($createSql);
        $tableName = $conn->real_escape_string($table);
        $conn->query("SHOW COLUMNS FROM `{$tableName}`");
        return;
    } catch (mysqli_sql_exception $e) {
        if (stripos($e->getMessage(), "doesn't exist in engine") === false) {
            throw $e;
        }
    }

    $tableName = $conn->real_escape_string($table);
    $conn->query("DROP TABLE IF EXISTS `{$tableName}`");
    $conn->query($createSql);
}

function ensureJasaanTourismSchema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS `asset_statuses` (
            `status_id` int(11) NOT NULL AUTO_INCREMENT,
            `status_code` varchar(40) NOT NULL,
            `status_label` varchar(80) NOT NULL,
            PRIMARY KEY (`status_id`),
            UNIQUE KEY `unique_asset_status_code` (`status_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "INSERT INTO asset_statuses (status_code, status_label) VALUES
            ('open', 'Open'),
            ('temporarily_closed', 'Temporarily Closed'),
            ('permanently_closed', 'Permanently Closed'),
            ('abandoned', 'Abandoned'),
            ('under_renovation', 'Under Renovation')
         ON DUPLICATE KEY UPDATE status_label = VALUES(status_label)"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `social_platforms` (
            `platform_id` int(11) NOT NULL AUTO_INCREMENT,
            `platform_code` varchar(50) NOT NULL,
            `platform_label` varchar(80) NOT NULL,
            PRIMARY KEY (`platform_id`),
            UNIQUE KEY `unique_social_platform_code` (`platform_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "INSERT INTO social_platforms (platform_code, platform_label) VALUES
            ('facebook', 'Facebook'),
            ('instagram', 'Instagram'),
            ('twitter', 'Twitter'),
            ('tiktok', 'TikTok'),
            ('youtube', 'YouTube'),
            ('linkedin', 'LinkedIn'),
            ('x', 'X')
         ON DUPLICATE KEY UPDATE platform_label = VALUES(platform_label)"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `user_roles` (
            `role_id` int(11) NOT NULL AUTO_INCREMENT,
            `role_code` varchar(50) NOT NULL,
            `role_label` varchar(80) NOT NULL,
            PRIMARY KEY (`role_id`),
            UNIQUE KEY `unique_user_role_code` (`role_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conn->query(
        "INSERT INTO user_roles (role_code, role_label) VALUES
            ('admin', 'Admin'),
            ('tourist', 'Tourist')
         ON DUPLICATE KEY UPDATE role_label = VALUES(role_label)"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `asset_types` (
            `type_id` int(11) NOT NULL AUTO_INCREMENT,
            `type_name` varchar(100) NOT NULL,
            PRIMARY KEY (`type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    if (!jasaanIndexExists($conn, "asset_types", "unique_asset_type_name")) {
        $conn->query("ALTER TABLE `asset_types` ADD UNIQUE KEY `unique_asset_type_name` (`type_name`)");
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `asset_type_assignments` (
            `asset_id` int(11) NOT NULL,
            `type_id` int(11) NOT NULL,
            PRIMARY KEY (`asset_id`, `type_id`),
            CONSTRAINT `asset_type_assignments_asset_fk`
                FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE,
            CONSTRAINT `asset_type_assignments_type_fk`
                FOREIGN KEY (`type_id`) REFERENCES `asset_types` (`type_id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    if (jasaanTableExists($conn, "assets") && jasaanColumnExists($conn, "assets", "type_id")) {
        $conn->query(
            "INSERT IGNORE INTO asset_type_assignments (asset_id, type_id)
             SELECT asset_id, type_id
             FROM assets
             WHERE type_id IS NOT NULL AND type_id > 0"
        );
    }

    if (jasaanTableExists($conn, "assets")) {
        jasaanEnsureColumn($conn, "assets", "status_id", "int(11) DEFAULT NULL");
        jasaanEnsureColumn($conn, "assets", "status_note", "varchar(255) DEFAULT NULL");
        jasaanEnsureColumn($conn, "assets", "deleted_at", "datetime DEFAULT NULL");
        jasaanEnsureColumn($conn, "assets", "deleted_by", "int(11) DEFAULT NULL");

        if (jasaanColumnExists($conn, "assets", "asset_status")) {
            $conn->query(
                "UPDATE assets a
                 JOIN asset_statuses s ON s.status_code = a.asset_status
                 SET a.status_id = s.status_id
                 WHERE a.status_id IS NULL"
            );
        }

        $conn->query(
            "UPDATE assets a
             JOIN asset_statuses s ON s.status_code = 'open'
             SET a.status_id = s.status_id
             WHERE a.status_id IS NULL"
        );

        jasaanDropForeignKeyIfExists($conn, "assets", "assets_status_fk");

        if (!jasaanForeignKeyExists($conn, "assets", "assets_status_fk")) {
            $conn->query(
                "ALTER TABLE `assets`
                 ADD CONSTRAINT `assets_status_fk`
                 FOREIGN KEY (`status_id`) REFERENCES `asset_statuses` (`status_id`) ON DELETE RESTRICT"
            );
        }

        jasaanDropForeignKeyIfExists($conn, "assets", "assets_ibfk_1");
        jasaanDropIndexIfExists($conn, "assets", "type_id");
        jasaanDropColumnIfExists($conn, "assets", "type_id");
        jasaanDropColumnIfExists($conn, "assets", "asset_status");
    }

    $conn->query(
        "CREATE TABLE IF NOT EXISTS `asset_travel_info` (
            `asset_id` int(11) NOT NULL,
            `transportation` text DEFAULT NULL,
            `nearby_stay` text DEFAULT NULL,
            `travel_tips` text DEFAULT NULL,
            `estimated_cost` varchar(120) DEFAULT NULL,
            `travel_time` varchar(120) DEFAULT NULL,
            `best_time` varchar(120) DEFAULT NULL,
            `difficulty` varchar(80) DEFAULT NULL,
            PRIMARY KEY (`asset_id`),
            CONSTRAINT `asset_travel_info_ibfk_1`
                FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    jasaanEnsureColumn($conn, "asset_travel_info", "transportation", "text DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "nearby_stay", "text DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "travel_tips", "text DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "estimated_cost", "varchar(120) DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "travel_time", "varchar(120) DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "best_time", "varchar(120) DEFAULT NULL");
    jasaanEnsureColumn($conn, "asset_travel_info", "difficulty", "varchar(80) DEFAULT NULL");

    jasaanEnsureTable(
        $conn,
        "asset_social_links",
        "CREATE TABLE IF NOT EXISTS `asset_social_links` (
            `social_id` int(11) NOT NULL AUTO_INCREMENT,
            `asset_id` int(11) DEFAULT NULL,
            `url` varchar(255) DEFAULT NULL,
            `platform_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`social_id`),
            KEY `asset_id` (`asset_id`),
            KEY `asset_social_links_platform_fk` (`platform_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    if (jasaanTableExists($conn, "asset_social_links")) {
        jasaanEnsureColumn($conn, "asset_social_links", "platform_id", "int(11) DEFAULT NULL");

        if (jasaanColumnExists($conn, "asset_social_links", "platform")) {
            $conn->query(
                "INSERT IGNORE INTO social_platforms (platform_code, platform_label)
                 SELECT DISTINCT LOWER(TRIM(platform)), CONCAT(UCASE(LEFT(TRIM(platform), 1)), SUBSTRING(TRIM(platform), 2))
                 FROM asset_social_links
                 WHERE platform IS NOT NULL AND TRIM(platform) <> ''"
            );

            $conn->query(
                "UPDATE asset_social_links asl
                 JOIN social_platforms sp ON sp.platform_code = LOWER(TRIM(asl.platform))
                 SET asl.platform_id = sp.platform_id
                 WHERE asl.platform_id IS NULL"
            );
        }

        jasaanDropForeignKeyIfExists($conn, "asset_social_links", "asset_social_links_platform_fk");

        if (
            !jasaanForeignKeyExists($conn, "asset_social_links", "asset_social_links_ibfk_1")
            && !jasaanForeignKeyExists($conn, "asset_social_links", "asset_social_links_asset_fk_v2")
        ) {
            $conn->query(
                "ALTER TABLE `asset_social_links`
                 ADD CONSTRAINT `asset_social_links_asset_fk_v2`
                 FOREIGN KEY (`asset_id`) REFERENCES `assets` (`asset_id`) ON DELETE CASCADE"
            );
        }

        if (
            !jasaanForeignKeyExists($conn, "asset_social_links", "asset_social_links_platform_fk")
            && !jasaanForeignKeyExists($conn, "asset_social_links", "asset_social_links_platform_fk_v2")
        ) {
            $conn->query(
                "ALTER TABLE `asset_social_links`
                 ADD CONSTRAINT `asset_social_links_platform_fk_v2`
                 FOREIGN KEY (`platform_id`) REFERENCES `social_platforms` (`platform_id`) ON DELETE RESTRICT"
            );
        }

        jasaanDropColumnIfExists($conn, "asset_social_links", "platform");

        if (!jasaanIndexExists($conn, "asset_social_links", "unique_asset_social_platform")) {
            $conn->query("ALTER TABLE `asset_social_links` ADD UNIQUE KEY `unique_asset_social_platform` (`asset_id`, `platform_id`)");
        }
    }

    if (jasaanTableExists($conn, "users")) {
        jasaanEnsureColumn($conn, "users", "role_id", "int(11) DEFAULT NULL");
        jasaanEnsureColumn($conn, "users", "deleted_at", "datetime DEFAULT NULL");
        jasaanEnsureColumn($conn, "users", "deleted_by", "int(11) DEFAULT NULL");

        if (jasaanColumnExists($conn, "users", "role")) {
            $conn->query(
                "UPDATE users u
                 JOIN user_roles r ON r.role_code = LOWER(
                     CASE
                         WHEN TRIM(u.role) = 'user' THEN 'tourist'
                         ELSE TRIM(u.role)
                     END
                 )
                 SET u.role_id = r.role_id
                 WHERE u.role_id IS NULL"
            );
        }

        $conn->query(
            "UPDATE users u
             JOIN user_roles r ON r.role_code = 'tourist'
             SET u.role_id = r.role_id
             WHERE u.role_id IS NULL"
        );

        jasaanDropForeignKeyIfExists($conn, "users", "users_role_fk");

        if (!jasaanForeignKeyExists($conn, "users", "users_role_fk")) {
            $conn->query(
                "ALTER TABLE `users`
                 ADD CONSTRAINT `users_role_fk`
                 FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE RESTRICT"
            );
        }

        jasaanDropColumnIfExists($conn, "users", "role");
    }

    if (jasaanTableExists($conn, "asset_types")) {
        jasaanEnsureColumn($conn, "asset_types", "deleted_at", "datetime DEFAULT NULL");
        jasaanEnsureColumn($conn, "asset_types", "deleted_by", "int(11) DEFAULT NULL");
    }

    if (jasaanTableExists($conn, "feedbacks")) {
        jasaanEnsureColumn($conn, "feedbacks", "is_read", "TINYINT(1) NOT NULL DEFAULT 0");
        jasaanEnsureColumn($conn, "feedbacks", "is_hidden", "TINYINT(1) NOT NULL DEFAULT 0");
        jasaanEnsureColumn($conn, "feedbacks", "deleted_at", "datetime DEFAULT NULL");
        jasaanEnsureColumn($conn, "feedbacks", "deleted_by", "int(11) DEFAULT NULL");
    }
}
