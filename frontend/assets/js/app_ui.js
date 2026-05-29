(() => {
    const state = {
        callback: null,
        confirmed: false,
        onOpen: null,
        onClose: null,
        onCancel: null,
        title: "",
        variant: "default"
    };

    function getConfirmVariant(title = "", message = "") {
        const text = `${title} ${message}`.toLowerCase();
        return /delete|remove|permanent|cannot be undone/.test(text) ? "danger" : "default";
    }

    function getConfirmActionLabel(title = "", variant = "default") {
        if (/logout|sign out/.test(title.toLowerCase())) {
            return "Logout";
        }

        return variant === "danger" ? "Delete" : "Continue";
    }

    function getConfirmBusyLabel(title = "", variant = "default") {
        if (/logout|sign out/.test(title.toLowerCase())) {
            return "Logging out...";
        }

        if (/change|update|save|edit|mark/.test(title.toLowerCase())) {
            return "Saving...";
        }

        return variant === "danger" ? "Deleting..." : "Processing...";
    }

    function showToast(message, type = "success") {
        const toast = document.getElementById("toast");

        if (!toast) {
            return;
        }

        if (toast.hideTimeout) {
            window.clearTimeout(toast.hideTimeout);
        }

        toast.innerText = message;
        toast.className = "toast";
        toast.setAttribute("role", type === "error" ? "alert" : "status");
        toast.setAttribute("aria-live", type === "error" ? "assertive" : "polite");

        void toast.offsetWidth;
        toast.classList.add(type, "show");

        toast.hideTimeout = window.setTimeout(() => {
            toast.classList.remove("show");
        }, 3000);
    }

    function toReadableActionLabel(value = "") {
        const compact = value.replace(/\s+/g, " ").trim();

        if (!compact) {
            return "";
        }

        if (compact === compact.toUpperCase() && /[A-Z]/.test(compact)) {
            return compact.toLowerCase().replace(/\b\w/g, (char) => char.toUpperCase());
        }

        return compact;
    }

    function inferActionTitle(element) {
        if (!(element instanceof HTMLElement)) {
            return "";
        }

        const classList = element.classList;
        const text = toReadableActionLabel(element.innerText || element.textContent || "");
        const id = element.id || "";

        if (element.hasAttribute("title")) {
            return element.getAttribute("title")?.trim() || "";
        }

        if (element.hasAttribute("aria-label")) {
            return element.getAttribute("aria-label")?.trim() || "";
        }

        if (id === "profileBtn") return "Open profile";
        if (id === "logoutBtn") return "Logout";
        if (id === "hamburger") return "Toggle navigation menu";
        if (id === "assetFilterReset") return "Reset current asset filters";
        if (id === "confirmYes") return "Confirm this action";
        if (id === "closeModal" || id === "closeSignup" || id === "closeForgot") return "Close dialog";

        if (classList.contains("theme-toggle")) return "Switch theme";
        if (classList.contains("modal-close")) return "Close dialog";
        if (classList.contains("assets-close-btn")) return "Close panel";
        if (classList.contains("location-clear-btn")) return "Clear selected location";
        if (classList.contains("mark-all-btn")) return "Mark all feedback as read";
        if (classList.contains("add-btn")) return "Add a new asset";
        if (classList.contains("feedback-visibility-btn")) {
            return element.dataset.hidden === "1"
                ? "Show this feedback to users again"
                : "Hide this feedback from users";
        }
        if (classList.contains("feedback-delete-btn")) return "Move this feedback to the Recycle Bin";
        if (classList.contains("restore-btn")) return "Restore this item";
        if (classList.contains("permanent-delete-btn")) return "Permanently delete this item";
        if (classList.contains("edit")) return "Edit this item";
        if (classList.contains("delete")) return "Delete this item";
        if (classList.contains("jt-history-btn")) return "Jump to the history section";
        if (classList.contains("jt-hero-btn-primary")) return "Jump to the Why Jasaan section";
        if (classList.contains("jt-show-more-btn")) return "Show more assets";
        if (classList.contains("jtam-submit-btn")) return "Submit feedback";
        if (classList.contains("jtam-prev") || classList.contains("jtam-fullscreen-prev")) return "Previous image";
        if (classList.contains("jtam-next") || classList.contains("jtam-fullscreen-next")) return "Next image";
        if (classList.contains("jtam-fullscreen-close") || classList.contains("jtam-close")) return "Close";
        if (classList.contains("btn-cancel")) return text || "Cancel action";
        if (classList.contains("btn-primary")) return text || "Confirm action";
        if (classList.contains("login-btn")) return text || "Submit form";

        if (/^x$/i.test(text)) {
            return "Close";
        }

        if (element.matches(".nav-menu a")) {
            return text || "Open page";
        }

        return text;
    }

    function ensureKeyboardSupport(element) {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        if (!element.hasAttribute("onclick") || element.dataset.keyboardBound === "true") {
            return;
        }

        if (element.matches("button, a, input, select, textarea")) {
            return;
        }

        element.setAttribute("role", element.getAttribute("role") || "button");

        if (!element.hasAttribute("tabindex")) {
            element.tabIndex = 0;
        }

        element.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            element.click();
        });

        element.dataset.keyboardBound = "true";
    }

    function enhanceActionTitles(scope = document) {
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;
        const actionables = root.querySelectorAll(
            [
                "button",
                ".icon-btn",
                ".action-icon",
                ".edit",
                ".delete",
                ".delete-feedback-overview",
                ".feedback-delete-btn",
                ".restore-btn",
                ".permanent-delete-btn",
                ".assets-close-btn",
                ".jtam-close",
                ".jtam-nav",
                ".jtam-fullscreen-nav",
                ".jtam-fullscreen-close",
                ".nav-menu a"
            ].join(", ")
        );

        actionables.forEach((element) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const label = inferActionTitle(element);

            if (label && !element.hasAttribute("title")) {
                element.setAttribute("title", label);
            }

            if (label && !element.getAttribute("aria-label") && !toReadableActionLabel(element.innerText || element.textContent || "")) {
                element.setAttribute("aria-label", label);
            }

            ensureKeyboardSupport(element);
        });
    }

    function resetConfirmState() {
        state.callback = null;
        state.confirmed = false;
        state.onOpen = null;
        state.onClose = null;
        state.onCancel = null;
        state.title = "";
        state.variant = "default";
    }

    function closeConfirmModal(reason = "dismiss") {
        const confirmModal = document.getElementById("confirmModal");
        const onClose = state.onClose;
        const onCancel = state.onCancel;
        const shouldRunCancel = !state.confirmed && reason !== "replace" && typeof onCancel === "function";

        if (confirmModal) {
            confirmModal.style.display = "none";
            confirmModal.dataset.variant = "default";
            confirmModal.setAttribute("aria-hidden", "true");
        }

        resetConfirmState();

        if (shouldRunCancel) {
            onCancel();
        }

        if (typeof onClose === "function") {
            onClose();
        }
    }

    function openConfirmModal(title, message, callback, options = {}) {
        const confirmModal = document.getElementById("confirmModal");
        const confirmTitle = document.getElementById("confirmTitle");
        const confirmMessage = document.getElementById("confirmMessage");
        const confirmYes = document.getElementById("confirmYes");
        const confirmBox = document.querySelector("#confirmModal .confirm-box");

        if (!confirmModal || !confirmTitle || !confirmMessage || !confirmYes) {
            return;
        }

        closeConfirmModal("replace");

        const variant = getConfirmVariant(title, message);
        const actionLabel = getConfirmActionLabel(title, variant);

        state.callback = typeof callback === "function" ? callback : null;
        state.confirmed = false;
        state.onOpen = typeof options.onOpen === "function" ? options.onOpen : null;
        state.onClose = typeof options.onClose === "function" ? options.onClose : null;
        state.onCancel = typeof options.onCancel === "function" ? options.onCancel : null;
        state.title = title;
        state.variant = variant;

        confirmTitle.innerText = title;
        confirmMessage.innerText = message;

        if (window.ActionLock) {
            window.ActionLock.prime(confirmYes, actionLabel);
        } else {
            confirmYes.disabled = false;
            confirmYes.innerText = actionLabel;
        }

        confirmModal.dataset.variant = variant;
        confirmModal.setAttribute("aria-hidden", "false");
        confirmBox?.setAttribute("role", "dialog");
        confirmBox?.setAttribute("aria-modal", "true");
        confirmModal.style.display = "flex";

        if (state.onOpen) {
            state.onOpen();
        }
    }

    async function handleConfirmAction() {
        const confirmYes = document.getElementById("confirmYes");

        if (!confirmYes || !state.callback || window.ActionLock?.isBusy(confirmYes)) {
            return;
        }

        state.confirmed = true;

        try {
            const runConfirmAction = () => Promise.resolve(state.callback?.());

            if (window.ActionLock) {
                await window.ActionLock.withLock(
                    confirmYes,
                    runConfirmAction,
                    {
                        busyText: getConfirmBusyLabel(state.title, state.variant),
                        keepBusyOnSuccess: true
                    }
                );
            } else {
                await runConfirmAction();
            }
        } catch (error) {
            console.error("Confirm action failed:", error);
        } finally {
            closeConfirmModal("confirm");
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        const confirmYes = document.getElementById("confirmYes");
        const confirmModal = document.getElementById("confirmModal");

        confirmYes?.addEventListener("click", handleConfirmAction);

        confirmModal?.addEventListener("click", (event) => {
            if (event.target === confirmModal) {
                closeConfirmModal();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && confirmModal?.style.display === "flex") {
                closeConfirmModal();
            }
        });

        enhanceActionTitles();

        if (document.body) {
            const observer = new MutationObserver((mutations) => {
                for (const mutation of mutations) {
                    if (mutation.type !== "childList" || mutation.addedNodes.length === 0) {
                        continue;
                    }

                    enhanceActionTitles(document);
                    break;
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });

    window.showToast = showToast;
    window.openConfirmModal = openConfirmModal;
    window.closeConfirmModal = closeConfirmModal;
})();
