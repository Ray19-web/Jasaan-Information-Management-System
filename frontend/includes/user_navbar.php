<?php
$BASE_URL = $BASE_URL ?? '/jasaan-tourism';
require_once __DIR__ . '/nav_state.php';
$currentPath = jt_normalize_nav_path($_SERVER['REQUEST_URI'] ?? '', $BASE_URL);
?>

<header class="navbar">
    <!-- Logo area: clicking this sends the user back to the main Explore page. -->
    <div class="logo">
        <a href="<?= $BASE_URL ?>/explore" class="brand-link">
            <img src="<?= $BASE_URL ?>/frontend/assets/images/branding.png" class="brand-logo" alt="JASAYA Journey Center">
        </a>
    </div>

    <!-- Main navigation links: these become a dropdown grid on small screens. -->
    <nav class="nav-menu" id="nav-menu">
        <a href="<?= $BASE_URL ?>/explore"
           class="<?= $currentPath === $BASE_URL . '/explore' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> <span>Explore</span>
        </a>

        <a href="<?= $BASE_URL ?>/attractions"
           class="<?= $currentPath === $BASE_URL . '/attractions' ? 'active' : '' ?>">
            <i class="fa-solid fa-camera"></i> <span>Attractions</span>
        </a>

        <a href="<?= $BASE_URL ?>/resorts"
           class="<?= $currentPath === $BASE_URL . '/resorts' ? 'active' : '' ?>">
            <i class="fa-solid fa-water"></i> <span>Resorts</span>
        </a>

        <a href="<?= $BASE_URL ?>/products"
           class="<?= $currentPath === $BASE_URL . '/products' ? 'active' : '' ?>">
            <i class="fa-solid fa-bowl-food"></i> <span>Local Products</span>
        </a>

        <a href="<?= $BASE_URL ?>/markets"
           class="<?= $currentPath === $BASE_URL . '/markets' ? 'active' : '' ?>">
            <i class="fa-solid fa-bag-shopping"></i> <span>Markets</span>
        </a>
    </nav>

    <!-- Right side controls: theme, profile, logout, and mobile menu button. -->
    <div class="nav-right">
        <div class="nav-icons">
            <!-- Theme button: JavaScript changes the icon and switches light/dark mode. -->
            <button
                type="button"
                id="themeToggle"
                class="theme-toggle icon-btn"
                title="Switch to dark mode"
                aria-label="Activate dark mode"
                aria-pressed="false"
            >
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="#" id="profileBtn" class="icon-btn" title="Profile">
                <i class="fa-regular fa-circle-user"></i>
            </a>
            <a href="#" id="logoutBtn" class="icon-btn" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>

        <!-- Hamburger button: only appears on mobile and opens the navigation menu. -->
        <button class="hamburger" id="hamburger" type="button" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="nav-menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
    </div>
</header>
