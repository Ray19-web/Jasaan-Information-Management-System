<?php
$BASE_URL = $BASE_URL ?? '/jasaan-tourism';
require_once __DIR__ . '/nav_state.php';
$currentPath = jt_normalize_nav_path($_SERVER['REQUEST_URI'] ?? '', $BASE_URL);
?>
<header class="navbar">
    <div class="logo">
        <a href="<?= $BASE_URL ?>/admin" class="brand-link">
            <img src="<?= $BASE_URL ?>/frontend/assets/images/branding.png" alt="JASAYA Journey Center" class="brand-logo">
            <span class="sr-only">JASAYA Journey Center</span>
        </a>
    </div>
    <nav class="nav-menu" id="nav-menu">
        <a href="<?= $BASE_URL ?>/admin?page=overview" class="<?= ($page ?? '') === 'overview' ? 'active' : '' ?>">
            <i class="fa-solid fa-eye"></i> Overview
        </a>
        <a href="<?= $BASE_URL ?>/admin?page=assets" class="<?= ($page ?? '') === 'assets' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i> Assets
        </a>
        <a href="<?= $BASE_URL ?>/admin?page=classifications" class="<?= ($page ?? '') === 'classifications' ? 'active' : '' ?>">
            <i class="fa-solid fa-tags"></i> Classifications
        </a>
        <a href="<?= $BASE_URL ?>/admin?page=accounts" class="<?= ($page ?? '') === 'accounts' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Accounts
        </a>
        <a href="<?= $BASE_URL ?>/admin?page=recycle_bin" class="<?= ($page ?? '') === 'recycle_bin' ? 'active' : '' ?>">
            <i class="fa-solid fa-recycle"></i> Recycle Bin
        </a>
    </nav>
    <div class="nav-right">
        <div class="nav-icons">
            <button
                type="button"
                id="themeToggle"
                class="theme-toggle icon-btn"
                title="Switch to dark mode"
                aria-label="Activate dark mode"
                aria-pressed="false">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="#" id="profileBtn" class="icon-btn" title="Profile">
                <i class="fa-regular fa-circle-user"></i>
            </a>
            <a href="#" id="logoutBtn" class="icon-btn" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>

        <button class="hamburger" id="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
    </div>
</header>
