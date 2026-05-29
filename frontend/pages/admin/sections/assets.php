<?php
require_once __DIR__ . "/../../../../backend/db.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

$assets = $conn->query("
    SELECT 
        a.asset_id,
        a.asset_name AS name,
        a.location,
        a.thumbnail,
        s.status_code AS asset_status,
        a.status_note,
        GROUP_CONCAT(DISTINCT t.type_id ORDER BY t.type_name SEPARATOR ',') AS type_ids,
        GROUP_CONCAT(DISTINCT t.type_name ORDER BY t.type_name SEPARATOR ', ') AS type_name
    FROM assets a
    LEFT JOIN asset_type_assignments ata ON ata.asset_id = a.asset_id
    LEFT JOIN asset_types t ON ata.type_id = t.type_id AND t.deleted_at IS NULL
    LEFT JOIN asset_statuses s ON s.status_id = a.status_id
    WHERE a.deleted_at IS NULL
    GROUP BY a.asset_id
    ORDER BY a.asset_id DESC
");
$types = $conn->query("SELECT * FROM asset_types WHERE deleted_at IS NULL ORDER BY type_name");
$assetTypes = [];
while ($t = $types->fetch_assoc()) {
    $assetTypes[] = $t;
}

$assetTypeSlug = static function (?string $value): string {
    $normalized = preg_replace('/\s+/', '-', trim((string) $value));
    return strtolower((string) $normalized);
};

$assetStatuses = [
    'open' => ['label' => 'Open', 'icon' => 'fa-circle-check'],
    'temporarily_closed' => ['label' => 'Temporarily Closed', 'icon' => 'fa-clock'],
    'permanently_closed' => ['label' => 'Permanently Closed', 'icon' => 'fa-circle-xmark'],
    'abandoned' => ['label' => 'Abandoned', 'icon' => 'fa-triangle-exclamation'],
    'under_renovation' => ['label' => 'Under Renovation', 'icon' => 'fa-screwdriver-wrench'],
];

$renderStatusOptions = static function (array $assetStatuses): void {
    foreach ($assetStatuses as $value => $status) {
        ?>
        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($status['label']) ?></option>
        <?php
    }
};

$statusMeta = static function (?string $status) use ($assetStatuses): array {
    return $assetStatuses[$status ?: 'open'] ?? $assetStatuses['open'];
};

$renderTypeCheckboxes = static function (array $assetTypes, string $prefix): void {
    foreach ($assetTypes as $type) {
        $inputId = $prefix . '_type_' . (int) $type['type_id'];
        ?>
        <label class="type-choice" for="<?= htmlspecialchars($inputId) ?>">
            <input type="checkbox" id="<?= htmlspecialchars($inputId) ?>" name="type_ids[]"
                value="<?= (int) $type['type_id'] ?>">
            <span class="type-choice__check"><i class="fa-solid fa-check"></i></span>
            <span class="type-choice__text"><?= htmlspecialchars($type['type_name']) ?></span>
        </label>
        <?php
    }
};
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/map.js?v=<?php echo time(); ?>"></script>

<div class="admin-content">

    <div class="assets-box">

        <div class="assets-header">
            <div class="assets-heading-copy">
                <span class="assets-kicker">Content Control Panel</span>
                <h3><i class="fa-solid fa-layer-group"></i> Manage Assets</h3>
                <p>Search, filter, and update every tourism listing from one polished workspace.</p>
            </div>

            <div class="assets-toolbar">
                <div class="filter-box">
                    <label>Filter by Classification</label>
                    <div class="filter-chip-group" id="assetTypeFilters" role="group"
                        aria-label="Filter assets by classification">
                        <button type="button" class="filter-chip asset-filter-btn is-active" data-asset-filter=""
                            aria-pressed="true" title="Show all asset classifications">
                            All
                        </button>
                        <?php foreach ($assetTypes as $type): ?>
                            <button type="button" class="filter-chip asset-filter-btn"
                                data-asset-filter="<?= htmlspecialchars($assetTypeSlug($type['type_name'])) ?>"
                                aria-pressed="false" title="Show only <?= htmlspecialchars($type['type_name']) ?>">
                                <?= htmlspecialchars($type['type_name']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search by name, place, or type..."
                        title="Search assets by name, location, or classification" />
                </div>

                <button type="button" id="assetFilterReset" class="filter-reset-btn" title="Clear search and filter"
                    hidden>
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </button>

                <button type="button" onclick="openModal()" class="add-btn" title="Add a new tourism asset">
                    <i class="fa-solid fa-plus"></i> Add Assets
                </button>
            </div>
        </div>

        <div class="assets-results-bar" aria-live="polite">
            <span class="result-pill" id="assetResultCount">Showing all assets</span>
            <span class="result-pill result-pill--active" id="assetActiveFilter">All classifications</span>
        </div>

        <table class="assets-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Classification</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="assetsTable">
                <?php while ($row = $assets->fetch_assoc()): ?>
                    <?php
                    $typeNames = array_values(array_filter(array_map('trim', explode(',', (string) $row['type_name']))));
                    if ($typeNames === []) {
                        $typeNames = ['Unclassified'];
                    }
                    $typeSlugs = array_map($assetTypeSlug, $typeNames);
                    $typeClass = $typeSlugs[0] ?? 'default';
                    $searchIndex = strtolower(trim(implode(' ', [
                        (string) $row['name'],
                        (string) $row['location'],
                        (string) $row['type_name'],
                        (string) ($statusMeta($row['asset_status'])['label'] ?? ''),
                        (string) ($row['status_note'] ?? '')
                    ])));
                    $currentStatus = $statusMeta($row['asset_status'] ?? 'open');
                    ?>
                    <tr data-type="<?= htmlspecialchars(implode(' ', $typeSlugs)) ?>"
                        data-search="<?= htmlspecialchars($searchIndex) ?>">

                        <td data-label="Name" style="text-align: left;">
                            <div class="asset-info">
                                <img src="<?= !empty($row['thumbnail'])
                                    ? $BASE_URL . '/uploads/' . $row['thumbnail']
                                    : $BASE_URL . '/frontend/assets/images/default.png' ?>" class="asset-img">

                                <div>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                                    <small>ID: <?= $row['asset_id'] ?></small>
                                </div>
                            </div>
                        </td>

                        <td data-label="Classification">
                            <div class="asset-type-badges">
                                <?php foreach ($typeNames as $typeName): ?>
                                    <span class="badge asset-badge <?= htmlspecialchars($assetTypeSlug($typeName)) ?>">
                                        <?= htmlspecialchars($typeName) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <td data-label="Status">
                            <span
                                class="asset-status-pill asset-status-pill--<?= htmlspecialchars($row['asset_status'] ?: 'open') ?>">
                                <i class="fa-solid <?= htmlspecialchars($currentStatus['icon']) ?>"></i>
                                <?= htmlspecialchars($currentStatus['label']) ?>
                            </span>
                            <?php if (!empty($row['status_note'])): ?>
                                <small class="asset-status-note"><?= htmlspecialchars($row['status_note']) ?></small>
                            <?php endif; ?>
                        </td>

                        <td data-label="Location" style="text-align: left;"><?= htmlspecialchars($row['location']) ?></td>

                        <td data-label="Action" class="table-actions-cell">
                            <i class="fa-solid fa-pen action-icon edit" onclick="editAsset(<?= $row['asset_id'] ?>)"
                                title="Edit this asset"></i>

                            <i class="fa-solid fa-trash action-icon delete" onclick="confirmDelete(<?= $row['asset_id'] ?>)"
                                title="Delete this asset"></i>
                        </td>

                    </tr>
                <?php endwhile; ?>

                <tr id="assetsEmptyState" class="assets-empty-row" hidden>
                    <td colspan="5">
                        <div class="assets-empty-state">
                            <i class="fa-solid fa-water"></i>
                            <strong>No assets match the current filters</strong>
                            <p>Try a different keyword or switch back to all classifications.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<div class="assets-modal" id="addAssetModal">
    <div class="assets-modal-content modern-modal">

        <div class="modal-header">
            <h2><i class="fa-regular fa-file-lines"></i> ADD ASSETS</h2>
            <span class="assets-close-btn" onclick="closeModal()" title="Close add asset form">
                <i class="fa-solid fa-xmark"></i>
            </span>
        </div>

        <form id="assetForm" class="assets-form" method="POST" enctype="multipart/form-data">

            <div class="modal-grid">

                <div class="modal-left">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Asset Name</label>
                            <input type="text" name="asset_name" placeholder="e.g Sagpulong Falls" required>
                        </div>



                        <div class="form-group">
                            <label>Asset Location</label>
                            <div class="location-search-shell" data-mode="add">
                                <span class="location-search-icon" aria-hidden="true">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <input type="text" id="locationInput" name="location" class="location-search-input"
                                    placeholder="Search a place or paste a Google Maps link / iframe..."
                                    autocomplete="off" required>
                                <button type="button" class="location-clear-btn" onclick="clearLocationSelection('add')"
                                    aria-label="Clear selected location" hidden>
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <div class="location-search-results" id="locationResults"></div>
                            </div>
                        </div>


                    </div>

                    <div class="form-group">
                        <label>Classifications</label>
                        <div class="type-picker" data-type-picker style="margin-top: 10px;">
                            <?php $renderTypeCheckboxes($assetTypes, 'add'); ?>
                        </div>
                    </div>

                    <section class="asset-status-panel">
                        <div class="asset-status-panel__head">
                            <span class="detail-panel-kicker">Availability</span>
                            <h4>Place Status</h4>
                        </div>
                        <div class="asset-status-fields">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="asset_status" required>
                                    <?php $renderStatusOptions($assetStatuses); ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status Note</label>
                                <input type="text" name="status_note" maxlength="255"
                                    placeholder="e.g. Closed after storm damage, reopening soon">
                            </div>
                        </div>
                    </section>

                    <section class="location-stage location-stage--add">
                        <div class="location-stage-head">
                            <div>
                                <span class="location-kicker">Search And Pin</span>
                                <h4>Location Details</h4>
                            </div>
                            <span class="location-badge">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                Search or click on the map
                            </span>
                        </div>

                        <p class="location-helper-copy">
                            Search by place name, or paste a Google Maps link or embed iframe, then refine the pin on
                            the map.
                        </p>

                        <div class="location-meta">
                            <div class="location-pill">
                                <span>Latitude</span>
                                <strong id="latDisplay">Not set</strong>
                            </div>
                            <div class="location-pill">
                                <span>Longitude</span>
                                <strong id="lngDisplay">Not set</strong>
                            </div>
                        </div>

                        <div class="map-card">
                            <div class="map-card-head">
                                <div>
                                    <span class="location-kicker">Pinpoint Spot</span>
                                    <h4>Interactive Map</h4>
                                </div>
                                <p>Tap on the map to place the marker exactly where visitors should go.</p>
                            </div>
                            <div id="map"></div>
                        </div>
                    </section>

                    <input type="hidden" name="latitude" id="lat">
                    <input type="hidden" name="longitude" id="lng">


                    <div class="form-group full">
                        <label>Place Overview</label>
                        <textarea name="description"
                            placeholder="Write a short but vivid overview of the place, what makes it special, and what visitors can expect."></textarea>
                    </div>

                    <div class="detail-section-grid">
                        <section class="detail-panel detail-panel--travel">
                            <div class="detail-panel-header">
                                <span class="detail-panel-kicker">Plan The Route</span>
                                <h4>Travel Info</h4>
                                <p>Give visitors the practical route details before they leave.</p>
                            </div>

                            <div class="detail-fields">
                                <div class="form-group full">
                                    <label>Transportation</label>
                                    <textarea name="transportation"
                                        placeholder="Explain how to get there, what vehicle to ride, where to stop, and any route landmarks."></textarea>
                                </div>

                                <div class="form-group full">
                                    <label>Nearby Stay</label>
                                    <textarea name="nearby_stay"
                                        placeholder="List nearby hotels, homestays, inns, or places where visitors can stay close to this destination."></textarea>
                                </div>

                                <div class="detail-fields detail-fields--split">
                                    <div class="form-group">
                                        <label>Estimated Cost</label>
                                        <input type="text" name="estimated_cost"
                                            placeholder="e.g. PHP 80 - PHP 150 one way">
                                    </div>

                                    <div class="form-group">
                                        <label>Travel Time</label>
                                        <input type="text" name="travel_time" placeholder="e.g. 35-45 minutes from CDO">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="detail-panel detail-panel--visitor">
                            <div class="detail-panel-header">
                                <span class="detail-panel-kicker">Visitor Prep</span>
                                <h4>Visitor Info</h4>
                                <p>Share reminders that help people enjoy the trip safely and comfortably.</p>
                            </div>

                            <div class="detail-fields">
                                <div class="form-group full">
                                    <label>Travel Tips</label>
                                    <textarea name="travel_tips"
                                        placeholder="Add reminders like what to bring, local etiquette, weather notes, or useful advice."></textarea>
                                </div>

                                <div class="detail-fields detail-fields--split">
                                    <div class="form-group">
                                        <label>Best Time</label>
                                        <input type="text" name="best_time"
                                            placeholder="e.g. Early morning or dry season">
                                    </div>

                                    <div class="form-group">
                                        <label>Difficulty</label>
                                        <input type="text" name="difficulty"
                                            placeholder="e.g. Easy, Moderate, Challenging">
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="upload-row">

                        <div class="upload-wrapper small">
                            <label class="upload-box upload-box--thumbnail">
                                <div class="upload-empty-state">
                                    <i class="fa-regular fa-image"></i>
                                    <strong>Cover Photo</strong>
                                    <p>Choose one strong image that will represent this place in cards and lists.</p>
                                </div>
                                <div id="thumbnailPreview" class="preview-inside"></div>
                                <div class="upload-meta">
                                    <strong>Primary Image</strong>
                                    <span>Recommended: landscape photo</span>
                                </div>
                                <input type="file" name="thumbnail" id="thumbnailInput">
                            </label>
                        </div>

                        <div class="upload-wrapper large">
                            <label class="upload-box upload-box--gallery" id="dropZone">
                                <div class="upload-empty-state">
                                    <i class="fa-solid fa-images"></i>
                                    <strong>Gallery Photos</strong>
                                    <p>Add more views of the destination. You can drag and drop multiple files here.</p>
                                </div>

                                <div id="imagesPreview" class="preview-inside multi"></div>

                                <div id="imageCount" class="image-count">0 / 100</div>

                                <div class="upload-meta">
                                    <strong>Add Photos</strong>
                                    <span>Supports multiple uploads</span>
                                </div>
                                <input type="file" id="imagesInput" multiple>
                            </label>
                        </div>

                    </div>

                </div>

                <div class="modal-right">

                    <div class="side-card side-card--accent">
                        <div class="side-card-heading">
                            <span class="side-card-kicker">Stay Connected</span>
                            <h4>Contact Information</h4>
                        </div>

                        <input type="text" name="phone_number" placeholder="Phone Number">
                        <input type="email" name="email" placeholder="Email Address">
                    </div>

                    <div class="side-card">
                        <div class="side-card-heading">
                            <span class="side-card-kicker">Social Reach</span>
                            <h4>Social Media Links</h4>
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-facebook"></i>
                            <input type="text" name="facebook" placeholder="https://...">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-instagram"></i>
                            <input type="text" name="instagram">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-twitter"></i>
                            <input type="text" name="twitter">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-tiktok"></i>
                            <input type="text" name="tiktok">
                        </div>

                    </div>

                    <button type="submit" class="btn-primary">ADD ITEM</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>

                </div>

            </div>
        </form>

    </div>
</div>

<div id="editAssetModal" class="assets-modal">
    <div class="assets-modal-content modern-modal">

        <div class="modal-header">
            <h2><i class="fa-solid fa-pen"></i> EDIT ASSET</h2>
            <span class="assets-close-btn" onclick="closeEditModal()" title="Close edit asset form">
                <i class="fa-solid fa-xmark"></i>
            </span>
        </div>

        <form id="editAssetForm" class="assets-form" enctype="multipart/form-data">

            <input type="hidden" name="asset_id" id="edit_asset_id">

            <div class="modal-grid">

                <div class="modal-left">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Asset Name</label>
                            <input type="text" name="asset_name" id="edit_asset_name" required>
                        </div>



                        <div class="form-group">
                            <label>Asset Location</label>
                            <div class="location-search-shell" data-mode="edit">
                                <span class="location-search-icon" aria-hidden="true">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <input type="text" id="edit_location" name="location" class="location-search-input"
                                    placeholder="Search a place or paste a Google Maps link / iframe..."
                                    autocomplete="off">
                                <button type="button" class="location-clear-btn"
                                    onclick="clearLocationSelection('edit')" aria-label="Clear selected location"
                                    hidden>
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                                <div class="location-search-results" id="edit_locationResults"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Classifications</label>
                        <div class="type-picker" id="editTypePicker" data-type-picker>
                            <?php $renderTypeCheckboxes($assetTypes, 'edit'); ?>
                        </div>
                    </div>

                    <section class="asset-status-panel">
                        <div class="asset-status-panel__head">
                            <span class="detail-panel-kicker">Availability</span>
                            <h4>Place Status</h4>
                        </div>
                        <div class="asset-status-fields">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="asset_status" id="edit_asset_status" required>
                                    <?php $renderStatusOptions($assetStatuses); ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status Note</label>
                                <input type="text" name="status_note" id="edit_status_note" maxlength="255"
                                    placeholder="e.g. Closed after storm damage, reopening soon">
                            </div>
                        </div>
                    </section>

                    <section class="location-stage location-stage--edit">
                        <div class="location-stage-head">
                            <div>
                                <span class="location-kicker">Search And Pin</span>
                                <h4>Location Details</h4>
                            </div>
                            <span class="location-badge">
                                <i class="fa-solid fa-pen-ruler"></i>
                                Update the search or move the pin
                            </span>
                        </div>

                        <p class="location-helper-copy">
                            Search by place name, paste a Google Maps link or embed iframe, or click the map to
                            fine-tune the stored location.
                        </p>

                        <div class="location-meta">
                            <div class="location-pill">
                                <span>Latitude</span>
                                <strong id="edit_latDisplay">Not set</strong>
                            </div>
                            <div class="location-pill">
                                <span>Longitude</span>
                                <strong id="edit_lngDisplay">Not set</strong>
                            </div>
                        </div>

                        <div class="map-card">
                            <div class="map-card-head">
                                <div>
                                    <span class="location-kicker">Pinpoint Spot</span>
                                    <h4>Interactive Map</h4>
                                </div>
                                <p>Drag the marker or tap a new spot on the map to update this destination.</p>
                            </div>
                            <div id="editMap"></div>
                        </div>
                    </section>

                    <input type="hidden" name="latitude" id="edit_lat">
                    <input type="hidden" name="longitude" id="edit_lng">

                    <div class="form-group full">
                        <label>Place Overview</label>
                        <textarea name="description" id="edit_description"
                            placeholder="Write a short but vivid overview of the place, what makes it special, and what visitors can expect."></textarea>
                    </div>

                    <div class="detail-section-grid">
                        <section class="detail-panel detail-panel--travel">
                            <div class="detail-panel-header">
                                <span class="detail-panel-kicker">Plan The Route</span>
                                <h4>Travel Info</h4>
                                <p>Give visitors the practical route details before they leave.</p>
                            </div>

                            <div class="detail-fields">
                                <div class="form-group full">
                                    <label>Transportation</label>
                                    <textarea name="transportation" id="edit_transportation"
                                        placeholder="Explain how to get there, what vehicle to ride, where to stop, and any route landmarks."></textarea>
                                </div>

                                <div class="form-group full">
                                    <label>Nearby Stay</label>
                                    <textarea name="nearby_stay" id="edit_nearby_stay"
                                        placeholder="List nearby hotels, homestays, inns, or places where visitors can stay close to this destination."></textarea>
                                </div>

                                <div class="detail-fields detail-fields--split">
                                    <div class="form-group">
                                        <label>Estimated Cost</label>
                                        <input type="text" name="estimated_cost" id="edit_estimated_cost"
                                            placeholder="e.g. PHP 80 - PHP 150 one way">
                                    </div>

                                    <div class="form-group">
                                        <label>Travel Time</label>
                                        <input type="text" name="travel_time" id="edit_travel_time"
                                            placeholder="e.g. 35-45 minutes from CDO">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="detail-panel detail-panel--visitor">
                            <div class="detail-panel-header">
                                <span class="detail-panel-kicker">Visitor Prep</span>
                                <h4>Visitor Info</h4>
                                <p>Share reminders that help people enjoy the trip safely and comfortably.</p>
                            </div>

                            <div class="detail-fields">
                                <div class="form-group full">
                                    <label>Travel Tips</label>
                                    <textarea name="travel_tips" id="edit_travel_tips"
                                        placeholder="Add reminders like what to bring, local etiquette, weather notes, or useful advice."></textarea>
                                </div>

                                <div class="detail-fields detail-fields--split">
                                    <div class="form-group">
                                        <label>Best Time</label>
                                        <input type="text" name="best_time" id="edit_best_time"
                                            placeholder="e.g. Early morning or dry season">
                                    </div>

                                    <div class="form-group">
                                        <label>Difficulty</label>
                                        <input type="text" name="difficulty" id="edit_difficulty"
                                            placeholder="e.g. Easy, Moderate, Challenging">
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="upload-row">

                        <div class="upload-wrapper small">
                            <label class="upload-box upload-box--thumbnail">
                                <div class="upload-empty-state">
                                    <i class="fa-regular fa-image"></i>
                                    <strong>Update Cover Photo</strong>
                                    <p>Replace the main image if you want a better first impression for this asset.</p>
                                </div>
                                <div id="edit_thumbnailPreview" class="preview-inside"></div>
                                <div class="upload-meta">
                                    <strong>Primary Image</strong>
                                    <span>Leave empty to keep the current one</span>
                                </div>
                                <input type="file" name="thumbnail" id="edit_thumbnailInput">
                            </label>
                        </div>

                        <div class="upload-wrapper large">
                            <label class="upload-box upload-box--gallery" id="edit_dropZone">
                                <div class="upload-empty-state">
                                    <i class="fa-solid fa-images"></i>
                                    <strong>Update Gallery</strong>
                                    <p>Keep existing photos and add more to make the place feel complete.</p>
                                </div>

                                <div id="edit_imagesPreview" class="preview-inside multi"></div>

                                <div id="edit_imageCount" class="image-count">0 / 100</div>

                                <div class="upload-meta">
                                    <strong>Add Photos</strong>
                                    <span>Remove saved photos or add more</span>
                                </div>
                                <input type="file" id="edit_imagesInput" multiple>
                            </label>
                        </div>

                    </div>

                </div>

                <div class="modal-right">

                    <div class="side-card side-card--accent">
                        <div class="side-card-heading">
                            <span class="side-card-kicker">Stay Connected</span>
                            <h4>Contact Information</h4>
                        </div>

                        <input type="text" name="phone_number" id="edit_phone">
                        <input type="email" name="email" id="edit_email">
                    </div>

                    <div class="side-card">
                        <div class="side-card-heading">
                            <span class="side-card-kicker">Social Reach</span>
                            <h4>Social Media Links</h4>
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-facebook"></i>
                            <input type="text" name="facebook" id="edit_facebook">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-instagram"></i>
                            <input type="text" name="instagram" id="edit_instagram">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-twitter"></i>
                            <input type="text" name="twitter" id="edit_twitter">
                        </div>

                        <div class="social-input">
                            <i class="fa-brands fa-tiktok"></i>
                            <input type="text" name="tiktok" id="edit_tiktok">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">UPDATE ITEM</button>
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">CANCEL</button>

                </div>

            </div>
        </form>

    </div>
</div>

<script src="<?= $BASE_URL ?>/frontend/assets/js/add_modal.js?v=<?php echo time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/edit_modal.js?v=<?php echo time(); ?>"></script>
