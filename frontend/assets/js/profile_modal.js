document.addEventListener("DOMContentLoaded", function () {
    const profileModal = document.getElementById("profileModal");
    const profileBtn = document.getElementById("profileBtn");
    const profileForm = document.getElementById("profileForm");
    const cancelBtn = document.getElementById("cancelProfileBtn");
    const profilePictureInput = document.getElementById("profilePictureInput");
    const profilePicture = document.getElementById("profilePicture");
    const togglePasswordBtn = document.getElementById("toggleProfilePassword");
    const passwordInput = document.getElementById("profilePassword");
    const baseUrl = window.BASE_URL || "/jasaan-tourism";

    function openProfileModal() {
        if (!profileModal) {
            return;
        }

        profileModal.style.display = "flex";
    }

    function closeProfileModal() {
        if (!profileModal) {
            return;
        }

        profileModal.style.display = "none";
    }

    function openLoginModal() {
        const modal = document.getElementById("loginModal") || document.getElementById("logoutModal");

        if (modal) {
            modal.classList.add("show");
        }
    }

    if (profileBtn) {
        profileBtn.addEventListener("click", (e) => {
            e.preventDefault();

            if (!window.isLoggedIn) {
                openLoginModal();
                return;
            }

            openProfileModal();
        });
    }

    cancelBtn?.addEventListener("click", closeProfileModal);

    if (profilePictureInput) {
        profilePictureInput.addEventListener("change", function () {
            if (!this.files || !this.files[0] || !profilePicture) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                profilePicture.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
        });
    }

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePasswordBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
            } else {
                passwordInput.type = "password";
                togglePasswordBtn.innerHTML = '<i class="fa-solid fa-eye"></i>';
            }
        });
    }

    if (profileForm) {
        profileForm.addEventListener("submit", function (e) {
            e.preventDefault();

            window.openConfirmModal?.(
                "Confirm Update",
                "Are you sure you want to save changes?",
                async () => {
                    const formData = new FormData(profileForm);

                    try {
                        const res = await fetch(`${baseUrl}/backend/update_profile.php`, {
                            method: "POST",
                            body: formData
                        });

                        const data = await res.json();

                        if (data.status === "success") {
                            window.showToast?.("Profile updated successfully!", "success");
                            closeProfileModal();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            window.showToast?.(data.message || "Update failed", "error");
                        }
                    } catch (err) {
                        console.error(err);
                        window.showToast?.("Update failed", "error");
                    }
                },
                {
                    onOpen: () => {
                        if (profileModal) {
                            profileModal.style.pointerEvents = "none";
                        }
                    },
                    onClose: () => {
                        if (profileModal) {
                            profileModal.style.pointerEvents = "auto";
                        }
                    }
                }
            );
        });
    }

    const logoutBtn = document.getElementById("logoutBtn");

    if (logoutBtn) {
        logoutBtn.addEventListener("click", function (e) {
            e.preventDefault();

            if (!window.isLoggedIn) {
                openLoginModal();
                return;
            }

            window.openConfirmModal?.(
                "Logout",
                "Are you sure you want to logout?",
                async () => {
                    try {
                        await fetch(`${baseUrl}/backend/logout.php`, {
                            method: "POST"
                        });

                        window.showToast?.("Logged out successfully!", "success");

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } catch (err) {
                        window.showToast?.("Logout failed", "error");
                    }
                },
                {
                    onOpen: () => {
                        if (profileModal) {
                            profileModal.style.pointerEvents = "none";
                        }
                    },
                    onClose: () => {
                        if (profileModal) {
                            profileModal.style.pointerEvents = "auto";
                        }
                    }
                }
            );
        });
    }
});
