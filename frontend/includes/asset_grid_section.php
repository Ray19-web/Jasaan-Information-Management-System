<?php
$assetSectionId = $assetSectionId ?? 'jt-assets-section';
$assetSectionLabel = $assetSectionLabel ?? 'ASSETS';
$assetSectionTitle = $assetSectionTitle ?? 'EXPLORE TOURISM';
$assetSearchPlaceholder = $assetSearchPlaceholder ?? 'Search...';
$assetEmptyMessage = $assetEmptyMessage ?? 'No results found.';
$assetShowMoreLabel = $assetShowMoreLabel ?? 'Show More';
$assetInitialLimit = isset($assetInitialLimit) ? (int) $assetInitialLimit : 8;
$assetTypeBadgeClass = static function (string $typeName): string {
    return strtolower(preg_replace('/\s+/', '_', trim($typeName)));
};
$assetTypeFilterSlug = static function (string $typeName): string {
    return strtolower(preg_replace('/\s+/', '-', trim($typeName)));
};
$assetStatuses = [
    'open' => ['label' => 'Open', 'icon' => 'fa-circle-check'],
    'temporarily_closed' => ['label' => 'Temporarily Closed', 'icon' => 'fa-clock'],
    'permanently_closed' => ['label' => 'Permanently Closed', 'icon' => 'fa-circle-xmark'],
    'abandoned' => ['label' => 'Abandoned', 'icon' => 'fa-triangle-exclamation'],
    'under_renovation' => ['label' => 'Under Renovation', 'icon' => 'fa-screwdriver-wrench'],
];
$assetStatusMeta = static function (?string $status) use ($assetStatuses): array {
    return $assetStatuses[$status ?: 'open'] ?? $assetStatuses['open'];
};
$renderAssetTypeBadges = static function (?string $typeList) use ($assetTypeBadgeClass): void {
    $typeNames = array_values(array_filter(array_map('trim', explode(',', (string) $typeList))));

    if ($typeNames === []) {
        $typeNames = ['Unclassified'];
    }

    foreach ($typeNames as $typeName) {
        ?>
        <span class="jt-type-badge <?= htmlspecialchars($assetTypeBadgeClass($typeName)) ?>">
            <?= htmlspecialchars((string) $typeName) ?>
        </span>
        <?php
    }
};
$assetFilterTypes = [];
foreach (($assets ?? []) as $asset) {
    $typeNames = array_values(array_filter(array_map('trim', explode(',', (string) ($asset['type_name'] ?? '')))));

    foreach ($typeNames as $typeName) {
        $assetFilterTypes[$assetTypeFilterSlug($typeName)] = $typeName;
    }
}
asort($assetFilterTypes, SORT_NATURAL | SORT_FLAG_CASE);
?>
<section class="jt-assets-section" id="<?= htmlspecialchars($assetSectionId) ?>">
    <div
        class="jt-assets-section-container"
        data-asset-grid-root
        data-empty-message="<?= htmlspecialchars($assetEmptyMessage) ?>"
        data-initial-limit="<?= $assetInitialLimit ?>"
    >
        <div class="jt-assets-header">
            <p class="jt-assets-sub">
                <span class="jt-bar"></span> <?= htmlspecialchars($assetSectionLabel) ?>
            </p>
            <div class="title-and-search">
                <h2><?= htmlspecialchars($assetSectionTitle) ?></h2>
                <div class="jt-search-box">
                    <div class="search-input-container">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" data-asset-search placeholder="<?= htmlspecialchars($assetSearchPlaceholder) ?>">
                    </div>
                </div>
            </div>

            <?php if (count($assetFilterTypes) > 0): ?>
                <div class="jt-user-filter-bar" role="group" aria-label="Filter assets by classification">
                    <button type="button" class="jt-user-filter-chip is-active" data-user-asset-filter="" aria-pressed="true">
                        All
                    </button>
                    <?php foreach ($assetFilterTypes as $typeSlug => $typeName): ?>
                        <button
                            type="button"
                            class="jt-user-filter-chip"
                            data-user-asset-filter="<?= htmlspecialchars($typeSlug) ?>"
                            aria-pressed="false">
                            <?= htmlspecialchars($typeName) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="jt-assets-grid" data-asset-grid>
            <?php if (count($assets) > 0): ?>
                <?php foreach ($assets as $index => $asset): ?>
                    <?php
                    $cardTypeNames = array_values(array_filter(array_map('trim', explode(',', (string) ($asset['type_name'] ?? '')))));
                    $cardTypeSlugs = array_map($assetTypeFilterSlug, $cardTypeNames);
                    $cardStatusValue = (string) (($asset['asset_status'] ?? '') ?: 'open');
                    $cardStatus = $assetStatusMeta($cardStatusValue);
                    ?>
                    <div
                        class="jt-asset-card <?= $index >= $assetInitialLimit ? 'jt-hidden' : '' ?>"
                        data-name="<?= htmlspecialchars(strtolower((string) $asset['asset_name'])) ?>"
                        data-location="<?= htmlspecialchars(strtolower((string) $asset['location'])) ?>"
                        data-type="<?= htmlspecialchars(strtolower((string) $asset['type_name'])) ?>"
                        data-type-slugs="<?= htmlspecialchars(implode(' ', $cardTypeSlugs)) ?>"
                        data-status="<?= htmlspecialchars($cardStatusValue) ?>"
                        onclick="jtamOpen(<?= (int) $asset['asset_id'] ?>)"
                    >
                        <div
                            class="jt-asset-image"
                            style="background-image: url('<?= $BASE_URL ?>/uploads/<?= htmlspecialchars((string) $asset['image_path']) ?>');"
                        ></div>

                        <div class="jt-assets-title-container">
                            <p class="jt-assets-sub">
                                <span class="jt-type-badge-list"><?php $renderAssetTypeBadges($asset['type_name'] ?? ''); ?></span>
                            </p>
                            <span class="jt-status-badge jt-status-badge--<?= htmlspecialchars($cardStatusValue) ?>">
                                <i class="fa-solid <?= htmlspecialchars($cardStatus['icon']) ?>"></i>
                                <?= htmlspecialchars($cardStatus['label']) ?>
                            </span>
                            <h3><?= htmlspecialchars(strtoupper((string) $asset['asset_name'])) ?></h3>
                        </div>

                        <div class="jt-asset-overlay">
                            <p class="jt-assets-sub">
                                <span class="jt-type-badge-list"><?php $renderAssetTypeBadges($asset['type_name'] ?? ''); ?></span>
                            </p>
                            <span class="jt-status-badge jt-status-badge--<?= htmlspecialchars($cardStatusValue) ?>">
                                <i class="fa-solid <?= htmlspecialchars($cardStatus['icon']) ?>"></i>
                                <?= htmlspecialchars($cardStatus['label']) ?>
                            </span>
                            <h3><?= htmlspecialchars(strtoupper((string) $asset['asset_name'])) ?></h3>
                            <hr style="border: none; height: 1px; background: rgba(255, 255, 255, 0.32);">

                            <div class="card-simi-jasaan-container">
                                <div>
                                    <p class="jt-location">📍 <?= htmlspecialchars((string) $asset['location']) ?></p>
                                </div>
                                <div class="jt-rating">⭐ <?= number_format((float) $asset['avg_rating'], 1) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($assets) > $assetInitialLimit): ?>
                    <div class="jt-show-more-container" data-show-more-wrap>
                        <button type="button" class="jt-show-more-btn" data-show-more-btn>
                            <?= htmlspecialchars($assetShowMoreLabel) ?>
                        </button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="jt-no-results" data-static-empty-state><?= htmlspecialchars($assetEmptyMessage) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
