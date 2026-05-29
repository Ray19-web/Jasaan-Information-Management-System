(() => {
    const SPLASH_KEY = "jtSplashShown";
    const SPLASH_DURATION_MS = 2200;
    const FADE_DURATION_MS = 420;

    function hasSeenSplash() {
        try {
            return localStorage.getItem(SPLASH_KEY) === "true";
        } catch (error) {
            return false;
        }
    }

    function markSplashSeen() {
        try {
            localStorage.setItem(SPLASH_KEY, "true");
        } catch (error) {
            
        }
    }

    function revealMainContent() {
        const splashScreen = document.getElementById("splashScreen");
        const mainContent = document.getElementById("mainContent");

        if (mainContent) {
            mainContent.style.display = "block";
        }

        if (splashScreen) {
            splashScreen.style.display = "none";
        }

        document.documentElement.classList.add("skip-splash");
    }

    document.addEventListener("DOMContentLoaded", () => {
        const splashScreen = document.getElementById("splashScreen");
        const mainContent = document.getElementById("mainContent");

        if (!splashScreen || !mainContent) {
            return;
        }

        if (hasSeenSplash()) {
            revealMainContent();
            return;
        }

        window.setTimeout(() => {
            splashScreen.classList.add("fade-out");

            window.setTimeout(() => {
                markSplashSeen();
                revealMainContent();
            }, FADE_DURATION_MS);
        }, SPLASH_DURATION_MS);
    });
})();
