<?php
require_once __DIR__ . "/../../../../backend/db.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

$types = $conn->query("
    SELECT
        t.type_id,
        t.type_name,
        COUNT(a.asset_id) AS asset_count
    FROM asset_types t
    LEFT JOIN asset_type_assignments ata ON ata.type_id = t.type_id
    LEFT JOIN assets a ON a.asset_id = ata.asset_id AND a.deleted_at IS NULL
    WHERE t.deleted_at IS NULL
    GROUP BY t.type_id, t.type_name
    ORDER BY t.type_name ASC
");

$typeTone = static function (int $typeId): string {
    $tones = ['cyan', 'green', 'gold', 'rose', 'violet', 'blue'];
    return $tones[$typeId % count($tones)];
};
?>

<div class="admin-content">
    <div class="assets-box classification-page">
        <div class="assets-header">
            <div class="assets-heading-copy">
                <span class="assets-kicker">Taxonomy Control</span>
                <h3><i class="fa-solid fa-tags"></i> Manage Classifications</h3>
                <p>Create and organize the asset types used by attractions, resorts, markets, products, and future tourism categories.</p>
            </div>
        </div>

        <div class="classification-hero">
            <form id="classificationForm" class="classification-create-form">
                <div>
                    <span class="classification-form-kicker">New Type</span>
                    <label for="classificationName">Classification Name</label>
                </div>
                <div class="classification-create-row">
                    <input type="text" id="classificationName" name="type_name" placeholder="e.g. Heritage Sites" maxlength="100" required>
                    <button type="submit" class="add-btn">
                        <i class="fa-solid fa-plus"></i>
                        Add Type
                    </button>
                </div>
            </form>
        </div>

        <div class="assets-toolbar classification-toolbar">
            <div class="filter-box">
                <label>Filter by Usage</label>
                <div class="filter-chip-group" id="classificationUsageFilters" role="group" aria-label="Filter classifications by usage">
                    <button type="button" class="filter-chip classification-filter-btn is-active" data-classification-filter="" aria-pressed="true">All</button>
                    <button type="button" class="filter-chip classification-filter-btn" data-classification-filter="used" aria-pressed="false">Used</button>
                    <button type="button" class="filter-chip classification-filter-btn" data-classification-filter="unused" aria-pressed="false">Unused</button>
                </div>
            </div>

            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input
                    type="text"
                    id="classificationSearch"
                    placeholder="Search classifications..."
                    title="Search classifications by name or ID">
            </div>

            <button type="button" id="classificationFilterReset" class="filter-reset-btn" hidden>
                <i class="fa-solid fa-rotate-left"></i>
                Reset
            </button>
        </div>

        <div class="assets-results-bar" aria-live="polite">
            <span class="result-pill" id="classificationResultCount">
                <?= $types->num_rows ?> classification<?= $types->num_rows === 1 ? '' : 's' ?>
            </span>
            <span class="result-pill result-pill--active" id="classificationActiveFilter">All classifications</span>
        </div>

        <table class="assets-table classification-table">
            <thead>
                <tr>
                    <th>Classification</th>
                    <th>Assets Using It</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="classificationTable">
                <?php while ($type = $types->fetch_assoc()): ?>
                    <tr
                        data-type-id="<?= (int) $type['type_id'] ?>"
                        data-asset-count="<?= (int) $type['asset_count'] ?>"
                        data-search="<?= htmlspecialchars(strtolower($type['type_name'] . ' #' . $type['type_id'])) ?>">
                        <td data-label="Classification" style="text-align: left;">
                            <div class="classification-name-cell">
                                <span class="classification-icon">
                                    <i class="fa-solid fa-tag"></i>
                                </span>
                                <input
                                    type="text"
                                    class="classification-name-input"
                                    value="<?= htmlspecialchars($type['type_name']) ?>"
                                    data-original="<?= htmlspecialchars($type['type_name']) ?>"
                                    maxlength="100"
                                    title="Edit classification name">
                            </div>
                        </td>
                        <td data-label="Assets Using It">
                            <span class="classification-count-pill classification-count-pill--<?= $typeTone((int) $type['type_id']) ?>">
                                <?= (int) $type['asset_count'] ?> asset<?= (int) $type['asset_count'] === 1 ? '' : 's' ?>
                            </span>
                        </td>
                        <td data-label="Action" class="table-actions-cell">
                            <i class="fa-solid fa-check action-icon edit" data-classification-save title="Save classification name"></i>
                            <i class="fa-solid fa-trash action-icon delete" data-classification-delete title="Delete classification"></i>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <tr id="classificationsEmptyState" class="assets-empty-row" <?= $types->num_rows > 0 ? 'hidden' : '' ?>>
                    <td colspan="3">
                        <div class="assets-empty-state">
                            <i class="fa-solid fa-tags"></i>
                            <strong>No classifications yet</strong>
                            <p>Add your first classification so assets can be grouped cleanly.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= $BASE_URL ?>/frontend/assets/js/classifications.js?v=<?= time(); ?>"></script>
