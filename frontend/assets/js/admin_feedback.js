document.addEventListener("DOMContentLoaded", () => {
    const feedbackRows = Array.from(document.querySelectorAll("#feedbackTable .feedback-row[data-feedback-id]"));
    const markAllBtn = document.getElementById("markAllReadBtn");
    const readFilterButtons = Array.from(document.querySelectorAll("[data-feedback-read]"));
    const visibilityFilterButtons = Array.from(document.querySelectorAll("[data-feedback-visibility]"));
    const feedbackSearch = document.getElementById("feedbackSearch");
    const feedbackResultCount = document.getElementById("feedbackResultCount");
    const feedbackActiveFilter = document.getElementById("feedbackActiveFilter");
    const feedbackEmptyState = document.getElementById("feedbackEmptyState");

    function setActiveFilter(buttons, nextButton) {
        buttons.forEach((button) => {
            const isActive = button === nextButton;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function getActiveFilterValue(buttons, attributeName) {
        const activeButton = buttons.find((button) => button.classList.contains("is-active"));
        return activeButton?.dataset?.[attributeName] || "";
    }

    function getActiveFilterLabel(buttons) {
        const activeButton = buttons.find((button) => button.classList.contains("is-active"));
        return activeButton?.textContent?.trim() || "All";
    }

    function applyFeedbackFilters() {
        const query = feedbackSearch ? feedbackSearch.value.toLowerCase().trim() : "";
        const readFilter = getActiveFilterValue(readFilterButtons, "feedbackRead");
        const visibilityFilter = getActiveFilterValue(visibilityFilterButtons, "feedbackVisibility");
        let visibleCount = 0;

        feedbackRows.forEach((row) => {
            const rowText = `${row.dataset.search || ""} ${row.innerText || ""}`.toLowerCase();
            const matchesSearch = !query || rowText.includes(query);
            const matchesRead = !readFilter || row.dataset.readState === readFilter;
            const matchesVisibility = !visibilityFilter || row.dataset.visibilityState === visibilityFilter;
            const isVisible = matchesSearch && matchesRead && matchesVisibility;

            row.hidden = !isVisible;

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (feedbackResultCount) {
            if (!feedbackRows.length) {
                feedbackResultCount.textContent = "No feedback available";
            } else if (!query && !readFilter && !visibilityFilter) {
                feedbackResultCount.textContent = `Showing all ${feedbackRows.length} feedback item${feedbackRows.length === 1 ? "" : "s"}`;
            } else {
                feedbackResultCount.textContent = `${visibleCount} feedback item${visibleCount === 1 ? "" : "s"} visible`;
            }
        }

        if (feedbackActiveFilter) {
            const readLabel = getActiveFilterLabel(readFilterButtons);
            const visibilityLabel = getActiveFilterLabel(visibilityFilterButtons);
            const labels = [];

            if (query) {
                labels.push(`Search: "${query}"`);
            }

            if (readFilter) {
                labels.push(`${readLabel} feedback`);
            }

            if (visibilityFilter) {
                labels.push(`${visibilityLabel} feedback`);
            }

            if (labels.length === 0) {
                feedbackActiveFilter.textContent = "All feedback";
            } else {
                feedbackActiveFilter.textContent = labels.join(" - ");
            }
        }

        if (feedbackEmptyState) {
            feedbackEmptyState.hidden = visibleCount > 0;
        }
    }

    window.applyFeedbackFilters = applyFeedbackFilters;

    feedbackSearch?.addEventListener("input", applyFeedbackFilters);

    readFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveFilter(readFilterButtons, button);
            applyFeedbackFilters();
        });
    });

    visibilityFilterButtons.forEach((button) => {
        button.addEventListener("click", () => {
            setActiveFilter(visibilityFilterButtons, button);
            applyFeedbackFilters();
        });
    });

    feedbackRows.forEach((row) => {
        row.addEventListener("click", async (event) => {
            if (event.target.closest(".feedback-visibility-btn, .feedback-delete-btn")) {
                return;
            }

            const feedbackId = row.dataset.feedbackId;
            if (!feedbackId || row.dataset.readState === "read" || row.dataset.pending === "true") {
                return;
            }

            row.dataset.pending = "true";

            try {
                const response = await fetch("/jasaan-tourism/backend/mark_feedback_read.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `id=${encodeURIComponent(feedbackId)}`
                });

                const data = await response.json();
                if (data.success) {
                    row.dataset.readState = "read";
                    row.classList.remove("unread");
                    row.classList.add("read");

                    const pill = row.querySelector(".feedback-status-line .status-pill");
                    if (pill) {
                        pill.textContent = "Read";
                    }

                    applyFeedbackFilters();
                }
            } catch (error) {
                console.error("Unable to mark feedback read:", error);
            } finally {
                row.dataset.pending = "false";
            }
        });
    });

    if (markAllBtn) {
        markAllBtn.addEventListener("click", async () => {
            if (window.ActionLock?.isBusy(markAllBtn)) {
                return;
            }

            window.ActionLock?.setBusy(markAllBtn, true, { busyText: "Marking..." });

            try {
                const response = await fetch("/jasaan-tourism/backend/mark_all_feedback_read.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    }
                });

                const data = await response.json();
                if (data.success) {
                    feedbackRows.forEach((row) => {
                        row.dataset.readState = "read";
                        row.classList.remove("unread");
                        row.classList.add("read");

                        const pill = row.querySelector(".feedback-status-line .status-pill");
                        if (pill) {
                            pill.textContent = "Read";
                        }
                    });

                    const sidebarBadge = document.querySelector(".sidebar-badge");
                    if (sidebarBadge) {
                        sidebarBadge.remove();
                    }

                    applyFeedbackFilters();
                }
            } catch (error) {
                console.error("Unable to mark all feedback read:", error);
            } finally {
                window.ActionLock?.setBusy(markAllBtn, false, { idleText: "Mark all read" });
            }
        });
    }

    applyFeedbackFilters();
});
