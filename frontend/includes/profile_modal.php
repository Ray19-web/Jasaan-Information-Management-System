<?php
$profileUser = $user ?? [];
$profileUsername = (string) ($profileUser['username'] ?? '');
$profileFullName = (string) ($profileUser['full_name'] ?? '');
$profileEmail = (string) ($profileUser['email'] ?? '');
$profileRole = strtoupper((string) ($profileUser['role'] ?? ''));
$profilePicture = !empty($profileUser['profile_picture'])
    ? $BASE_URL . '/' . ltrim((string) $profileUser['profile_picture'], '/')
    : $BASE_URL . '/frontend/assets/images/default-user.jpg';
?>
<div id="profileModal" class="profile-modal123">
    <div class="profile-modal-content profile-modal-card">
        <h2 class="profile-title">
            <span class="highlight-bar"></span> PROFILE
        </h2>
        <p class="profile-subtitle">Manage your profile information</p>

        <form id="profileForm" enctype="multipart/form-data">
            <div class="profile-picture-wrapper">
                <label for="profilePictureInput">
                    <img id="profilePicture" src="<?= htmlspecialchars($profilePicture) ?>" class="profile-picture">
                    <input type="file" id="profilePictureInput" name="profile_picture" hidden>
                </label>
                <small class="profile-picture-hint">
                    Click image to update profile picture
                </small>
            </div>

            <div class="profile-grid">
                <div class="profile-group">
                    <label>User Name</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($profileUsername) ?>" required>
                </div>

                <div class="profile-group">
                    <label>Authorization</label>
                    <input type="text" value="<?= htmlspecialchars($profileRole) ?>" disabled>
                </div>

                <div class="profile-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($profileFullName) ?>" required>
                </div>

                <div class="profile-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($profileEmail) ?>" required>
                </div>

                <div class="profile-group full">
                    <label>Password</label>
                    <div class="profile-password">
                        <input type="password" id="profilePassword" name="password" placeholder="Enter new password">
                        <button type="button" id="toggleProfilePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn-update">UPDATE PROFILE</button>
                <button type="button" class="btn-cancel" id="cancelProfileBtn">CANCEL</button>
            </div>
        </form>
    </div>
</div>
