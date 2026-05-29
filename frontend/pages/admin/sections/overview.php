<?php
require_once __DIR__ . "/../../../../backend/db.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

// This list controls the icon and color for known classification cards.
$statCardStyles = [
    'attractions' => ['class' => 'green', 'icon' => 'fa-camera'],
    'resorts' => ['class' => 'blue', 'icon' => 'fa-umbrella-beach'],
    'local products' => ['class' => 'yellow', 'icon' => 'fa-box-open'],
    'markets' => ['class' => 'red', 'icon' => 'fa-store'],
];

// This query counts every active classification, so newly added classifications appear in Overview automatically.
$classificationStats = $conn->query("
    SELECT
        t.type_name,
        COUNT(DISTINCT a.asset_id) AS total
    FROM asset_types t
    LEFT JOIN asset_type_assignments ata ON ata.type_id = t.type_id
    LEFT JOIN assets a ON a.asset_id = ata.asset_id AND a.deleted_at IS NULL
    WHERE t.deleted_at IS NULL
    GROUP BY t.type_id, t.type_name
    ORDER BY t.type_name ASC
");

$feedbacks = $conn->query("
    SELECT 
        f.feedback_id,
        f.comment,
        f.rating,
        f.created_at,
        f.is_read,
        f.is_hidden,
        u.username,
        u.user_id,
        u.profile_picture,
        a.asset_name
    FROM feedbacks f
    JOIN users u ON f.user_id = u.user_id
    JOIN assets a ON f.asset_id = a.asset_id
    WHERE f.deleted_at IS NULL
      AND a.deleted_at IS NULL
      AND u.deleted_at IS NULL
    ORDER BY f.feedback_id DESC
");
?>

<div class="admin-content">

    <div class="stats-grid">
        <?php if ($classificationStats && $classificationStats->num_rows > 0): ?>
            <?php while ($stat = $classificationStats->fetch_assoc()): ?>
                <?php
                // Unknown new classifications use a neutral card style until a custom style is added above.
                $statKey = strtolower((string) $stat['type_name']);
                $statStyle = $statCardStyles[$statKey] ?? ['class' => 'teal', 'icon' => 'fa-tag'];
                ?>
                <div class="stat-card <?= htmlspecialchars($statStyle['class']) ?>">
                    <h4><?= htmlspecialchars((string) $stat['type_name']) ?></h4>

                    <div class="stat-content">
                        <p class="stat-number"><?= (int) $stat['total'] ?></p>
                        <div class="stat-icon-box">
                            <i class="fas <?= htmlspecialchars($statStyle['icon']) ?>"></i>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="stat-card teal">
                <h4>No Classifications</h4>

                <div class="stat-content">
                    <p class="stat-number">0</p>
                    <div class="stat-icon-box">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="feedback-box">
        <div class="feedback-header">
            <div class="feedback-heading-copy">
                <h3><i class="fa-solid fa-bullhorn"></i> Manage Feedback</h3>
                <p>Review visitor comments, mark them as read, and control whether they stay visible to users.</p>
            </div>

            <button id="markAllReadBtn" class="mark-all-btn" title="Mark every feedback item as read">Mark all read</button>
        </div>

        <div class="feedback-toolbar">
            <div class="feedback-search-block">
                <label for="feedbackSearch" class="feedback-filter-label">Search Feedback</label>
                <div class="search-box feedback-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        id="feedbackSearch"
                        placeholder="Search by user, asset, comment, rating, or ID..."
                        title="Search feedback by user, asset, comment, rating, or ID">
                </div>
            </div>

            <div class="feedback-filter-block">
                <span class="feedback-filter-label">Read Status</span>
                <div class="filter-chip-group" id="feedbackReadFilters" role="group" aria-label="Filter feedback by read status">
                    <button type="button" class="filter-chip feedback-filter-btn is-active" data-feedback-read="" aria-pressed="true" title="Show all feedback items">All</button>
                    <button type="button" class="filter-chip feedback-filter-btn" data-feedback-read="unread" aria-pressed="false" title="Show only unread feedback">Unread</button>
                    <button type="button" class="filter-chip feedback-filter-btn" data-feedback-read="read" aria-pressed="false" title="Show only read feedback">Read</button>
                </div>
            </div>

            <div class="feedback-filter-block">
                <span class="feedback-filter-label">Public Visibility</span>
                <div class="filter-chip-group" id="feedbackVisibilityFilters" role="group" aria-label="Filter feedback by public visibility">
                    <button type="button" class="filter-chip feedback-visibility-filter-btn is-active" data-feedback-visibility="" aria-pressed="true" title="Show both visible and hidden feedback">All</button>
                    <button type="button" class="filter-chip feedback-visibility-filter-btn" data-feedback-visibility="visible" aria-pressed="false" title="Show only feedback visible to users">Visible</button>
                    <button type="button" class="filter-chip feedback-visibility-filter-btn" data-feedback-visibility="hidden" aria-pressed="false" title="Show only feedback hidden from users">Hidden</button>
                </div>
            </div>
        </div>

        <div class="feedback-results-bar" aria-live="polite">
            <span class="result-pill" id="feedbackResultCount">Showing all feedback</span>
            <span class="result-pill result-pill--active" id="feedbackActiveFilter">All feedback</span>
        </div>

        <div class="feedback-table-wrap">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>Users</th>
                        <th>Feedback</th>
                        <th>Assets</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="feedbackTable">
                    <?php while ($row = $feedbacks->fetch_assoc()): ?>
                        <?php
                        $readState = $row['is_read'] ? 'read' : 'unread';
                        $visibilityState = $row['is_hidden'] ? 'hidden' : 'visible';
                        $profilePicture = trim((string) ($row['profile_picture'] ?? ''));
                        $profileImage = $profilePicture !== ''
                            ? $BASE_URL . '/' . ltrim($profilePicture, '/')
                            : $BASE_URL . '/frontend/assets/images/default-user.jpg';
                        $searchIndex = strtolower(trim(implode(' ', [
                            '#' . (string) $row['feedback_id'],
                            (string) $row['username'],
                            '#' . (string) $row['user_id'],
                            (string) $row['asset_name'],
                            (string) $row['comment'],
                            (string) $row['rating'] . ' stars',
                            $readState,
                            $visibilityState,
                        ])));
                        ?>
                        <tr
                            class="feedback-row <?= $readState ?> <?= $visibilityState === 'hidden' ? 'is-hidden' : 'is-visible' ?>"
                            data-feedback-id="<?= $row['feedback_id'] ?>"
                            data-read-state="<?= $readState ?>"
                            data-visibility-state="<?= $visibilityState ?>"
                            data-search="<?= htmlspecialchars($searchIndex) ?>">
                            <td data-label="User">
                                <div class="user-profile">
                                    <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile picture">
                                    <div class="user-info">
                                        <span class="user-name"><?= htmlspecialchars($row['username']) ?></span>
                                        <small class="user-id">ID: #<?= $row['user_id'] ?></small>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Feedback" style="text-align: left;">
                                <div class="feedback-copy"><?= nl2br(htmlspecialchars($row['comment'])) ?></div>
                                <div class="feedback-stars" aria-label="Rating: <?= (int) $row['rating'] ?> out of 5">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $row['rating'] ? '&#9733;' : '&#9734;' ?>
                                    <?php endfor; ?>
                                </div>
                            </td>

                            <td data-label="Asset" style="text-align: left;">
                                <span class="tag"><?= htmlspecialchars($row['asset_name']) ?></span>
                            </td>

                            <td data-label="Status">
                                <div class="feedback-status-line">
                                    <span class="status-pill"><?= $row['is_read'] ? 'Read' : 'Unread' ?></span>
                                    <span class="status-pill status-pill--visibility <?= $row['is_hidden'] ? 'status-pill--hidden' : 'status-pill--visible' ?>">
                                        <?= $row['is_hidden'] ? 'Hidden' : 'Visible' ?>
                                    </span>
                                </div>
                            </td>

                            <td data-label="Action">
                                <div class="feedback-action-row">
                                    <button
                                        type="button"
                                        class="feedback-visibility-btn <?= $row['is_hidden'] ? 'is-hidden' : 'is-visible' ?>"
                                        data-hidden="<?= $row['is_hidden'] ? '1' : '0' ?>"
                                        onclick="toggleFeedbackVisibility(<?= $row['feedback_id'] ?>, this)"
                                        title="<?= $row['is_hidden'] ? 'Show this feedback to users again' : 'Hide this feedback from users' ?>">
                                        <i class="fa-solid <?= $row['is_hidden'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                        <span><?= $row['is_hidden'] ? 'Show' : 'Hide' ?></span>
                                    </button>

                                    <button
                                        type="button"
                                        class="feedback-delete-btn"
                                        onclick="deleteFeedback(<?= $row['feedback_id'] ?>, this)"
                                        title="Move this feedback to the Recycle Bin">
                                        <i class="fa-solid fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                    <tr id="feedbackEmptyState" class="feedback-empty-row" hidden>
                        <td colspan="5">
                            <div class="assets-empty-state">
                                <i class="fa-solid fa-comments"></i>
                                <strong>No feedback matches the current filters</strong>
                                <p>Try another search or switch the filter buttons to reveal more visitor comments.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
