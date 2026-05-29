function toggleFeedbackVisibility(id, trigger) {
    const button = trigger instanceof HTMLElement
        ? trigger
        : document.querySelector(`.feedback-row[data-feedback-id="${id}"] .feedback-visibility-btn`);

    if (!button) {
        return;
    }

    const isCurrentlyHidden = button.dataset.hidden === "1";
    const nextHiddenValue = isCurrentlyHidden ? 0 : 1;
    const actionLabel = isCurrentlyHidden ? "Show Feedback" : "Hide Feedback";
    const confirmMessage = isCurrentlyHidden
        ? "Show this feedback to users again?"
        : "Hide this feedback from users on the public side?";

    const confirmAction = async () => {
        if (window.ActionLock?.isBusy(button)) {
            return;
        }

        window.ActionLock?.setBusy(button, true, {
            busyText: isCurrentlyHidden ? "Showing..." : "Hiding..."
        });

        try {
            const response = await fetch("/jasaan-tourism/backend/toggle_feedback_visibility.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `id=${encodeURIComponent(id)}&hidden=${encodeURIComponent(nextHiddenValue)}`
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to update feedback visibility.");
            }

            const row = document.querySelector(`.feedback-row[data-feedback-id="${id}"]`);

            if (row) {
                row.dataset.visibilityState = data.hidden ? "hidden" : "visible";
                row.dataset.readState = data.is_read ? "read" : (row.dataset.readState || "read");
                row.classList.toggle("is-hidden", data.hidden);
                row.classList.toggle("is-visible", !data.hidden);
                row.classList.remove("unread");
                row.classList.add("read");
            }

            button.dataset.hidden = data.hidden ? "1" : "0";
            button.classList.toggle("is-hidden", data.hidden);
            button.classList.toggle("is-visible", !data.hidden);
            button.title = data.hidden ? "Show this feedback to users again" : "Hide this feedback from users";
            button.setAttribute("aria-label", button.title);
            button.innerHTML = data.hidden
                ? '<i class="fa-solid fa-eye"></i><span>Show</span>'
                : '<i class="fa-solid fa-eye-slash"></i><span>Hide</span>';
            button.dataset.lockDefaultHtml = button.innerHTML;
            button.dataset.lockDefaultText = button.textContent.trim();

            if (row) {
                const readPill = row.querySelector(".feedback-status-line .status-pill");
                if (readPill) {
                    readPill.textContent = "Read";
                }

                const visibilityPill = row.querySelector(".status-pill--visibility");
                if (visibilityPill) {
                    visibilityPill.textContent = data.hidden ? "Hidden" : "Visible";
                    visibilityPill.classList.toggle("status-pill--hidden", data.hidden);
                    visibilityPill.classList.toggle("status-pill--visible", !data.hidden);
                }
            }

            window.applyFeedbackFilters?.();

            if (typeof showToast === "function") {
                showToast(data.hidden ? "Feedback hidden from users." : "Feedback is visible to users again.", "success");
            }
        } catch (error) {
            console.error("Feedback visibility update failed", error);

            if (typeof showToast === "function") {
                showToast("Unable to update feedback visibility right now.", "error");
            }
        } finally {
            window.ActionLock?.setBusy(button, false);
        }
    };

    if (typeof openConfirmModal === "function") {
        openConfirmModal(actionLabel, confirmMessage, confirmAction);
    } else if (confirm(confirmMessage)) {
        confirmAction();
    }
}

function deleteFeedback(id, trigger) {
    const button = trigger instanceof HTMLElement
        ? trigger
        : document.querySelector(`.feedback-row[data-feedback-id="${id}"] .feedback-delete-btn`);

    const confirmAction = async () => {
        if (button && window.ActionLock?.isBusy(button)) {
            return;
        }

        if (button) {
            window.ActionLock?.setBusy(button, true, { busyText: "Deleting..." });
        }

        try {
            const response = await fetch("/jasaan-tourism/backend/delete_feedback.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `id=${encodeURIComponent(id)}`
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || "Unable to delete feedback.");
            }

            window.showToast?.(data.message || "Feedback moved to Recycle Bin.", "success");
            setTimeout(() => location.reload(), 700);
        } catch (error) {
            window.showToast?.(error.message || "Unable to delete feedback right now.", "error");

            if (button) {
                window.ActionLock?.setBusy(button, false);
            }
        }
    };

    if (typeof openConfirmModal === "function") {
        openConfirmModal(
            "Delete Feedback",
            "Move this feedback to the Recycle Bin? You can restore it later.",
            confirmAction
        );
    } else if (confirm("Move this feedback to the Recycle Bin?")) {
        confirmAction();
    }
}
