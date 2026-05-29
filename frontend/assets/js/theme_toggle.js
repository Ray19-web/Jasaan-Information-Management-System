const root = document.documentElement;
const themeToggle = document.getElementById("themeToggle");
const storageKey = "theme";
const motionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
const systemThemeQuery = window.matchMedia("(prefers-color-scheme: dark)");

let isThemeAnimating = false;

function getStoredTheme() {
    try {
        return localStorage.getItem(storageKey);
    } catch (error) {
        return null;
    }
}

function getPreferredTheme() {
    const savedTheme = getStoredTheme();

    if (savedTheme === "light" || savedTheme === "dark") {
        return savedTheme;
    }

    return systemThemeQuery.matches ? "dark" : "light";
}

function setTheme(theme, { persist = false } = {}) {
    const isDark = theme === "dark";

    root.classList.toggle("dark-mode", isDark);
    root.dataset.theme = theme;
    root.style.colorScheme = theme;

    if (persist) {
        try {
            localStorage.setItem(storageKey, theme);
        } catch (error) {
            
        }
    }

    updateThemeToggle(theme);
}

function updateThemeToggle(theme) {
    if (!themeToggle) {
        return;
    }

    const icon = themeToggle.querySelector("i");
    const isDark = theme === "dark";

    themeToggle.setAttribute("aria-pressed", isDark ? "true" : "false");
    themeToggle.setAttribute("aria-label", isDark ? "Activate light mode" : "Activate dark mode");
    themeToggle.setAttribute("title", isDark ? "Switch to light mode" : "Switch to dark mode");

    if (icon) {
        icon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
    }
}

function createThemeTransitionLayer(nextTheme, origin) {
    const layer = document.createElement("div");
    layer.className = `theme-transition-layer theme-transition-layer--${nextTheme}`;
    layer.style.setProperty("--theme-origin-x", `${origin.x}px`);
    layer.style.setProperty("--theme-origin-y", `${origin.y}px`);
    document.body.appendChild(layer);
    return layer;
}

function finishThemeAnimation(layer) {
    layer?.remove();
    document.body.classList.remove("theme-transitioning");
    themeToggle?.classList.remove("is-switching");
    isThemeAnimating = false;
}

function animateThemeToggle(nextTheme, origin) {
    if (motionQuery.matches || !document.body) {
        setTheme(nextTheme, { persist: true });
        return;
    }

    const layer = createThemeTransitionLayer(nextTheme, origin);
    const applyDelay = 120;

    isThemeAnimating = true;
    document.body.classList.add("theme-transitioning");
    themeToggle?.classList.add("is-switching");

    requestAnimationFrame(() => {
        layer.classList.add("is-active");
    });

    window.setTimeout(() => {
        setTheme(nextTheme, { persist: true });
    }, applyDelay);

    layer.addEventListener(
        "animationend",
        () => finishThemeAnimation(layer),
        { once: true }
    );
}

function getToggleOrigin() {
    if (!themeToggle) {
        return {
            x: window.innerWidth / 2,
            y: 80
        };
    }

    const rect = themeToggle.getBoundingClientRect();
    return {
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2
    };
}

setTheme(getPreferredTheme());

window.addEventListener("DOMContentLoaded", () => {
    root.classList.add("theme-ready");
});

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        if (isThemeAnimating) {
            return;
        }

        const currentTheme = root.classList.contains("dark-mode") ? "dark" : "light";
        const nextTheme = currentTheme === "dark" ? "light" : "dark";
        animateThemeToggle(nextTheme, getToggleOrigin());
    });
}

systemThemeQuery.addEventListener("change", (event) => {
    if (getStoredTheme()) {
        return;
    }

    setTheme(event.matches ? "dark" : "light");
});

const hamburger = document.getElementById("hamburger");
const navMenu = document.getElementById("nav-menu");

function closeMobileMenu() {
    // Closes the mobile menu and changes the close icon back to a hamburger icon.
    hamburger?.classList.remove("active");
    navMenu?.classList.remove("mobile-menu");
    navMenu?.classList.remove("show");
    hamburger?.setAttribute("aria-expanded", "false");
}

if (hamburger && navMenu) {
    hamburger.addEventListener("click", function () {
        // Opens or closes the mobile navigation when the hamburger button is clicked.
        const willOpen = !navMenu.classList.contains("show");
        hamburger.classList.toggle("active", willOpen);
        navMenu.classList.toggle("mobile-menu", willOpen);
        navMenu.classList.toggle("show", willOpen);
        hamburger.setAttribute("aria-expanded", willOpen ? "true" : "false");
    });
}

document.addEventListener("click", function (event) {
    if (hamburger && navMenu) {
        // Closes the menu when the user clicks outside the navbar menu area.
        if (!hamburger.contains(event.target) && !navMenu.contains(event.target)) {
            closeMobileMenu();
        }
    }
});

if (navMenu) {
    navMenu.addEventListener("click", function (event) {
        // Closes the mobile menu after the user chooses a navigation link.
        if (event.target.closest("a")) {
            closeMobileMenu();
        }
    });
}

window.addEventListener("resize", function () {
    // Resets mobile menu classes when the screen becomes desktop size.
    if (window.innerWidth > 992 && hamburger && navMenu) {
        closeMobileMenu();
    }
});
