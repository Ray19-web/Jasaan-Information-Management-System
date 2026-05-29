<?php
require_once __DIR__ . "/../../../../backend/db.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

$deletedAssets = $conn->query("
    SELECT
        a.asset_id,
        a.asset_name,
        a.location,
        a.deleted_at,
        u.username AS deleted_by_name
    FROM assets a
    LEFT JOIN users u ON u.user_id = a.deleted_by
    WHERE a.deleted_at IS NOT NULL
    ORDER BY a.deleted_at DESC
");

$deletedClassifications = $conn->query("
    SELECT
        t.type_id,
        t.type_name,
        t.deleted_at,
        u.username AS deleted_by_name
    FROM asset_types t
    LEFT JOIN users u ON u.user_id = t.deleted_by
    WHERE t.deleted_at IS NOT NULL
    ORDER BY t.deleted_at DESC
");

$deletedUsers = $conn->query("
    SELECT
        du.user_id,
        du.full_name,
        du.username,
        r.role_label,
        du.deleted_at,
        admin.username AS deleted_by_name
    FROM users du
    LEFT JOIN user_roles r ON r.role_id = du.role_id
    LEFT JOIN users admin ON admin.user_id = du.deleted_by
    WHERE du.deleted_at IS NOT NULL
    ORDER BY du.deleted_at DESC
");

$deletedFeedbacks = $conn->query("
    SELECT
        f.feedback_id,
        f.comment,
        f.rating,
        f.deleted_at,
        visitor.username AS visitor_name,
        a.asset_name,
        admin.username AS deleted_by_name
    FROM feedbacks f
    LEFT JOIN users visitor ON visitor.user_id = f.user_id
    LEFT JOIN assets a ON a.asset_id = f.asset_id
    LEFT JOIN users admin ON admin.user_id = f.deleted_by
    WHERE f.deleted_at IS NOT NULL
    ORDER BY f.deleted_at DESC
");

$formatDeletedAt = static function (?string $value): string {
    if (!$value) {
        return 'Unknown date';
    }

    return date('M d, Y h:i A', strtotime($value));
};

$renderEmpty = static function (string $icon, string $title, string $copy, int $colspan): void {
    ?>
    <tr class="assets-empty-row">
        <td colspan="<?= $colspan ?>">
            <div class="assets-empty-state">
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i>
                <strong><?= htmlspecialchars($title) ?></strong>
                <p><?= htmlspecialchars($copy) ?></p>
            </div>
        </td>
    </tr>
    <?php
};

$shortText = static function (?string $value, int $limit = 120): string {
    $text = trim((string) $value);

    if (strlen($text) <= $limit) {
        return $text;
    }

    return substr($text, 0, $limit - 3) . '...';
};

$searchIndex = static function (array $values): string {
    return strtolower(trim(implode(' ', array_map(static function ($value): string {
        return trim((string) $value);
    }, $values))));
};

$totalDeletedItems = $deletedAssets->num_rows
    + $deletedClassifications->num_rows
    + $deletedUsers->num_rows
    + $deletedFeedbacks->num_rows;
?>

<div class="admin-content">
    <div class="assets-box recycle-bin-page">
        <div class="assets-header">
            <div class="assets-heading-copy">
                <span class="assets-kicker">Temporary Deletion</span>
                <h3><i class="fa-solid fa-recycle"></i> Recycle Bin</h3>
                <p>Restore records that were deleted by mistake, or permanently remove records that are no longer needed.</p>
            </div>

            <div class="assets-toolbar recycle-toolbar">
                <div class="filter-box">
                    <label>Filter by Record Type</label>
                    <div class="filter-chip-group" id="recycleTypeFilters" role="group" aria-label="Filter recycle bin by record type">
                        <button type="button" class="filter-chip recycle-filter-btn is-active" data-recycle-filter="" aria-pressed="true" title="Show every deleted record">All</button>
                        <button type="button" class="filter-chip recycle-filter-btn" data-recycle-filter="asset" aria-pressed="false" title="Show deleted assets">Assets</button>
                        <button type="button" class="filter-chip recycle-filter-btn" data-recycle-filter="classification" aria-pressed="false" title="Show deleted classifications">Classifications</button>
                        <button type="button" class="filter-chip recycle-filter-btn" data-recycle-filter="user" aria-pressed="false" title="Show deleted accounts">Accounts</button>
                        <button type="button" class="filter-chip recycle-filter-btn" data-recycle-filter="feedback" aria-pressed="false" title="Show deleted feedback">Feedback</button>
                    </div>
                </div>

                <div class="filter-box recycle-date-filter">
                    <label for="recycleDateFilter">Deleted Date</label>
                    <select id="recycleDateFilter" title="Filter records by deletion date">
                        <option value="">Any time</option>
                        <option value="today">Today</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                    </select>
                </div>

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="recycleSearch" placeholder="Search name, ID, admin, location, or comment..." title="Search deleted records" />
                </div>

                <button type="button" id="recycleFilterReset" class="filter-reset-btn" title="Clear recycle bin filters" hidden>
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </button>
            </div>
        </div>

        <div class="assets-results-bar">
            <span class="result-pill" id="recycleResultCount">Showing all <?= $totalDeletedItems ?> deleted item<?= $totalDeletedItems === 1 ? '' : 's' ?></span>
            <span class="result-pill result-pill--active" id="recycleActiveFilter">All deleted records</span>
        </div>

        <div class="recycle-bin-grid">
            <section class="recycle-panel" data-recycle-panel="asset">
                <div class="recycle-panel__head">
                    <h4><i class="fa-solid fa-layer-group"></i> Deleted Assets</h4>
                    <span><?= $deletedAssets->num_rows ?> item<?= $deletedAssets->num_rows === 1 ? '' : 's' ?></span>
                </div>
                <table class="assets-table recycle-table">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Deleted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedAssets->num_rows === 0): ?>
                            <?php $renderEmpty('fa-box-open', 'No deleted assets', 'Deleted assets will appear here.', 3); ?>
                        <?php else: ?>
                            <?php while ($asset = $deletedAssets->fetch_assoc()): ?>
                                <tr data-recycle-row data-item-type="asset" data-deleted-at="<?= htmlspecialchars($asset['deleted_at']) ?>" data-search="<?= htmlspecialchars($searchIndex([
                                    $asset['asset_name'],
                                    $asset['location'],
                                    $asset['deleted_by_name'],
                                    'asset',
                                    'assets',
                                    'id ' . $asset['asset_id'],
                                    '#' . $asset['asset_id'],
                                    $formatDeletedAt($asset['deleted_at'])
                                ])) ?>">
                                    <td data-label="Asset" style="text-align: left;">
                                        <strong><?= htmlspecialchars($asset['asset_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($asset['location'] ?: 'No location') ?> - ID: <?= (int) $asset['asset_id'] ?></small>
                                    </td>
                                    <td data-label="Deleted">
                                        <?= htmlspecialchars($formatDeletedAt($asset['deleted_at'])) ?><br>
                                        <small>By <?= htmlspecialchars($asset['deleted_by_name'] ?: 'Unknown admin') ?></small>
                                    </td>
                                    <td data-label="Action" class="table-actions-cell recycle-actions">
                                        <button type="button" class="restore-btn" data-recycle-action="restore" data-item-type="asset" data-item-id="<?= (int) $asset['asset_id'] ?>">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                        <button type="button" class="permanent-delete-btn" data-recycle-action="delete" data-item-type="asset" data-item-id="<?= (int) $asset['asset_id'] ?>">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <tr class="assets-empty-row recycle-filter-empty" data-recycle-filter-empty hidden>
                            <td colspan="3">
                                <div class="assets-empty-state">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <strong>No deleted assets match</strong>
                                    <p>Try another search, record type, or date range.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="recycle-panel" data-recycle-panel="classification">
                <div class="recycle-panel__head">
                    <h4><i class="fa-solid fa-tags"></i> Deleted Classifications</h4>
                    <span><?= $deletedClassifications->num_rows ?> item<?= $deletedClassifications->num_rows === 1 ? '' : 's' ?></span>
                </div>
                <table class="assets-table recycle-table">
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Deleted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedClassifications->num_rows === 0): ?>
                            <?php $renderEmpty('fa-tags', 'No deleted classifications', 'Deleted classifications will appear here.', 3); ?>
                        <?php else: ?>
                            <?php while ($type = $deletedClassifications->fetch_assoc()): ?>
                                <tr data-recycle-row data-item-type="classification" data-deleted-at="<?= htmlspecialchars($type['deleted_at']) ?>" data-search="<?= htmlspecialchars($searchIndex([
                                    $type['type_name'],
                                    $type['deleted_by_name'],
                                    'classification',
                                    'classifications',
                                    'id ' . $type['type_id'],
                                    '#' . $type['type_id'],
                                    $formatDeletedAt($type['deleted_at'])
                                ])) ?>">
                                    <td data-label="Classification" style="text-align: left;">
                                        <strong><?= htmlspecialchars($type['type_name']) ?></strong><br>
                                        <small>ID: <?= (int) $type['type_id'] ?></small>
                                    </td>
                                    <td data-label="Deleted">
                                        <?= htmlspecialchars($formatDeletedAt($type['deleted_at'])) ?><br>
                                        <small>By <?= htmlspecialchars($type['deleted_by_name'] ?: 'Unknown admin') ?></small>
                                    </td>
                                    <td data-label="Action" class="table-actions-cell recycle-actions">
                                        <button type="button" class="restore-btn" data-recycle-action="restore" data-item-type="classification" data-item-id="<?= (int) $type['type_id'] ?>">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                        <button type="button" class="permanent-delete-btn" data-recycle-action="delete" data-item-type="classification" data-item-id="<?= (int) $type['type_id'] ?>">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <tr class="assets-empty-row recycle-filter-empty" data-recycle-filter-empty hidden>
                            <td colspan="3">
                                <div class="assets-empty-state">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <strong>No deleted classifications match</strong>
                                    <p>Try another search, record type, or date range.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="recycle-panel" data-recycle-panel="user">
                <div class="recycle-panel__head">
                    <h4><i class="fa-solid fa-users"></i> Deleted Accounts</h4>
                    <span><?= $deletedUsers->num_rows ?> item<?= $deletedUsers->num_rows === 1 ? '' : 's' ?></span>
                </div>
                <table class="assets-table recycle-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Deleted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedUsers->num_rows === 0): ?>
                            <?php $renderEmpty('fa-user-check', 'No deleted accounts', 'Deleted accounts will appear here.', 3); ?>
                        <?php else: ?>
                            <?php while ($user = $deletedUsers->fetch_assoc()): ?>
                                <tr data-recycle-row data-item-type="user" data-deleted-at="<?= htmlspecialchars($user['deleted_at']) ?>" data-search="<?= htmlspecialchars($searchIndex([
                                    $user['full_name'],
                                    $user['username'],
                                    $user['role_label'],
                                    $user['deleted_by_name'],
                                    'user',
                                    'users',
                                    'account',
                                    'accounts',
                                    'id ' . $user['user_id'],
                                    '#' . $user['user_id'],
                                    $formatDeletedAt($user['deleted_at'])
                                ])) ?>">
                                    <td data-label="User" style="text-align: left;">
                                        <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                        <small>@<?= htmlspecialchars($user['username']) ?> - <?= htmlspecialchars($user['role_label'] ?: 'No role') ?> - ID: <?= (int) $user['user_id'] ?></small>
                                    </td>
                                    <td data-label="Deleted">
                                        <?= htmlspecialchars($formatDeletedAt($user['deleted_at'])) ?><br>
                                        <small>By <?= htmlspecialchars($user['deleted_by_name'] ?: 'Unknown admin') ?></small>
                                    </td>
                                    <td data-label="Action" class="table-actions-cell recycle-actions">
                                        <button type="button" class="restore-btn" data-recycle-action="restore" data-item-type="user" data-item-id="<?= (int) $user['user_id'] ?>">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                        <button type="button" class="permanent-delete-btn" data-recycle-action="delete" data-item-type="user" data-item-id="<?= (int) $user['user_id'] ?>">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <tr class="assets-empty-row recycle-filter-empty" data-recycle-filter-empty hidden>
                            <td colspan="3">
                                <div class="assets-empty-state">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <strong>No deleted accounts match</strong>
                                    <p>Try another search, record type, or date range.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="recycle-panel" data-recycle-panel="feedback">
                <div class="recycle-panel__head">
                    <h4><i class="fa-solid fa-comments"></i> Deleted Feedback</h4>
                    <span><?= $deletedFeedbacks->num_rows ?> item<?= $deletedFeedbacks->num_rows === 1 ? '' : 's' ?></span>
                </div>
                <table class="assets-table recycle-table">
                    <thead>
                        <tr>
                            <th>Feedback</th>
                            <th>Deleted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($deletedFeedbacks->num_rows === 0): ?>
                            <?php $renderEmpty('fa-comments', 'No deleted feedback', 'Deleted feedback will appear here.', 3); ?>
                        <?php else: ?>
                            <?php while ($feedback = $deletedFeedbacks->fetch_assoc()): ?>
                                <tr data-recycle-row data-item-type="feedback" data-deleted-at="<?= htmlspecialchars($feedback['deleted_at']) ?>" data-search="<?= htmlspecialchars($searchIndex([
                                    $feedback['visitor_name'],
                                    $feedback['asset_name'],
                                    $feedback['comment'],
                                    $feedback['deleted_by_name'],
                                    'feedback',
                                    'rating ' . $feedback['rating'],
                                    'id ' . $feedback['feedback_id'],
                                    '#' . $feedback['feedback_id'],
                                    $formatDeletedAt($feedback['deleted_at'])
                                ])) ?>">
                                    <td data-label="Feedback" style="text-align: left;">
                                        <strong><?= htmlspecialchars($feedback['visitor_name'] ?: 'Unknown visitor') ?></strong>
                                        <span class="feedback-stars">
                                            <?php for ($i = 1; $i <= (int) $feedback['rating']; $i++): ?>&#9733;<?php endfor; ?>
                                        </span><br>
                                        <small><?= htmlspecialchars($feedback['asset_name'] ?: 'Deleted asset') ?> - ID: <?= (int) $feedback['feedback_id'] ?></small>
                                        <p class="recycle-feedback-copy"><?= htmlspecialchars($shortText($feedback['comment'])) ?></p>
                                    </td>
                                    <td data-label="Deleted">
                                        <?= htmlspecialchars($formatDeletedAt($feedback['deleted_at'])) ?><br>
                                        <small>By <?= htmlspecialchars($feedback['deleted_by_name'] ?: 'Unknown admin') ?></small>
                                    </td>
                                    <td data-label="Action" class="table-actions-cell recycle-actions">
                                        <button type="button" class="restore-btn" data-recycle-action="restore" data-item-type="feedback" data-item-id="<?= (int) $feedback['feedback_id'] ?>">
                                            <i class="fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                        <button type="button" class="permanent-delete-btn" data-recycle-action="delete" data-item-type="feedback" data-item-id="<?= (int) $feedback['feedback_id'] ?>">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        <tr class="assets-empty-row recycle-filter-empty" data-recycle-filter-empty hidden>
                            <td colspan="3">
                                <div class="assets-empty-state">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <strong>No deleted feedback match</strong>
                                    <p>Try another search, record type, or date range.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</div>
