<?php
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['role']);
$currentUserName = trim((string) ($user['full_name'] ?? $user['username'] ?? 'You'));
$currentUserImage = !empty($user['profile_picture'])
    ? $BASE_URL . '/' . ltrim((string) $user['profile_picture'], '/')
    : $BASE_URL . '/uploads/profile_pictures/default.png';
?>
<script>
    window.BASE_URL = <?= json_encode($BASE_URL) ?>;
    window.isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    window.currentUser = {
        name: <?= json_encode($currentUserName) ?>,
        image: <?= json_encode($currentUserImage) ?>
    };
</script>
