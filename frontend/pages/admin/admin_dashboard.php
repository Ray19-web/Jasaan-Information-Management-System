<?php
require_once __DIR__ . '/../../../backend/check_admin.php';
require_once __DIR__ . '/../../includes/page_bootstrap.php';

$feedbackCount = 0;
$hasReadColumn = $conn->query("SHOW COLUMNS FROM feedbacks LIKE 'is_read'");

if ($hasReadColumn && $hasReadColumn->num_rows > 0) {
    $feedbackCount = $conn->query("SELECT COUNT(*) AS cnt FROM feedbacks WHERE is_read = 0 AND deleted_at IS NULL")->fetch_assoc()['cnt'] ?? 0;
} else {
    $feedbackCount = $conn->query("SELECT COUNT(*) AS cnt FROM feedbacks WHERE created_at >= NOW() - INTERVAL 1 DAY AND deleted_at IS NULL")->fetch_assoc()['cnt'] ?? 0;
}

$pageTitle = 'Admin Dashboard - Tourism_Jasaan';
$page = $_GET['page'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../../includes/user_head.php'; ?>
<?php include __DIR__ . '/../../includes/page_state.php'; ?>

<body>
    <?php include __DIR__ . '/../../includes/page_alerts.php'; ?>

    <div id="mainContent" class="main-content admin-main-content">
        <?php include __DIR__ . '/../../includes/admin_navbar.php'; ?>

        <div class="admin-container">
            <div class="admin-sidebar">
                <div class="admin-card">
                    <div class="admin-icon">
                        <i class="fa-solid fa-gear"></i>
                    </div>

                    <h3>Admin Panel</h3>

                    <a href="?page=overview" class="<?= $page === 'overview' ? 'active-btn button' : '' ?>">
                        <i class="fa-solid fa-eye"></i> Overview
                        <?php if ($feedbackCount > 0): ?>
                            <span class="sidebar-badge"><?= $feedbackCount > 99 ? '99+' : $feedbackCount ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="?page=assets" class="<?= $page === 'assets' ? 'active-btn button' : '' ?>">
                        <i class="fa-solid fa-chart-bar"></i> Manage Assets
                    </a>

                    <a href="?page=classifications" class="<?= $page === 'classifications' ? 'active-btn button' : '' ?>">
                        <i class="fa-solid fa-tags"></i> Manage Classifications
                    </a>

                    <a href="?page=accounts" class="<?= $page === 'accounts' ? 'active-btn button' : '' ?>">
                        <i class="fa-solid fa-user"></i> Manage Accounts
                    </a>

                    <a href="?page=recycle_bin" class="<?= $page === 'recycle_bin' ? 'active-btn button' : '' ?>">
                        <i class="fa-solid fa-recycle"></i> Recycle Bin
                    </a>
                </div>
            </div>

            <?php
            if ($page === 'assets') {
                include __DIR__ . '/sections/assets.php';
            } elseif ($page === 'classifications') {
                include __DIR__ . '/sections/classifications.php';
            } elseif ($page === 'accounts') {
                include __DIR__ . '/sections/accounts.php';
            } elseif ($page === 'recycle_bin') {
                include __DIR__ . '/sections/recycle_bin.php';
            } else {
                include __DIR__ . '/sections/overview.php';
            }
            ?>
        </div>

        <?php include __DIR__ . '/../../includes/footer.php'; ?>
    </div>

    <?php $includeAuthModals = false; ?>
    <?php include __DIR__ . '/../../includes/page_modals.php'; ?>

    <script src="<?= $BASE_URL ?>/frontend/assets/js/delete_button.js?v=<?= time(); ?>"></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/profile_modal.js?v=<?= time(); ?>"></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/assets_search.js?v=<?= time(); ?>"></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/admin_feedback.js?v=<?= time(); ?>"></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/recycle_bin.js?v=<?= time(); ?>"></script>
</body>

</html>
