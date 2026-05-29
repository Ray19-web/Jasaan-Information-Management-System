<?php $includeCarouselScript = $includeCarouselScript ?? false; ?>
<script src="<?= $BASE_URL ?>/frontend/assets/js/profile_modal.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/auth-modal.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/signup.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/login.js?v=<?= time(); ?>"></script>
<?php if ($includeCarouselScript): ?>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/carousel.js?v=<?= time(); ?>"></script>
<?php endif; ?>
<script src="<?= $BASE_URL ?>/frontend/assets/js/user.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/asset_grid.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/asset_modal.js?v=<?= time(); ?>"></script>
<script src="<?= $BASE_URL ?>/frontend/assets/js/reset_password.js?v=<?= time(); ?>"></script>
