function getBaseUrl() {
    return window.BASE_URL || "/jasaan-tourism";
}

function resolvePostLoginRedirect(role) {
    const baseUrl = getBaseUrl();
    const normalizedRole = String(role || "").toLowerCase();

    if (normalizedRole === "admin") {
        return `${baseUrl}/admin`;
    }

    const currentPath = window.location.pathname || "";
    const currentSearch = window.location.search || "";
    const currentHash = window.location.hash || "";
    const fallbackPath = `${baseUrl}/explore`;

    const isInProject = currentPath.startsWith(baseUrl);
    const isBackendRoute = currentPath.startsWith(`${baseUrl}/backend`);

    if (!isInProject || isBackendRoute) {
        return fallbackPath;
    }

    return `${currentPath}${currentSearch}${currentHash}`;
}

function login(event) {
    event.preventDefault();

    const form = document.getElementById("loginForm");

    if (!form) {
        console.error("loginForm not found");
        return;
    }

    const formData = new FormData(form);
    const submitBtn = form.querySelector(".login-btn");

    if (window.ActionLock?.isBusy(submitBtn)) {
        return;
    }

    const username = formData.get("username");
    const password = formData.get("password");
    const remember = document.getElementById("loginRemember")?.checked;

    if (!username || !password) {
        window.showToast?.("All fields must be filled", "error");
        return;
    }

    formData.set("remember", remember ? "1" : "0");

    window.ActionLock?.setBusy(submitBtn, true, { busyText: "Signing in..." });

    fetch("/jasaan-tourism/backend/login.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            window.showToast?.(data.message, data.success ? "success" : "error");

            if (data.success) {
                const redirectTarget = resolvePostLoginRedirect(data.role);

                setTimeout(() => {
                    window.location.href = redirectTarget;
                }, 1000);
                return;
            }

            window.ActionLock?.setBusy(submitBtn, false, { idleText: "LOGIN" });
        })
        .catch(err => {
            console.error("Login error:", err);
            window.ActionLock?.setBusy(submitBtn, false, { idleText: "LOGIN" });
            window.showToast?.("Login failed. Please try again.", "error");
        });
}

function setupToggle(inputId, toggleId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);

    if (!input || !toggle) return;

    toggle.addEventListener("click", () => {
        if (input.type === "password") {
            input.type = "text";
            toggle.classList.remove("fa-eye-slash");
            toggle.classList.add("fa-eye");
        } else {
            input.type = "password";
            toggle.classList.remove("fa-eye");
            toggle.classList.add("fa-eye-slash");
        }
    });
}

setupToggle("loginPassword", "toggleLoginPassword");
setupToggle("signupPassword", "toggleSignupPassword");
setupToggle("newPassword", "toggleNewPassword");
setupToggle("confirmPassword", "toggleConfirmPassword");
