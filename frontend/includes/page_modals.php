<?php if ($user !== null): ?>
    <?php include __DIR__ . '/profile_modal.php'; ?>
<?php endif; ?>
<?php include __DIR__ . '/confirm_modal.php'; ?>
<?php if (($includeAuthModals ?? true) === true): ?>
    <?php include __DIR__ . '/auth-modals.php'; ?>
<?php endif; ?>
