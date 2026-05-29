<?php
require_once __DIR__ . "/../../../../backend/db.php";

$BASE_URL = $BASE_URL ?? '/jasaan-tourism';

$users = $conn->query("
    SELECT
        u.user_id,
        u.full_name,
        u.username,
        r.role_label AS role,
        u.profile_picture
    FROM users u
    JOIN user_roles r ON r.role_id = u.role_id
    WHERE u.deleted_at IS NULL
    ORDER BY u.user_id DESC
");
?>

<div class="admin-content">

    <div class="assets-box">
        <div class="assets-header">
            <div class="assets-heading-copy">
                <span class="assets-kicker">Access Control</span>
                <h3><i class="fa-solid fa-users"></i> Manage Accounts</h3>
                <p>Search people quickly, filter by role, and manage who can access the admin side.</p>
            </div>

            <div class="assets-toolbar">
                <div class="filter-box">
                    <label>User Role</label>
                    <div class="filter-chip-group" id="accountRoleFilters" role="group" aria-label="Filter accounts by role">
                        <button
                            type="button"
                            class="filter-chip account-filter-btn is-active"
                            data-account-role-filter=""
                            aria-pressed="true"
                            title="Show all accounts">
                            All
                        </button>
                        <button
                            type="button"
                            class="filter-chip account-filter-btn"
                            data-account-role-filter="tourist"
                            aria-pressed="false"
                            title="Show only tourist accounts">
                            Tourist
                        </button>
                        <button
                            type="button"
                            class="filter-chip account-filter-btn"
                            data-account-role-filter="admin"
                            aria-pressed="false"
                            title="Show only admin accounts">
                            Admin
                        </button>
                    </div>
                </div>

                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        id="accountSearch"
                        placeholder="Search by name, username, role, or ID..."
                        title="Search accounts by name, username, role, or ID" />
                </div>

                <button type="button" id="accountFilterReset" class="filter-reset-btn" title="Clear account search and filters" hidden>
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </button>
            </div>
        </div>

        <div class="assets-results-bar" aria-live="polite">
            <span class="result-pill" id="accountResultCount">Showing all accounts</span>
            <span class="result-pill result-pill--active" id="accountActiveFilter">All roles</span>
        </div>

        <table class="assets-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody id="accountsTable">
                <?php while ($row = $users->fetch_assoc()): ?>
                    <?php
                    $rawRole = trim((string) $row['role']);
                    $normalizedRole = strtolower($rawRole);
                    $isTouristRole = in_array($normalizedRole, ['tourist', 'user'], true);
                    $displayRole = $isTouristRole ? 'Tourist' : ($normalizedRole === 'admin' ? 'Admin' : $rawRole);
                    $roleSlug = $isTouristRole ? 'tourist' : $normalizedRole;
                    $username = trim((string) ($row['username'] ?? ''));
                    $searchIndex = strtolower(trim(implode(' ', [
                        (string) $row['full_name'],
                        $username,
                        $displayRole,
                        $rawRole,
                        '#' . (string) $row['user_id']
                    ])));
                    ?>
                    <tr
                        data-role="<?= htmlspecialchars($roleSlug) ?>"
                        data-search="<?= htmlspecialchars($searchIndex) ?>">
                        <td data-label="User" style="text-align: left;">
                            <div class="asset-info">
                                <div class="user-avatar">
                                    <?php if (!empty($row['profile_picture'])): ?>
                                        <img src="<?= $BASE_URL . '/' . htmlspecialchars($row['profile_picture']) ?>" alt="User Avatar">
                                    <?php else: ?>
                                        <img src="<?= $BASE_URL ?>/frontend/assets/images/default-user.jpg" alt="Default Avatar">
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                    <small>
                                        <?php if ($username !== ''): ?>
                                            @<?= htmlspecialchars($username) ?> -
                                        <?php endif; ?>
                                        ID: <?= $row['user_id'] ?>
                                    </small>
                                </div>
                            </div>
                        </td>

                        <td data-label="Role">
                            <select
                                class="role-select"
                                data-old="<?= htmlspecialchars($displayRole) ?>"
                                onchange="confirmRoleChange(<?= $row['user_id'] ?>, this)"
                                title="Change this user's role">
                                <option value="Tourist" <?= $roleSlug === 'tourist' ? 'selected' : '' ?>>Tourist</option>
                                <option value="Admin" <?= $roleSlug === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </td>

                        <td data-label="Action" class="table-actions-cell">
                            <i class="fa-solid fa-trash action-icon delete"
                                onclick="confirmDeleteUser(<?= $row['user_id'] ?>)" title="Delete this user account"></i>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <tr id="accountsEmptyState" class="assets-empty-row" hidden>
                    <td colspan="3">
                        <div class="assets-empty-state">
                            <i class="fa-solid fa-user-slash"></i>
                            <strong>No accounts match the current filters</strong>
                            <p>Try another search or switch back to all roles.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<script src="<?= $BASE_URL ?>/frontend/assets/js/account.js?v=<?php echo time(); ?>"></script>
