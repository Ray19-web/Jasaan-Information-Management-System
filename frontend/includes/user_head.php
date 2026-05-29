<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    $BASE_URL = '/jasaan-tourism';
    ?>

    <link rel="shortcut icon" href="<?= $BASE_URL ?>/frontend/assets/images/logo.png" type="image/x-icon">

    <script>
        (() => {
            const root = document.documentElement;
            const storageKey = 'theme';
            let theme = 'light';

            try {
                const savedTheme = localStorage.getItem(storageKey);
                if (savedTheme === 'light' || savedTheme === 'dark') {
                    theme = savedTheme;
                } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            } catch (error) {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    theme = 'dark';
                }
            }

            root.dataset.theme = theme;
            root.style.colorScheme = theme;
            root.classList.toggle('dark-mode', theme === 'dark');
        })();
    </script>

    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/auth.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/admin.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/footer.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/admin_assets.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/add_modal.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/edit_asset.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/account.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/admin_profile.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/carousel.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?= $BASE_URL ?>/frontend/assets/css/user.css?v=<?= time(); ?>">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <title><?= $pageTitle ?? 'JASAYA Journey Center' ?></title>

    <script src="<?= $BASE_URL ?>/frontend/assets/js/app_ui.js?v=<?= time(); ?>"></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/action_lock.js?v=<?= time(); ?>" defer></script>
    <script src="<?= $BASE_URL ?>/frontend/assets/js/theme_toggle.js?v=<?= time(); ?>" defer></script>
</head>
