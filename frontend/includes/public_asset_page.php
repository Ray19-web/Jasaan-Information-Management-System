<?php
require_once __DIR__ . '/page_bootstrap.php';

$assetPageConfig = $assetPageConfig ?? [];
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$pageTitle = $assetPageConfig['pageTitle'] ?? 'Tourism_Jasaan';
$assets = jt_fetch_assets(
    $conn,
    $search,
    $assetPageConfig['typeFilter'] ?? null,
    $assetPageConfig['typeMode'] ?? 'exact'
);

$assetSectionId = $assetPageConfig['sectionId'] ?? 'jt-assets-section';
$assetSectionLabel = $assetPageConfig['sectionLabel'] ?? 'ASSETS';
$assetSectionTitle = $assetPageConfig['sectionTitle'] ?? 'EXPLORE TOURISM';
$assetSearchPlaceholder = $assetPageConfig['searchPlaceholder'] ?? 'Search...';
$assetEmptyMessage = $assetPageConfig['emptyMessage'] ?? 'No results found.';
$assetShowMoreLabel = $assetPageConfig['showMoreLabel'] ?? 'Show More';
?>
<!DOCTYPE html>
<html lang="en">

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<?php include __DIR__ . '/user_head.php'; ?>
<?php include __DIR__ . '/page_state.php'; ?>

<body>
    <?php $includeAuthMessage = true; ?>
    <?php include __DIR__ . '/page_alerts.php'; ?>

    <?php include $navbarInclude; ?>

    <?php include __DIR__ . '/asset_grid_section.php'; ?>

    <?php include __DIR__ . '/footer.php'; ?>

    <?php include __DIR__ . '/page_modals.php'; ?>
    <?php include __DIR__ . '/public_scripts.php'; ?>
</body>

</html>
